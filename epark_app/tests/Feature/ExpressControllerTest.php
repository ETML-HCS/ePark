<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpressControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $subscriber;

    private Place $place;

    protected function setUp(): void
    {
        parent::setUp();

        $owner = User::factory()->create([
            'role' => 'proprietaire',
            'onboarded' => true,
        ]);

        $site = Site::factory()->create(['user_id' => $owner->id]);

        $this->place = Place::factory()->create([
            'user_id' => $owner->id,
            'site_id' => $site->id,
            'hourly_price_cents' => 300,
            'is_active' => true,
        ]);

        $this->subscriber = User::factory()->create([
            'role' => 'locataire',
            'onboarded' => true,
            'auto_pay_enabled' => true,
        ]);
    }

    public function test_guest_sees_express_login_page_when_no_key_provided(): void
    {
        $response = $this->get('/express');

        $response->assertStatus(200);
        $response->assertViewIs('express.login');
        $response->assertSee('Mon accès rapide');
    }

    public function test_guest_is_auto_logged_in_via_query_param_key(): void
    {
        $token = $this->subscriber->getOrGenerateExpressToken();

        $response = $this->get("/express?key={$token}");

        $response->assertRedirect('/express');
        $this->assertAuthenticatedAs($this->subscriber);
    }

    public function test_guest_is_auto_logged_in_via_cookie_key(): void
    {
        $token = $this->subscriber->getOrGenerateExpressToken();

        $response = $this->withCookie('epark_express_key', $token)->get('/express');

        $response->assertRedirect('/express');
        $this->assertAuthenticatedAs($this->subscriber);
    }

    public function test_guest_can_login_via_post_key(): void
    {
        $token = $this->subscriber->getOrGenerateExpressToken();

        $response = $this->post('/express/login', [
            'express_key' => $token,
            'remember_device' => 1,
        ]);

        $response->assertRedirect('/express');
        $this->assertAuthenticatedAs($this->subscriber);
        $response->assertCookie('epark_express_key');
    }

    public function test_guest_cannot_login_with_invalid_key(): void
    {
        $response = $this->post('/express/login', [
            'express_key' => 'invalid-token-1234',
        ]);

        $response->assertSessionHasErrors('express_key');
        $this->assertGuest();
    }

    public function test_authenticated_user_sees_express_cockpit(): void
    {
        $response = $this->actingAs($this->subscriber)->get('/express');

        $response->assertStatus(200);
        $response->assertViewIs('express.index');
        $response->assertSee('Réserver une place');
        $response->assertSee('Demander cette place');
    }

    public function test_one_click_booking_never_simulates_payment_or_owner_confirmation(): void
    {
        $date = now()->addDays(2)->format('Y-m-d');

        $response = $this->actingAs($this->subscriber)
            ->postJson('/express/book', [
                'place_id' => $this->place->id,
                'date' => $date,
                'segment' => 'matin_travail',
                'auto_pay' => 1,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'statut' => 'en_attente',
            'paid' => false,
        ]);

        $this->assertDatabaseHas('reservations', [
            'user_id' => $this->subscriber->id,
            'place_id' => $this->place->id,
            'statut' => 'en_attente',
            'payment_status' => 'pending',
            'paiement_effectue' => false,
            'amount_cents' => 1200,
        ]);

        $this->assertDatabaseHas('payments', [
            'provider' => 'manual',
            'provider_status' => 'pending',
        ]);
    }

    public function test_user_can_toggle_auto_pay(): void
    {
        $this->assertTrue((bool) $this->subscriber->auto_pay_enabled);

        $response = $this->actingAs($this->subscriber)->postJson('/express/toggle-autopay', [
            'enabled' => 0,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'auto_pay_enabled' => false]);
        $this->assertFalse((bool) $this->subscriber->fresh()->auto_pay_enabled);
    }

    public function test_user_can_fetch_availability_json(): void
    {
        $date = now()->addDay()->format('Y-m-d');

        $response = $this->actingAs($this->subscriber)
            ->getJson("/express/availability?date={$date}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'date' => $date,
        ]);
        $response->assertJsonStructure([
            'success',
            'date',
            'places',
            'placeHours',
        ]);
    }

    public function test_user_can_regenerate_express_key(): void
    {
        $oldToken = $this->subscriber->getOrGenerateExpressToken();

        $response = $this->actingAs($this->subscriber)->post('/express/regenerate-key');

        $response->assertRedirect();
        $newToken = $this->subscriber->fresh()->express_token;

        $this->assertNotEmpty($newToken);
        $this->assertNotEquals($oldToken, $newToken);
    }

    public function test_user_can_update_quick_vehicle_plate(): void
    {
        $response = $this->actingAs($this->subscriber)
            ->postJson('/express/update-plate', [
                'plate' => 'vd 998877',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'plate' => 'VD 998877',
        ]);

        $this->assertEquals('VD 998877', $this->subscriber->fresh()->quick_vehicle_plate);
    }
}
