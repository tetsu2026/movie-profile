# Issue #32: ECS on EC2 クラスタ構築（AWSコンソール作業 + EC2側準備）

## 背景 / 目的

既存 EC2 t3.micro を ECS Container Instance として登録し、Laravel コンテナを ECS タスクとして起動できるようにする。Fargate ではなく ECS on EC2 起動タイプを採用（月額 $30 予算堅持のため）。

メモリ逼迫対策として swap 2GiB を追加。SSM Parameter Store にシークレットを格納し、Task Execution Role 経由で安全に参照する。

**方針**: Phase 7 では IaC（CloudFormation）化はせず、AWS マネジメントコンソールで手動構築する。透明性が高く、初学者でも進めやすい。再現性が必要になったら Phase 8 以降で IaC 化を検討。

- **依存**: #31
- **ラベル**: infra
- **関連プラン**: `docs/plans/ecs_ecr_migration_phase1.md`

---

## スコープ / 作業項目

### 1. EC2 swap 追加（SSH作業）
```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
free -m  # 確認
```

### 2. Docker + ecs-init インストール（SSH作業）
```bash
sudo dnf install -y docker ecs-init
sudo systemctl enable --now docker
echo 'ECS_CLUSTER=movie-prf' | sudo tee /etc/ecs/ecs.config
echo 'ECS_ENABLE_CONTAINER_METADATA=true' | sudo tee -a /etc/ecs/ecs.config
sudo systemctl enable --now ecs
sudo systemctl status ecs
```

### 3. EC2 IAM Instance Profile 更新（AWSコンソール）
- EC2 コンソール → 既存EC2インスタンスを選択 → アクション → セキュリティ → IAM ロールを変更
- 既存ロールに マネージドポリシー `AmazonEC2ContainerServiceforEC2Role` を追加
- IAM コンソール → ロール → 該当ロール → アクセス許可を追加 → AWS マネージドポリシーをアタッチ

### 4. SSM Parameter Store にシークレット格納（AWSコンソール）
- Systems Manager コンソール → パラメータストア → パラメータを作成
- 名前と値の例:
  - `/movie-prf/laravel/APP_KEY` (SecureString)
  - `/movie-prf/laravel/DB_PASSWORD` (SecureString)
  - `/movie-prf/laravel/BEDROCK_ACCESS_KEY_ID` (SecureString)
  - `/movie-prf/laravel/BEDROCK_SECRET_ACCESS_KEY` (SecureString)
  - `/movie-prf/node/JWT_SECRET` (SecureString)
  - `/movie-prf/node/DATABASE_URL` (SecureString)
- 階層は `/movie-prf/<app>/<key>` で統一

### 5. IAM ロール作成（AWSコンソール）
- IAM コンソール → ロール → ロールを作成
- (a) `ecsTaskExecutionRole`:
  - 信頼されたエンティティ: ECS Task
  - マネージドポリシー: `AmazonECSTaskExecutionRolePolicy`
  - インラインポリシー追加: SSM `GetParameters` + KMS `Decrypt` (自動的に既存のaws/ssm KMS key 使用)
- (b) `movie-prf-task-role`:
  - 信頼されたエンティティ: ECS Task
  - インラインポリシー: S3 動画バケット read/write (PutObject, GetObject, DeleteObject)

### 6. CloudWatch ロググループ作成（AWSコンソール）
- CloudWatch コンソール → ロググループ → ロググループを作成
- 名前: `/ecs/movie-prf/laravel-web`, `/ecs/movie-prf/laravel-worker`, `/ecs/movie-prf/nodejs-api`
- 保持期間: 7日

### 7. ECS クラスタ作成（AWSコンソール）
- ECS コンソール → クラスター → クラスターの作成
- クラスター名: `movie-prf`
- インフラストラクチャ: EC2 インスタンス
  - **既存EC2 を使うので、Auto Scaling Group は作らずに「EC2インスタンスをクラスターに登録」する形にする**（ecs-init が ECS_CLUSTER=movie-prf を読んで自動登録）
- VPC/サブネット: 既存EC2のもの

### 8. ECS タスク定義の作成（AWSコンソール）
- ECS コンソール → タスク定義 → 新しいタスク定義の作成
- 起動タイプの互換性: **EC2**
- ネットワークモード: **bridge**

| タスク | image | command | memory | port mapping |
|---|---|---|---|---|
| `laravel-web` | `<ecr>/movie-prf-laravel:latest` | (entrypoint.sh) | 300MiB | 8080:80 |
| `laravel-worker` | `<ecr>/movie-prf-laravel:latest` | `php artisan queue:work --tries=3 --max-time=3600 --sleep=3` | 200MiB | - |
| `nodejs-api` | `<ecr>/movie-prf-node:latest` | (デフォルト) | 280MiB | 8081:3000 |

- 環境変数:
  - 通常: `DB_HOST` 等を直接入力
  - シークレット: `valueFrom` フィールドに SSM Parameter Store の ARN を指定
- `taskRoleArn`: `movie-prf-task-role`
- `executionRoleArn`: `ecsTaskExecutionRole`
- ログ設定: `awslogs` ドライバ、ロググループは Step 6 で作成したもの

### 9. ECS サービスの作成（AWSコンソール）
- クラスター `movie-prf` → サービス → 作成
- 起動タイプ: EC2
- タスク定義: Step 8 で作成したもの
- サービス名: `laravel-web` / `laravel-worker` / `nodejs-api`
- 必要なタスク数: 1
- デプロイ設定: ローリングアップデート、最小ヘルシー率 0%、最大率 100%
- ロードバランサー: なし（Phase 7 は ALB 不使用）

### 10. 動作確認
```bash
aws ecs list-container-instances --cluster movie-prf   # 1台見える
aws ecs describe-services --cluster movie-prf --services laravel-web laravel-worker nodejs-api  # RUNNING
docker ps  # 3 つのコンテナが見える
```

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] EC2 に swap 2GiB が `/swapfile` で恒久化され、`free -m` で確認できる
- [ ] EC2 に Docker と ecs-init がインストールされ、`ECS_CLUSTER=movie-prf` が `/etc/ecs/ecs.config` に設定される
- [ ] EC2 IAM Instance Profile に `AmazonEC2ContainerServiceforEC2Role` が付与される
- [ ] AWSコンソール で ECS クラスター `movie-prf`、タスク定義3種、サービス3種が作成される
- [ ] SSM Parameter Store にシークレットが格納され、Task Execution Role に `ssm:GetParameters` が付与される
- [ ] `aws ecs list-container-instances --cluster movie-prf` で 1 台見える
- [ ] ECS タスク 3 種（web / worker / nodejs-api）が RUNNING、ヘルスチェック PASS

---

## テスト観点

### EC2 swap
- [ ] `free -m` で Swap 2048MiB
- [ ] 再起動後も swap が維持される（`/etc/fstab` 設定確認）

### ECS Agent
- [ ] `systemctl status ecs` が active
- [ ] `/var/log/ecs/ecs-agent.log` にエラーがない
- [ ] AWS コンソールの ECS クラスタ画面で 1 インスタンス見える

### タスク起動
- [ ] `aws ecs describe-tasks --cluster movie-prf --tasks <task-arn>` で RUNNING
- [ ] `docker ps` で laravel-web / laravel-worker / nodejs-api コンテナ稼働
- [ ] コンテナ内 `curl http://localhost/up` で 200

### シークレット注入
- [ ] コンテナ内 `printenv APP_KEY` で SSM から取得した値が設定されている
- [ ] DB 接続成功（`php artisan migrate:status`）

### メモリ
- [ ] `free -m` で実メモリ + swap の合計使用量が 3GiB 以下
- [ ] swap 使用量 < 500MiB

---

## 課題確認事項

- **既存 EC2 の運用継続**: ECS Container Instance 化する間も既存の Laravel（PHP-FPM）は動作する。nginx 切替（Issue #33）まで両方稼働
- **タスク定義の image 指定**: 初回は手動 push（Issue #31）したタグを参照、以降は scripts/deploy-laravel.sh が `:latest` を上書き push して `--force-new-deployment` で再起動
- **AWSコンソール作業の手間**: 一度限りの構築作業なので許容、再現性が必要になったら Phase 8 で IaC 化

---

## 参考資料

- 移行プラン: `docs/plans/ecs_ecr_migration_phase1.md`
- アーキテクチャ設計書: `docs/design-docs/02_architecture.md`
- ECS on EC2 公式: https://docs.aws.amazon.com/AmazonECS/latest/developerguide/launch_container_instance.html
- SSM Parameter Store + ECS: https://docs.aws.amazon.com/AmazonECS/latest/developerguide/specifying-sensitive-data-parameters.html
