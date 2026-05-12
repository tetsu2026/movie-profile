# Issue #32: ECS on EC2 クラスタ構築（CloudFormation + swap + agent）

## 背景 / 目的

既存 EC2 t3.micro を ECS Container Instance として登録し、Laravel コンテナを ECS タスクとして起動できるようにする。Fargate ではなく ECS on EC2 起動タイプを採用（月額 $30 予算堅持のため）。

メモリ逼迫対策として swap 2GiB を追加。SSM Parameter Store にシークレットを格納し、Task Execution Role 経由で安全に参照する。

- **依存**: #31
- **ラベル**: infra
- **関連プラン**: `docs/plans/ecs_ecr_migration_phase1.md`

---

## スコープ / 作業項目

### 1. EC2 swap 追加
```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

### 2. Docker + ecs-init インストール
```bash
sudo dnf install -y docker ecs-init
sudo systemctl enable --now docker
echo 'ECS_CLUSTER=movie-prf' | sudo tee /etc/ecs/ecs.config
echo 'ECS_ENABLE_CONTAINER_METADATA=true' | sudo tee -a /etc/ecs/ecs.config
sudo systemctl enable --now ecs
```

### 3. EC2 IAM Instance Profile 更新
- 既存 Instance Profile に `AmazonEC2ContainerServiceforEC2Role` を追加
- CloudFormation `templates/iam.yaml` で管理

### 4. ECS クラスタ CloudFormation テンプレ
- `infrastructure/cloudformation/templates/ecs-cluster.yaml` 新規作成
- リソース:
  - `AWS::ECS::Cluster`（`movie-prf`）
  - `AWS::ECS::TaskDefinition`（laravel-web）
  - `AWS::ECS::TaskDefinition`（laravel-worker）
  - `AWS::ECS::Service`（laravel-web、`desiredCount=1`、`launchType=EC2`）
  - `AWS::ECS::Service`（laravel-worker、`desiredCount=1`、`launchType=EC2`）
  - CloudWatch Logs `LogGroup`（`/ecs/movie-prf/laravel-web`、`/ecs/movie-prf/laravel-worker`）

### 5. タスク定義の構成

| タスク | コマンド | memory | port | RUN_MIGRATIONS |
|---|---|---|---|---|
| laravel-web | entrypoint.sh（supervisord） | 300MiB | 8080→80 | true |
| laravel-worker | `php artisan queue:work --tries=3 --max-time=3600 --sleep=3` | 200MiB | - | false |

- network mode: `bridge`
- `minimumHealthyPercent=0, maximumPercent=100`（メモリ制約のため新旧並走させない）
- `secrets` で SSM Parameter Store から `APP_KEY`、`DB_PASSWORD` 等を取得

### 6. SSM Parameter Store
```bash
aws ssm put-parameter --name /movie-prf/laravel/APP_KEY --type SecureString --value "..."
aws ssm put-parameter --name /movie-prf/laravel/DB_PASSWORD --type SecureString --value "..."
# その他: BEDROCK_ACCESS_KEY_ID, BEDROCK_SECRET_ACCESS_KEY 等
```

### 7. IAM ロール
- `ecsTaskExecutionRole`: `AmazonECSTaskExecutionRolePolicy` + SSM/KMS 権限
- `movie-prf-task-role`: S3 動画バケット read/write

### 8. EC2 へのコンテナ起動確認
- `aws ecs list-container-instances --cluster movie-prf` で 1 台見える
- `aws ecs describe-services --cluster movie-prf --services laravel-web` で `RUNNING`
- `docker ps` でコンテナ稼働確認

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] EC2 に swap 2GiB が `/swapfile` で恒久化され、`free -m` で確認できる
- [ ] EC2 に Docker と ecs-init がインストールされ、`ECS_CLUSTER=movie-prf` が `/etc/ecs/ecs.config` に設定される
- [ ] EC2 IAM Instance Profile に `AmazonEC2ContainerServiceforEC2Role` が付与される
- [ ] CloudFormation テンプレ `templates/ecs-cluster.yaml` が新規作成され、ECS クラスタ・タスク定義（laravel-web / laravel-worker）が定義される
- [ ] SSM Parameter Store にシークレットが格納され、Task Execution Role に `ssm:GetParameters` が付与される
- [ ] `aws ecs list-container-instances --cluster movie-prf` で 1 台見える
- [ ] ECS タスク 2 種（web / worker）が RUNNING、ヘルスチェック PASS

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
- [ ] `docker ps` で laravel-web / laravel-worker コンテナ稼働
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
- **タスク定義の image 指定**: 初回は手動 push（Issue #31）したタグを参照、以降は GitHub Actions が動的に書き換え（Issue #34）
- **CloudFormation の冪等性**: 既存 EC2 を変更する部分は手動コマンドで実施、テンプレ化は新規リソース部分のみ

---

## 参考資料

- 移行プラン: `docs/plans/ecs_ecr_migration_phase1.md`
- アーキテクチャ設計書: `docs/design-docs/02_architecture.md`
- ECS on EC2 公式: https://docs.aws.amazon.com/AmazonECS/latest/developerguide/launch_container_instance.html
- SSM Parameter Store + ECS: https://docs.aws.amazon.com/AmazonECS/latest/developerguide/specifying-sensitive-data-parameters.html
