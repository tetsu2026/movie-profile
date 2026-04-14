#!/bin/bash
#
# アプリケーションデプロイスクリプト
# Movie Profile Platform - EC2へのLaravelアプリデプロイ
#
# リポジトリ構造:
#   /var/www/movie-prf/   ... gitリポジトリルート = Laravelアプリルート
#     artisan
#     public/             ... Nginxのdocument root
#     resources/
#     ...
#

set -e

# 色付き出力
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 設定
APP_DIR="/var/www/movie-prf"
APP_USER="nginx"
REPO_URL="git@github.com:tetsu2026/movie-profile.git"
BRANCH="master"

# ログ出力関数
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
}

# 権限設定
# - アプリ全体: nginx:nginx 所有、ディレクトリ755・ファイル644
# - storage と bootstrap/cache: 2775(setgid)・664 でグループ書き込み可
#   → ec2-user(nginxグループ所属)と PHP-FPM(nginx) の双方から書き込める
set_permissions() {
    sudo chown -R "$APP_USER":"$APP_USER" "$APP_DIR"
    # 大文字X: ディレクトリ(および既に実行ビットのあるファイル)のみ実行ビットを付与
    sudo chmod -R u=rwX,g=rX,o=rX "$APP_DIR"
    # storage と bootstrap/cache はグループ書き込み可
    sudo chmod -R u=rwX,g=rwX,o=rX "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
    # setgid: 新規ファイル・ディレクトリのグループを nginx に継承させる
    sudo find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type d -exec chmod g+s {} \;
}

# RDS状態チェック（.envのDB_HOSTからインスタンス識別子を取得）
check_db_connection() {
    local db_host
    db_host=$(grep '^DB_HOST=' "$APP_DIR/.env" | cut -d'=' -f2)
    local db_identifier
    db_identifier=$(echo "$db_host" | cut -d'.' -f1)

    if [[ -z "$db_identifier" ]]; then
        log_warn "DB_HOSTが設定されていません"
        return 1
    fi

    local status
    status=$(aws rds describe-db-instances \
        --db-instance-identifier "$db_identifier" \
        --query 'DBInstances[0].DBInstanceStatus' \
        --output text --region "${REGION:-ap-northeast-1}" 2>/dev/null)

    if [[ "$status" == "available" ]]; then
        log_info "RDS状態: available"
        return 0
    else
        log_warn "RDS状態: ${status:-取得失敗}"
        return 1
    fi
}

# ヘルプ表示
show_help() {
    echo "使用方法: $0 [オプション]"
    echo ""
    echo "オプション:"
    echo "  -h, --help          このヘルプを表示"
    echo "  -r, --repo          GitリポジトリURL"
    echo "  -b, --branch        デプロイするブランチ (デフォルト: master)"
    echo "  -d, --dir           アプリケーションディレクトリ (デフォルト: /var/www/movie-prf)"
    echo "  --setup             初回セットアップを実行"
    echo "  --update            既存アプリの更新のみ"
    echo ""
    echo "例:"
    echo "  $0 --setup"
    echo "  $0 --update"
}

# 初回セットアップ
initial_setup() {
    log_step "1/9: ディレクトリ準備"

    if [[ -d "$APP_DIR/.git" ]]; then
        log_warn "既存のアプリケーションが存在します。--updateオプションを使用してください。"
        exit 1
    fi

    # ディレクトリ作成
    sudo mkdir -p "$APP_DIR"
    sudo chown "$USER":"$USER" "$APP_DIR"

    log_step "2/9: Gitリポジトリのクローン"
    # GitHubホストキーを登録（SSH接続時のHost key verification failedを防ぐ）
    mkdir -p ~/.ssh
    ssh-keyscan github.com >> ~/.ssh/known_hosts 2>/dev/null
    git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
    cd "$APP_DIR"

    log_step "3/9: Composerパッケージインストール"
    composer install --no-dev --optimize-autoloader

    log_step "4/9: npmパッケージインストールとビルド"
    npm ci
    npm run build

    log_step "5/9: .envファイル設定"
    if [[ ! -f "$APP_DIR/.env" ]]; then
        cp "$APP_DIR/.env.example" "$APP_DIR/.env"
        log_warn ".envファイルを作成しました。以下の値を設定してください:"
        echo ""
        echo "  DB_HOST=<RDSエンドポイント>"
        echo "  DB_PORT=3306"
        echo "  DB_DATABASE=movie_prf"
        echo "  DB_USERNAME=<DBユーザー名>"
        echo "  DB_PASSWORD=<DBパスワード>"
        echo ""
        echo "  AWS_ACCESS_KEY_ID=<空欄でOK - IAMロール使用>"
        echo "  AWS_SECRET_ACCESS_KEY=<空欄でOK - IAMロール使用>"
        echo "  AWS_DEFAULT_REGION=ap-northeast-1"
        echo "  AWS_BUCKET=<S3バケット名>"
        echo ""
        echo "  APP_URL=http://<Elastic IP>"
        echo ""
        read -p ".envファイルを編集しましたか？ (yes): " confirm
        if [[ "$confirm" != "yes" ]]; then
            log_error "セットアップを中断します。.envを編集後、再度実行してください。"
            exit 1
        fi
    fi

    log_step "6/9: アプリケーションキー生成"
    php artisan key:generate --force

    log_step "7/9: データベースマイグレーション"
    if check_db_connection; then
        php artisan migrate --force
    else
        log_warn "RDSに接続できません。マイグレーションをスキップしました。"
        log_warn "RDS起動後に手動で実行してください: php artisan migrate --force"
        DB_SKIPPED=true
    fi

    log_step "8/9: キャッシュクリアと最適化"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan optimize

    log_step "9/9: 権限設定"
    # ec2-user を nginx グループに追加(初回のみ、既に所属していれば no-op)
    sudo usermod -aG "$APP_USER" "$USER"
    set_permissions

    # ストレージリンク
    php artisan storage:link

    log_info "初回セットアップ完了！"
    if [[ "${DB_SKIPPED:-false}" == "true" ]]; then
        log_warn "※ RDS停止中のためマイグレーションがスキップされています"
        log_warn "  RDS起動後: cd $APP_DIR && php artisan migrate --force"
    fi
    echo ""
    echo "次のステップ:"
    echo "  1. 一度ログアウトして再ログイン(nginxグループの反映に必要)"
    echo "  2. Webブラウザでアプリケーションにアクセス"
    echo "  3. 動作確認"
}

# アプリケーション更新
update_app() {
    log_step "1/7: ディレクトリ移動"
    cd "$APP_DIR"

    # git操作・artisanコマンド実行のため一時的にec2-userへ所有権変更（終了時にnginxへ戻す）
    sudo chown -R "$USER":"$USER" "$APP_DIR"

    # .gitがない場合はリポジトリを復元してからアップデートを続行
    if [[ ! -d ".git" ]]; then
        log_warn ".gitが見つかりません。Gitリポジトリを復元します..."
        mkdir -p ~/.ssh
        ssh-keyscan github.com >> ~/.ssh/known_hosts 2>/dev/null
        git init
        git remote add origin "$REPO_URL"
        git fetch origin "$BRANCH"
        git reset --hard "origin/$BRANCH"
        log_info "Gitリポジトリの復元完了"
    fi

    log_step "2/7: メンテナンスモード開始"
    php artisan down --retry=60

    log_step "3/7: 最新コードを取得"
    git fetch origin
    git reset --hard "origin/$BRANCH"

    log_step "4/7: Composerパッケージ更新"
    composer install --no-dev --optimize-autoloader

    log_step "5/7: npmパッケージ更新とビルド"
    npm ci
    npm run build

    log_step "6/7: データベースマイグレーション"
    if check_db_connection; then
        php artisan migrate --force
    else
        log_warn "RDSに接続できません。マイグレーションをスキップしました。"
        log_warn "RDS起動後に手動で実行してください: php artisan migrate --force"
        DB_SKIPPED=true
    fi

    log_step "7/7: キャッシュクリアと最適化"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan optimize

    # メンテナンスモード終了（chownより前に実行。chown後はec2-userでunlinkできなくなるため）
    php artisan up

    # 権限再設定
    set_permissions

    log_info "アプリケーション更新完了！"
    if [[ "${DB_SKIPPED:-false}" == "true" ]]; then
        log_warn "※ RDS停止中のためマイグレーションがスキップされています"
        log_warn "  RDS起動後: cd $APP_DIR && php artisan migrate --force"
    fi
}

# サービス再起動
restart_services() {
    log_info "PHP-FPMを再起動中..."
    sudo systemctl restart php-fpm

    log_info "Nginxを再起動中..."
    sudo systemctl restart nginx

    log_info "サービス再起動完了"
}

# 状態確認
check_status() {
    echo ""
    log_info "サービス状態:"
    echo ""

    echo "Nginx:"
    sudo systemctl status nginx --no-pager -l | head -5
    echo ""

    echo "PHP-FPM:"
    sudo systemctl status php-fpm --no-pager -l | head -5
    echo ""

    echo "アプリケーション:"
    cd "$APP_DIR"
    php artisan --version
    echo ""

    echo "ディスク使用量:"
    df -h "$APP_DIR"
}

# メイン処理
main() {
    local setup_mode=false
    local update_mode=false

    # 引数解析
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_help
                exit 0
                ;;
            -r|--repo)
                REPO_URL="$2"
                shift 2
                ;;
            -b|--branch)
                BRANCH="$2"
                shift 2
                ;;
            -d|--dir)
                APP_DIR="$2"
                shift 2
                ;;
            --setup)
                setup_mode=true
                shift
                ;;
            --update)
                update_mode=true
                shift
                ;;
            *)
                log_error "不明なオプション: $1"
                show_help
                exit 1
                ;;
        esac
    done

    echo "======================================"
    echo "Movie Profile Platform - App Deploy"
    echo "======================================"
    echo ""

    if $setup_mode; then
        initial_setup
        restart_services
        check_status
    elif $update_mode; then
        update_app
        restart_services
        check_status
    else
        log_error "--setupまたは--updateオプションを指定してください"
        show_help
        exit 1
    fi

    echo ""
    log_info "完了！"
}

main "$@"
