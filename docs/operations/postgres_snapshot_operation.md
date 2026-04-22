# Postgres スナップショット運用手順書（廃止）

> **⚠️ このドキュメントは廃止されました。**
>
> MySQL + pgsql_chatbot の 2 接続構成から PostgreSQL 単一構成へ統合した（要件定義 v5.1）ため、本番の PostgreSQL RDS はアプリ本体と共通で常時稼働が必須となり、スナップショット運用は適用不可となりました。
>
> 残置の理由: 旧構成時のコスト最適化手順として、将来再分離する可能性・履歴記録用に保持します。新規に読む必要はありません。

## 概要（旧構成時の記録）

チャットボット機能(FAQ RAG)で使用するPostgreSQL(pgvector)について、本番環境でのコスト抑制を目的としたスナップショット運用手順。

本機能は自己学習目的で利用するため、常時稼働は行わず、利用時のみスナップショットから復元し、利用終了後にスナップショットを取得してインスタンスを削除する。

### コスト概算

| 運用方式 | 月額コスト |
|---|---|
| 常時稼働(db.t4g.micro, 20GB) | 約 $15 |
| **スナップショット運用(本手順)** | **約 $2** |

内訳(スナップショット運用):
- スナップショット保存(20GB): $0.095 × 20 = $1.90/月
- 復元時インスタンス稼働(月10時間想定): $0.017 × 10 = $0.17/月

---

## 構成図

```
[待機時]
┌──────────────┐
│ RDSスナップショット │ ← 保存のみ(課金は保存容量)
│  (chatbot-snap)   │
└──────────────┘

[利用時]
┌──────────────┐     ┌──────────────┐
│ RDSスナップショット │────→│ RDS Postgres  │ ← EC2から接続
│  (chatbot-snap)   │ 復元 │  インスタンス  │
└──────────────┘     └──────────────┘
                          ↓ 利用終了時
                          [最新スナップショット取得 → インスタンス削除]
```

---

## 前提

- AWSアカウントでRDS操作権限があること
- リージョン: `ap-northeast-1`(東京)
- VPC/サブネット/セキュリティグループ: アプリ用EC2と同一VPC
- セキュリティグループ: EC2から `tcp/5432` を許可
- エンジン: PostgreSQL 16(pgvector拡張を手動CREATE)
- インスタンスクラス: `db.t4g.micro`
- ストレージ: 20GB(gp3)

---

## 初回セットアップ

### 1. RDS Postgresインスタンス作成

AWSコンソール → RDS → データベースの作成

- エンジン: PostgreSQL 16
- テンプレート: 開発/テスト
- DBインスタンス識別子: `chatbot-postgres`
- マスターユーザー: `chatbot`
- マスターパスワード: 強度の高いもの(Secrets Managerで管理推奨)
- インスタンスクラス: `db.t4g.micro`
- ストレージ: gp3 20GB
- VPC/サブネット: アプリ用EC2と同一VPC
- パブリックアクセス: なし
- セキュリティグループ: EC2からの5432を許可
- データベース名: `chatbot`
- 自動バックアップ: 無効(手動スナップショットで運用)

### 2. pgvector拡張の有効化

EC2からPostgresへ接続し、拡張を有効化する。

```bash
psql -h <RDSエンドポイント> -U chatbot -d chatbot
```

```sql
CREATE EXTENSION IF NOT EXISTS vector;
\dx  -- 拡張リスト確認
```

### 3. Laravelマイグレーション実行

```bash
ssh ec2-user@<本番EC2>
cd /var/www/movie_prf_pj
php artisan migrate --database=pgsql_chatbot --force
php artisan chatbot:index
```

### 4. 初回スナップショット取得

```bash
aws rds create-db-snapshot \
  --db-instance-identifier chatbot-postgres \
  --db-snapshot-identifier chatbot-snap-initial \
  --region ap-northeast-1
```

---

## 利用開始時の手順(スナップショットから復元)

### 1. 最新スナップショットから復元

```bash
aws rds restore-db-instance-from-db-snapshot \
  --db-instance-identifier chatbot-postgres \
  --db-snapshot-identifier chatbot-snap-latest \
  --db-instance-class db.t4g.micro \
  --vpc-security-group-ids sg-xxxxxxxx \
  --db-subnet-group-name <サブネットグループ名> \
  --no-publicly-accessible \
  --region ap-northeast-1
```

復元完了まで10〜30分待機。

### 2. エンドポイント確認

```bash
aws rds describe-db-instances \
  --db-instance-identifier chatbot-postgres \
  --query 'DBInstances[0].Endpoint.Address' \
  --output text
```

### 3. Laravelの `.env` を更新(本番)

```
DB_CHATBOT_HOST=<復元後のエンドポイント>
```

### 4. 動作確認

```bash
php artisan tinker
>>> DB::connection('pgsql_chatbot')->select('SELECT COUNT(*) FROM faq_chunks');
```

---

## 利用終了時の手順(スナップショット取得 → 削除)

### 1. データ更新があれば再インデックス

`docs/` 配下を更新した場合:

```bash
php artisan chatbot:index --fresh
```

### 2. スナップショット取得

```bash
aws rds create-db-snapshot \
  --db-instance-identifier chatbot-postgres \
  --db-snapshot-identifier chatbot-snap-$(date +%Y%m%d) \
  --region ap-northeast-1
```

ステータスが `available` になるまで待機(5〜10分)。

```bash
aws rds describe-db-snapshots \
  --db-snapshot-identifier chatbot-snap-$(date +%Y%m%d) \
  --query 'DBSnapshots[0].Status'
```

### 3. インスタンス削除(最終スナップショット不要)

```bash
aws rds delete-db-instance \
  --db-instance-identifier chatbot-postgres \
  --skip-final-snapshot \
  --region ap-northeast-1
```

### 4. 古いスナップショットの整理

3世代以上前のスナップショットは削除してストレージコストを抑える。

```bash
aws rds describe-db-snapshots \
  --snapshot-type manual \
  --query 'DBSnapshots[?starts_with(DBSnapshotIdentifier, `chatbot-snap-`)].[DBSnapshotIdentifier,SnapshotCreateTime]' \
  --output table

aws rds delete-db-snapshot \
  --db-snapshot-identifier <古いスナップショットID>
```

---

## トラブルシューティング

### pgvector拡張が見つからない

スナップショット復元後も拡張は引き継がれる。引き継がれない場合は再度 `CREATE EXTENSION vector;` を実行。

### 接続できない

- セキュリティグループでEC2からの5432が許可されているか確認
- DBサブネットグループのAZがEC2と同一VPCか確認
- `DB_CHATBOT_HOST` が最新のエンドポイントに更新されているか確認

### 復元に時間がかかる

db.t4g.micro + 20GBで通常10〜15分。30分超える場合はAWS RDSステータスを確認。

---

## 注意事項

- 本手順は**自己学習目的**の運用。外部ユーザー向けサービスでは常時稼働とすること
- スナップショットはリージョン間でコピーすれば災害対策にもなるが、Phase 1では不要
- 本番 `.env` のDB接続情報はエンドポイント変更のたびに更新する
- ローカル開発では `docker-compose.yml` の `postgres` サービスを使用するため、本手順は不要
