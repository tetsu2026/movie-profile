<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreviewTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function preview_page_can_be_displayed_for_authenticated_users(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/dashboard/preview');

        $response->assertStatus(200);
        $response->assertViewIs('preview');
    }

    /** @test */
    public function preview_page_redirects_guests_to_login(): void
    {
        $response = $this->get('/dashboard/preview');

        $response->assertRedirect('/login');
    }
}
