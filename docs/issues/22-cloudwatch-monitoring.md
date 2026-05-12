# Issue #22: CloudWatch監視設定（本番環境）

> **⚠️ Phase 7 で置換予定**: 本 issue で構築した CloudWatch Agent によるログファイル収集方式は、Phase 7（ECS化）の Issue #35 で **ECS タスク定義の `awslogs` ドライバ**（コンテナの stdout/stderr を直接 CloudWatch Logs に送信）に置き換える。EC2 標準メトリクスでのリソース監視は継続。詳細: `docs/plans/ecs_ecr_migration_phase1.md`

## 背景 / 目的

CloudWatchでEC2/RDSのログ・メトリクス監視を設定し、本番環境の安定運用を実現する。リソース使用率やエラーログを可視化し、問題の早期発見を可能にする。

- **依存**: #1（インフラ構築完了後）
- **後続**: #35（Phase 7 で awslogs ドライバ統合に置換）
- **ラベル**: infra

---

## スコープ / 作業項目

### 1. EC2メトリクス監視設定
- CloudWatch AgentをEC2にインストール
- 監視対象メトリクス:
  - CPU使用率
  - メモリ使用率
  - ディスク使用率
  - ネットワークトラフィック

### 2. RDSメトリクス監視設定
- RDSの標準メトリクスを有効化
- 監視対象:
  - 接続数
  - CPU使用率
  - ストレージ使用率
  - 読み書きIOPS

### 3. Laravelログ収集
- Laravelのログ（`storage/logs/laravel.log`）をCloudWatch Logsに送信
- ログストリーム名: `/aws/ec2/laravel`

### 4. CloudWatch Dashboard作成
- 主要メトリクスを可視化するダッシュボード作成
- グラフ:
  - EC2 CPU使用率
  - RDS接続数
  - エラーログ件数

### 5. アラート設定（オプション）
- CPU使用率が80%を超えた場合
- RDS接続数が上限に近づいた場合
- エラーログが発生した場合

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] EC2インスタンスのCPU/メモリ使用率がCloudWatchで監視される
- [ ] RDSの接続数・ストレージ使用率がCloudWatchで監視される
- [ ] Laravelのログ（storage/logs/laravel.log）がCloudWatch Logsに送信される
- [ ] エラーログが発生した際にアラート通知が設定される（オプション）
- [ ] CloudWatch Dashboardで主要メトリクスが可視化される
- [ ] 監視設定が本番環境で動作確認される

---

## テスト観点

### CloudWatch Agent動作確認
- [ ] EC2インスタンスにSSH接続
- [ ] CloudWatch Agentのステータス確認
```bash
sudo systemctl status amazon-cloudwatch-agent
```
- [ ] CloudWatch Agentが正常に動作している

### メトリクス確認
- [ ] AWSマネジメントコンソールでCloudWatchを開く
- [ ] EC2のメトリクス（CPU、メモリ）が表示される
- [ ] RDSのメトリクス（接続数、ストレージ）が表示される

### ログ収集確認
- [ ] Laravelでエラーログを発生させる
```php
Log::error('テストエラーログ');
```
- [ ] CloudWatch Logsで `/aws/ec2/laravel` ログストリームを確認
- [ ] エラーログが収集されている

### ダッシュボード確認
- [ ] CloudWatch Dashboardを開く
- [ ] EC2 CPU使用率、RDS接続数、エラーログ件数のグラフが表示される

### アラート確認（オプション）
- [ ] 意図的にCPU使用率を上げる（負荷テスト）
- [ ] CloudWatchアラームが発火する
- [ ] SNSでメール通知が届く

### 検証方法
1. EC2にCloudWatch Agentをインストール
2. CloudWatchマネジメントコンソールでメトリクスを確認
3. Laravelでテストログを出力し、CloudWatch Logsに送信されることを確認

---

## 実装例

### CloudWatch Agentインストール（EC2）
```bash
# CloudWatch Agentダウンロード
wget https://s3.amazonaws.com/amazoncloudwatch-agent/ubuntu/amd64/latest/amazon-cloudwatch-agent.deb

# インストール
sudo dpkg -i amazon-cloudwatch-agent.deb

# 設定ファイル作成
sudo vi /opt/aws/amazon-cloudwatch-agent/etc/amazon-cloudwatch-agent.json
```

### CloudWatch Agent設定ファイル
```json
{
  "agent": {
    "metrics_collection_interval": 60,
    "run_as_user": "root"
  },
  "logs": {
    "logs_collected": {
      "files": {
        "collect_list": [
          {
            "file_path": "/var/www/html/storage/logs/laravel.log",
            "log_group_name": "/aws/ec2/laravel",
            "log_stream_name": "{instance_id}",
            "timezone": "Asia/Tokyo"
          }
        ]
      }
    }
  },
  "metrics": {
    "namespace": "CWAgent",
    "metrics_collected": {
      "cpu": {
        "measurement": [
          {
            "name": "cpu_usage_idle",
            "rename": "CPU_IDLE",
            "unit": "Percent"
          }
        ],
        "metrics_collection_interval": 60,
        "totalcpu": false
      },
      "disk": {
        "measurement": [
          {
            "name": "used_percent",
            "rename": "DISK_USED",
            "unit": "Percent"
          }
        ],
        "metrics_collection_interval": 60,
        "resources": [
          "*"
        ]
      },
      "mem": {
        "measurement": [
          {
            "name": "mem_used_percent",
            "rename": "MEM_USED",
            "unit": "Percent"
          }
        ],
        "metrics_collection_interval": 60
      }
    }
  }
}
```

### CloudWatch Agent起動
```bash
sudo /opt/aws/amazon-cloudwatch-agent/bin/amazon-cloudwatch-agent-ctl \
  -a fetch-config \
  -m ec2 \
  -s \
  -c file:/opt/aws/amazon-cloudwatch-agent/etc/amazon-cloudwatch-agent.json

# ステータス確認
sudo systemctl status amazon-cloudwatch-agent
```

### CloudWatch アラーム作成（AWS CLI）
```bash
# CPU使用率80%超過アラーム
aws cloudwatch put-metric-alarm \
  --alarm-name ec2-high-cpu \
  --alarm-description "EC2 CPU使用率が80%を超えました" \
  --metric-name CPUUtilization \
  --namespace AWS/EC2 \
  --statistic Average \
  --period 300 \
  --threshold 80 \
  --comparison-operator GreaterThanThreshold \
  --evaluation-periods 2 \
  --alarm-actions arn:aws:sns:ap-northeast-1:123456789012:my-topic
```

### Laravelログ設定（.env）
```env
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

### Laravelログテスト
```php
use Illuminate\Support\Facades\Log;

// テストログ出力
Log::info('アプリケーション起動');
Log::error('エラーテスト', ['context' => 'test']);
```

---

## 課題確認事項

- **CloudWatch Agentのインストールタイミング**: EC2構築時に自動インストールする？（CloudFormation UserDataで設定）
- **ログ保持期間**: CloudWatch Logsの保持期間は何日？（デフォルトは無期限だがコスト増加）
- **SNS通知先**: アラート通知のメールアドレスは？
- **ダッシュボードの更新頻度**: リアルタイム表示？1分ごと？

---

## 参考資料

- アーキテクチャ設計書: `docs/02_architecture.md`（CloudWatch監視）
- 要件定義書: `docs/01_requirements.md`（運用要件）
- CloudWatch公式ドキュメント: https://docs.aws.amazon.com/ja_jp/AmazonCloudWatch/latest/monitoring/WhatIsCloudWatch.html
