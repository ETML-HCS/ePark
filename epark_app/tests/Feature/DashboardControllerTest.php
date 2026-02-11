<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private function onboardedUser(string $role = 'locataire'): User
    {
        $user = User::factory()->create(['role' => $role, 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $user->id]);
        $user->update(['favorite_site_id' => $site->id]);
        return $user;
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_tenant_can_access_dashboard(): void
    {
        $user = $this->onboardedUser('locataire');

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_owner_can_access_dashboard(): void
    {
        $user = $this->onboardedUser('proprietaire');

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_dashboard(): void
    {
        $user = $this->onboardedUser('admin');

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_les_deux_can_access_dashboard(): void
    {
        $user = $this->onboardedUser('les deux');

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }
}
