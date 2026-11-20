<?php

namespace Tests\Feature;

use App\Models\Place;
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

    public function test_user_cannot_create_duplicate_site_name_for_themselves(): void
    {
        $user = $this->onboardedUser();
        Site::factory()->create([
            'user_id' => $user->id,
            'nom' => 'Parking Gare',
        ]);

        $response = $this->actingAs($user)->post('/sites', [
            'nom' => 'Parking Gare',
            'adresse' => 'Autre Rue 2',
        ]);

        $response->assertSessionHasErrors('nom');

        // Un autre utilisateur peut utiliser le même nom de site
        $otherUser = $this->onboardedUser();
        $this->actingAs($otherUser)->post('/sites', [
            'nom' => 'Parking Gare',
            'adresse' => 'Autre Rue 3',
        ])->assertRedirect(route('sites.index'));
    }

    public function test_owner_can_edit_and_update_own_site(): void
    {
        $user = $this->onboardedUser();
        $site = Site::factory()->create(['user_id' => $user->id, 'nom' => 'Ancien Nom']);

        $this->actingAs($user)->get(route('sites.edit', $site))->assertOk()->assertSee('Ancien Nom');

        $this->actingAs($user)->put(route('sites.update', $site), [
            'nom' => 'Nouveau Nom',
            'adresse' => 'Nouvelle Adresse 10',
        ])->assertRedirect(route('sites.index'));

        $this->assertDatabaseHas('sites', [
            'id' => $site->id,
            'nom' => 'Nouveau Nom',
            'adresse' => 'Nouvelle Adresse 10',
        ]);
    }

    public function test_user_cannot_edit_update_or_delete_another_users_site(): void
    {
        $owner = $this->onboardedUser();
        $other = $this->onboardedUser();
        $site = Site::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)->get(route('sites.edit', $site))->assertForbidden();
        $this->actingAs($other)->put(route('sites.update', $site), [
            'nom' => 'Piraté',
            'adresse' => 'Rue X',
        ])->assertForbidden();
        $this->actingAs($other)->delete(route('sites.destroy', $site))->assertForbidden();

        $this->assertDatabaseHas('sites', ['id' => $site->id]);
    }

    public function test_owner_can_delete_empty_site_but_not_site_with_places(): void
    {
        $owner = $this->onboardedUser();
        $siteWithPlace = Site::factory()->create(['user_id' => $owner->id]);
        Place::factory()->create(['user_id' => $owner->id, 'site_id' => $siteWithPlace->id]);

        $this->actingAs($owner)->delete(route('sites.destroy', $siteWithPlace))
            ->assertSessionHasErrors('site');
        $this->assertDatabaseHas('sites', ['id' => $siteWithPlace->id]);

        $emptySite = Site::factory()->create(['user_id' => $owner->id]);
        $owner->update(['favorite_site_id' => $emptySite->id]);

        $this->actingAs($owner)->delete(route('sites.destroy', $emptySite))
            ->assertRedirect(route('sites.index'));
        $this->assertDatabaseMissing('sites', ['id' => $emptySite->id]);
        $this->assertNull($owner->fresh()->favorite_site_id);
    }
}
