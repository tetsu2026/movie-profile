# Issue #1: ローカル開発環境構築（Docker）

## 背景 / 目的

ローカル開発環境をDockerで構築し、本番環境との差異を最小化することでチーム開発への拡張性を確保する。MVP開発に必要なLaravel + MySQL + MinIO（S3エミュレータ）を含む統合開発環境を準備する。

- **依存**: なし
- **ラベル**: infra

---

## スコープ / 作業項目

### 1. Docker Compose設定ファイル作成
- `docker-compose.yml` の作成
- 以下のコンテナを定義:
  - **web**: Nginx（ポート80）
  - **app**: PHP 8.2 + Laravel
  - **db**: MySQL 8.0
  - **minio**: MinIO（S3エミュレータ、ポート9000/9001）

### 2. Dockerfileの作成
- **app用Dockerfile**: PHP 8.2, Composer, FFmpeg, 必要なPHP拡張をインストール
- **web用Dockerfile**: Nginx設定（Laravelのpublicディレクトリをドキュメントルートに設定）

### 3. 環境変数ファイル整備
- `.env.example` の作成（DB接続、S3設定のテンプレート）
- `.env` の初期設定（ローカル開発用）

### 4. 初期化スクリプト
- `docker-compose up -d` でコンテナ起動
- データベース初期化スクリプト（オプション）

### 5. ドキュメント整備
- `README.md` にセットアップ手順を記載
  - `docker-compose up -d`
  - `docker-compose exec app composer install`
  - `docker-compose exec app php artisan migrate`

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] `docker-compose.yml` が作成され、web/app/db/minioの4コンテナが定義されている
- [ ] `docker-compose up -d` でエラーなく全コンテナが起動する
- [ ] http://localhost でNginxのウェルカムページまたはLaravelの初期画面が表示される
- [ ] MySQLコンテナに `docker-compose exec db mysql -u root -p` で接続できる
- [ ] MinIOコンテナが起動し、http://localhost:9001 で管理画面にアクセスできる
- [ ] `.env.example` と `.env` が適切に設定されている（DB_HOST=db、S3エンドポイント設定）
- [ ] README.mdにセットアップ手順が記載されている

---

## テスト観点

### 動作確認
- [ ] `docker-compose up -d` でコンテナが正常起動する
- [ ] `docker-compose ps` で全コンテナが `Up` 状態になる
- [ ] `docker-compose exec app php -v` でPHP 8.2が表示される
- [ ] `docker-compose exec app composer --version` でComposerが実行できる
- [ ] `docker-compose exec app ffmpeg -version` でFFmpegがインストールされている
- [ ] `docker-compose exec db mysql -u root -proot -e "SELECT 1"` でMySQL接続成功
- [ ] MinIO管理画面（http://localhost:9001）にログインできる（デフォルトID/Pass: minioadmin/minioadmin）

### 検証方法
1. リポジトリをクローン
2. `docker-compose up -d` を実行
3. 上記の動作確認コマンドを全て実行
4. エラーが発生しないことを確認

---

## 課題確認事項

- **FFmpegバージョン**: 特定バージョンの指定は必要か？（最新の安定版でOK？）
- **MySQLバージョン**: 8.0系の最新版でOK？（8.0.35など）
- **MinIOバージョン**: 特定バージョン指定は不要？
- **ボリューム永続化**: データベースとMinIOのデータをボリュームで永続化する？（開発環境なので不要？）

---

## 参考資料

- アーキテクチャ設計書: `docs/02_architecture.md`
- 要件定義書（ローカル開発環境）: `docs/01_requirements.md`
