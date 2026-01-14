# アーキテクチャ設計書

## 技術スタック
- **フロントエンド**: Blade (Laravel標準テンプレートエンジン) + Tailwind CSS
- **バックエンド**: Laravel 11.x + PHP 8.2
- **データベース**: MySQL 8.0
- **認証**: Laravel Breeze (標準認証パッケージ)
- **インフラ**: AWS (EC2 + S3 + RDS) + CloudFormation
- **ローカル開発環境**: Docker + Docker Compose
- **動画処理**: FFmpeg
- **その他**: CloudWatch (監視・ログ)

## システム構成図

```mermaid
graph TB
    subgraph "ユーザー"
        Browser[ブラウザ]
    end

    subgraph "AWS環境"
        subgraph "VPC"
            EC2[EC2 t3.micro<br/>Laravel + FFmpeg]
            RDS[RDS MySQL 8.0<br/>db.t3.micro]
        end

        S3[S3バケット<br/>動画ストレージ]
        CloudWatch[CloudWatch<br/>ログ・監視]
    end

    Browser -->|HTTPS| EC2
    EC2 -->|動画アップロード| S3
    Browser -->|動画配信| S3
    EC2 -->|SQL| RDS
    EC2 -->|ログ送信| CloudWatch

    style EC2 fill:#FF9900
    style RDS fill:#527FFF
    style S3 fill:#569A31
    style CloudWatch fill:#FF4F8B
```

### ローカル開発環境構成

```mermaid
graph TB
    subgraph "Docker環境"
        Web[Webコンテナ<br/>Nginx]
        App[アプリコンテナ<br/>PHP 8.2 + Laravel]
        DB[DBコンテナ<br/>MySQL 8.0]
        LocalS3[MinIO<br/>S3エミュレータ]
    end

    Browser[開発者ブラウザ] -->|localhost:80| Web
    Web --> App
    App --> DB
    App --> LocalS3

    style Web fill:#269bd2
    style App fill:#dc322f
    style DB fill:#527FFF
    style LocalS3 fill:#569A31
```

## 選択理由

### フロントエンド
- **Blade + Tailwind CSS**:
  - Laravelの標準テンプレートエンジンで学習コストが低い
  - シンプルなUIのため、フロントエンドフレームワーク（React/Vue）は不要
  - Tailwind CSSでレスポンシブデザインを効率的に実装
  - 個人開発のため、複雑なSPAは避けシンプルな構成に

### バックエンド
- **Laravel 11.x**:
  - 要件に明記された指定技術
  - 認証・ファイルアップロード・バリデーションが標準で充実
  - Eloquent ORMでデータベース操作が直感的
  - コミュニティが活発で情報が豊富

### データベース
- **MySQL 8.0**:
  - 要件に明記された指定技術
  - Laravelとの相性が良い
  - RDSで簡単にマネージド運用可能
  - 小規模アプリケーションには十分な性能

### 認証
- **Laravel Breeze**:
  - Laravel公式の軽量認証スターターキット
  - ユーザー登録・ログイン・ログアウト機能が即座に実装可能
  - Bladeテンプレート対応
  - カスタマイズが容易

### インフラ
- **AWS EC2 (t3.micro)**:
  - LaravelアプリケーションとFFmpegを同居させる
  - 低コストで運用可能
  - 将来的なスケールアップも容易

- **AWS S3**:
  - 動画ファイルの保存・配信に最適
  - パブリック読み取りで直接配信
  - ストレージ容量の制限がなく、従量課金

- **AWS RDS (db.t3.micro)**:
  - マネージドMySQLで運用が簡単
  - 自動バックアップ機能（将来実装）
  - EC2と同一VPC内で安全な通信

- **CloudFormation**:
  - インフラをコードで管理（IaC）
  - 本番環境の構築・破棄が容易
  - パラメータで開発/本番環境を切り替え可能

### ローカル開発環境
- **Docker + Docker Compose**:
  - ローカル環境と本番環境の差異を最小化
  - チーム開発への拡張性（将来的に）
  - MinIOでS3をローカルエミュレート

### 動画処理
- **FFmpeg**:
  - オープンソースで無料
  - 多様な動画フォーマットに対応
  - EC2上で実行（Phase 1）
  - 将来的にLambda化を検討（Phase 2）

### 監視・ログ
- **CloudWatch**:
  - AWS標準の監視サービス
  - ログ収集とアラート設定が可能
  - 追加コストが最小限

## 初期コスト（月額）

### 本番環境（AWS）
- **EC2 (t3.micro)**: $7.50/月
- **RDS (db.t3.micro)**: $12.50/月
- **S3ストレージ**: $1.00/月（想定: 動画50GB程度）
- **S3データ転送**: $1.00/月（想定: 100GB転送）
- **CloudWatch**: $2.00/月（ログ・メトリクス）
- **合計**: **$24.00/月**

### 開発環境
- ローカル開発: $0（Docker使用）

### 備考
- 上記は想定コストで、実際の使用量により変動
- 月額目標$30以下を達成可能
- 開発中はEC2/RDSを停止してコスト削減
- 無料利用枠（初年度）があればさらに削減可能

## 将来の拡張性

### Phase 2での改善案
- **CloudFront**: S3の前段にCDNを配置し、動画配信を高速化
- **ECS + Lambda**: 非同期エンコード処理に移行
- **ElastiCache**: セッション管理やキャッシュに利用
- **RDS自動バックアップ**: データ保護の強化
- **マルチAZ構成**: 可用性向上（コスト増加）

### スケーラビリティ
- EC2のインスタンスタイプ変更でスケールアップ
- Auto Scalingで負荷に応じた自動スケール（将来）
- S3は自動的にスケール
