<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Video;
use App\Services\VideoEncoderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

class VideoEncoderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    #[Test]
    public function encode_with_retry_increments_retry_count_on_failure(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->encoding()->create([
            'user_id' => $user->id,
            'original_path' => 'users/1/original/test.mp4',
        ]);

        // S3にダミーファイルを配置（存在しないとエラーになる）
        Storage::disk('s3')->put($video->original_path, 'dummy content');

        // エンコードを試行（FFmpegがないため失敗する）
        $service = new VideoEncoderService();

        // 最大リトライまで実行してfailedになることを確認
        $result = $service->encodeWithRetry($video);

        $video->refresh();
        $this->assertFalse($result);
        $this->assertEquals('failed', $video->status);
        $this->assertEquals(3, $video->retry_count);
    }

    #[Test]
    public function video_status_changes_to_failed_after_max_retries(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->encoding()->create([
            'user_id' => $user->id,
            'original_path' => 'users/1/original/test.mp4',
            'retry_count' => 0,
        ]);

        Storage::disk('s3')->put($video->original_path, 'dummy content');

        $service = new VideoEncoderService();
        $service->encodeWithRetry($video);

        $video->refresh();
        $this->assertEquals('failed', $video->status);
    }

    #[Test]
    public function error_message_is_saved_on_failure(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->encoding()->create([
            'user_id' => $user->id,
            'original_path' => 'users/1/original/test.mp4',
        ]);

        Storage::disk('s3')->put($video->original_path, 'dummy content');

        $service = new VideoEncoderService();
        $service->encodeWithRetry($video);

        $video->refresh();
        $this->assertNotNull($video->error_message);
    }
}
