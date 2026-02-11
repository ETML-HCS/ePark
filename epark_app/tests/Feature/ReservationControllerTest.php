<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use App\Notifications\PaymentStatusChanged;
use App\Notifications\ReservationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReservationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $tenant;
    private Place $place;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'proprietaire', 'onboarded' => true]);
        $ownerSite = Site::factory()->create(['user_id' => $this->owner->id]);
        $this->owner->update(['favorite_site_id' => $ownerSite->id]);

        $this->place = Place::factory()->create([
            'user_id' => $this->owner->id,
            'site_id' => $ownerSite->id,
            'hourly_price_cents' => 500,
        ]);

        $this->tenant = User::factory()->create(['role' => 'locataire', 'onboarded' => true]);
        $tenantSite = Site::factory()->create(['user_id' => $this->tenant->id]);
        $this->tenant->update(['favorite_site_id' => $tenantSite->id]);
    }

    // ─── Index ───────────────────────────────────────────

    public function test_guest_cannot_access_reservations(): void
    {
        $response = $this->get('/reservations');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_see_reservations(): void
    {
        $response = $this->actingAs($this->tenant)->get('/reservations');
        $response->assertStatus(200);
    }

    // ─── Create ──────────────────────────────────────────

    public function test_tenant_can_see_create_form(): void
    {
        $response = $this->actingAs($this->tenant)->get('/reservations/create');
        $response->assertStatus(200);
    }

    public function test_tenant_can_create_reservation(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->tenant)->post('/reservations', [
            'place_id' => $this->place->id,
            'date' => Carbon::tomorrow()->format('Y-m-d'),
            'segment' => 'matin_travail',
            'battement' => 5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reservations', [
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
        ]);
    }

    // ─── Show ────────────────────────────────────────────

    public function test_tenant_can_see_own_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
        ]);

        $response = $this->actingAs($this->tenant)->get("/reservations/{$reservation->id}");
        $response->assertStatus(200);
    }

    public function test_owner_can_see_reservation_on_their_place(): void
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
        ]);

        $response = $this->actingAs($this->owner)->get("/reservations/{$reservation->id}");
        $response->assertStatus(200);
    }

    // ─── Payer ───────────────────────────────────────────

    public function test_tenant_can_pay_reservation(): void
    {
        Notification::fake();

        $reservation = Reservation::factory()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($this->tenant)->post("/reservations/{$reservation->id}/payer");
        $response->assertRedirect();

        $reservation->refresh();
        $this->assertEquals('paid', $reservation->payment_status);
    }

    // ─── Valider ─────────────────────────────────────────

    public function test_owner_can_validate_paid_reservation(): void
    {
        Notification::fake();

        $reservation = Reservation::factory()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
            'payment_status' => 'paid',
            'statut' => 'en_attente',
        ]);

        $response = $this->actingAs($this->owner)->post("/reservations/{$reservation->id}/valider", [
            'owner_message' => 'Confirmé!',
        ]);
        $response->assertRedirect();

        $reservation->refresh();
        $this->assertEquals('confirmée', $reservation->statut);
    }

    // ─── Refuser ─────────────────────────────────────────

    public function test_owner_can_refuse_reservation(): void
    {
        Notification::fake();

        $reservation = Reservation::factory()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
            'statut' => 'en_attente',
        ]);

        $response = $this->actingAs($this->owner)->post("/reservations/{$reservation->id}/refuser", [
            'owner_message' => 'Pas disponible',
        ]);
        $response->assertRedirect();

        $reservation->refresh();
        $this->assertEquals('annulée', $reservation->statut);
    }

    // ─── Destroy ─────────────────────────────────────────

    public function test_tenant_can_cancel_own_reservation(): void
    {
        Notification::fake();

        $reservation = Reservation::factory()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
            'statut' => 'en_attente',
        ]);

        $response = $this->actingAs($this->tenant)->delete("/reservations/{$reservation->id}");
        $response->assertRedirect();

        $reservation->refresh();
        $this->assertEquals('annulée', $reservation->statut);
    }

    // ─── Accès non autorisé ──────────────────────────────

    public function test_other_user_cannot_see_foreign_reservation(): void
    {
        $otherUser = User::factory()->create(['role' => 'locataire', 'onboarded' => true]);
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);
        $otherUser->update(['favorite_site_id' => $otherSite->id]);

        $reservation = Reservation::factory()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
        ]);

        $response = $this->actingAs($otherUser)->get("/reservations/{$reservation->id}");
        $response->assertStatus(403);
    }
}
