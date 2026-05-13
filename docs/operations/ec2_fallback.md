# EC2 ホスト直接運用への Fallback 手順（ハイブリッド方式）

## 背景

Phase 7 で ECS on EC2 + ECR のコンテナ運用に移行したが、何らかの理由（ECS の問題、コスト圧縮、運用上の判断）で「ECS化前の EC2 ホスト直接運用」に戻したいケースが想定される。

本ドキュメントは**ハイブリッド方式**でその切り替えを行う手順を示す。コードはそのまま、起動場所だけ ECS ↔ EC2 ホスト の間で切り替える方式。

## ハイブリッド方式とは

`docs/plans/ecs_ecr_migration_phase1.md` の Phase 7 では Laravel 動画エンコードを `EncodeVideoJob` で Queue 化した。Laravel Queue は `QUEUE_CONNECTION=database` ドライバ（PostgreSQL の `jobs` テーブル）を使うため、**ジョブを実行する `php artisan queue:work` プロセスは ECS でも EC2 ホスト上でも同じように動く**。

```
[ECS 化中（Phase 7 の通常状態）]
EC2 ─┬─ Docker
     │    ├─ laravel-web コンテナ (PHP-FPM + nginx)
     │    └─ laravel-worker コンテナ (queue:work)
     └─ Redis
                ↓ jobsテーブル
                RDS (PostgreSQL)
                ↑ jobsテーブル

[EC2 fallback 状態]
EC2 ─┬─ PHP-FPM (ホスト直接)              ← nginx → fastcgi_pass で接続
     ├─ systemd: laravel-worker.service   ← queue:work プロセス
     └─ Redis
                ↓ jobsテーブル
                RDS (PostgreSQL)
```

つまり「ECS でもEC2 ホストでも同じコードが動く」のがハイブリッド方式の肝。コード変更は不要、起動場所だけ切り替える。

---

## 事前準備（Phase 7 ECS 移行時に整備しておく）

### 1. systemd ユニットファイル

EC2 ホスト上に `/etc/systemd/system/laravel-worker.service` を準備しておく（disable状態でOK、いざという時に起動）。

```ini
[Unit]
Description=Laravel Queue Worker (fallback)
After=network.target postgresql.service

[Service]
Type=simple
User=ec2-user
WorkingDirectory=/var/www/movie-prf
ExecStart=/usr/bin/php artisan queue:work --tries=3 --max-time=3600 --sleep=3
Restart=always
RestartSec=5
StandardOutput=append:/var/log/laravel-worker.log
StandardError=append:/var/log/laravel-worker.log

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl disable laravel-worker.service  # 通常は無効、fallback時のみ有効化
```

### 2. nginx の旧conf をバックアップ

`docs/issues/33-nginx-reverse-proxy.md` の手順内で、旧 `conf` を `/etc/nginx/conf.d/hozu.click.conf.bak` として保存しておく（PHP-FPM 向け fastcgi_pass を含むもの）。

### 3. PHP-FPM サービス

`systemctl disable --now php-fpm` で停止中だが**アンインストールはしない**。fallback 時に `systemctl enable --now php-fpm` で復活させる。

### 4. アプリコード

`/var/www/movie-prf` 配下のソースコードはそのまま残しておく（ECS タスクは ECR の image を使うが、ホスト上のソースは消さない）。`composer install` 済の vendor も残す。

---

## Fallback 手順（ECS → EC2 ホスト直接）

### Step 1: ECS サービスを停止

```bash
aws ecs update-service --cluster movie-prf --service laravel-web --desired-count 0
aws ecs update-service --cluster movie-prf --service laravel-worker --desired-count 0
aws ecs update-service --cluster movie-prf --service nodejs-api --desired-count 0
```

ECS タスクが順次停止し、コンテナがなくなる。

### Step 2: ホスト PHP-FPM を起動

```bash
ssh ec2-user@<EC2 IP>
sudo systemctl enable --now php-fpm
sudo systemctl status php-fpm  # active 確認
```

### Step 3: nginx を旧conf に切替

```bash
sudo cp /etc/nginx/conf.d/hozu.click.conf /etc/nginx/conf.d/hozu.click.conf.ecs-bak
sudo cp /etc/nginx/conf.d/hozu.click.conf.bak /etc/nginx/conf.d/hozu.click.conf
sudo nginx -t
sudo systemctl reload nginx
```

### Step 4: Laravel Queue Worker を ホスト上で起動

```bash
cd /var/www/movie-prf
git pull origin master  # 最新コードに同期（ECS にデプロイした版と同じ SHA で）
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

sudo systemctl enable --now laravel-worker.service
sudo systemctl status laravel-worker.service  # active 確認
tail -f /var/log/laravel-worker.log  # ジョブ消化が始まればOK
```

### Step 5: Node.js 版 fallback（必要なら）

```bash
cd /var/www/movie-prf-node
git pull origin master
cd backend && npm ci && npx prisma generate && npm run build
cd ..
pm2 start ecosystem.config.js  # 既存の PM2 設定で復活
```

### Step 6: 動作確認

```bash
curl -I https://hozu.click/up         # 200
curl -I https://node.hozu.click/api/health  # 200（Node.js 動作中なら）
# ブラウザで動画アップロード → encoding → completed まで確認
```

---

## ECS への復帰手順（EC2 ホスト直接 → ECS）

Fallback から ECS 運用に戻したいときの手順。

### Step 1: ホスト laravel-worker.service を停止

```bash
sudo systemctl disable --now laravel-worker.service
```

### Step 2: ホスト PHP-FPM を停止

```bash
sudo systemctl disable --now php-fpm
```

### Step 3: nginx を ECS リバプロ用に切替

```bash
sudo cp /etc/nginx/conf.d/hozu.click.conf.ecs-bak /etc/nginx/conf.d/hozu.click.conf
sudo nginx -t
sudo systemctl reload nginx
```

### Step 4: ECS サービスを再起動

```bash
aws ecs update-service --cluster movie-prf --service laravel-web --desired-count 1
aws ecs update-service --cluster movie-prf --service laravel-worker --desired-count 1
aws ecs update-service --cluster movie-prf --service nodejs-api --desired-count 1
aws ecs wait services-stable --cluster movie-prf --services laravel-web laravel-worker nodejs-api
```

### Step 5: 動作確認

`curl https://hozu.click/up` で 200 を確認、`docker ps` でコンテナ復活を確認。

---

## 注意点

### コードの同期
- ECS で動いている image とホスト上のソースコードが乖離していないか確認すること
- ECS デプロイ時、`scripts/deploy-laravel.sh` 実行と同時に `git push origin master` も行うとよい
- Fallback 時に `git pull` で最新コードを取得

### DB マイグレーション
- ECS 側で migrate 済みのDBスキーマと、EC2 ホスト側で動かすコードのバージョンが合っているか確認
- 必要なら `php artisan migrate:status` でズレを検出

### Redis
- Redis の場所は変わらない（EC2 ホスト常駐）。Node.js 版で `REDIS_HOST` 環境変数だけ要確認
  - ECS 中: `REDIS_HOST=172.17.0.1`（コンテナから見た bridge gateway）
  - ホスト中: `REDIS_HOST=127.0.0.1`（PM2 はホスト上で直接動くため localhost）

### S3 アクセス
- ECS タスクは `movie-prf-task-role` で S3 アクセスしているが、EC2 ホスト上のプロセスは EC2 Instance Profile で S3 アクセスする
- EC2 Instance Profile に S3 動画バケットの read/write 権限があるか確認

### ダウンタイム
- Step 1〜4 の切替で 5〜10 分のダウンタイムが発生する
- 切替前に「メンテナンス画面表示」など別途検討

---

## 想定されるユースケース

- ECS で重大な障害発生時の緊急退避
- ECS 利用料金が予算超過した場合のコスト削減
- 開発・検証目的で従来構成と挙動を比較したい場合
- Phase 7 の構成を一時的に「巻き戻したい」場合
