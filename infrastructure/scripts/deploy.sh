#!/bin/bash
#
# CloudFormation デプロイスクリプト
# Movie Profile Platform - AWS本番環境構築
#

set -e

# 色付き出力
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 設定
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CFN_DIR="$(dirname "$SCRIPT_DIR")/cloudformation"
STACK_NAME="movie-prf-production"
REGION="${AWS_REGION:-ap-northeast-1}"
TEMPLATES_BUCKET_PREFIX="movie-prf-cfn-templates"

# ヘルプ表示
show_help() {
    echo "使用方法: $0 [オプション]"
    echo ""
    echo "オプション:"
    echo "  -h, --help          このヘルプを表示"
    echo "  -n, --stack-name    スタック名 (デフォルト: movie-prf-production)"
    echo "  -r, --region        AWSリージョン (デフォルト: ap-northeast-1)"
    echo "  -p, --parameters    パラメータファイルパス (デフォルト: parameters/prod.json)"
    echo "  --delete            スタックを削除"
    echo ""
    echo "必要な環境変数:"
    echo "  ADMIN_IP        SSH許可IPアドレス (例: 203.0.113.0/32)"
    echo "  KEY_PAIR_NAME   EC2キーペア名 (例: my-key-pair)"
    echo "  DB_USERNAME     RDSマスターユーザー名 (例: admin)"
    echo "  DB_PASSWORD     RDSマスターパスワード (8文字以上)"
    echo "  BUCKET_SUFFIX   S3バケット名サフィックス (例: 20260126)"
    echo ""
    echo "例:"
    echo "  export ADMIN_IP=\"\$(curl -s ifconfig.me)/32\""
    echo "  export KEY_PAIR_NAME=\"movie-prf-key\""
    echo "  export DB_USERNAME=\"movieprf_admin\""
    echo "  export DB_PASSWORD=\"YourSecurePassword\""
    echo "  export BUCKET_SUFFIX=\"\$(date +%Y%m%d)\""
    echo "  $0"
}

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

# 必須環境変数チェック
check_required_env() {
    local missing=()

    [[ -z "${ADMIN_IP}" ]] && missing+=("ADMIN_IP")
    [[ -z "${KEY_PAIR_NAME}" ]] && missing+=("KEY_PAIR_NAME")
    [[ -z "${DB_USERNAME}" ]] && missing+=("DB_USERNAME")
    [[ -z "${DB_PASSWORD}" ]] && missing+=("DB_PASSWORD")
    [[ -z "${BUCKET_SUFFIX}" ]] && missing+=("BUCKET_SUFFIX")

    if [[ ${#missing[@]} -gt 0 ]]; then
        log_error "以下の環境変数が設定されていません:"
        for var in "${missing[@]}"; do
            echo "  - $var"
        done
        echo ""
        echo "設定例:"
        echo "  export ADMIN_IP=\"\$(curl -s ifconfig.me)/32\""
        echo "  export KEY_PAIR_NAME=\"movie-prf-key\""
        echo "  export DB_USERNAME=\"movieprf_admin\""
        echo "  export DB_PASSWORD=\"YourSecurePassword\""
        echo "  export BUCKET_SUFFIX=\"\$(date +%Y%m%d)\""
        exit 1
    fi

    # パスワード長チェック
    if [[ ${#DB_PASSWORD} -lt 8 ]]; then
        log_error "DB_PASSWORD は8文字以上である必要があります"
        exit 1
    fi

    # AdminIP形式チェック
    if [[ ! "${ADMIN_IP}" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+/[0-9]+$ ]]; then
        log_error "ADMIN_IP はCIDR形式である必要があります (例: 203.0.113.0/32)"
        exit 1
    fi

    log_info "環境変数チェック: OK"
}

# AWS CLIチェック
check_aws_cli() {
    if ! command -v aws &> /dev/null; then
        log_error "AWS CLIがインストールされていません"
        exit 1
    fi

    if ! aws sts get-caller-identity &> /dev/null; then
        log_error "AWS認証情報が設定されていません"
        exit 1
    fi

    log_info "AWS CLI認証: OK"
    aws sts get-caller-identity --query 'Account' --output text
}

# テンプレート用S3バケット作成
create_templates_bucket() {
    local account_id=$(aws sts get-caller-identity --query 'Account' --output text)
    TEMPLATES_BUCKET="${TEMPLATES_BUCKET_PREFIX}-${account_id}"

    if aws s3 ls "s3://${TEMPLATES_BUCKET}" 2>&1 | grep -q 'NoSuchBucket'; then
        log_info "テンプレート用S3バケットを作成: ${TEMPLATES_BUCKET}"
        aws s3 mb "s3://${TEMPLATES_BUCKET}" --region "${REGION}"
    else
        log_info "テンプレート用S3バケットは既に存在: ${TEMPLATES_BUCKET}"
    fi
}

# テンプレートをS3にアップロード
upload_templates() {
    log_info "CloudFormationテンプレートをS3にアップロード中..."

    # メインテンプレート
    aws s3 cp "${CFN_DIR}/main.yaml" "s3://${TEMPLATES_BUCKET}/main.yaml"

    # ネストテンプレート
    aws s3 sync "${CFN_DIR}/templates/" "s3://${TEMPLATES_BUCKET}/templates/" --delete

    log_info "テンプレートのアップロード完了"
}

# パラメータファイルの検証と更新
prepare_parameters() {
    local param_file="$1"

    if [[ ! -f "$param_file" ]]; then
        log_error "パラメータファイルが見つかりません: $param_file"
        exit 1
    fi

    # 一時パラメータファイル作成（環境変数でプレースホルダーを置換）
    TEMP_PARAMS=$(mktemp)
    sed -e "s|\${ADMIN_IP}|${ADMIN_IP}|g" \
        -e "s|\${KEY_PAIR_NAME}|${KEY_PAIR_NAME}|g" \
        -e "s|\${DB_USERNAME}|${DB_USERNAME}|g" \
        -e "s|\${DB_PASSWORD}|${DB_PASSWORD}|g" \
        -e "s|\${TEMPLATES_BUCKET}|${TEMPLATES_BUCKET}|g" \
        -e "s|\${BUCKET_SUFFIX}|${BUCKET_SUFFIX}|g" \
        "$param_file" > "$TEMP_PARAMS"

    echo "$TEMP_PARAMS"
}

# スタックのデプロイ
deploy_stack() {
    local param_file="$1"
    local temp_params=$(prepare_parameters "$param_file")

    log_info "CloudFormationスタックをデプロイ中: ${STACK_NAME}"

    # スタックが存在するかチェック
    if aws cloudformation describe-stacks --stack-name "${STACK_NAME}" --region "${REGION}" &> /dev/null; then
        log_info "既存のスタックを更新中..."
        aws cloudformation update-stack \
            --stack-name "${STACK_NAME}" \
            --template-url "https://${TEMPLATES_BUCKET}.s3.${REGION}.amazonaws.com/main.yaml" \
            --parameters file://"${temp_params}" \
            --capabilities CAPABILITY_NAMED_IAM \
            --region "${REGION}" || {
                if [[ $? -eq 255 ]]; then
                    log_info "スタックに変更はありません"
                    rm "$temp_params"
                    return 0
                fi
            }

        log_info "スタック更新を待機中..."
        aws cloudformation wait stack-update-complete \
            --stack-name "${STACK_NAME}" \
            --region "${REGION}"
    else
        log_info "新規スタックを作成中..."
        aws cloudformation create-stack \
            --stack-name "${STACK_NAME}" \
            --template-url "https://${TEMPLATES_BUCKET}.s3.${REGION}.amazonaws.com/main.yaml" \
            --parameters file://"${temp_params}" \
            --capabilities CAPABILITY_NAMED_IAM \
            --region "${REGION}" \
            --on-failure DO_NOTHING

        log_info "スタック作成を待機中（数分かかります）..."
        aws cloudformation wait stack-create-complete \
            --stack-name "${STACK_NAME}" \
            --region "${REGION}"
    fi

    rm "$temp_params"
    log_info "デプロイ完了！"
}

# スタックの出力値を表示
show_outputs() {
    log_info "スタック出力値:"
    echo ""
    aws cloudformation describe-stacks \
        --stack-name "${STACK_NAME}" \
        --region "${REGION}" \
        --query 'Stacks[0].Outputs[*].[OutputKey,OutputValue]' \
        --output table
}

# スタックの削除
delete_stack() {
    log_warn "スタック ${STACK_NAME} を削除します"
    read -p "本当に削除しますか？ (yes/no): " confirm

    if [[ "$confirm" != "yes" ]]; then
        log_info "削除をキャンセルしました"
        exit 0
    fi

    # RDSの削除保護を無効化（手動で行う必要がある場合があります）
    log_warn "RDSの削除保護が有効な場合、先にAWSコンソールで無効化してください"

    log_info "スタックを削除中..."
    aws cloudformation delete-stack \
        --stack-name "${STACK_NAME}" \
        --region "${REGION}"

    log_info "スタック削除を待機中..."
    aws cloudformation wait stack-delete-complete \
        --stack-name "${STACK_NAME}" \
        --region "${REGION}"

    log_info "スタック削除完了"
}

# メイン処理
main() {
    local param_file="${CFN_DIR}/parameters/prod.json"
    local delete_mode=false

    # 引数解析
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_help
                exit 0
                ;;
            -n|--stack-name)
                STACK_NAME="$2"
                shift 2
                ;;
            -r|--region)
                REGION="$2"
                shift 2
                ;;
            -p|--parameters)
                param_file="$2"
                shift 2
                ;;
            --delete)
                delete_mode=true
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
    echo "Movie Profile Platform - AWS Deploy"
    echo "======================================"
    echo ""

    # AWS CLI認証チェック
    check_aws_cli
    echo ""

    if $delete_mode; then
        delete_stack
    else
        # 環境変数チェック
        check_required_env
        echo ""

        # テンプレートバケット作成
        create_templates_bucket
        echo ""

        # テンプレートアップロード
        upload_templates
        echo ""

        # デプロイ実行
        deploy_stack "$param_file"
        echo ""

        # 出力値表示
        show_outputs
    fi

    echo ""
    log_info "完了！"
}

main "$@"
