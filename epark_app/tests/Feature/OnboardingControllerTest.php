<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_onboarding(): void
    {
        $response = $this->get('/onboarding');
        $response->assertRedirect('/login');
    }

    public function test_non_onboarded_user_sees_onboarding(): void
    {
        $user = User::factory()->create([
            'role' => 'locataire',
            'onboarded' => false,
            'favorite_site_id' => null,
        ]);

        $response = $this->actingAs($user)->get('/onboarding');
        $response->assertStatus(200);
    }

    public function test_onboarded_user_can_still_access_onboarding(): void
    {
        // Le contrôleur n'a pas de guard pour les utilisateurs déjà onboardés
        $user = User::factory()->create(['role' => 'locataire', 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $user->id]);
        $user->update(['favorite_site_id' => $site->id]);

        $response = $this->actingAs($user)->get('/onboarding');
        $response->assertStatus(200);
    }

    public function test_user_can_choose_existing_site(): void
    {
        $user = User::factory()->create([
            'role' => 'locataire',
            'onboarded' => false,
            'favorite_site_id' => null,
        ]);
        $site = Site::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post('/onboarding', [
            'action' => 'choose',
            'site_id' => $site->id,
        ]);

        $response->assertRedirect(route('dashboard'));
        $user->refresh();
        $this->assertEquals($site->id, $user->favorite_site_id);
        $this->assertTrue($user->onboarded);
    }

    public function test_non_onboarded_user_is_redirected_to_onboarding_from_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'locataire',
            'onboarded' => false,
            'favorite_site_id' => null,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('onboarding.index'));
    }
}
