<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function profile_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $profile->user);
        $this->assertEquals($user->id, $profile->user->id);
    }

    /** @test */
    public function profile_belongs_to_thumbnail_video(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->completed()->create(['user_id' => $user->id]);
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'thumbnail_video_id' => $video->id,
        ]);

        $this->assertInstanceOf(Video::class, $profile->thumbnailVideo);
        $this->assertEquals($video->id, $profile->thumbnailVideo->id);
    }

    /** @test */
    public function profile_can_be_soft_deleted(): void
    {
        $profile = Profile::factory()->create();

        $profile->delete();

        $this->assertSoftDeleted($profile);
        $this->assertNull(Profile::find($profile->id));
        $this->assertNotNull(Profile::withTrashed()->find($profile->id));
    }

    /** @test */
    public function profile_fillable_attributes_work(): void
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'name' => 'テスト名前',
            'biography' => 'テスト経歴',
            'is_public' => false,
        ]);

        $this->assertEquals('テスト名前', $profile->name);
        $this->assertEquals('テスト経歴', $profile->biography);
        $this->assertFalse($profile->is_public);
    }

    /** @test */
    public function profile_casts_is_public_to_boolean(): void
    {
        $profile = Profile::factory()->create(['is_public' => 1]);

        $this->assertTrue($profile->is_public);
        $this->assertIsBool($profile->is_public);
    }

    /** @test */
    public function profile_casts_video_order_to_array(): void
    {
        $profile = Profile::factory()->create(['video_order' => [1, 2, 3]]);

        $this->assertIsArray($profile->video_order);
        $this->assertEquals([1, 2, 3], $profile->video_order);
    }
}
