# 動画プロフィール(Laravel版)

ユーザーが動画を使った自己紹介ページを作成・公開できるプラットフォーム

## 技術スタック

- **バックエンド**: Laravel 11.x + PHP 8.2
- **データベース**: MySQL 8.0
- **ローカル開発環境**: Docker + Docker Compose
- **動画処理**: FFmpeg
- **ストレージ**: AWS S3 (ローカルはMinIO)
- **フロントエンド**: Blade + Tailwind CSS

## 開発環境セットアップ

### 必要な環境

- Docker Desktop
- Docker Compose

### セットアップ手順

1. **リポジトリのクローン**
```bash
git clone https://github.com/tetsu2026/movie-profile.git
cd movie-profile
```

2. **Dockerコンテナの起動**
```bash
docker-compose up -d
```

3. **Laravelプロジェクトの初期化**（Issue #2で実施）
```bash
# srcディレクトリにLaravelをインストール（次のIssueで実施）
docker-compose exec app composer create-project laravel/laravel .
docker-compose exec app composer install
docker-compose exec app cp .env.example .env
docker-compose exec app php artisan key:generate
```

4. **データベースマイグレーション**（Issue #3で実施）
```bash
docker-compose exec app php artisan migrate
```

5. **動作確認**
- ブラウザで http://localhost にアクセス
- MinIO管理画面: http://localhost:9001 (ID: minioadmin / Pass: minioadmin)

### コンテナ操作コマンド

```bash
# コンテナ起動
docker-compose up -d

# コンテナ停止
docker-compose down

# コンテナ状態確認
docker-compose ps

# ログ確認
docker-compose logs -f app

# appコンテナ内でコマンド実行
docker-compose exec app bash
docker-compose exec app php artisan migrate
docker-compose exec app composer install

# データベース接続
docker-compose exec db mysql -u root -proot movie_prf
```

### 主なコンテナ

| サービス | コンテナ名 | ポート | 用途 |
|---------|-----------|--------|------|
| web | movie_prf_web | 80 | Nginx Webサーバー |
| app | movie_prf_app | - | PHP + Laravel |
| db | movie_prf_db | 3306 | MySQL 8.0 |
| minio | movie_prf_minio | 9000, 9001 | S3エミュレータ |

## コーディング規約

### 命名規則

| 対象 | ルール | 例 |
|------|--------|------|
| クラス名 | PascalCase | `VideoController`, `ProfileService` |
| メソッド名 | camelCase | `uploadVideo()`, `encodeVideo()` |
| 変数名 | camelCase | `$userId`, `$encodedPath` |
| 定数 | UPPER_SNAKE_CASE | `MAX_FILE_SIZE`, `ENCODING_TIMEOUT` |
| DBテーブル | snake_case（複数形） | `users`, `profiles`, `videos` |
| DBカラム | snake_case | `user_id`, `created_at` |

### コードスタイル

- PSR-12 準拠（PHP 4スペース、Blade 2スペース）
- Tailwind CSS ユーティリティクラスのみ使用（カスタムCSS禁止）
- Eloquent モデル: テーブル名は複数形、モデル名は単数形
- バリデーション: FormRequest クラスを使用
- ビジネスロジック: `app/Services/` に分離

### Bladeテンプレート構成

```
resources/views/
├── layouts/
│   ├── app.blade.php        # 共通レイアウト
│   └── guest.blade.php      # 未認証ユーザー用レイアウト
├── components/              # 再利用可能なコンポーネント
├── dashboard/               # 認証ユーザー向け画面
│   ├── index.blade.php
│   ├── profile/
│   └── videos/
├── users/                   # 公開プロフィールページ
│   └── show.blade.php
└── admin/                   # 管理者専用画面
    └── users/
```

## プロジェクト構成

```
.
├── docker/               # Docker設定ファイル
│   ├── nginx/           # Nginx設定
│   └── php/             # PHP設定
├── docs/                # 設計書・ドキュメント
│   ├── design-docs/    # アーキテクチャ・DB設計など
│   └── issues/         # GitHub Issue定義
├── src/                 # Laravelアプリケーション
├── docker-compose.yml   # Docker Compose設定
├── .env.example         # 環境変数テンプレート
└── README.md           # このファイル
```

## 設計書

- [要件定義書](docs/requirments/01_requirements.md)
- [アーキテクチャ設計](docs/design-docs/02_architecture.md)
- [データベース設計](docs/design-docs/03_database.md)
- [サイトマップ](docs/design-docs/04_sitemap.md)
- [データフロー](docs/design-docs/05_data_flow.md)
- [ルーティング設計](docs/design-docs/06_routing.md)
- [画面設計](docs/design-docs/07_screen_design.md)
- [動画ステートマシン](docs/design-docs/08_state_machine_video.md)
- [ER図](docs/design-docs/09_er.md)

## 開発フェーズ

### Phase 0: Walking Skeleton（#1〜#6）
環境構築とエンドツーエンド動作確認

### Phase 1: MVP Core（#7〜#14）
プロフィール作成・公開ページ表示・動画アップロード機能

### Phase 2: 動画処理（#15〜#18）
動画エンコード処理の実装

### Phase 3: 管理・運用（#19〜#22）
管理者機能と運用に必要な機能

### Phase 4: 堅牢化（#23〜#26）
テスト・セキュリティ強化・パフォーマンス最適化

## トラブルシューティング

### ポート競合エラー
```bash
# 使用中のポートを確認
lsof -i :80
lsof -i :3306

# 競合するサービスを停止するか、docker-compose.ymlのポート番号を変更
```

### 権限エラー
```bash
# srcディレクトリの権限を修正
sudo chown -R $USER:$USER src/
chmod -R 755 src/
```

### データベース接続エラー
```bash
# データベースコンテナのログ確認
docker-compose logs db

# データベース接続テスト
docker-compose exec db mysql -u root -proot -e "SELECT 1"
```

## ライセンス

MIT License

## 作者

- GitHub: [@tetsu2026](https://github.com/tetsu2026)
