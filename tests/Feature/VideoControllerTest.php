<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    /** @test */
    public function video_upload_page_can_be_displayed(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/dashboard/videos/upload');

        $response->assertStatus(200);
        $response->assertViewIs('videos.create');
    }

    /** @test */
    public function video_upload_page_redirects_guests_to_login(): void
    {
        $response = $this->get('/dashboard/videos/upload');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function video_index_page_can_be_displayed(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/dashboard/videos');

        $response->assertStatus(200);
        $response->assertViewIs('videos.index');
    }

    /** @test */
    public function video_index_shows_user_videos(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        Video::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/dashboard/videos');

        $response->assertStatus(200);
        $response->assertViewHas('videos');
        $this->assertCount(3, $response->viewData('videos'));
    }

    /** @test */
    public function video_index_does_not_show_other_users_videos(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        Profile::factory()->create(['user_id' => $otherUser->id]);
        Video::factory()->count(2)->create(['user_id' => $user->id]);
        Video::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get('/dashboard/videos');

        $this->assertCount(2, $response->viewData('videos'));
    }

    /** @test */
    public function video_can_be_deleted(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $video = Video::factory()->completed()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/dashboard/videos/{$video->id}");

        $response->assertRedirect('/dashboard/videos');
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('videos', ['id' => $video->id]);
    }

    /** @test */
    public function video_deletion_is_forbidden_for_other_users_videos(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        Profile::factory()->create(['user_id' => $otherUser->id]);
        $video = Video::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->delete("/dashboard/videos/{$video->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function video_in_use_by_profile_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->completed()->create(['user_id' => $user->id]);
        Profile::factory()->create([
            'user_id' => $user->id,
            'thumbnail_video_id' => $video->id,
        ]);

        $response = $this->actingAs($user)->delete("/dashboard/videos/{$video->id}");

        $response->assertRedirect('/dashboard/videos');
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('videos', ['id' => $video->id]);
    }

    /** @test */
    public function video_upload_requires_video_file(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post('/dashboard/videos', [
            'video' => null,
        ]);

        $response->assertSessionHasErrors('video');
    }

    /** @test */
    public function video_upload_rejects_invalid_mime_type(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $file = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

        $response = $this->actingAs($user)->post('/dashboard/videos', [
            'video' => $file,
        ]);

        $response->assertSessionHasErrors('video');
    }

    /** @test */
    public function video_upload_rejects_oversized_files(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        // 110MB（制限超過）
        $file = UploadedFile::fake()->create('test.mp4', 112640, 'video/mp4');

        $response = $this->actingAs($user)->post('/dashboard/videos', [
            'video' => $file,
        ]);

        $response->assertSessionHasErrors('video');
    }
}
