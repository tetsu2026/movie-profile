# 動画プロフィール（Laravel版）

動画を使った自己紹介ページを作成・公開できる Web サービス。
**NestJS・React版**（[movie-profile-node](https://github.com/tetsu2026/movie-profile-node)）と
**同一サービスを別スタックで実装**し、本番では**同一の RDS PostgreSQL を共有**しています。

🔗 **デモ**: https://hozu.click/

> Laravel 11 + PHP 8.2 / PostgreSQL（pgvector）/ AWS（ECS on EC2・S3・SES・Bedrock）

## デモ

### 動作確認用アカウント

動作確認用アカウントは、応募書類（職務経歴書「個人開発」の項）に記載しています。

> Laravel版・NestJS版は **同一アカウントでログイン可能**です（DB共有のため）。

## 主な機能

- ユーザー登録・ログイン（メール認証つき）
- プロフィール作成と**公開ページ**の表示
- **動画アップロード → 自動エンコード**（H.264 / 最大1080p / mp4）
- 動画つきプロフィールの公開
- 管理者によるユーザー・動画の管理
- **AIチャットボット**（AWS Bedrock + pgvector による RAG）

## 技術スタック

- **バックエンド**: Laravel 11.x + PHP 8.2
- **フロントエンド**: Blade + Tailwind CSS
- **データベース**: PostgreSQL 16（pgvector 拡張）
- **動画処理**: FFmpeg（メタデータ取得に getid3）
- **ストレージ**: AWS S3（ローカルは MinIO）
- **メール**: AWS SES（メール認証）
- **生成AI**: AWS Bedrock（Amazon Nova Lite + Titan Embeddings V2）
- **インフラ**: AWS（ECS on EC2・ECR・RDS・S3）/ ローカルは Docker Compose

## アーキテクチャ（本番 / AWS）

```
ユーザー
  │  HTTPS（hozu.click 直）
  ▼
EC2（t3.micro / Elastic IP）
  ├─ host nginx … SSL終端（Let's Encrypt）+ リバースプロキシ
  │     └─ :8080 → ECS タスク laravel-web（nginx + PHP-FPM）
  │                 └─ laravel-worker（キュー処理 / 同一クラスタ）
  └─ ECS on EC2（クラスタ: movie-prf / 起動タイプ EC2）
        ▼
   ┌──────────── 共有 ────────────┐
   │ RDS PostgreSQL（pgvector） │ ← NestJS版と共有
   │ S3（動画オリジナル/エンコード済）│
   │ ECR（コンテナイメージ）        │
   │ SES（メール認証）             │
   │ Bedrock（Nova / Titan）      │
   └──────────────────────────────┘
```

- **Webルーティング**: `hozu.click` は EC2 の host nginx が直接受け（CloudFront 不経由）、`laravel-web`（:8080）へプロキシ。
- **キュー**: `QUEUE_CONNECTION=database`。**RDS の `jobs` テーブル**を使い、`laravel-worker` が非同期で動画エンコードを処理（Redis 不使用）。
- **動画処理フロー**: アップロード → S3 保存 → `laravel-worker` が FFmpeg でエンコード → S3 に保存。

## 工夫した点・技術的こだわり

- **同一サービスを Laravel版と NestJS・React版の2スタックで実装**し、本番で **RDS PostgreSQL を共有**。設計から本番運用・デプロイまで一人で構築。
- **AWS Bedrock + pgvector で RAG チャットボットを実装**：生成に Amazon Nova Lite（Converse API）、埋め込みに Titan Embeddings V2（1024次元）、検索に pgvector（HNSW / コサイン類似度）。ドキュメントをチャンク化して近傍検索 → 回答生成。
- **キューを RDS の `jobs` テーブルで実現**し、Redis 等の追加ミドルウェアなしで非同期エンコードを構成（**コスト最適化**）。
- **ALB を使わず EC2 ホストの nginx に SSL終端＋リバースプロキシを担わせ**、ロードバランサ費用（月$16〜相当）を回避。さらに **Fargate ではなく ECS on EC2（単一 t3.micro）に全タスクを集約**し、証明書は Let's Encrypt（無料）とすることで**月額コストを抑制**（目標 $30以下）。
- **DBスキーマの主導権を Laravel migration に集約**し、NestJS版（Prisma）は `prisma db pull` で追従させる運用を確立。

## ローカル開発

```bash
git clone https://github.com/tetsu2026/movie-profile.git
cd movie-profile
docker-compose up -d

docker-compose exec app cp .env.example .env
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app npm install && docker-compose exec app npm run build
```

- アプリ: http://localhost ／ MinIO 管理画面: http://localhost:9001（minioadmin / minioadmin）

| サービス | ポート | 用途 |
|---------|--------|------|
| web | 80 | Nginx Webサーバー |
| app | - | PHP 8.2 + Laravel |
| db | 5432 | PostgreSQL 16（pgvector） |
| minio | 9000 / 9001 | S3 エミュレータ |

## ディレクトリ構成

```
├── app/                  # アプリ本体（Http / Models / Services / Jobs）
├── docker/               # Docker設定（nginx / php）
├── docs/                 # 設計書（design-docs ほか）
├── infrastructure/       # CloudFormation・デプロイ補助・構成図
├── scripts/              # deploy-laravel.sh（ECSデプロイ）
├── resources/views/      # Bladeテンプレート
└── docker-compose.yml    # ローカル開発用
```

> デプロイは `scripts/deploy-laravel.sh`（ECR `movie-prf-laravel` ビルド/プッシュ → ECS `laravel-web`/`laravel-worker` 更新）。

## 設計書

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

## 作者

- GitHub: [@tetsu2026](https://github.com/tetsu2026)
