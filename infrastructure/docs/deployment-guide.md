# AWS本番環境デプロイ手順書

動画付き自己紹介プラットフォームのAWS本番環境構築手順です。

## 目次

1. [事前準備](#1-事前準備)
2. [CloudFormationデプロイ](#2-cloudformationデプロイ)
3. [アプリケーションデプロイ](#3-アプリケーションデプロイ)
4. [動作確認](#4-動作確認)
5. [トラブルシューティング](#5-トラブルシューティング)
6. [運用・保守](#6-運用保守)

---

## 1. 事前準備

### 1.1 必要なツール

ローカル環境に以下をインストールしてください：

```bash
# AWS CLI v2
curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip"
unzip awscliv2.zip
sudo ./aws/install

# バージョン確認
aws --version
```

### 1.2 AWS認証設定

IAMユーザーを作成し、アクセスキーを設定します：

```bash
aws configure
# AWS Access Key ID: <アクセスキーID>
# AWS Secret Access Key: <シークレットアクセスキー>
# Default region name: ap-northeast-1
# Default output format: json

# 認証確認
aws sts get-caller-identity
```

**必要なIAM権限**:
- CloudFormation: フルアクセス
- EC2: フルアクセス
- RDS: フルアクセス
- S3: フルアクセス
- IAM: ロール作成・アタッチ権限
- VPC: フルアクセス

### 1.3 EC2キーペア作成

SSH接続用のキーペアを作成します：

```bash
# AWSコンソールまたはCLIで作成
aws ec2 create-key-pair \
    --key-name movie-prf-key \
    --query 'KeyMaterial' \
    --output text > movie-prf-key.pem

# 権限設定
chmod 400 movie-prf-key.pem
```

### 1.4 環境変数の設定

デプロイに必要な機密情報は環境変数で管理します。`prod.json` は編集不要です。

#### 方法1: 直接設定

```bash
# 現在のIPアドレスを確認
curl ifconfig.me

# 環境変数を設定（値は各自で設定してください）
export ADMIN_IP="<あなたのIPアドレス>/32"
export KEY_PAIR_NAME="<キーペア名>"
export DB_USERNAME="<DBユーザー名>"
export DB_PASSWORD="<DBパスワード（8文字以上）>"
export BUCKET_SUFFIX="<一意のサフィックス>"
```

#### 方法2: .env.deploy ファイルを使用（推奨）

```bash
cd infrastructure/scripts

# テンプレートをコピー
cp .env.deploy.example .env.deploy

# .env.deploy を編集して値を設定
nano .env.deploy

# 環境変数をロード
source .env.deploy
```

**必須環境変数一覧**:

| 変数名 | 説明 | 例 |
|--------|------|-----|
| `ADMIN_IP` | SSH許可IPアドレス（CIDR形式） | `203.0.113.45/32` |
| `KEY_PAIR_NAME` | EC2キーペア名 | `movie-prf-key` |
| `DB_USERNAME` | RDSマスターユーザー名 | `movieprf_admin` |
| `DB_PASSWORD` | RDSマスターパスワード（8文字以上） | - |
| `BUCKET_SUFFIX` | S3バケット名サフィックス | `20260126` |

> **Note**: `.env.deploy` はGit管理対象外です。機密情報をGitにコミットしないでください。

---

## 2. CloudFormationデプロイ

### 2.1 デプロイスクリプトの実行

```bash
cd infrastructure/scripts

# 実行権限付与
chmod +x deploy.sh

# 環境変数が設定されていることを確認
echo $ADMIN_IP $KEY_PAIR_NAME $DB_USERNAME $BUCKET_SUFFIX

# デプロイ実行
./deploy.sh
```

> **Note**: 環境変数が未設定の場合、エラーメッセージと設定例が表示されます。

### 2.2 デプロイの流れ

1. **テンプレート用S3バケット作成** - CloudFormationテンプレートを格納
2. **テンプレートアップロード** - 全テンプレートをS3にアップロード
3. **スタック作成** - 以下の順序でリソースを作成
   - VPC、サブネット、インターネットゲートウェイ
   - セキュリティグループ
   - S3バケット
   - IAMロール
   - RDSインスタンス（数分かかります）
   - EC2インスタンス

### 2.3 デプロイ完了の確認

```bash
# スタック状態確認
aws cloudformation describe-stacks \
    --stack-name movie-prf-production \
    --query 'Stacks[0].StackStatus'

# 期待される結果: "CREATE_COMPLETE"
```

### 2.4 出力値の確認

デプロイ完了後、以下の情報が表示されます：

| 出力キー | 説明 |
|----------|------|
| EC2PublicIP | Elastic IPアドレス |
| RDSEndpoint | RDSエンドポイント |
| VideosBucketName | S3バケット名 |
| SSHCommand | SSH接続コマンド |
| WebURL | WebアクセスURL |

```bash
# 出力値を確認
aws cloudformation describe-stacks \
    --stack-name movie-prf-production \
    --query 'Stacks[0].Outputs'
```

---

## 3. アプリケーションデプロイ

### 3.1 EC2にSSH接続

SSH Agent Forwardingを使用して接続します。これにより、ローカルのSSH鍵を使ってEC2からGitHubにアクセスできます。

```bash
# ローカルでSSH Agentに鍵を登録（初回のみ）
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_rsa

# Agent Forwarding付きでEC2に接続（-Aオプション）
ssh -A -i movie-prf-key.pem ec2-user@<Elastic IP>
```

> **Note**: `-A`オプションにより、EC2上で`git clone`や`git pull`がローカルの認証情報を使って実行できます。

### 3.2 初回セットアップ

EC2インスタンス上で実行：

```bash
# デプロイスクリプトをダウンロード（または手動でコピー）
curl -O https://raw.githubusercontent.com/YOUR_REPO/infrastructure/scripts/app-deploy.sh
chmod +x app-deploy.sh

# 初回セットアップ実行（SSH形式のURL）
./app-deploy.sh --setup --repo git@github.com:YOUR_USERNAME/movie-prf.git
```

### 3.3 .envファイルの設定

セットアップ中に `.env` ファイルの編集を求められます：

```bash
sudo nano /var/www/movie-prf/.env
```

設定例：

```env
APP_NAME="Movie Profile"
APP_ENV=production
APP_KEY=base64:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
APP_DEBUG=false
APP_URL=http://<Elastic IP>

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=<RDSエンドポイント>
DB_PORT=3306
DB_DATABASE=movie_prf
DB_USERNAME=admin
DB_PASSWORD=<DBパスワード>

FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=ap-northeast-1
AWS_BUCKET=<S3バケット名>
AWS_USE_PATH_STYLE_ENDPOINT=false

QUEUE_CONNECTION=database
```

> **Note**: `AWS_ACCESS_KEY_ID` と `AWS_SECRET_ACCESS_KEY` は空欄でOKです。
> EC2のIAMロールが自動的に認証を行います。

### 3.4 アプリケーション更新（以降のデプロイ）

```bash
./app-deploy.sh --update
```

---

## 4. 動作確認

### 4.1 インフラ検証チェックリスト

- [ ] CloudFormationスタックが `CREATE_COMPLETE`
- [ ] EC2にSSH接続できる
- [ ] EC2からRDSに接続できる
- [ ] S3にファイルをアップロードできる

**RDS接続テスト**:
```bash
mysql -h <RDSエンドポイント> -u admin -p
# パスワード入力後、MySQLプロンプトが表示されればOK
```

**S3接続テスト**:
```bash
# EC2上で実行（IAMロールによる認証）
aws s3 ls s3://<バケット名>
```

### 4.2 アプリケーション検証チェックリスト

- [ ] `http://<Elastic IP>` でトップページが表示される
- [ ] ユーザー登録ができる
- [ ] ログインができる
- [ ] 動画アップロードができる
- [ ] 動画エンコードが完了する
- [ ] 公開プロフィールページで動画が再生される

### 4.3 ログ確認

```bash
# Laravelログ
sudo tail -f /var/www/movie-prf/storage/logs/laravel.log

# Nginxアクセスログ
sudo tail -f /var/log/nginx/access.log

# Nginxエラーログ
sudo tail -f /var/log/nginx/error.log

# PHP-FPMログ
sudo tail -f /var/log/php-fpm/www-error.log
```

---

## 5. トラブルシューティング

### 5.1 CloudFormation関連

**スタック作成失敗**:
```bash
# イベントログ確認
aws cloudformation describe-stack-events \
    --stack-name movie-prf-production \
    --query 'StackEvents[?ResourceStatus==`CREATE_FAILED`]'
```

**よくある原因**:
- AdminIPの形式が不正（CIDR形式 `/32` が必要）
- キーペアが存在しない
- IAM権限不足

### 5.2 EC2接続関連

**SSH接続できない**:
1. セキュリティグループでAdminIPが許可されているか確認
2. キーペアの権限が `400` か確認
3. EC2インスタンスが `running` 状態か確認

```bash
# セキュリティグループ確認
aws ec2 describe-security-groups \
    --filters "Name=group-name,Values=*movie-prf-ec2*" \
    --query 'SecurityGroups[].IpPermissions'
```

### 5.3 RDS接続関連

**EC2からRDSに接続できない**:
1. RDSセキュリティグループでEC2からのアクセスが許可されているか
2. RDSが `available` 状態か確認

```bash
# RDS状態確認
aws rds describe-db-instances \
    --db-instance-identifier production-movie-prf-db \
    --query 'DBInstances[0].DBInstanceStatus'
```

### 5.4 アプリケーション関連

**500エラー**:
```bash
# Laravelログ確認
sudo tail -100 /var/www/movie-prf/storage/logs/laravel.log

# 権限確認
ls -la /var/www/movie-prf/storage/
ls -la /var/www/movie-prf/bootstrap/cache/
```

**動画アップロード失敗**:
1. PHP設定の `upload_max_filesize` が100Mか確認
2. Nginx設定の `client_max_body_size` が100Mか確認
3. S3バケットポリシーが正しいか確認

```bash
# PHP設定確認
php -i | grep upload_max_filesize
```

### 5.5 スタック削除

テストや再構築のためにスタックを削除する場合：

```bash
# 削除前にRDSの削除保護を無効化（AWSコンソールで実行）

# スタック削除
./deploy.sh --delete
```

> **Warning**: S3バケットは `DeletionPolicy: Retain` のため、手動で削除が必要です。

---

## 6. 運用・保守

### 6.1 コスト管理

**月額見積もり**: 約$24/月

| リソース | 月額 |
|----------|------|
| EC2 (t3.micro) | $7.50 |
| RDS (db.t3.micro) | $12.50 |
| S3 (50GB) | $2.00 |
| CloudWatch | $2.00 |

**コスト削減のヒント**:
- 開発終了後はEC2/RDSを停止
- S3のライフサイクルルールでINTELLIGENT_TIERINGに移行

### 6.2 バックアップ

**RDS自動バックアップ**:
- 保持期間: 1日（無料枠対応）
- バックアップウィンドウ: 18:00-19:00 UTC

**手動スナップショット**:
```bash
aws rds create-db-snapshot \
    --db-instance-identifier production-movie-prf-db \
    --db-snapshot-identifier manual-snapshot-$(date +%Y%m%d)
```

### 6.3 モニタリング

**CloudWatchメトリクス確認**:
- EC2: CPU使用率、ネットワークI/O
- RDS: CPU使用率、接続数、ストレージ使用量
- S3: バケットサイズ、リクエスト数

### 6.4 HTTPS対応（将来）

Let's Encryptを使用した無料SSL証明書の設定：

```bash
# Certbot インストール
sudo dnf install -y certbot python3-certbot-nginx

# 証明書取得（ドメイン設定後）
sudo certbot --nginx -d yourdomain.com

# 自動更新設定
sudo systemctl enable certbot-renew.timer
```

### 6.5 セキュリティアップデート

```bash
# システム更新（月1回推奨）
sudo dnf update -y

# PHP/Composerパッケージ更新
cd /var/www/movie-prf
composer update --no-dev
```

---

## 付録

### A. 作成されるリソース一覧

| カテゴリ | リソース | 名前 |
|----------|----------|------|
| ネットワーク | VPC | production-movie-prf-vpc |
| | Public Subnet | production-movie-prf-public-subnet |
| | Private Subnet 1 | production-movie-prf-private-subnet-1 |
| | Private Subnet 2 | production-movie-prf-private-subnet-2 |
| | Internet Gateway | production-movie-prf-igw |
| セキュリティ | EC2 Security Group | production-movie-prf-ec2-sg |
| | RDS Security Group | production-movie-prf-rds-sg |
| | IAM Role | production-movie-prf-ec2-role |
| ストレージ | S3 Bucket | production-movie-prf-videos-{account_id} |
| データベース | RDS Instance | production-movie-prf-db |
| コンピュート | EC2 Instance | production-movie-prf-ec2 |
| | Elastic IP | production-movie-prf-eip |

### B. 重要なファイルパス（EC2内）

| パス | 説明 |
|------|------|
| /var/www/movie-prf | アプリケーションルート |
| /var/www/movie-prf/.env | 環境設定ファイル |
| /var/www/movie-prf/storage/logs | Laravelログ |
| /etc/nginx/conf.d/movie-prf.conf | Nginx設定 |
| /etc/php-fpm.d/www.conf | PHP-FPM設定 |
| /var/log/nginx | Nginxログ |
| /var/log/user-data.log | EC2初期化ログ |

### C. 便利なコマンド

```bash
# Laravelコマンド
cd /var/www/movie-prf
php artisan queue:work --daemon     # キューワーカー起動
php artisan migrate:status          # マイグレーション状態確認
php artisan route:list              # ルート一覧

# サービス管理
sudo systemctl status nginx
sudo systemctl status php-fpm
sudo systemctl restart nginx php-fpm

# ログ監視
sudo tail -f /var/www/movie-prf/storage/logs/laravel.log
```

---

**作成日**: 2026-01-26
**最終更新**: 2026-01-27
