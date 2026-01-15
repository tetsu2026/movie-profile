<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VideoTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function video_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $video->user);
        $this->assertEquals($user->id, $video->user->id);
    }

    /** @test */
    public function video_can_be_soft_deleted(): void
    {
        $video = Video::factory()->create();

        $video->delete();

        $this->assertSoftDeleted($video);
        $this->assertNull(Video::find($video->id));
        $this->assertNotNull(Video::withTrashed()->find($video->id));
    }

    /** @test */
    public function video_has_default_status_uploading(): void
    {
        $video = Video::factory()->create();

        $this->assertEquals('uploading', $video->status);
    }

    /** @test */
    public function video_can_be_encoding(): void
    {
        $video = Video::factory()->encoding()->create();

        $this->assertEquals('encoding', $video->status);
        $this->assertNotNull($video->original_path);
    }

    /** @test */
    public function video_can_be_completed(): void
    {
        $video = Video::factory()->completed()->create();

        $this->assertEquals('completed', $video->status);
        $this->assertNotNull($video->encoded_path);
    }

    /** @test */
    public function video_can_be_failed(): void
    {
        $video = Video::factory()->failed()->create();

        $this->assertEquals('failed', $video->status);
        $this->assertEquals(3, $video->retry_count);
        $this->assertNotNull($video->error_message);
    }

    /** @test */
    public function video_fillable_attributes_work(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create([
            'user_id' => $user->id,
            'original_filename' => 'test.mp4',
            'duration' => 30,
            'file_size' => 10000000,
            'status' => 'completed',
        ]);

        $this->assertEquals('test.mp4', $video->original_filename);
        $this->assertEquals(30, $video->duration);
        $this->assertEquals(10000000, $video->file_size);
        $this->assertEquals('completed', $video->status);
    }

    /** @test */
    public function video_casts_duration_to_integer(): void
    {
        $video = Video::factory()->create(['duration' => '45']);

        $this->assertIsInt($video->duration);
        $this->assertEquals(45, $video->duration);
    }

    /** @test */
    public function video_casts_file_size_to_integer(): void
    {
        $video = Video::factory()->create(['file_size' => '5000000']);

        $this->assertIsInt($video->file_size);
        $this->assertEquals(5000000, $video->file_size);
    }
}
