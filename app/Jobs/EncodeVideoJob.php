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

/**
 * 動画エンコードジョブ
 *
 * Laravel Queue 経由で非同期実行される。
 * Phase 7 以降、VideoController::store() からは encodeWithRetry の同期呼び出しを廃止し、
 * 本ジョブを dispatch する。Worker タスク（`php artisan queue:work`）が拾って実行する。
 *
 * リトライは Laravel Queue 標準の $tries=3 と failed_jobs テーブルに任せる。
 */
class EncodeVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 最大試行回数
     */
    public int $tries = 3;

    /**
     * ジョブの最大実行時間（秒）
     * ffmpeg 5分タイムアウト + S3 操作の余裕で 900 秒に設定
     */
    public int $timeout = 900;

    /**
     * コンストラクタ
     */
    public function __construct(public Video $video)
    {
    }

    /**
     * ジョブ実行
     */
    public function handle(VideoEncoderService $encoder): void
    {
        Log::info(sprintf(
            'EncodeVideoJob 開始: video_id=%d, attempt=%d/%d',
            $this->video->id,
            $this->attempts(),
            $this->tries
        ));

        // 既存の retry_count カラムとの互換維持（statuses API 経由で表示中）
        $this->video->update(['retry_count' => $this->attempts() - 1]);

        $encoder->encode($this->video);

        Log::info("EncodeVideoJob 完了: video_id={$this->video->id}");
    }

    /**
     * 全リトライ失敗後に呼ばれる
     *
     * @param  Throwable  $e
     */
    public function failed(Throwable $e): void
    {
        Log::error(sprintf(
            'EncodeVideoJob 最終失敗: video_id=%d, error=%s',
            $this->video->id,
            $e->getMessage()
        ));

        $this->video->update([
            'status' => 'failed',
            'error_message' => mb_substr($e->getMessage(), 0, 1000),
        ]);
    }
}
