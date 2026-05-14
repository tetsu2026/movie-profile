#!/bin/bash
# =====================================================================
# Laravel 版 ECS デプロイスクリプト（Phase 7）
# =====================================================================
# ローカル PC から実行:
#   ./scripts/deploy-laravel.sh
#
# 前提:
#   - AWS CLI v2 が設定済み (aws configure)
#   - Docker buildx が利用可能 (docker buildx)
#   - ECR リポジトリ `movie-prf-laravel` が ap-northeast-1 に作成済み
#   - ECS クラスタ `movie-prf` とサービス laravel-web / laravel-worker が作成済み
#
# 動作:
#   1. 現在の git HEAD の short SHA をタグに使用
#   2. ECR にログイン
#   3. docker buildx build --push で multi-platform ビルド & ECR push（:sha と :latest）
#   4. laravel-web / laravel-worker サービスを --force-new-deployment で再起動
#   5. 安定化を待機
# =====================================================================

set -euo pipefail

REGION=ap-northeast-1
CLUSTER=movie-prf
REPO_NAME=movie-prf-laravel

# AWS CLI 用 profile を固定（ローカルPCの ~/.aws/credentials の [movie-prf] を使用）
export AWS_PROFILE=movie-prf
echo "==> Using AWS profile: ${AWS_PROFILE}"

# ===== 事前チェック =====
if ! command -v aws &>/dev/null; then
    echo "Error: AWS CLI が見つかりません。aws configure で設定してください。" >&2
    exit 1
fi
if ! docker buildx version &>/dev/null; then
    echo "Error: docker buildx が必要です。" >&2
    exit 1
fi

ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)
ECR=${ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/${REPO_NAME}
TAG=$(git rev-parse --short HEAD)

# 未コミット変更があれば警告（処理は続行可能、デプロイのトレーサビリティ低下の注意）
if ! git diff --quiet HEAD || ! git diff --cached --quiet HEAD; then
    echo "⚠️  Warning: 未コミット変更があります。コミットしてから push したほうがトレーサビリティが高まります。"
fi

echo "==> ECR ログイン (${ECR})"
aws ecr get-login-password --region "${REGION}" \
    | docker login --username AWS --password-stdin "${ECR}"

echo "==> イメージビルド & push (tag: ${TAG}, latest)"
docker buildx build \
    --platform linux/amd64 \
    -t "${ECR}:${TAG}" \
    -t "${ECR}:latest" \
    -f docker/php/Dockerfile.prod \
    --push \
    .

echo "==> ECS サービス再起動 (laravel-web, laravel-worker)"
aws ecs update-service --cluster "${CLUSTER}" \
    --service laravel-web \
    --force-new-deployment > /dev/null
aws ecs update-service --cluster "${CLUSTER}" \
    --service laravel-worker \
    --force-new-deployment > /dev/null

echo "==> 安定化待機 (新タスクヘルスチェック PASS まで)"
aws ecs wait services-stable --cluster "${CLUSTER}" \
    --services laravel-web laravel-worker

echo "==> 完了: ${REPO_NAME}:${TAG} がデプロイされました"
