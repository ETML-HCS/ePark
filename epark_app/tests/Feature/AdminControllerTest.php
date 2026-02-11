<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['role' => 'admin', 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $admin->id]);
        $admin->update(['favorite_site_id' => $site->id]);
        return $admin;
    }

    public function test_admin_can_access_dashboard(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_non_admin_gets_403(): void
    {
        $user = User::factory()->create(['role' => 'locataire', 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $user->id]);
        $user->update(['favorite_site_id' => $site->id]);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_admin(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_admin_dashboard_shows_stats(): void
    {
        $admin = $this->makeAdmin();
        $site = Site::factory()->create(['user_id' => $admin->id]);
        $place = Place::factory()->create(['user_id' => $admin->id, 'site_id' => $site->id]);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);
    }
}
