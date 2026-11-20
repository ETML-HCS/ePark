<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
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

    public function test_tenant_can_see_group_place_with_saved_secret_code_in_create_form(): void
    {
        $groupPlace = Place::factory()->create([
            'user_id' => $this->owner->id,
            'site_id' => $this->place->site_id,
            'nom' => 'Place Groupe Auto Reservation',
            'is_group_reserved' => true,
            'group_name' => 'Equipe A',
            'group_access_code_hash' => Hash::make('CODE1234'),
        ]);

        $this->tenant->update([
            'secret_group_codes' => ['CODE1234'],
        ]);

        $response = $this->actingAs($this->tenant)->get('/reservations/create');
        $response->assertStatus(200);
        $response->assertSee($groupPlace->nom);
    }

    public function test_tenant_can_see_group_place_with_allowed_email_domain_in_create_form(): void
    {
        $tenantWithDomain = User::factory()->create([
            'role' => 'locataire',
            'onboarded' => true,
            'email' => 'membre@eduvaud.ch',
        ]);
        $tenantSite = Site::factory()->create(['user_id' => $tenantWithDomain->id]);
        $tenantWithDomain->update(['favorite_site_id' => $tenantSite->id]);

        $groupPlace = Place::factory()->create([
            'user_id' => $this->owner->id,
            'site_id' => $this->place->site_id,
            'nom' => 'Place Domaine Reservation',
            'is_group_reserved' => true,
            'group_name' => 'Groupe EduVaud',
            'group_access_code_hash' => Hash::make('CODE1234'),
            'group_allowed_email_domains' => ['eduvaud.ch'],
        ]);

        $response = $this->actingAs($tenantWithDomain)->get('/reservations/create');
        $response->assertStatus(200);
        $response->assertSee($groupPlace->nom);
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

    public function test_store_ignores_client_supplied_paiement_effectue_flag_for_paid_place(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->tenant)->post('/reservations', [
            'place_id' => $this->place->id,
            'date' => Carbon::tomorrow()->format('Y-m-d'),
            'segment' => 'matin_travail',
            'battement' => 5,
            'paiement_effectue' => 1,
        ]);

        $response->assertRedirect(route('reservations.index'));
        $this->assertDatabaseHas('reservations', [
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
            'statut' => 'en_attente',
            'payment_status' => 'pending',
            'paiement_effectue' => false,
        ]);
        $this->assertDatabaseHas('payments', [
            'provider' => 'manual',
            'provider_status' => 'pending',
        ]);
    }

    public function test_unverified_email_cannot_access_or_book_domain_restricted_group_place(): void
    {
        Notification::fake();

        $unverifiedTenant = User::factory()->unverified()->create([
            'role' => 'locataire',
            'onboarded' => true,
            'email' => 'fake@eduvaud.ch',
        ]);
        $tenantSite = Site::factory()->create(['user_id' => $unverifiedTenant->id]);
        $unverifiedTenant->update(['favorite_site_id' => $tenantSite->id]);

        $groupPlace = Place::factory()->create([
            'user_id' => $this->owner->id,
            'site_id' => $this->place->site_id,
            'nom' => 'Place Privée Domaine Vérifié',
            'is_group_reserved' => true,
            'group_name' => 'Groupe EduVaud',
            'group_access_code_hash' => Hash::make('CODE1234'),
            'group_allowed_email_domains' => ['eduvaud.ch'],
        ]);

        $this->actingAs($unverifiedTenant)->get('/')->assertOk()->assertDontSee($groupPlace->nom);
        $this->actingAs($unverifiedTenant)->get('/reservations/create')->assertOk()->assertDontSee($groupPlace->nom);

        $date = Carbon::tomorrow()->format('Y-m-d');
        $availability = $this->actingAs($unverifiedTenant)->getJson('/express/availability?date='.$date)->assertOk();
        $this->assertNull(collect($availability->json('places'))->firstWhere('id', $groupPlace->id));

        $this->actingAs($unverifiedTenant)->post('/reservations', [
            'place_id' => $groupPlace->id,
            'date' => $date,
            'segment' => 'matin_travail',
            'battement' => 5,
        ])->assertSessionHasErrors('place_id');

        $this->actingAs($unverifiedTenant)->postJson('/express/book', [
            'place_id' => $groupPlace->id,
            'date' => $date,
            'segment' => 'matin_travail',
        ])->assertForbidden();

        $this->assertDatabaseCount('reservations', 0);
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

    public function test_owner_sees_confirm_and_refuse_actions_on_reservation_show(): void
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
            'statut' => 'en_attente',
            'payment_status' => 'paid',
            'date_debut' => now()->addDay()->setTime(8, 0),
            'date_fin' => now()->addDay()->setTime(12, 0),
        ]);

        $this->actingAs($this->owner)
            ->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('Confirmer la réservation')
            ->assertSee('Refuser la demande');
    }

    public function test_in_app_notifications_are_stored_displayed_and_marked_read(): void
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
            'statut' => 'en_attente',
            'payment_status' => 'paid',
            'date_debut' => now()->addDay()->setTime(8, 0),
            'date_fin' => now()->addDay()->setTime(12, 0),
        ]);

        $this->actingAs($this->owner)->post(route('reservations.valider', $reservation), [
            'owner_message' => 'Accès par la barrière sud',
        ])->assertRedirect(route('dashboard'));

        $notification = $this->tenant->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('reservation_status_changed', $notification->data['type']);

        $this->actingAs($this->tenant)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Réservation confirmée')
            ->assertSee('Accès par la barrière sud');

        $this->actingAs($this->tenant)
            ->post(route('notifications.markRead', $notification->id))
            ->assertRedirect('/reservations/'.$reservation->id);

        $this->assertNotNull($notification->fresh()->read_at);
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
