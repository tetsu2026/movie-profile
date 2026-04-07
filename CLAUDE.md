# Claude Code プロジェクトルール設定

## プロジェクト概要

**プロジェクト名**: 動画プロフィール(Laravel版)
**技術スタック**: Laravel 11.x + PHP 8.2 + MySQL 8.0 + Blade + Tailwind CSS
**インフラ**: AWS (EC2, S3, RDS) + Docker (ローカル開発)
**設計書**: `docs/` 配下（新機能実装・DB変更時は必ず確認）

---

## 言語設定

- **日本語で回答**。コメント・エラーメッセージ・ドキュメントも日本語
- 公開メソッドにはDocBlock（日本語）を記述

---

## コミットメッセージ

フォーマット: `<type>: <subject>`（日本語・体言止め）

| Type | 説明 |
|------|------|
| `feat` | 新機能の追加 |
| `fix` | バグ修正 |
| `refactor` | リファクタリング（機能変更なし） |
| `perf` | パフォーマンス改善 |
| `style` | コードスタイル修正（機能影響なし） |
| `test` | テストの追加・修正 |
| `docs` | ドキュメント更新 |
| `chore` | ビルド・設定ファイルの変更 |
| `security` | セキュリティ対策 |

例: `feat: 動画エンコード機能を実装`

---

## コーディングルール

- PSR-12 に準拠
- カスタムCSSファイル作成禁止。Tailwind ユーティリティクラスのみ使用
- バリデーションは FormRequest クラスを使用（`$request->all()` で直接受け取らない）
- Blade テンプレートでは `{{ }}` でエスケープ。`{!! !!}` 使用禁止
- 生SQL（`DB::raw()`, `DB::select()` 等）は避け、Eloquent / クエリビルダを使用
- フォームには必ず `@csrf` を含める
- 複雑なビジネスロジックは `app/Services/` に分離
- flashメッセージのキー名は `success` / `error` で統一

---

## データベースルール

- テーブル名: snake_case 複数形（`users`, `profiles`, `videos`）
- カラム名: snake_case（`user_id`, `created_at`）
- 外部キー: `{関連テーブル単数}_id`（例: `user_id`, `thumbnail_video_id`）
- **ソフトデリート**: `SoftDeletes` トレイトを使用。`withTrashed()` は明確に必要な場合のみ
- 設計書: `docs/design-docs/03_database.md`

---

## プロジェクト固有のルール

### 動画処理

- 出力: mp4 (H.264/AAC)、最大1080p
- タイムアウト: 5分/回、リトライ: 最大3回
- ステータス: `uploading` → `encoding` → `completed` / `failed`
- S3パス: `videos/{user_id}/{video_id}/original.mp4`, `encoded.mp4`
- エンコード完了後は元動画を削除
- 処理方式: Laravel Queue（Redis）で非同期実行

### 認証・権限

- Laravel Breeze によるセッション認証
- パスワード: `Hash::make()` でハッシュ化
- 一般ユーザー: 自分のプロフィール・動画のみ編集可能
- 管理者: 全ユーザーのデータを閲覧・編集・削除可能
- ミドルウェア: `auth`（認証）、`role:admin`（管理者権限）

### バリデーション

| 項目 | ルール |
|------|--------|
| 名前 | 必須、50文字以内 |
| 経歴 | 任意、1000文字以内 |
| 動画ファイルサイズ | 100MB以内 |
| 動画の長さ | 1分以内 |
| 対応フォーマット | mp4, mov, avi, wmv |

---

## テスト

- 単体テスト: `tests/Unit/`
- 機能テスト: `tests/Feature/`

---

## 参考ドキュメント

| ドキュメント | パス |
|------------|------|
| 要件定義書 | `docs/requirements/01_requirements.md` |
| アーキテクチャ設計 | `docs/design-docs/02_architecture.md` |
| データベース設計 | `docs/design-docs/03_database.md` |
| サイトマップ | `docs/design-docs/04_sitemap.md` |
| データフロー | `docs/design-docs/05_data_flow.md` |
| ルーティング | `docs/design-docs/06_routing.md` |
| 画面設計 | `docs/design-docs/07_screen_design.md` |
| ステートマシン | `docs/design-docs/08_state_machine_video.md` |
| ER図 | `docs/design-docs/09_er.md` |

---

## 開発環境

### コマンド

```bash
docker-compose up -d                              # コンテナ起動
docker-compose exec app php artisan migrate        # マイグレーション実行
docker-compose exec app php artisan db:seed        # シーダー実行（開発のみ）
docker-compose exec app php artisan test           # テスト実行
docker-compose exec app npm run build              # CSS再ビルド
```

---

## その他

- MVP開発中のため破壊的変更は許容
- AWS月額 $30 以下を目標

---

## Playwright MCP使用ルール

- ブラウザ操作は **MCPツール直接呼び出しのみ**（コード実行でのブラウザ操作は禁止）
- エラー時は回避策を探さず即座に報告
