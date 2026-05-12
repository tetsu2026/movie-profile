# Laravel版・Node.js版 ECS/ECR 移行プラン（Phase 1）

## Context

動画プロフィールプロジェクト（Laravel版・Node.js版）は、現在 EC2 t3.micro 1台に PHP-FPM、NestJS(PM2)、nginx、Redis、PostgreSQL(RDS)、frontend静的ファイルがすべて同居している。

**現状の課題:**
- デプロイは ssh + git pull の手動運用で、再現性とロールバックに難あり
- アプリのバージョン管理（イメージ単位）ができていない
- 将来的なALB導入・オートスケーリングへの土台がない
- Laravel版の動画エンコードが同期処理で、リクエストが最大15分ブロックされる

**目標:**
- AWS月額$30以内を維持しながら、両アプリをECS/ECRでコンテナ運用化
- frontend(Node.js版SPA)はS3+CloudFrontで配信し、コスト効率・スケール準備を向上
- Phase 1ではALB導入しない（EC2上のnginxがSSL終端＋リバプロを継続）
- Phase 2(ALB+オートスケーリング)、Phase 3(Fargate移行)への布石を整える

**ユーザー確定方針:**
- 予算: $30/月堅持 → **ECS on EC2 起動タイプ**を採用
- RDS: PostgreSQLで本番稼働中（CloudFormationテンプレが古いMySQL定義のまま）
- SPA配信: S3 + CloudFront（Node.js版）
- 両アプリ本番稼働、両方ECS化
- メモリ対策: **swap 2GiB追加でt3.micro継続**
- Node.js版ドメイン: **案A** = `node.hozu.click` をCloudFrontに、`/api/*` だけEC2にBehaviorで振り分け

---

## 移行後の構成図

```
ブラウザ
  │
  ├─ hozu.click ────────────→ EC2 Elastic IP
  │  (Laravel版・既存 Let's Encrypt)    │
  │                                     ↓ EC2 nginx (host, SSL終端)
  │                                     ↓ リバプロ 127.0.0.1:8080
  │                                  ECSタスク: laravel-web (PHP-FPM+nginx同梱コンテナ)
  │                                  ECSタスク: laravel-worker (queue:work)
  │
  └─ node.hozu.click ──────→ CloudFront (ACM us-east-1)
                                  ├─ Behavior "/*"     → S3 (frontend SPA, OAC)
                                  └─ Behavior "/api/*" → EC2 Elastic IP
                                                          ↓ EC2 nginx (Let's Encrypt SSL終端)
                                                          ↓ リバプロ 127.0.0.1:8081
                                                       ECSタスク: nodejs-api (NestJS)

EC2ホスト上:
  ・nginx (SSL終端 + リバプロ)
  ・Redis 6 (BullMQ用、localhost 127.0.0.1 + docker bridge 172.17.0.1 にbind)
  ・Docker Engine + ECS agent
  ・swap 2GiB (新規追加)

RDS:
  ・PostgreSQL（既存・触らない）
```

---

## 重要な実装ファイル

### 新規作成
- `movie_prf_pj/docker/php/Dockerfile.prod` — マルチステージ本番イメージ
- `movie_prf_pj/docker/php/supervisord.conf` — nginx + php-fpm 同居起動
- `movie_prf_pj/docker/php/entrypoint.sh` — config/route/view cache + migrate
- `movie_prf_pj/app/Jobs/EncodeVideoJob.php` — 動画エンコードQueue Job化
- `movie_prf_pj/infrastructure/ecs/task-def-laravel-web.json`
- `movie_prf_pj/infrastructure/ecs/task-def-laravel-worker.json`
- `movie_prf_pj/.github/workflows/deploy.yml`
- `movie-prf-node/docker/node/Dockerfile.prod` — マルチステージ本番イメージ
- `movie-prf-node/infrastructure/ecs/task-def-nodejs-api.json`
- `movie-prf-node/.github/workflows/deploy-api.yml`
- `movie-prf-node/.github/workflows/deploy-frontend.yml`
- `movie-prf-node/frontend/.env.production` — `VITE_API_URL=/api`（同ドメインのため相対パス維持で可）
- `movie_prf_pj/infrastructure/cloudformation/templates/ecr.yaml` — 2リポジトリ + ライフサイクル
- `movie_prf_pj/infrastructure/cloudformation/templates/cloudfront-spa.yaml` — S3 + OAC + CloudFront + 2 Behavior
- `movie_prf_pj/infrastructure/cloudformation/templates/ecs-cluster.yaml`

### 修正
- `movie_prf_pj/app/Http/Controllers/VideoController.php` — `encodeWithRetry` → `EncodeVideoJob::dispatch` に置換
- `movie-prf-node/frontend/src/api/client.ts` — baseURL は `/api` 相対のまま（案A採用のため変更不要、再確認のみ）
- `/etc/nginx/conf.d/movie-prf.conf` （EC2 host上）— リバプロ先をPHP-FPM/PM2 → コンテナポートへ
- `movie_prf_pj/infrastructure/cloudformation/templates/rds.yaml` — MySQL → PostgreSQL に整合化（**本番RDSは触らない**、テンプレ整合のみ）
- `movie_prf_pj/infrastructure/cloudformation/templates/iam.yaml` — ECS Task Role / Execution Role / GitHub OIDC Role追加
- `movie_prf_pj/infrastructure/cloudformation/main.yaml` — ECR/ECS/CloudFront スタック呼び出し追加

---

## 既存実装の活用ポイント

調査で判明した「既に揃っているもの」をそのまま活用:
- Laravel `.env.example` の `QUEUE_CONNECTION=database`、`session.driver=database` — **そのまま採用**で追加実装不要
- NestJS `backend/src/app.module.ts:21-29` — BullMQ + Redis 接続は既に実装済み、環境変数 `REDIS_HOST=172.17.0.1` で差し替え
- NestJS `backend/src/videos/video-encoder.processor.ts` — BullMQ Processor 実装済み、流用可
- NestJS `backend/src/main.ts` — `app.enableCors({ origin: process.env.FRONTEND_URL })` 実装済み（案A採用で当面CORS不要だが将来のために維持）
- Laravel 11 標準の `/up` ヘルスチェック、NestJS の `/api/health` (`health.controller.ts`) — そのまま利用

---

## 実装ステップ

### Step 1: ローカルで本番Dockerfileを刷新（0.5日）
- Laravel: マルチステージ（composer→node→php-fpm-alpine）、supervisordでnginx+php-fpm同居、ffmpeg同梱
- Node.js: マルチステージ（builder→runtime）、`node dist/main`実行、ffmpeg同梱
- `docker compose -f docker-compose.prod.yml up` で `/up`・`/api/health` 応答確認

### Step 2: Laravel動画エンコードのQueue Job化（0.5–1日）
- `app/Jobs/EncodeVideoJob.php`新規作成（`ShouldQueue`、`tries=3`、`timeout=900`）
- `VideoController::store`で `EncodeVideoJob::dispatch($video)` に置換
- `failed_jobs` テーブルは Laravel標準migrationで生成
- worker専用ECSタスクで `php artisan queue:work --tries=3 --max-time=3600 --sleep=3` を実行
- session/cache/queueは **`database`ドライバで統一**（Redis障害時の影響回避、$0追加）

### Step 3: ECRリポジトリ作成 + 初回手動push（0.5日）
- AWS CLI でap-northeast-1に2リポジトリ作成: `movie-prf-laravel`, `movie-prf-node`
- ライフサイクル: untagged 7日でexpire、tagged 直近10個保持
- `docker buildx build --platform linux/amd64 --push` で初回push

### Step 4: EC2 swap追加 + Docker/ECS agentインストール（0.5日）
```bash
# swap 2GiB追加（恒久化）
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# Docker + ecs-init
sudo dnf install -y docker ecs-init
sudo systemctl enable --now docker
echo 'ECS_CLUSTER=movie-prf' | sudo tee /etc/ecs/ecs.config
sudo systemctl enable --now ecs
```
- EC2のIAM Instance Profileに `AmazonEC2ContainerServiceforEC2Role` を追加
- 既存PHP-FPMはStep 7のnginx切替後に `systemctl disable --now`

### Step 5: ECSクラスタ + タスク定義作成（1日）

| タスク | image | command | memory | port |
|---|---|---|---|---|
| `laravel-web` | movie-prf-laravel | (entrypoint.sh) | 300MiB | 8080→80 |
| `laravel-worker` | movie-prf-laravel | `queue:work` | 200MiB | - |
| `nodejs-api` | movie-prf-node | `node dist/main` | 280MiB | 8081→3000 |

- ECSクラスタ `movie-prf` を作成、既存EC2を手動register（ASG未使用、Phase 1限定）
- network mode: `bridge`、`desiredCount=1`、`minimumHealthyPercent=0, maximumPercent=100`
- 環境変数は **SSM Parameter Store SecureString** に格納（Secrets Manager は $0.40/secret/月で予算圧迫のため不採用）
- Task Execution Role に `ssm:GetParameters` + `kms:Decrypt` 権限
- Task Role に S3 動画バケット read/write

### Step 6: Redis をホスト側で起動（0.25日）
```
sudo dnf install -y redis6
# /etc/redis6/redis.conf: bind 127.0.0.1 172.17.0.1, protected-mode no
sudo systemctl enable --now redis6
```
- ECSタスクの環境変数で `REDIS_HOST=172.17.0.1` を渡す
- Laravelは `QUEUE_CONNECTION=database` のままなのでRedis不使用、NestJS側のBullMQのみ利用

### Step 7: ホストnginxをリバプロ専用に再構成（0.5日）
- `/etc/nginx/conf.d/hozu.click.conf`: `hozu.click → 127.0.0.1:8080`（Laravel-web）
- `/etc/nginx/conf.d/node.hozu.click.conf`: `node.hozu.click → 127.0.0.1:8081`（NestJS）
  - **注: 案A採用のため、`node.hozu.click` 自体は最終的にCloudFrontに向くが、CloudFrontのoriginとして nginx は `/api/*` を受ける役割を継続**
- `client_max_body_size 100M;` `proxy_read_timeout 300s;` 維持
- 旧 `location ~ \.php$` ブロックと PHP-FPM サービスを `systemctl disable --now php-fpm` で停止（confはbackupとして残す）

### Step 8: Node.js版 frontend を S3+CloudFront へ移行（0.5–1日）
- `frontend/src/api/client.ts` のbaseURLは `/api` 相対のまま（案A: 同ドメイン下で動くため変更不要）
- S3バケット `movie-prf-spa-<account-id>` をPrivate作成、OACでCloudFrontからのみ許可
- CloudFront Distribution:
  - Origin 1: S3 OAC
  - Origin 2: EC2 (`node.hozu.click` ではなく、EC2のElastic IPまたは別ホスト名を直接指定)
  - Behavior `Default (/*)`: S3 origin、`CachingOptimized`
  - **Behavior `/api/*`**: EC2 origin、`CachingDisabled`、All Headers/Cookies/Query forward
  - Custom error 403/404 → `/index.html` 200（SPA fallback）
  - Alternate domain: `node.hozu.click`
  - ACM 証明書を us-east-1 で発行
- Route 53 で `node.hozu.click` を CloudFront ディストリビューションに Alias 切替
- 初回デプロイ: `cd frontend && npm run build && aws s3 sync dist/ s3://movie-prf-spa-<id>/ --delete && aws cloudfront create-invalidation --distribution-id <id> --paths '/*'`

**注意**: CloudFront の origin に EC2 を指定する場合、EC2側のSSL証明書名と origin host header の整合が必要。EC2 nginx に `node-origin.hozu.click` などのサーバー名を別途設定するか、`node.hozu.click` のCNAMEをCloudFront切替前に origin 用に別ホスト名で発行しておく。

### Step 9: GitHub Actions CI/CD（0.5日）
- 各リポジトリに `.github/workflows/deploy.yml`
- AWS 認証は **OIDC**（IAM長期キー作成不要）
- `GitHubActionsDeployRole` Trust policy で `token.actions.githubusercontent.com` を信頼
- 権限: ECR push、ECS update-service、ECS register-task-definition、S3 sync、CloudFront invalidation
- フロー: `docker buildx --push :sha` → `register-task-definition` → `update-service`
- frontendは別job: `npm run build && aws s3 sync && cloudfront invalidate`

### Step 10: CloudWatch Logs統合（0.25日）
- 各タスク定義の `logConfiguration.logDriver = "awslogs"`
- `awslogs-group = /ecs/movie-prf/<task-name>`、retention 7日（コスト最小化）

---

## メモリ容量計画（t3.micro 1GiB + swap 2GiB）

| 項目 | メモリ |
|---|---|
| OS + systemd | 150 MiB |
| Docker daemon | 100 MiB |
| ECS agent | 80 MiB |
| nginx (host) | 30 MiB |
| Redis (host) | 50 MiB |
| **コンテナ外計** | **410 MiB** |
| laravel-web | 300 MiB |
| laravel-worker | 200 MiB |
| nodejs-api | 280 MiB |
| **タスク計** | **780 MiB** |
| **総計** | **1190 MiB** |

→ 1GiB超過分(~190MiB)はswapで吸収。CloudWatchでswap使用率を監視し、200MiB超で恒常的なら t3.small（$15/月）へアップグレード判断。

---

## デプロイフロー

```
[git push to main]
     ↓
[GitHub Actions OIDC 認証]
     ↓
[backend Job]                         [frontend Job (Node.js版のみ)]
docker buildx --push :sha             npm ci && npm run build
     ↓                                aws s3 sync dist/ s3://...
register-task-definition              cloudfront create-invalidation
     ↓
update-service (ECS)
     ↓
ECS rolling deploy (新タスク起動 → ヘルスチェックOK → 旧タスク停止)
※ minimumHealthyPercent=0 のため数秒のダウンタイム
```

**ロールバック**:
- ECS: 前バージョンのタスク定義ARN は ECS コンソールに残るため、`aws ecs update-service --task-definition <prev-arn>` で30秒以内に切戻し
- frontend: 前回ビルドの `dist-prev/` を保持、または `git revert` → 再デプロイ
- nginx設定: 旧confを `/etc/nginx/conf.d/*.bak` でバックアップ、`systemctl start php-fpm` で完全旧構成に戻す（5分以内）

---

## コスト試算

| 項目 | 月額 | 備考 |
|---|---|---|
| EC2 t3.micro | $7.50 | 既存、変更なし |
| RDS db.t3.micro PostgreSQL | $12.50 | 既存、変更なし |
| S3 (SPA) | $0.10 | 数十MB |
| CloudFront | $0–$1 | 1TB/月 + 1000万req/月の永続無料枠内 |
| ACM | $0 | 完全無料 |
| ECR | $0–$1 | 500MB/月までは無料、超過後 $0.10/GB |
| SSM Parameter Store | $0 | Standard は無料 |
| CloudWatch Logs | $0.50 | 7日retention、合計1GB想定 |
| Bedrock | $1–3 | 既存 |
| **合計** | **$22–26/月** | 予算$30以内 |

---

## 検証手順

### 移行前ローカル確認
1. `docker compose -f docker-compose.prod.yml up` で本番イメージ起動
2. Laravel `/up` 応答、NestJS `/api/health` 応答
3. Laravel: `php artisan queue:work` で EncodeVideoJob が実行され、`videos.status` が `encoding → completed` まで遷移
4. NestJS: 既存BullMQフロー動作確認
5. frontend: `npm run build && npx serve dist` で本番ビルド表示

### 本番切替チェックリスト
- [ ] ECRに両イメージpush完了
- [ ] SSM Parameter Storeに全シークレット投入
- [ ] EC2でswap 2GiB有効化、`free -m` で確認
- [ ] EC2でecs-agent稼働、`aws ecs list-container-instances` で1台見える
- [ ] ECSタスク3種が `RUNNING`、ヘルスチェックPASS
- [ ] EC2 host nginx の `nginx -t` 合格
- [ ] `curl https://hozu.click/up` で200
- [ ] CloudFront経由 `curl https://node.hozu.click/api/health` で200
- [ ] ブラウザで `https://node.hozu.click/` SPA表示
- [ ] 動画アップロードフロー: `encoding → completed` 遷移確認
- [ ] CloudWatch Logs に両アプリのログ流入
- [ ] CloudWatch Metrics でswap使用率<50%

### 切り戻し
- nginx旧conf復元 + `systemctl start php-fpm` → Laravel旧構成に5分以内で戻る
- Node.js: ECSタスク停止後 `pm2 start ecosystem.config.js` で旧PM2構成に復帰
- CloudFront切替前ならRoute 53 でCNAMEを旧EC2 Aレコードに戻す

---

## リスク・注意点

1. **メモリ逼迫**: swap前提のためI/O性能低下の可能性。CloudWatchで監視、超過時はt3.small（$15/月）へアップグレード
2. **CloudFront origin の証明書整合**: EC2のSSL証明書名と origin host header が一致しないとSSL検証失敗。EC2に origin 用の別サブドメインを発行するか、Origin Custom Header で逃がす
3. **migrationの実行タイミング**: Web タスクのentrypointで `migrate --force` を実行。Workerタスクは実行しない（競合回避）。`desiredCount=1` なので競合は理論上発生しない
4. **CloudFront `/api/*` Behavior**: `Cache Policy = CachingDisabled`、`Origin Request Policy = AllViewer` でCookieやAuthorization headerを全てforward必要
5. **Prisma migration**: ECSでは実行しない。スキーマ変更は Laravel migration主導、Node.js は `prisma db pull` → `prisma generate` → コミット → image rebuild
6. **デプロイ時の一瞬ダウン**: `minimumHealthyPercent=0` で新旧並走しない設計のため数秒のダウンタイム。本格運用時は Phase 2 でALB+Blue/Green に移行

---

## 将来の拡張余地

### Phase 2: ALB + オートスケーリング
- トリガー条件: 月間PV>50万、または t3.small でも CPU平均>70%継続
- ALBをfrontに置き、ホストnginxを撤去。SSL終端をACMへ移行
- ECSサービスを `awsvpc` ネットワークモードへ
- Auto Scaling Group + Capacity Provider 導入
- 想定追加コスト: ALB $16/月 + 2台目EC2 $7.5/月 ≈ $24/月（予算超のため別途判断）

### Phase 3: Fargate移行
- トリガー条件: EC2 1台運用が CPU/メモリ的に限界、運用負荷削減を優先
- タスク定義流用可（`requiresCompatibilities: ["FARGATE"]`、`networkMode: awsvpc`、CPU/memory明示）
- Spot Fargate で最大70%削減可能
- ECR/GitHub Actions ワークフローはほぼ流用

---

## 工数見積もり

| Step | 内容 | 工数 |
|---|---|---|
| 1 | Dockerfile マルチステージ化 | 0.5日 |
| 2 | Laravel Queue Job化 | 0.5–1日 |
| 3 | ECR作成 + 初回push | 0.5日 |
| 4 | EC2 swap + Docker/ECS agent | 0.5日 |
| 5 | ECSクラスタ + タスク定義 | 1日 |
| 6 | Redis ホスト設定 | 0.25日 |
| 7 | nginx リバプロ再構成 | 0.5日 |
| 8 | S3+CloudFront 移行 | 0.5–1日 |
| 9 | GitHub Actions CI/CD | 0.5日 |
| 10 | CloudWatch Logs統合 | 0.25日 |
| - | 検証・本番切替 | 1日 |
| **合計** | | **6–8日** |
