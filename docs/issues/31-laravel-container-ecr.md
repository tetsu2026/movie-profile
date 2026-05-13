# Issue #31: Laravel 版コンテナ化（Dockerfile + ECR push）

## 背景 / 目的

Laravel 版の本番 Docker イメージを作成し、ECR リポジトリへ push できる状態にする。マルチステージビルドで本番最適化し、nginx + php-fpm + ffmpeg を同梱した単一コンテナとして起動する。

ローカル用既存 Dockerfile（`docker/php/Dockerfile`）はそのまま残し、本番用は別ファイルとして新規作成する。

- **依存**: #30
- **ラベル**: infra, backend
- **関連プラン**: `docs/plans/ecs_ecr_migration_phase1.md`

---

## スコープ / 作業項目

### 1. 本番用 Dockerfile 作成
- `docker/php/Dockerfile.prod` を新規作成
- マルチステージ:
  - Stage 1: `composer:2` で `composer install --no-dev --optimize-autoloader`
  - Stage 2: `node:20-alpine` で Vite ビルド（`npm ci && npm run build`）
  - Stage 3: `php:8.2-fpm-alpine` ランタイム
- 同梱物: nginx, php-fpm, ffmpeg, supervisord
- 拡張モジュール: pdo_pgsql, mbstring, exif, pcntl, bcmath, gd, redis（任意）

### 2. supervisord 設定
- `docker/php/supervisord.conf` を新規作成
- nginx と php-fpm を同居起動
- ログは stdout/stderr に流す（CloudWatch Logs `awslogs` ドライバ用）

### 3. entrypoint.sh 作成
- `docker/php/entrypoint.sh` を新規作成
- 起動時実行:
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`
  - `php artisan migrate --force`（Web タスクのみ、env で制御）
- supervisord を `exec` で起動

### 4. nginx 内蔵設定
- `docker/php/nginx.conf`（コンテナ内 nginx 用、ホスト nginx とは別）
- listen 80, fastcgi_pass 127.0.0.1:9000
- `client_max_body_size 100M`

### 5. ECR リポジトリ作成（AWSコンソール）
- ECR コンソール → リポジトリを作成
- リポジトリ名: `movie-prf-laravel`
- タグの不変性: 無効
- スキャン設定: プッシュ時にスキャン（オプション、推奨）
- 暗号化: AES-256 (デフォルト)
- 作成後、ライフサイクルポリシーを編集:
  - ルール1: untagged 7 日 expire
  - ルール2: tagged 直近 10 個保持

### 6. ローカルでのビルド・push 検証
- `aws ecr get-login-password | docker login`
- `docker buildx build --platform linux/amd64 -t <ecr>:initial --push -f docker/php/Dockerfile.prod .`
- `docker compose -f docker-compose.prod.yml up` でローカル動作確認

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] `docker/php/Dockerfile.prod` がマルチステージ（composer → node → php-fpm-alpine）で作成される
- [ ] supervisord で nginx + php-fpm を同居起動する設定が完成
- [ ] `entrypoint.sh` で `config:cache`、`route:cache`、`view:cache`、`migrate --force` が実行される
- [ ] ECR リポジトリ `movie-prf-laravel` が AWSコンソールで作成される（ライフサイクル: untagged 7 日、tagged 10 個保持）
- [ ] `docker buildx build --platform linux/amd64 --push` で ECR に push できる
- [ ] ローカルで `docker compose -f docker-compose.prod.yml up` で `/up` が 200 を返す

---

## テスト観点

### Dockerfile.prod ビルド
- [ ] `docker build -f docker/php/Dockerfile.prod -t movie-prf-laravel:test .` がエラーなく完了
- [ ] イメージサイズが 500MB 以下になる（最適化確認）
- [ ] `docker run --rm movie-prf-laravel:test ffmpeg -version` が動作

### supervisord 起動
- [ ] `docker run --rm movie-prf-laravel:test` で nginx + php-fpm が両方起動
- [ ] `docker exec` で `supervisorctl status` を確認

### entrypoint.sh 動作
- [ ] DB 接続環境変数を設定して起動 → migrate が実行される
- [ ] config/route/view cache が生成される

### ECR push
- [ ] `aws ecr describe-repositories --repository-names movie-prf-laravel` でリポジトリが見える
- [ ] `aws ecr list-images --repository-name movie-prf-laravel` でタグが見える

### ローカル動作確認
- [ ] `docker compose -f docker-compose.prod.yml up` で起動
- [ ] `curl http://localhost/up` で 200
- [ ] 動画アップロードフロー（Web タスクが Queue に dispatch）

---

## 実装例

### docker/php/Dockerfile.prod（骨子）
```dockerfile
# ========== Stage 1: composer dependencies ==========
FROM composer:2 AS composer-deps
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# ========== Stage 2: frontend build ==========
FROM node:20-alpine AS frontend-build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ========== Stage 3: runtime ==========
FROM php:8.2-fpm-alpine
RUN apk add --no-cache \
    nginx supervisor ffmpeg \
    postgresql-dev libpng-dev oniguruma-dev \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd

WORKDIR /var/www/html
COPY --from=composer-deps /app/vendor ./vendor
COPY . .
COPY --from=frontend-build /app/public/build ./public/build
RUN composer dump-autoload --optimize --no-dev

COPY docker/php/nginx.conf /etc/nginx/nginx.conf
COPY docker/php/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
```

### entrypoint.sh
```bash
#!/bin/sh
set -e

php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
```

---

## 課題確認事項

- **イメージサイズ**: alpine ベースで 300-400MB 想定。さらに削るなら distroless 検討
- **migration 実行制御**: Web タスクのみ `RUN_MIGRATIONS=true`、Worker は false。タスク定義の環境変数で制御
- **Worker タスクのコマンド差替え**: 同じイメージで Worker は `command: ["php", "artisan", "queue:work"]` に上書き

---

## 参考資料

- 移行プラン: `docs/plans/ecs_ecr_migration_phase1.md`
- アーキテクチャ設計書: `docs/design-docs/02_architecture.md`
- Issue #1 の既存 Dockerfile: `docker/php/Dockerfile`（ローカル開発用、本番用とは別）
