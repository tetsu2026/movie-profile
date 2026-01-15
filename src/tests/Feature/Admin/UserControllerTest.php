<?php

namespace Tests\Feature\Admin;

use App\Models\Profile;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    /** @test */
    public function admin_user_index_page_can_be_displayed(): void
    {
        $admin = User::factory()->admin()->create();
        Profile::factory()->create(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.index');
    }

    /** @test */
    public function admin_user_index_page_is_forbidden_for_regular_users(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/admin/users');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_user_index_page_redirects_guests_to_login(): void
    {
        $response = $this->get('/admin/users');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function admin_user_edit_page_can_be_displayed(): void
    {
        $admin = User::factory()->admin()->create();
        Profile::factory()->create(['user_id' => $admin->id]);
        $targetUser = User::factory()->create();
        Profile::factory()->create(['user_id' => $targetUser->id]);

        $response = $this->actingAs($admin)->get("/admin/users/{$targetUser->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.edit');
    }

    /** @test */
    public function admin_user_edit_page_is_forbidden_for_regular_users(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $targetUser = User::factory()->create();
        Profile::factory()->create(['user_id' => $targetUser->id]);

        $response = $this->actingAs($user)->get("/admin/users/{$targetUser->id}/edit");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_user(): void
    {
        $admin = User::factory()->admin()->create();
        Profile::factory()->create(['user_id' => $admin->id]);
        $targetUser = User::factory()->create();
        Profile::factory()->create(['user_id' => $targetUser->id]);

        $response = $this->actingAs($admin)->put("/admin/users/{$targetUser->id}", [
            'name' => '更新された名前',
            'biography' => '更新された自己紹介',
            'role' => 'user',
        ]);

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('profiles', [
            'user_id' => $targetUser->id,
            'name' => '更新された名前',
            'biography' => '更新された自己紹介',
        ]);
    }

    /** @test */
    public function admin_can_change_user_role_to_admin(): void
    {
        $admin = User::factory()->admin()->create();
        Profile::factory()->create(['user_id' => $admin->id]);
        $targetUser = User::factory()->create(['role' => 'user']);
        Profile::factory()->create(['user_id' => $targetUser->id]);

        $response = $this->actingAs($admin)->put("/admin/users/{$targetUser->id}", [
            'name' => 'テスト',
            'biography' => null,
            'role' => 'admin',
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'role' => 'admin',
        ]);
    }

    /** @test */
    public function regular_user_cannot_update_other_users(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $targetUser = User::factory()->create();
        Profile::factory()->create(['user_id' => $targetUser->id]);

        $response = $this->actingAs($user)->put("/admin/users/{$targetUser->id}", [
            'name' => '悪意のある更新',
            'biography' => null,
            'role' => 'admin',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_user(): void
    {
        $admin = User::factory()->admin()->create();
        Profile::factory()->create(['user_id' => $admin->id]);
        $targetUser = User::factory()->create();
        Profile::factory()->create(['user_id' => $targetUser->id]);
        $video = Video::factory()->completed()->create(['user_id' => $targetUser->id]);

        $response = $this->actingAs($admin)->delete("/admin/users/{$targetUser->id}");

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('users', ['id' => $targetUser->id]);
    }

    /** @test */
    public function regular_user_cannot_delete_users(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $targetUser = User::factory()->create();
        Profile::factory()->create(['user_id' => $targetUser->id]);

        $response = $this->actingAs($user)->delete("/admin/users/{$targetUser->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $targetUser->id]);
    }

    /** @test */
    public function admin_user_index_shows_all_users(): void
    {
        $admin = User::factory()->admin()->create();
        Profile::factory()->create(['user_id' => $admin->id]);
        User::factory()->count(5)->create()->each(function ($user) {
            Profile::factory()->create(['user_id' => $user->id]);
        });

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertViewHas('users');
    }
}
