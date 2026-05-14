#!/bin/sh
# =====================================================================
# コンテナ起動時の初期化スクリプト
# 1. キャッシュ生成（config / route / view）
# 2. RUN_MIGRATIONS=true の場合のみ migration を実行
#    （web タスクのみ true、worker タスクは false で migration 重複を防ぐ）
# 3. CMD で渡されたコマンドを exec で起動
# =====================================================================
set -e

cd /var/www/html

echo "==> Laravel cache 生成"
php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "==> Migration 実行"
    php artisan migrate --force
fi

echo "==> CMD 実行: $@"
exec "$@"
