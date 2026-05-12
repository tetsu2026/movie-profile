# Issue #35: CloudWatch Logs 統合・本番切替・検証

## 背景 / 目的

ECS タスク定義に `awslogs` ドライバを設定して、コンテナログを CloudWatch Logs に集約する。Issue #22 で構築した CloudWatch Agent によるファイル収集方式を、`awslogs` ドライバ方式に置き換える。

本番切替手順・チェックリスト・ロールバック手順を整備し、最終的に本番運用へ切り替える。Phase 7 の最終 issue。

- **依存**: #34
- **ラベル**: infra, ops
- **関連プラン**: `docs/plans/ecs_ecr_migration_phase1.md`

---

## スコープ / 作業項目

### 1. CloudWatch ロググループ作成
- `/ecs/movie-prf/laravel-web`（retention 7 日）
- `/ecs/movie-prf/laravel-worker`（retention 7 日）
- CloudFormation `templates/cloudwatch-logs.yaml` を新規作成

### 2. タスク定義に awslogs 設定
```json
"logConfiguration": {
  "logDriver": "awslogs",
  "options": {
    "awslogs-group": "/ecs/movie-prf/laravel-web",
    "awslogs-region": "ap-northeast-1",
    "awslogs-stream-prefix": "ecs"
  }
}
```

### 3. アプリ側のログ設定変更
- `.env`: `LOG_CHANNEL=stack`、`LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter`
- `config/logging.php` の `stderr` チャネルが正しく定義されているか確認
- Laravel のログ書込先を `php://stderr` に切替（コンテナの stdout/stderr が awslogs ドライバに流れる）

### 4. 旧 CloudWatch Agent 無効化
```bash
sudo systemctl disable --now amazon-cloudwatch-agent
```
- 旧 `/opt/aws/amazon-cloudwatch-agent/etc/*.json` は `.bak` で残す
- EC2 のメトリクス収集（CPU/メモリ/swap）は CloudWatch Agent ではなく EC2 標準メトリクス + 詳細監視で代替（必要に応じて）

### 5. 本番切替チェックリスト実行
- `docs/plans/ecs_ecr_migration_phase1.md` の「本番切替チェックリスト」を実行
- すべてのチェック項目をクリア

### 6. 動作検証
- 動画アップロード → Queue → encoding → completed フロー全体
- ECS Exec でコンテナ内に入り、`php artisan tinker` で疎通確認
- CloudWatch Logs で laravel-web / laravel-worker の stdout を確認

### 7. ロールバック手順整備
- `docs/operations/rollback_phase7.md` 新規作成
- 「nginx 旧 conf 復元 + php-fpm 再起動」「ECS タスク定義切戻し」「ECR イメージタグ指定」の手順を記載

### 8. swap 使用率モニタリング
- CloudWatch Metrics で swap 使用率を確認
- 200MiB 超が継続するなら t3.small へアップグレード判断

### 9. Issue #22（旧 CloudWatch 監視）クローズ
- Issue #22 のスコープは Phase 7 で置換されたため、本 issue 完了時にクローズ

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] 各タスク定義の `logConfiguration.logDriver = "awslogs"`、`awslogs-group = /ecs/movie-prf/<task-name>`（retention 7 日）が設定される
- [ ] CloudWatch Logs で laravel-web / laravel-worker の stdout が確認できる
- [ ] 旧 CloudWatch Agent のファイル収集設定は無効化（Issue #22 の置換）
- [ ] 本番切替チェックリスト（`docs/plans/ecs_ecr_migration_phase1.md` 参照）が完了している
- [ ] 動画アップロード→Queue→encoding→completed の一連フローが本番で確認される
- [ ] swap 使用率が CloudWatch Metrics で監視され、200MiB 超が継続するなら t3.small へアップグレード判断
- [ ] ロールバック手順が文書化され、検証済み

---

## テスト観点

### ログ流入
- [ ] `php artisan tinker` で `Log::info('test')` → CloudWatch Logs に流れる
- [ ] エラー発生時に `Log::error` がスタックトレース付きで流れる
- [ ] BullMQ ではなく Laravel Queue Worker のジョブ実行ログが流れる

### 旧 CloudWatch Agent 停止
- [ ] `systemctl status amazon-cloudwatch-agent` が disabled
- [ ] 旧 `/aws/ec2/laravel` ロググループには新規ログが入らない（Phase 7 切替後）

### 動画フロー
- [ ] 動画アップロード → 即レスポンス（待たされない）
- [ ] `videos.status` が `uploading → encoding → completed` まで遷移
- [ ] エンコード済み動画が S3 にアップロードされ、元動画が削除される
- [ ] ブラウザで公開プロフィールページから動画再生確認

### swap・メモリ監視
- [ ] CloudWatch Metrics で swap 使用率の時系列確認
- [ ] EC2 CPU 使用率 < 50% で安定
- [ ] OOM Killer が発動していない（`dmesg | grep -i oom`）

### ロールバック検証
- [ ] 検証環境（または時間外）で旧構成への切戻しを実演
- [ ] 5 分以内に旧 Laravel が応答する状態に戻る

---

## 課題確認事項

- **EC2 メトリクス監視**: CloudWatch Agent を完全停止するなら、メモリ・swap 監視は EC2 標準メトリクスにない → CloudWatch Agent を「メトリクスのみモード」で残すか、停止するか判断
- **ログ保持コスト**: retention 7 日で月 $0.50 想定。エラー調査で長期保持が必要ならフィルター対象を絞って `error.log` だけ 30 日保持等
- **本番切替タイミング**: ユーザートラフィックが少ない時間帯（深夜）に実施

---

## 参考資料

- 移行プラン: `docs/plans/ecs_ecr_migration_phase1.md`
- アーキテクチャ設計書: `docs/design-docs/02_architecture.md`（更新済み）
- データフロー設計書: `docs/design-docs/05_data_flow.md`（更新済み）
- Issue #22（置換対象）: `docs/issues/22-cloudwatch-monitoring.md`
- ECS awslogs ドライバ: https://docs.aws.amazon.com/AmazonECS/latest/developerguide/using_awslogs.html
