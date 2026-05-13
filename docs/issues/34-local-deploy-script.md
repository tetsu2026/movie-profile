# Issue #34: ローカルデプロイスクリプト整備

## 背景 / 目的

Phase 7 は一人開発のため、GitHub Actions による自動 CI/CD は導入せず、ローカル PC から直接シェルスクリプトを実行してデプロイする。シンプル・透明・即実行で、初期構築コストを最小化する。

GitHub Actions OIDC は将来複数人開発になった時、Phase 8 以降で導入を検討。

- **依存**: #33
- **ラベル**: infra, ops
- **関連プラン**: `docs/plans/ecs_ecr_migration_phase1.md`

---

## スコープ / 作業項目

### 1. Laravel 版デプロイスクリプト
- `scripts/deploy-laravel.sh` 新規作成
- 実行内容:
  1. `git rev-parse --short HEAD` で短SHAを取得
  2. ECR にログイン（`aws ecr get-login-password`）
  3. `docker buildx build --platform linux/amd64 --push` で ECR に push（`:sha` と `:latest` の2タグ）
  4. `aws ecs update-service --force-new-deployment` で laravel-web と laravel-worker を再起動
- 実行権限: `chmod +x scripts/deploy-laravel.sh`

### 2. Node.js 版 API デプロイスクリプト
- `scripts/deploy-nodejs-api.sh` 新規作成（Node.js リポジトリ側）
- 実行内容は Laravel 版と類似。違いは:
  - Dockerfile パス
  - ECR リポジトリ名（`movie-prf-node`）
  - サービス名（`nodejs-api`）

### 3. Node.js 版 frontend デプロイスクリプト
- `scripts/deploy-frontend.sh` 新規作成（Node.js リポジトリ側）
- 実行内容:
  1. `cd frontend && npm ci && npm run build`
  2. `aws s3 sync ./dist s3://movie-prf-spa-<account-id>/ --delete`
  3. `aws cloudfront create-invalidation --distribution-id <id> --paths '/*'`

### 4. 前提条件のドキュメント化
- README に「デプロイには AWS CLI と Docker buildx が必要」と明記
- `aws configure` で本人のアクセスキーを設定（ECR push と ECS update 権限を持つ IAM ユーザー）
- ECR push 権限を持つ IAM ユーザーを AWSコンソールで作成
  - ポリシー: `AmazonEC2ContainerRegistryPowerUser` + ECS update インラインポリシー

### 5. ロールバック手順の確認
- AWS コンソールで前バージョンのタスク定義 ARN を確認
- `aws ecs update-service --cluster movie-prf --service laravel-web --task-definition <prev-arn>` で30秒以内に切戻し
- スクリプトに「最新10タグはECRに残る」ことを利用、誤デプロイ時は前タグでビルドし直して再 push

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] `scripts/deploy-laravel.sh` が動作し、ローカル実行で ECS が更新される
- [ ] `scripts/deploy-nodejs-api.sh` が動作する（Node.js リポジトリ側）
- [ ] `scripts/deploy-frontend.sh` が動作し、S3 sync + CloudFront invalidation が走る
- [ ] スクリプト実行に必要な IAM ユーザーが作成され、`aws configure` で本人マシンに設定される
- [ ] デプロイ後、`aws ecs describe-services` で新タスク定義が反映されている
- [ ] ロールバック（前バージョンのタスク定義 ARN への切戻し）が 30 秒以内に完了する

---

## テスト観点

### スクリプト実行
- [ ] `./scripts/deploy-laravel.sh` でエラーなく完了
- [ ] `docker buildx --push` が成功
- [ ] `aws ecr describe-images --repository-name movie-prf-laravel` で新タグが見える
- [ ] `aws ecs describe-services` で新タスク定義が反映

### ECS デプロイ
- [ ] 新タスクが RUNNING、旧タスクが STOPPED
- [ ] `aws ecs wait services-stable` で安定確認
- [ ] `curl https://hozu.click/up` が 200（デプロイ中も即時 200 ≒ ダウンタイム数秒以内）

### ロールバック
- [ ] AWS コンソール ECS から旧タスク定義 ARN をコピー
- [ ] `aws ecs update-service --task-definition <prev-arn>` で 30 秒以内に切戻し

---

## 実装例

### scripts/deploy-laravel.sh
```bash
#!/bin/bash
set -euo pipefail

REGION=ap-northeast-1
ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)
ECR=${ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/movie-prf-laravel
TAG=$(git rev-parse --short HEAD)

echo "==> ECR ログイン"
aws ecr get-login-password --region $REGION \
  | docker login --username AWS --password-stdin $ECR

echo "==> イメージビルド & push (tag: $TAG, latest)"
docker buildx build --platform linux/amd64 \
  -t $ECR:$TAG -t $ECR:latest \
  -f docker/php/Dockerfile.prod \
  --push .

echo "==> ECS サービス再起動"
aws ecs update-service --cluster movie-prf \
  --service laravel-web --force-new-deployment > /dev/null
aws ecs update-service --cluster movie-prf \
  --service laravel-worker --force-new-deployment > /dev/null

echo "==> 安定化待機"
aws ecs wait services-stable --cluster movie-prf \
  --services laravel-web laravel-worker

echo "==> 完了: $TAG"
```

### scripts/deploy-frontend.sh （Node.js リポジトリ側）
```bash
#!/bin/bash
set -euo pipefail

REGION=ap-northeast-1
BUCKET=movie-prf-spa-$(aws sts get-caller-identity --query Account --output text)
DIST_ID=$(aws cloudfront list-distributions --query "DistributionList.Items[?Aliases.Items[0]=='node.hozu.click'].Id | [0]" --output text)

echo "==> frontend ビルド"
cd frontend
npm ci
npm run build

echo "==> S3 sync"
aws s3 sync ./dist s3://$BUCKET/ --delete

echo "==> CloudFront invalidation"
aws cloudfront create-invalidation --distribution-id $DIST_ID --paths '/*'

echo "==> 完了"
```

---

## 課題確認事項

- **デプロイ時の一瞬ダウン**: `minimumHealthyPercent=0` だと新旧並走しない → 数秒のダウンタイム。許容する（Phase 8 で ALB + Blue/Green で解消）
- **IAM ユーザー長期キーの扱い**: ローカル PC の `~/.aws/credentials` に保管。漏洩リスク管理は本人責任。複数人開発になったら OIDC へ移行
- **テストの自動実行**: GitHub Actions ではなくローカルでスクリプト実行前に `vendor/bin/pest` を手動で動かす運用とする

---

## 参考資料

- 移行プラン: `docs/plans/ecs_ecr_migration_phase1.md`
- AWS CLI ECS リファレンス: https://docs.aws.amazon.com/cli/latest/reference/ecs/
- AWS CLI ECR リファレンス: https://docs.aws.amazon.com/cli/latest/reference/ecr/
