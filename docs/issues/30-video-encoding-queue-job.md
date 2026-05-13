# Issue #30: 動画エンコードの Queue Job 化

## 背景 / 目的

`VideoController::store()` で同期実行している `VideoEncoderService::encodeWithRetry()` を、Laravel Queue Job `EncodeVideoJob` に置換する。Web リクエストは即時応答し、Worker タスクで非同期エンコードを実行する。

Phase 7（ECS化）に向けた前提整理。同期処理のままだと ECS rolling deploy / ヘルスチェック / オートスケール時に処理が消える問題が発生するため、ジョブを DB の `jobs` テーブルに保持して耐障害性を確保する。

- **依存**: #17, #18
- **ラベル**: backend
- **関連プラン**: `docs/plans/ecs_ecr_migration_phase1.md`

---

## スコープ / 作業項目

### 1. EncodeVideoJob クラス作成
- `app/Jobs/EncodeVideoJob.php` を新規作成
- `ShouldQueue` インターフェース実装
- プロパティ: `$tries = 3`, `$timeout = 900`
- コンストラクタで `Video $video` を受け取り、`handle()` で `VideoEncoderService::encode()` を呼ぶ
- `failed(Throwable $e)` メソッドで `videos.status = failed` + `error_message` 保存

### 2. VideoController::store() の変更
- `encodeWithRetry()` 直呼びを削除
- `EncodeVideoJob::dispatch($video)` に置換
- レスポンスは即時 redirect（「アップロード完了、エンコード処理を開始しました」）

### 3. VideoEncoderService の整理
- `encodeWithRetry()` を削除（Laravel Queue が `attempts` でリトライ管理するため）
- `encode()` のみ残す
- 内部の独自リトライループ・`retry_count` インクリメントロジックを撤去

### 4. videos テーブルから `retry_count` カラム削除（任意）
- 既存の `retry_count` カラムは Laravel Queue の `attempts` と二重管理になるため削除推奨
- migration `drop_retry_count_from_videos_table` を作成
- 影響範囲: VideoEncoderService、VideoController、Video モデル、ビュー

### 5. failed_jobs テーブル
- Laravel 標準 migration `0001_01_01_000002_create_jobs_table.php` で生成
- 既に存在することを確認、なければ `php artisan queue:failed-table && php artisan migrate`

### 6. .env / config 確認
- `.env.example`: `QUEUE_CONNECTION=database` を確認（既に設定済み）
- `config/queue.php`: `connections.database` がデフォルト

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] `app/Jobs/EncodeVideoJob.php` が新規作成され、`ShouldQueue` 実装・`tries=3`・`timeout=900` が設定されている
- [ ] `VideoController::store()` が `EncodeVideoJob::dispatch($video)` を呼び出し、レスポンスを即返却する
- [ ] `php artisan queue:work` で Job が実行され、`videos.status` が `encoding → completed` に遷移する
- [ ] エンコード 3 回失敗時に `failed_jobs` テーブルに記録され、`videos.status = failed` になる
- [ ] 既存の `retry_count` カラム依存ロジックが整理され、Laravel Queue 標準の `attempts` に統一されている
- [ ] `.env.example` の `QUEUE_CONNECTION=database` が確認される

---

## テスト観点

### Queue Job 動作確認
- [ ] ローカルで動画アップロード → `/videos` にすぐ戻る（同期待ち無し）
- [ ] `jobs` テーブルに `EncodeVideoJob` のレコードが INSERT される
- [ ] `php artisan queue:work --once` でジョブが消費される
- [ ] エンコード完了後 `videos.status = completed`、`jobs` テーブルから削除
- [ ] `php artisan queue:listen` でも動作する

### リトライ・失敗ハンドリング
- [ ] わざと不正な動画をアップロードして 3 回失敗させる
- [ ] `failed_jobs` テーブルに記録される
- [ ] `videos.status = failed`、`error_message` に内容が入る
- [ ] `php artisan queue:retry all` で再実行できる

### 既存テスト
- [ ] `php artisan test` の VideoController テストが通る（Queue::fake() で fake する）
- [ ] VideoEncoderService の単体テストが通る

---

## 実装例

### app/Jobs/EncodeVideoJob.php
```php
<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\VideoEncoderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class EncodeVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 900;

    public function __construct(public Video $video) {}

    public function handle(VideoEncoderService $service): void
    {
        Log::info("EncodeVideoJob 開始: video_id={$this->video->id}, attempt={$this->attempts()}");
        $service->encode($this->video);
    }

    public function failed(Throwable $e): void
    {
        Log::error("EncodeVideoJob 最終失敗: video_id={$this->video->id}, error={$e->getMessage()}");
        $this->video->update([
            'status' => 'failed',
            'error_message' => mb_substr($e->getMessage(), 0, 1000),
        ]);
    }
}
```

### VideoController::store() 抜粋
```php
public function store(StoreVideoRequest $request): RedirectResponse
{
    // ... バリデーション・S3 アップロード・videos レコード作成（status: encoding）...

    EncodeVideoJob::dispatch($video);

    return redirect()->route('videos.index')
        ->with('success', 'アップロードが完了しました。エンコード処理を開始します。');
}
```

---

## 課題確認事項

- **`retry_count` カラム削除のタイミング**: 既存データへの影響を考えて、Phase 7 完了後の別 issue で実施するか、本 issue 内でやるか
- **ステータスポーリング**: フロントエンドからの定期ポーリング（`/videos`）で進捗を見せる既存実装はそのまま流用できるか
- **Worker タスクの起動**: ローカル開発時は `php artisan queue:work` を手動起動、本番は ECS タスクとして常駐起動（#32 で対応）

---

## 参考資料

- データフロー設計書: `docs/design-docs/05_data_flow.md`（更新済み）
- アーキテクチャ設計書: `docs/design-docs/02_architecture.md`（更新済み）
- 移行プラン: `docs/plans/ecs_ecr_migration_phase1.md`
- Laravel Queue 公式: https://laravel.com/docs/11.x/queues
