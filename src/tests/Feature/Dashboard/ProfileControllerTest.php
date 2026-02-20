<?php

namespace Tests\Feature\Dashboard;

use App\Models\Profile;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function profile_edit_page_can_be_displayed(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/dashboard/profile/edit');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.profile.edit');
    }

    /** @test */
    public function profile_edit_page_redirects_guests_to_login(): void
    {
        $response = $this->get('/dashboard/profile/edit');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function profile_can_be_updated(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put('/dashboard/profile', [
            'name' => '更新後の名前',
            'biography' => '更新後の自己紹介',
            'theme_color' => '#667eea',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'name' => '更新後の名前',
            'biography' => '更新後の自己紹介',
            'theme_color' => '#667eea',
        ]);
    }

    /** @test */
    public function profile_update_fails_without_name(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put('/dashboard/profile', [
            'name' => '',
            'biography' => '自己紹介',
        ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function profile_update_fails_with_too_long_name(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put('/dashboard/profile', [
            'name' => str_repeat('あ', 51),
            'biography' => '自己紹介',
        ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function profile_update_fails_with_too_long_biography(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put('/dashboard/profile', [
            'name' => 'テスト',
            'biography' => str_repeat('あ', 1001),
        ]);

        $response->assertSessionHasErrors('biography');
    }

    /** @test */
    public function profile_can_be_updated_with_thumbnail_video(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $video = Video::factory()->completed()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put('/dashboard/profile', [
            'name' => 'テスト',
            'biography' => '自己紹介',
            'thumbnail_video_id' => $video->id,
            'theme_color' => '#667eea',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'thumbnail_video_id' => $video->id,
        ]);
    }

    /** @test */
    public function profile_can_be_updated_with_theme_color(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put('/dashboard/profile', [
            'name' => 'テスト',
            'theme_color' => '#ff5733',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'theme_color' => '#ff5733',
        ]);
    }

    /** @test */
    public function profile_update_fails_without_theme_color(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put('/dashboard/profile', [
            'name' => 'テスト',
        ]);

        $response->assertSessionHasErrors('theme_color');
    }

    /** @test */
    public function profile_update_fails_with_invalid_theme_color(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put('/dashboard/profile', [
            'name' => 'テスト',
            'theme_color' => 'not-a-color',
        ]);

        $response->assertSessionHasErrors('theme_color');
    }

    /** @test */
    public function profile_edit_shows_completed_videos(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        Video::factory()->completed()->create(['user_id' => $user->id]);
        Video::factory()->encoding()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/dashboard/profile/edit');

        $response->assertStatus(200);
        $response->assertViewHas('completedVideos');
        $completedVideos = $response->viewData('completedVideos');
        $this->assertCount(1, $completedVideos);
    }
}
