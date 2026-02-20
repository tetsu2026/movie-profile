<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PublicProfileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function public_profile_page_can_be_displayed(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'name' => '山田太郎',
            'biography' => '自己紹介文です',
            'is_public' => true,
        ]);

        $response = $this->get("/users/{$user->id}");

        $response->assertStatus(200);
        $response->assertViewIs('users.show');
        $response->assertSee('山田太郎');
        $response->assertSee('自己紹介文です');
    }

    #[Test]
    public function public_profile_page_returns_404_for_nonexistent_user(): void
    {
        $response = $this->get('/users/99999');

        $response->assertStatus(404);
    }

    #[Test]
    public function public_profile_page_displays_thumbnail_video(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->completed()->create([
            'user_id' => $user->id,
        ]);
        Profile::factory()->create([
            'user_id' => $user->id,
            'thumbnail_video_id' => $video->id,
            'is_public' => true,
        ]);

        $response = $this->get("/users/{$user->id}");

        $response->assertStatus(200);
        $response->assertViewHas('profile');
    }
}
