<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\StoreVideoRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class StoreVideoRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function video_is_required(): void
    {
        $request = new StoreVideoRequest();
        $rules = $request->rules();

        $validator = Validator::make(['video' => null], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('video', $validator->errors()->toArray());
    }

    /** @test */
    public function video_must_be_file(): void
    {
        $request = new StoreVideoRequest();
        $rules = $request->rules();

        $validator = Validator::make(['video' => 'not a file'], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('video', $validator->errors()->toArray());
    }

    /** @test */
    public function video_must_be_mp4_mov_avi_or_wmv(): void
    {
        $request = new StoreVideoRequest();
        $rules = $request->rules();

        // php形式のファイルは拒否される
        $file = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');
        $validator = Validator::make(['video' => $file], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('video', $validator->errors()->toArray());
    }

    /** @test */
    public function video_mp4_format_passes(): void
    {
        $request = new StoreVideoRequest();
        $rules = $request->rules();

        $file = UploadedFile::fake()->create('test.mp4', 1000, 'video/mp4');
        $validator = Validator::make(['video' => $file], $rules);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function video_must_be_100mb_or_less(): void
    {
        $request = new StoreVideoRequest();
        $rules = $request->rules();

        // 110MB（100MBを超える）
        $file = UploadedFile::fake()->create('test.mp4', 112640, 'video/mp4');
        $validator = Validator::make(['video' => $file], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('video', $validator->errors()->toArray());
    }

    /** @test */
    public function video_with_100mb_passes(): void
    {
        $request = new StoreVideoRequest();
        $rules = $request->rules();

        // 100MB（制限ぎりぎり）
        $file = UploadedFile::fake()->create('test.mp4', 102400, 'video/mp4');
        $validator = Validator::make(['video' => $file], $rules);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function custom_error_messages_are_returned(): void
    {
        $request = new StoreVideoRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('video.required', $messages);
        $this->assertArrayHasKey('video.mimes', $messages);
        $this->assertArrayHasKey('video.max', $messages);
        $this->assertEquals('動画ファイルを選択してください', $messages['video.required']);
    }
}
