<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaceControllerTest extends TestCase
{
    use RefreshDatabase;

    private function onboardedUser(string $role = 'proprietaire'): User
    {
        $user = User::factory()->create(['role' => $role, 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $user->id]);
        $user->update(['favorite_site_id' => $site->id]);
        return $user;
    }

    // ─── Index (public) ──────────────────────────────────

    public function test_guest_can_see_home_page(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_home_page_shows_active_places(): void
    {
        $owner = $this->onboardedUser('proprietaire');
        $site = Site::factory()->create(['user_id' => $owner->id]);
        Place::factory()->create([
            'user_id' => $owner->id,
            'site_id' => $site->id,
            'is_active' => true,
            'nom' => 'Place Visible',
        ]);

        $response = $this->get('/');
        $response->assertSee('Place Visible');
    }

    // ─── Mes Places ──────────────────────────────────────

    public function test_guest_cannot_access_mes_places(): void
    {
        $response = $this->get('/places');
        $response->assertRedirect('/login');
    }

    public function test_owner_can_see_mes_places(): void
    {
        $owner = $this->onboardedUser('proprietaire');

        $response = $this->actingAs($owner)->get('/places');
        $response->assertStatus(200);
    }

    // ─── Create ──────────────────────────────────────────

    public function test_owner_can_see_create_form(): void
    {
        $owner = $this->onboardedUser('proprietaire');

        $response = $this->actingAs($owner)->get('/places/create');
        $response->assertStatus(200);
    }

    public function test_owner_can_create_place(): void
    {
        $owner = $this->onboardedUser('proprietaire');
        $site = Site::first();

        $response = $this->actingAs($owner)->post('/places', [
            'nom' => 'Nouvelle Place',
            'site_id' => $site->id,
            'hourly_price' => 5.00,
            'caracteristiques' => 'Une belle place',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('places', ['nom' => 'Nouvelle Place']);
    }

    // ─── Edit ────────────────────────────────────────────

    public function test_owner_can_edit_own_place(): void
    {
        $owner = $this->onboardedUser('proprietaire');
        $site = Site::factory()->create(['user_id' => $owner->id]);
        $place = Place::factory()->create(['user_id' => $owner->id, 'site_id' => $site->id]);

        $response = $this->actingAs($owner)->get("/places/{$place->id}/edit");
        $response->assertStatus(200);
    }

    public function test_owner_can_update_place(): void
    {
        $owner = $this->onboardedUser('proprietaire');
        $site = Site::factory()->create(['user_id' => $owner->id]);
        $place = Place::factory()->create(['user_id' => $owner->id, 'site_id' => $site->id]);

        $response = $this->actingAs($owner)->put("/places/{$place->id}", [
            'nom' => 'Place Modifiée',
            'hourly_price' => 3.00,
            'caracteristiques' => 'Mise à jour',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('places', ['nom' => 'Place Modifiée']);
    }

    // ─── Non-onboarded redirect ──────────────────────────

    public function test_non_onboarded_user_is_redirected(): void
    {
        $user = User::factory()->create(['role' => 'locataire', 'onboarded' => false]);

        $response = $this->actingAs($user)->get('/places');
        $response->assertRedirect(route('onboarding.index'));
    }
}
