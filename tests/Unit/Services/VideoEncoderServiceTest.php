<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Video;
use App\Services\VideoEncoderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

/**
 * VideoEncoderService の単体テスト
 *
 * Phase 7 以降、リトライ機能と error_message 保存・status 更新は Laravel Queue の責務に移行した。
 * VideoEncoderService は 1 回のエンコード試行に専念し、失敗時は例外を投げる。
 * リトライ・失敗時の status 更新は EncodeVideoJob のテストで担保する。
 */
class VideoEncoderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    #[Test]
    public function encode_throws_exception_on_ffmpeg_failure(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->encoding()->create([
            'user_id' => $user->id,
            'original_path' => 'users/1/original/test.mp4',
        ]);

        // S3 にダミーファイルを配置（FFmpeg が動画として認識できない内容なので失敗する）
        Storage::disk('s3')->put($video->original_path, 'dummy content');

        $service = new VideoEncoderService();

        // 不正な動画ファイル or FFmpeg バイナリ不在で例外が投げられるはず
        $this->expectException(\Exception::class);
        $service->encode($video);
    }
}
