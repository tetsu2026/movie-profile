# AWS SES メール設定手順書

## 概要

動画プロフィールサービスのメール認証機能を本番環境で動作させるためのAWS SES設定手順。

## 構成図

### メール送信の全体構成

```
┌──────────┐     ┌──────────┐     ┌──────────┐
│  ユーザー  │────→│  EC2     │────→│ AWS SES  │────→ メール受信
│（ブラウザ）│     │ Laravel  │     │          │
└──────────┘     └──────────┘     └──────────┘
 新規登録         IAMロールで        DKIM署名付き
                  SES送信を許可      で送信
```

### メール認証フロー

```
登録 → 認証メール送信 → リンクをクリック → 認証完了 → サービス利用可能
```

---

## Laravel側の変更内容

### 1. メール認証の有効化

**`app/Models/User.php`**
- `MustVerifyEmail`インターフェースを実装
- これにより、新規登録時に認証リンク付きメールが自動送信される

### 2. ルートの保護

**`routes/web.php`**
- 以下のルートに`verified`ミドルウェアを追加（メール未認証ユーザーのアクセスをブロック）:
  - `/dashboard/video-stats`（動画統計API）
  - プロフィール編集（`/profile`）
  - ダッシュボードプロフィール編集（`/dashboard/profile/edit`）
  - プレビュー（`/dashboard/preview`）
  - 動画管理（`/dashboard/videos/*`）
- `/dashboard`は元々`verified`が付いていたため変更なし

### 3. SESリージョンの修正

**`config/services.php`**
- SESリージョンのデフォルト値を`us-east-1`→`ap-northeast-1`に変更

### 4. メール設定の追加

**`.env.example`**
- `MAIL_FROM_ADDRESS=noreply@hozu.click` 追加
- `MAIL_FROM_NAME="${APP_NAME}"` 追加
- ローカル/本番の切り替えコメント追加

### 5. メール文面の日本語翻訳

**`lang/ja.json`**（新規作成）
- メール認証メール: 件名、本文、ボタンテキスト
- パスワードリセットメール: 件名、本文、ボタンテキスト
- 共通: 挨拶、署名、ボタン代替テキスト

### 6. IAM権限追加

**`infrastructure/cloudformation/templates/iam.yaml`**
- EC2ロールに`ses:SendEmail`、`ses:SendRawEmail`権限を追加

---

## AWS側の設定手順

### 全体の流れ

```
Step 1: SESでドメイン認証     → メール送信元として信頼される
Step 2: DNSレコード追加        → DKIM認証を有効化
Step 3: サンドボックス解除申請  → 任意のアドレスに送信可能にする
Step 4: IAMロールに権限追加    → EC2にSES送信権限を付与
Step 5: EC2の.env更新          → MAIL_MAILER=ses に切り替え
```

### Step 1: SESでドメインIDを作成

1. AWSコンソール → **Amazon SES** → **Verified identities**
2. **Create identity** をクリック
3. 設定:
   - **Identity type**: `Domain`
   - **Domain**: `hozu.click`
   - **Advanced DKIM settings**: `Easy DKIM`（デフォルト）
   - **DKIM signing key length**: `2048`
   - Route 53でドメイン管理している場合は「**Publish DNS records in Route 53**」を有効に
4. **Create identity** をクリック

### Step 2: DNSレコードの追加

SESが生成する以下のレコードをDNS管理画面で追加（Route 53自動設定を有効にした場合は不要）:

| 種類 | 名前 | 値 | 目的 |
|------|------|-----|------|
| CNAME × 3 | `xxxx._domainkey.hozu.click` | `xxxx.dkim.amazonses.com` | DKIM認証 |
| TXT | `_dmarc.hozu.click` | `v=DMARC1; p=quarantine; rua=mailto:admin@hozu.click` | DMARC |

※ SPFはSESが自動的にReturn-Pathを設定するため不要
※ 反映まで最大72時間（通常は数分〜数時間）
※ DMARCの`p`の値は以下から選択。今回は`p=quarantine`を使用。SESから送る正規メールには影響なし。
  - `p=none`: なりすましメールがあっても何もしない（監視のみ）
  - `p=quarantine`: なりすましメールを迷惑メールフォルダに振り分ける
  - `p=reject`: なりすましメールを完全にブロックする

### Step 3: サンドボックス解除申請

初期状態では認証済みアドレスにしか送信できないため、解除が必要。

1. SES → **Account dashboard** → **Request production access**
2. 記入内容:
   - **Mail type**: `Transactional`
   - **Website URL**: `https://hozu.click`
   - **Use case description**:
     ```
     ユーザー新規登録時のメールアドレス確認メールを送信します。
     1日あたりの送信見込みは100通以下です。
     バウンスメールへの対応としてSNS通知を設定予定です。
     ```
3. 通常1営業日以内に審査完了

#### サンドボックス解除前のテスト方法

SES → Verified identities で自分のメールアドレスを個別に認証すれば、そのアドレスには送信可能。

### Step 4: IAMロールにSES送信権限を付与

EC2がSES経由でメールを送信するために、IAMロールに権限を追加する。

#### 方法A: AWSコンソールから手動で追加

1. AWSコンソール → **IAM** → 左メニューの **Roles（ロール）**
2. 検索ボックスに `movie-prf` と入力
3. `production-movie-prf-ec2-role` をクリック
4. **Permissions（許可）** タブ → **Add permissions** → **Create inline policy**
5. **JSON** タブを選択し、以下を貼り付け:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "ses:SendEmail",
                "ses:SendRawEmail"
            ],
            "Resource": "*"
        }
    ]
}
```

6. **Next** をクリック
7. **Policy name**: `movie-prf-ses-access`
8. **Create policy** をクリック
9. ロールの Permissions タブに `movie-prf-ses-access` が表示されていれば完了

※ EC2の再起動は不要。即座に反映される。

#### 方法B: CloudFormationスタック更新

IAMテンプレート（`infrastructure/cloudformation/templates/iam.yaml`）にSES権限を追加済み。スタックを更新する:

```bash
aws cloudformation update-stack \
  --stack-name production-movie-prf-iam \
  --template-body file://infrastructure/cloudformation/templates/iam.yaml \
  --capabilities CAPABILITY_NAMED_IAM \
  --parameters ParameterKey=VideosBucketName,UsePreviousValue=true
```

※ 方法Aで手動追加済みの場合、方法Bを実行するとCloudFormationが管理するポリシーとして上書きされる。両方実行しても問題ない。

### Step 5: EC2の.env更新

```env
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@hozu.click
MAIL_FROM_NAME="動画プロフィール(Laravel版)"
```

更新後にキャッシュをクリア:

```bash
php artisan config:clear
```

---

## 確認順序のおすすめ

1. まず **Step 1〜2**（ドメイン認証）を実施し、SESのステータスが「Verified」になるのを待つ
2. 待っている間に **Step 3**（サンドボックス解除申請）を出す
3. 認証完了後、**Step 4〜5** でEC2に接続して切り替え

---

## 料金

| 項目 | 料金 |
|------|------|
| SES送信 | $0.10 / 1,000通 |
| SNSバウンス通知 | 月100万リクエスト無料 |
| 合計（月間6,000通の場合） | 約$0.60/月 |

---

## セキュリティ

- EC2のIAMロール認証を使用（APIキー不要）
- SESは認証されたリクエストのみ送信可能（外部からの不正利用不可）
- サンドボックスのデフォルト送信上限: 200通/日
- バウンス率5%超でAWSから警告、10%超で送信停止

---

## 用語解説

### メールのなりすまし防止技術

| 技術 | 役割 | 例え |
|------|------|------|
| **SPF** | 「このサーバーから送っていいよ」という送信元の許可リスト | 「この郵便局から出した手紙だけ本物」 |
| **DKIM** | メールに電子署名を付けて改ざんされていないことを証明 | 「封蝋で封をした手紙」 |
| **DMARC** | SPFとDKIMの検証に失敗したメールの扱いを定義。不正送信の監視カメラのような役割 | 「偽物の手紙が来たら捨てて、報告して」 |

- **SPF**: 今回はSESを使うため、SESが自動で「SESのサーバーから送信された正規のメールです」と処理する。設定不要。
- **DKIM**: **設定が必要**（Step 2で設定）。
- **DMARC**: 第三者が`hozu.click`を騙って別のサーバーからメールを送る「なりすまし」への対策。設定しなくてもメール送信は動くが、なりすましを検知・ブロックできない。DNSにTXTレコードを1行追加するだけで、コストはゼロのため設定を推奨。`rua=mailto:...`を指定すると、なりすまし試行のレポート（XML形式）が管理者に届く。

### サンドボックスと認証済みアドレス

SESの初期状態は「サンドボックスモード」で、**送信先（To）もSESで事前に認証したアドレスにしか送れない**。

認証済みアドレスの登録方法:
1. SES → Verified identities → **Create identity** → **Email address** を選択
2. 自分のメールアドレス（例: `tetsu@gmail.com`）を入力
3. そのアドレスに確認メールが届く → リンクをクリックして認証完了
4. これで `tetsu@gmail.com` 宛てにはSESからメール送信可能

つまりサンドボックス中は**自分や開発チームのアドレスでしかテストできない**。一般ユーザーに送るにはサンドボックス解除（Step 3）が必要。

---

## 将来の拡張（未実装）

- **バウンス/苦情通知**: SNSトピック + SES Notification連携
- **キュー非同期送信**: `ShouldQueue` + systemdワーカー
- **CloudWatch監視**: SESメトリクスのアラーム設定
- **reCAPTCHA**: 登録フォームへのボット対策
