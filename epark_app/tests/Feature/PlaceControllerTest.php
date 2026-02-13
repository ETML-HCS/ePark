<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_group_reserved_place_is_hidden_without_code(): void
    {
        $owner = $this->onboardedUser('proprietaire');
        $site = Site::factory()->create(['user_id' => $owner->id]);

        Place::factory()->create([
            'user_id' => $owner->id,
            'site_id' => $site->id,
            'is_active' => true,
            'nom' => 'Place Groupe Privée',
            'is_group_reserved' => true,
            'group_name' => 'Equipe A',
            'group_access_code_hash' => Hash::make('CODE1234'),
        ]);

        $response = $this->get('/');
        $response->assertDontSee('Place Groupe Privée');
    }

    public function test_group_reserved_place_is_visible_with_valid_code(): void
    {
        $owner = $this->onboardedUser('proprietaire');
        $site = Site::factory()->create(['user_id' => $owner->id]);

        Place::factory()->create([
            'user_id' => $owner->id,
            'site_id' => $site->id,
            'is_active' => true,
            'nom' => 'Place Groupe Visible',
            'is_group_reserved' => true,
            'group_name' => 'Equipe A',
            'group_access_code_hash' => Hash::make('CODE1234'),
        ]);

        $response = $this->get('/?group_code=CODE1234');
        $response->assertSee('Place Groupe Visible');
    }

    public function test_authenticated_user_sees_group_place_with_saved_secret_code(): void
    {
        $owner = $this->onboardedUser('proprietaire');
        $site = Site::factory()->create(['user_id' => $owner->id]);

        Place::factory()->create([
            'user_id' => $owner->id,
            'site_id' => $site->id,
            'is_active' => true,
            'nom' => 'Place Groupe Auto',
            'is_group_reserved' => true,
            'group_name' => 'Equipe A',
            'group_access_code_hash' => Hash::make('CODE1234'),
        ]);

        $viewer = $this->onboardedUser('locataire');
        $viewer->update([
            'secret_group_codes' => ['CODE1234'],
        ]);

        $response = $this->actingAs($viewer)->get('/');
        $response->assertSee('Place Groupe Auto');
    }

    public function test_authenticated_user_sees_group_place_with_allowed_email_domain(): void
    {
        $owner = $this->onboardedUser('proprietaire');
        $site = Site::factory()->create(['user_id' => $owner->id]);

        Place::factory()->create([
            'user_id' => $owner->id,
            'site_id' => $site->id,
            'is_active' => true,
            'nom' => 'Place Domaine EduVaud',
            'is_group_reserved' => true,
            'group_name' => 'Groupe EduVaud',
            'group_access_code_hash' => Hash::make('CODE1234'),
            'group_allowed_email_domains' => ['eduvaud.ch'],
        ]);

        $viewer = User::factory()->create([
            'role' => 'locataire',
            'onboarded' => true,
            'email' => 'user@eduvaud.ch',
        ]);
        $viewerSite = Site::factory()->create(['user_id' => $viewer->id]);
        $viewer->update(['favorite_site_id' => $viewerSite->id]);

        $response = $this->actingAs($viewer)->get('/');
        $response->assertSee('Place Domaine EduVaud');
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

    public function test_owner_can_create_group_place_from_saved_group(): void
    {
        $owner = $this->onboardedUser('proprietaire');
        $site = Site::first();

        $owner->update([
            'secret_group_codes' => [
                ['name' => 'ETML/CFPV', 'code' => 'etml3865'],
            ],
        ]);

        $response = $this->actingAs($owner)->post('/places', [
            'nom' => 'Place Groupe 1',
            'site_id' => $site->id,
            'hourly_price' => 5.00,
            'caracteristiques' => 'Place de groupe',
            'cancel_deadline_hours' => 12,
            'is_group_reserved' => 1,
            'group_source' => 'existing',
            'secret_group_index' => 0,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('places', [
            'nom' => 'Place Groupe 1',
            'is_group_reserved' => 1,
            'group_name' => 'ETML/CFPV',
        ]);
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
