<?php

namespace Tests\Unit\Jobs;

use App\Jobs\EncodeVideoJob;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * EncodeVideoJob の単体テスト
 *
 * Phase 7 で導入。VideoController::store() からの dispatch 確認と、
 * 失敗時の failed() コールバックで videos.status / error_message が正しく更新されることを担保する。
 */
class EncodeVideoJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function job_can_be_dispatched_with_video(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $video = Video::factory()->encoding()->create(['user_id' => $user->id]);

        EncodeVideoJob::dispatch($video);

        Queue::assertPushed(EncodeVideoJob::class, function (EncodeVideoJob $job) use ($video) {
            return $job->video->id === $video->id;
        });
    }

    #[Test]
    public function failed_callback_updates_video_status_and_error_message(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->encoding()->create(['user_id' => $user->id]);

        $job = new EncodeVideoJob($video);
        $job->failed(new \Exception('FFmpeg 実行エラー（テスト）'));

        $video->refresh();
        $this->assertEquals('failed', $video->status);
        $this->assertEquals('FFmpeg 実行エラー（テスト）', $video->error_message);
    }

    #[Test]
    public function failed_callback_truncates_long_error_message(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->encoding()->create(['user_id' => $user->id]);

        $longMessage = str_repeat('あ', 2000);  // 2000 文字
        $job = new EncodeVideoJob($video);
        $job->failed(new \Exception($longMessage));

        $video->refresh();
        $this->assertLessThanOrEqual(1000, mb_strlen($video->error_message));
    }

    #[Test]
    public function job_has_correct_tries_and_timeout_settings(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->encoding()->create(['user_id' => $user->id]);

        $job = new EncodeVideoJob($video);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(900, $job->timeout);
    }
}
