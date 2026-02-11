<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteControllerTest extends TestCase
{
    use RefreshDatabase;

    private function onboardedUser(string $role = 'proprietaire'): User
    {
        $user = User::factory()->create(['role' => $role, 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $user->id]);
        $user->update(['favorite_site_id' => $site->id]);
        return $user;
    }

    public function test_guest_cannot_access_sites(): void
    {
        $response = $this->get('/sites');
        $response->assertRedirect('/login');
    }

    public function test_user_can_see_sites_index(): void
    {
        $user = $this->onboardedUser();
        $response = $this->actingAs($user)->get('/sites');
        $response->assertStatus(200);
    }

    public function test_user_can_see_create_form(): void
    {
        $user = $this->onboardedUser();
        $response = $this->actingAs($user)->get('/sites/create');
        $response->assertStatus(200);
    }

    public function test_user_can_create_site(): void
    {
        $user = $this->onboardedUser();

        $response = $this->actingAs($user)->post('/sites', [
            'nom' => 'Nouveau Site',
            'adresse' => 'Rue Test 1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sites', [
            'nom' => 'Nouveau Site',
            'user_id' => $user->id,
        ]);
    }

    public function test_create_site_requires_name(): void
    {
        $user = $this->onboardedUser();

        $response = $this->actingAs($user)->post('/sites', [
            'adresse' => 'Rue Test 1',
        ]);

        $response->assertSessionHasErrors('nom');
    }
}
