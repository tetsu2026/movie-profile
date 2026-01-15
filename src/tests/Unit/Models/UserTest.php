<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_has_one_profile(): void
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(Profile::class, $user->profile);
        $this->assertEquals($profile->id, $user->profile->id);
    }

    /** @test */
    public function user_has_many_videos(): void
    {
        $user = User::factory()->create();
        Video::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->videos);
        $this->assertInstanceOf(Video::class, $user->videos->first());
    }

    /** @test */
    public function user_can_be_soft_deleted(): void
    {
        $user = User::factory()->create();

        $user->delete();

        $this->assertSoftDeleted($user);
        $this->assertNull(User::find($user->id));
        $this->assertNotNull(User::withTrashed()->find($user->id));
    }

    /** @test */
    public function user_has_default_role_as_user(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('user', $user->role);
    }

    /** @test */
    public function user_can_be_admin(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertEquals('admin', $user->role);
    }

    /** @test */
    public function user_fillable_attributes_work(): void
    {
        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'role' => 'admin',
        ]);

        $this->assertEquals('テストユーザー', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('admin', $user->role);
    }
}
