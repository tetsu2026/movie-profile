# Issue #34: GitHub Actions OIDC による CI/CD パイプライン

## 背景 / 目的

GitHub Actions の OIDC 認証を使い、push 起点で「docker buildx → ECR push → ECS register-task-definition → update-service」のローリングデプロイを自動化する。IAM ユーザーの長期キーは作らず、OIDC で一時クレデンシャルを発行する。

- **依存**: #33
- **ラベル**: infra, ci
- **関連プラン**: `docs/plans/ecs_ecr_migration_phase1.md`

---

## スコープ / 作業項目

### 1. OIDC Provider 作成
- CloudFormation で `AWS::IAM::OIDCProvider`
- URL: `https://token.actions.githubusercontent.com`
- Audience: `sts.amazonaws.com`

### 2. GitHubActionsDeployRole 作成
- Trust policy: 特定リポジトリ（`tetsu2026/movie_prf_pj`）の特定ブランチのみ
  ```json
  "Condition": {
    "StringLike": {
      "token.actions.githubusercontent.com:sub": "repo:tetsu2026/movie_prf_pj:ref:refs/heads/master"
    }
  }
  ```
- 権限:
  - `ecr:GetAuthorizationToken`
  - `ecr:BatchCheckLayerAvailability`、`ecr:PutImage`、`ecr:InitiateLayerUpload`、`ecr:UploadLayerPart`、`ecr:CompleteLayerUpload`（特定リポジトリのみ）
  - `ecs:RegisterTaskDefinition`、`ecs:UpdateService`、`ecs:DescribeServices`、`ecs:DescribeTaskDefinition`
  - `iam:PassRole`（ecsTaskExecutionRole、movie-prf-task-role のみ）

### 3. ワークフロー作成
- `.github/workflows/deploy.yml` 新規作成
- トリガー: `push: branches: [master]`
- 主要ステップ:
  1. `aws-actions/configure-aws-credentials@v4`（OIDC）
  2. `aws ecr get-login-password | docker login`
  3. `docker buildx build --platform linux/amd64 --push -f docker/php/Dockerfile.prod -t <ecr>:$GITHUB_SHA -t <ecr>:latest .`
  4. `aws ecs describe-task-definition --task-definition laravel-web` → image タグを `:sha` に書き換え → `register-task-definition`
  5. `aws ecs update-service --cluster movie-prf --service laravel-web --task-definition <new-arn> --force-new-deployment`
  6. laravel-worker についても同様に register + update
  7. `aws ecs wait services-stable` で完了待機

### 4. PHP のテスト Job
- 既存の `vendor/bin/pest` を build 前に実行
- 失敗時はビルド・デプロイをスキップ

### 5. 既存 deploy.sh の役割整理
- `infrastructure/scripts/app-deploy.sh` は CloudFormation スタック更新時のみ使用
- アプリコードのデプロイは GitHub Actions のみ

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] CloudFormation で GitHub OIDC Provider と `GitHubActionsDeployRole` が定義される
- [ ] Trust policy で `token.actions.githubusercontent.com` を信頼し、特定リポジトリのみ許可
- [ ] `.github/workflows/deploy.yml` が新規作成され、`aws-actions/configure-aws-credentials@v4` で OIDC 認証する
- [ ] master push で web/worker タスク両方が自動更新される
- [ ] デプロイ後、`aws ecs describe-services` で新タスク定義が反映されている
- [ ] ロールバック（前バージョンのタスク定義 ARN への切戻し）が 30 秒以内に完了する

---

## テスト観点

### OIDC 認証
- [ ] GitHub Actions で `aws sts get-caller-identity` が成功（IAM Role ARN が表示）
- [ ] 別リポジトリから同じ Role を叩こうとすると AccessDenied

### ECR push
- [ ] `docker buildx --push` が成功
- [ ] `aws ecr describe-images` でタグが見える
- [ ] イメージサイズが 500MB 以下

### ECS デプロイ
- [ ] `update-service` が完了
- [ ] 新タスクが RUNNING、旧タスクが STOPPED
- [ ] `aws ecs wait services-stable` で安定確認
- [ ] `curl https://hozu.click/up` が 200（デプロイ中も即時 200 ≒ ダウンタイム数秒以内）

### ロールバック
- [ ] AWS コンソール ECS から旧タスク定義 ARN をコピー
- [ ] `aws ecs update-service --task-definition <prev-arn>` で 30 秒以内に切戻し

---

## 実装例

### .github/workflows/deploy.yml（骨子）
```yaml
name: Deploy to ECS

on:
  push:
    branches: [master]

permissions:
  id-token: write
  contents: read

env:
  AWS_REGION: ap-northeast-1
  ECR_REPOSITORY: movie-prf-laravel
  ECS_CLUSTER: movie-prf

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.2' }
      - run: composer install --prefer-dist --no-progress
      - run: vendor/bin/pest

  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: aws-actions/configure-aws-credentials@v4
        with:
          role-to-assume: arn:aws:iam::${{ secrets.AWS_ACCOUNT_ID }}:role/GitHubActionsDeployRole
          aws-region: ${{ env.AWS_REGION }}

      - uses: aws-actions/amazon-ecr-login@v2

      - name: Build, tag, and push image
        env:
          IMAGE_TAG: ${{ github.sha }}
        run: |
          docker buildx build --platform linux/amd64 \
            -t $ECR_REGISTRY/$ECR_REPOSITORY:$IMAGE_TAG \
            -t $ECR_REGISTRY/$ECR_REPOSITORY:latest \
            -f docker/php/Dockerfile.prod \
            --push .

      - name: Deploy laravel-web
        run: |
          aws ecs describe-task-definition --task-definition laravel-web \
            | jq '.taskDefinition | .containerDefinitions[0].image = "...'$GITHUB_SHA'"' \
            | jq 'del(.taskDefinitionArn, .revision, .status, .requiresAttributes, .compatibilities, .registeredAt, .registeredBy)' \
            > /tmp/task-def.json
          NEW_ARN=$(aws ecs register-task-definition --cli-input-json file:///tmp/task-def.json --query 'taskDefinition.taskDefinitionArn' --output text)
          aws ecs update-service --cluster $ECS_CLUSTER --service laravel-web --task-definition $NEW_ARN

      - name: Deploy laravel-worker
        # 同様の処理を laravel-worker タスク定義に対して実施

      - name: Wait for stable
        run: |
          aws ecs wait services-stable --cluster $ECS_CLUSTER --services laravel-web laravel-worker
```

---

## 課題確認事項

- **テストの実行環境**: Pest のテストは SQLite + sqlite メモリで動かす（既存設定）。本番 DB に依存しないこと
- **デプロイ中のダウンタイム**: `minimumHealthyPercent=0` だと新旧並走しない → 数秒のダウンタイム。許容する（Phase 8 で ALB + Blue/Green で解消）
- **Secrets 管理**: GitHub Secrets には `AWS_ACCOUNT_ID` のみ（機密ではない）。長期キーは保管しない

---

## 参考資料

- 移行プラン: `docs/plans/ecs_ecr_migration_phase1.md`
- AWS 公式 OIDC 設定: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles_providers_create_oidc.html
- GitHub Actions Configure AWS Credentials: https://github.com/aws-actions/configure-aws-credentials
