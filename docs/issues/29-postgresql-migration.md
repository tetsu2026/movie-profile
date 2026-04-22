# Issue #29: PostgreSQL 単一 DB 構成への統合移行（MySQL 廃止）

## 背景 / 目的

Issue #28（チャットボット実装）までは MySQL（アプリ本体）＋ PostgreSQL + pgvector（チャットボット RAG: `faq_chunks`）の 2 接続構成で運用していた。しかしこの構成には以下の問題があった:

- `chat_messages.user_id → users.id` が Cross-DB 参照となり、RDBMS レベルの外部キー制約を張れない
- 本番で RDS インスタンスを 2 系統（MySQL + PostgreSQL）維持する必要があり、運用コスト・管理負荷が大きい
- チャットボット用 Postgres はスナップショット運用前提だったが、`users` と FK を繋げない以上、利便性とデータ整合性が両立しない

本 Issue では **アプリ本体のデータベースも PostgreSQL へ完全移行** し、チャットボット用の `pgsql_chatbot` 接続を廃止して **PostgreSQL 単一 DB 構成** に統合する。MVP 段階のためデータ保全は緩く、`migrate:fresh --seed` での再構築を前提とする。

- **依存**: #28
- **ラベル**: backend, infrastructure, database
- **要件定義**: v5.1（PostgreSQL 単一 DB 構成への統合）

---

## スコープ / 作業項目

### 1. 設定ファイル修正

- `config/database.php`
  - デフォルト接続を `pgsql` に変更
  - `mysql` / `mariadb` 接続ブロックを削除
  - `pgsql_chatbot` 接続ブロックを削除
- `.env.example`
  - `DB_CONNECTION=pgsql` / `DB_PORT=5432` / ユーザー・パスワードを PostgreSQL 用に統一
  - `DB_CHATBOT_*` セクション一式を削除
- `docker-compose.yml`
  - MySQL `db` サービスを削除
  - `postgres` サービスを `db` にリネーム（イメージ: `pgvector/pgvector:pg16`）
  - app コンテナの環境変数を `DB_PORT=5432` / `DB_USERNAME=movie_prf` / `DB_PASSWORD=secret` に統一
  - 旧 `db_data` ボリューム（MySQL）は破棄、`postgres_data` に統合
- `docker/php/Dockerfile`
  - `pdo_mysql` 拡張を削除（`pdo_pgsql` は既存）

### 2. モデル / サービス層の接続統合

- `app/Models/FaqChunk.php`
  - `protected $connection = 'pgsql_chatbot';` を削除（デフォルト接続を使用）
- `app/Services/Chatbot/RagService.php`
  - `DB::connection('pgsql_chatbot')->select(...)` を `DB::select(...)` に変更
- `app/Console/Commands/ChatbotIndexCommand.php`
  - `DB::connection('pgsql_chatbot')->*` を `DB::*` に変更（3 箇所）

### 3. マイグレーション修正

- `database/migrations/2025_06_01_000002_create_faq_chunks_table.php`
  - 先頭で `if (DB::getDriverName() !== 'pgsql') { return; }` ガードを追加
    - Why: テスト環境（SQLite）で pgvector 依存の DDL が実行されないようにする
  - `$connection = 'pgsql_chatbot'` 指定を削除
  - `Schema::connection(...)` → `Schema::`、`DB::connection(...)` → `DB::` に置換
- 既存の `chat_messages`・`videos`・`add_role_to_users` マイグレーションは PostgreSQL でも互換（`enum` は Laravel が自動で CHECK 制約に変換、`json` は PostgreSQL `json` 型にマップ）なのでそのまま動作する

### 4. テスト環境

- `phpunit.xml`
  - `<env name="DB_CONNECTION" value="sqlite" force="true"/>` / `<env name="DB_DATABASE" value=":memory:" force="true"/>` を追加
    - Why: 開発者の `.env` が PostgreSQL を指しているとテストが実 DB に接続してしまう。`force="true"` で明示的に上書きする
- RagService / ChatbotIndexCommand は pgvector 依存のため Feature テストではモック化（既存）

### 5. インフラ（CloudFormation）

- `infrastructure/cloudformation/templates/rds.yaml`
  - `Engine: mysql` → `postgres`
  - `EngineVersion` を PostgreSQL 16 に更新
  - Port 3306 → 5432
  - （本 Issue では変更方針のみ確定、実際の反映は本番切替フェーズで実施）

### 6. ドキュメント更新

- `docs/requirements/01_requirements.md`: DB セクションを PostgreSQL 単一構成に統一、v5.1 変更履歴を追記
- `docs/design-docs/02_architecture.md`: システム構成図・ローカル開発環境図・チャットボット内部アーキ図、選択理由、コスト試算を更新
- `docs/design-docs/03_database.md`: DB 構成概要・各テーブルの DDL 方針・文字コード節を PostgreSQL 前提に書き換え
- `docs/design-docs/05_data_flow.md`: シーケンス図の participant を MySQL → PostgreSQL に変更、チャットボットの DB 統一を明記
- `docs/design-docs/09_er.md`: MySQL/Postgres 分離の表記を削除、同一 DB であることを明記
- `docs/operations/postgres_snapshot_operation.md`: 廃止通知を先頭に追加（単一 DB 統合のため常時稼働が必須となり運用手順が適用不可となった旨）

### 7. データ移行手順（本番）

1. 旧 MySQL RDS をダンプ保全（`mysqldump --single-transaction > backup.sql`）
2. PostgreSQL RDS を新規作成（pgvector 拡張を有効化）
3. `APP_MAINTENANCE=true` でメンテナンスモードへ
4. `.env` を PostgreSQL エンドポイントに切替
5. `php artisan migrate:fresh --seed --force`
6. `php artisan chatbot:index --fresh` で `faq_chunks` を再構築
7. スモークテスト → メンテナンス解除
8. 2 週間様子見後に MySQL RDS を削除

---

## ゴール / 完了条件（Acceptance Criteria）

- [x] `config/database.php` のデフォルト接続が `pgsql` で、`mysql` / `mariadb` / `pgsql_chatbot` の接続ブロックが削除されている
- [x] `.env.example` に `DB_CHATBOT_*` が存在せず、`pgsql` 単一接続として構成されている
- [x] `docker-compose.yml` に MySQL サービスが存在せず、`db` サービスが `pgvector/pgvector:pg16` で起動する
- [x] `app/Models/FaqChunk.php` に `$connection = 'pgsql_chatbot'` 指定がない
- [x] `RagService` / `ChatbotIndexCommand` の `DB::connection('pgsql_chatbot')` が全て削除されている
- [x] `create_faq_chunks_table` マイグレーションが非 PostgreSQL ドライバ時はスキップされる
- [x] `phpunit.xml` で `DB_CONNECTION=sqlite` が `force="true"` で固定されている
- [ ] ローカルで `docker compose down -v && docker compose up -d && php artisan migrate:fresh --seed && php artisan chatbot:index --fresh` が成功する
- [ ] `docker compose exec app php artisan test` が全件パスする
- [ ] ブラウザ手動検証: ログイン → プロフィール編集 → 動画アップロード → チャット問い合わせ（RAG 応答）まで一貫動作する
- [ ] `chat_messages` テーブルに `user_id → users.id` の外部キー制約が張られている（`\d chat_messages` で確認）
- [x] 設計書・要件定義・運用手順書が PostgreSQL 単一 DB 構成に更新されている
- [ ] CloudFormation テンプレートの RDS 定義が PostgreSQL 用に更新されている
- [ ] 本番 RDS 切替が完了し、旧 MySQL RDS が削除されている

---

## テスト観点

### ローカル環境クリーンビルド

```bash
docker compose down -v
docker compose up -d
sleep 10
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan chatbot:index --fresh
```

### テーブル・FK 確認

```bash
docker compose exec db psql -U movie_prf -d movie_prf -c "\dt"
docker compose exec db psql -U movie_prf -d movie_prf -c "\d chat_messages"
docker compose exec db psql -U movie_prf -d movie_prf -c "SELECT COUNT(*) FROM faq_chunks;"
docker compose exec db psql -U movie_prf -d movie_prf -c "SELECT extname FROM pg_extension WHERE extname='vector';"
```

### PHPUnit

```bash
docker compose exec app php artisan test
```

### 手動検証

- 登録・ログイン・ログアウト
- プロフィール作成・編集
- 動画アップロード・エンコード・削除
- チャットボット（操作ヘルプ質問 → RAG 応答・履歴保持）

---

## 課題確認事項

- **`LIMIT ?` のプレースホルダ**: `RagService` の pgvector 検索 SQL で `LIMIT ?` を使用している。PDO pgsql は文字列バインドで型エラーを起こす可能性があるため、動作確認必須。問題が出た場合は `(int)` キャストで SQL へ直埋めに変更する
- **`unsignedBigInteger`**: PostgreSQL は `unsigned` 修飾子を持たない（`bigint` にマップ）。`jobs.attempts` 等は正の値のみなので実害なしだが、将来負値チェックが必要になったら CHECK 制約を追加する
- **Seeder の ID 直指定**: auto-increment が `GENERATED BY DEFAULT AS IDENTITY` にマップされるため、ID を直接指定するシーダーがあれば `SELECT setval()` のリセットが必要になる可能性
- **`enum` 値の追加**: PostgreSQL では CHECK 制約で表現されるため、将来値を追加する場合は別マイグレーションで CHECK 制約を張り直す
- **チャットボットのスナップショット運用**: `docs/operations/postgres_snapshot_operation.md` の手順は適用不可となり廃止通知を記載済み。本番 PostgreSQL RDS は常時稼働（アプリ本体と共通）

---

## 参考資料

- 要件定義: `docs/requirements/01_requirements.md` v5.1
- アーキテクチャ: `docs/design-docs/02_architecture.md`
- DB 設計: `docs/design-docs/03_database.md`
- データフロー: `docs/design-docs/05_data_flow.md`
- ER 図: `docs/design-docs/09_er.md`
- 廃止された運用手順: `docs/operations/postgres_snapshot_operation.md`
- 実装作業ログ: `ToPostgressSQL.cosense.txt`（ローカルメモ・git 管理外）
