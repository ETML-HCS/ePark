<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Feedback;
use App\Models\Place;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProductFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $tenant;

    private Site $site;

    private Place $place;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->owner = User::factory()->create([
            'role' => 'proprietaire',
            'onboarded' => true,
            'twint_phone' => '+41 79 555 44 33',
            'iban' => 'CH93 0076 2011 6238 5295 7',
            'payment_instructions' => 'Indiquer la référence ePark.',
        ]);
        $this->site = Site::factory()->create([
            'user_id' => $this->owner->id,
            'nom' => 'Parking Lausanne Centre',
            'adresse' => 'Place Saint-François 1, 1003 Lausanne',
            'latitude' => 46.519653,
            'longitude' => 6.632273,
        ]);
        $this->owner->update(['favorite_site_id' => $this->site->id]);

        $this->place = Place::factory()->create([
            'user_id' => $this->owner->id,
            'site_id' => $this->site->id,
            'nom' => 'Place Centrale 1',
            'hourly_price_cents' => 250,
            'is_active' => true,
        ]);

        $this->tenant = User::factory()->create([
            'role' => 'locataire',
            'onboarded' => true,
            'quick_vehicle_plate' => 'VD 123456',
        ]);
    }

    public function test_profile_updates_twint_iban_and_vehicle_plate(): void
    {
        $this->actingAs($this->owner)->patch('/profile', [
            'name' => $this->owner->name,
            'email' => $this->owner->email,
            'quick_vehicle_plate' => 'ge 99887',
            'twint_phone' => '+41 78 111 22 33',
            'iban' => 'ch10 0023 000a 1098 2234 6',
            'payment_instructions' => 'TWINT préféré',
        ])->assertRedirect('/profile');

        $this->owner->refresh();
        $this->assertSame('GE 99887', $this->owner->quick_vehicle_plate);
        $this->assertSame('+41 78 111 22 33', $this->owner->twint_phone);
        $this->assertSame('CH10 0023 000A 1098 2234 6', $this->owner->iban);
        $this->assertSame('TWINT préféré', $this->owner->payment_instructions);
    }

    public function test_owner_payment_details_and_confirm_payment_received_flow(): void
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
            'statut' => 'en_attente',
            'payment_status' => 'pending',
            'amount_cents' => 1000,
            'date_debut' => now()->addDay()->setTime(8, 0),
            'date_fin' => now()->addDay()->setTime(12, 0),
        ]);

        // Le locataire voit les coordonnées TWINT / IBAN du propriétaire
        $this->actingAs($this->tenant)
            ->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('+41 79 555 44 33')
            ->assertSee('CH93 0076 2011 6238 5295 7')
            ->assertSee('Indiquer la référence ePark.');

        // Un locataire ne peut pas confirmer la réception du paiement
        $this->actingAs($this->tenant)
            ->post(route('reservations.confirm-payment', $reservation))
            ->assertForbidden();

        // Le propriétaire confirme la réception du règlement
        $this->actingAs($this->owner)
            ->post(route('reservations.confirm-payment', $reservation))
            ->assertRedirect();

        $reservation->refresh();
        $this->assertSame('paid', $reservation->payment_status);
        $this->assertNotNull($reservation->payment_received_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'confirm_payment',
            'entity_type' => 'reservation',
            'entity_id' => $reservation->id,
        ]);
    }

    public function test_full_day_segment_booking_persists_plate_and_exact_amount(): void
    {
        $date = now()->addDays(2)->toDateString();

        $response = $this->actingAs($this->tenant)->postJson('/express/book', [
            'place_id' => $this->place->id,
            'date' => $date,
            'segment' => 'journee',
            'vehicle_plate' => 'fr 445566',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        // 9.5 heures * 250 centimes/h = 2375 centimes
        $this->assertDatabaseHas('reservations', [
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
            'amount_cents' => 2375,
            'vehicle_plate' => 'FR 445566',
        ]);
        $this->assertSame('FR 445566', $this->tenant->fresh()->quick_vehicle_plate);
    }

    public function test_reservation_ics_export_and_authorization(): void
    {
        $reservation = Reservation::factory()->confirmed()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
            'vehicle_plate' => 'VD 123456',
            'date_debut' => now()->addDay()->setTime(8, 0),
            'date_fin' => now()->addDay()->setTime(17, 30),
        ]);

        $response = $this->actingAs($this->tenant)
            ->get(route('reservations.ics', $reservation));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $content = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $content);
        $this->assertStringContainsString('BEGIN:VEVENT', $content);
        $this->assertStringContainsString('BEGIN:VALARM', $content);
        $this->assertStringContainsString('Place Centrale 1', $content);

        $stranger = User::factory()->create(['onboarded' => true]);
        $this->actingAs($stranger)
            ->get(route('reservations.ics', $reservation))
            ->assertForbidden();
    }

    public function test_place_ratings_are_exposed_on_home_and_express_availability(): void
    {
        $pastRes1 = Reservation::factory()->confirmed()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
            'date_debut' => now()->subDays(3)->setTime(8, 0),
            'date_fin' => now()->subDays(3)->setTime(12, 0),
        ]);
        $pastRes2 = Reservation::factory()->confirmed()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
            'date_debut' => now()->subDays(2)->setTime(8, 0),
            'date_fin' => now()->subDays(2)->setTime(12, 0),
        ]);

        Feedback::create(['reservation_id' => $pastRes1->id, 'user_id' => $this->tenant->id, 'rating' => 5]);
        Feedback::create(['reservation_id' => $pastRes2->id, 'user_id' => $this->tenant->id, 'rating' => 4]);

        $this->get('/')
            ->assertOk()
            ->assertSee('4.5/5')
            ->assertSee('(2 avis)')
            ->assertSee('Carte des parkings');

        $availability = $this->actingAs($this->tenant)
            ->getJson('/express/availability?date='.now()->addDay()->toDateString())
            ->assertOk();

        $serialized = collect($availability->json('places'))->firstWhere('id', $this->place->id);
        $this->assertEquals(4.5, $serialized['rating_avg']);
        $this->assertSame(2, $serialized['rating_count']);
    }

    public function test_owner_can_register_overstay_and_calculate_penalty(): void
    {
        $reservation = Reservation::factory()->confirmed()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
            'date_debut' => now()->subHours(6),
            'date_fin' => now()->subHours(3),
            'battement_minutes' => 5,
        ]);

        // Un locataire ne peut pas déclarer un dépassement
        $this->actingAs($this->tenant)
            ->post(route('reservations.overstay', $reservation), [
                'actual_end_at' => now()->subHour()->format('Y-m-d\TH:i'),
            ])
            ->assertForbidden();

        // Le propriétaire enregistre un départ +2h après la fin prévue
        $actualEnd = $reservation->date_fin->copy()->addHours(2);
        $this->actingAs($this->owner)
            ->post(route('reservations.overstay', $reservation), [
                'actual_end_at' => $actualEnd->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();

        $reservation->refresh();
        $this->assertGreaterThan(60, $reservation->overstay_minutes);
        $this->assertSame(4000, $reservation->penalty_cents);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'overstay',
            'entity_type' => 'reservation',
            'entity_id' => $reservation->id,
        ]);
    }

    public function test_admin_stats_excludes_cancelled_from_occupancy_and_exports_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'onboarded' => true]);

        // Une réservation annulée ne doit pas compter dans les réservations/occupation par défaut
        Reservation::factory()->cancelled()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
            'amount_cents' => 2000,
            'date_debut' => now()->startOfYear()->addDays(10)->setTime(8, 0),
            'date_fin' => now()->startOfYear()->addDays(10)->setTime(12, 0),
        ]);

        // Une réservation confirmée avec un nom de locataire dangereux pour CSV
        $csvTenant = User::factory()->create(['name' => '=CMD("evil")', 'onboarded' => true]);
        Reservation::factory()->confirmed()->create([
            'user_id' => $csvTenant->id,
            'place_id' => $this->place->id,
            'payment_status' => 'paid',
            'amount_cents' => 1500,
            'date_debut' => now()->startOfYear()->addDays(12)->setTime(8, 0),
            'date_fin' => now()->startOfYear()->addDays(12)->setTime(12, 0),
        ]);

        $statsView = $this->actingAs($admin)->get(route('admin.stats'));
        $statsView->assertOk();
        $statsView->assertViewHas('totalReservations', 1);
        $statsView->assertViewHas('totalRevenueCents', 1500);

        $csvResponse = $this->actingAs($admin)->get(route('admin.stats.export'));
        $csvResponse->assertOk();
        $csvContent = $csvResponse->streamedContent();
        $this->assertStringContainsString("'=CMD", $csvContent);
        $this->assertStringContainsString('Place Centrale 1', $csvContent);

        // Export CSV utilisateur (propriétaire / locataire)
        $userCsv = $this->actingAs($this->owner)->get(route('reservations.export', ['period' => 'all']));
        $userCsv->assertOk();
        $this->assertStringContainsString('Place Centrale 1', $userCsv->streamedContent());

        // Non-admin bloqué sur l'export admin
        $this->actingAs($this->tenant)->get(route('admin.stats.export'))->assertForbidden();
    }

    public function test_site_coordinates_are_persisted_and_logged_in_audit(): void
    {
        $this->actingAs($this->owner)->post('/sites', [
            'nom' => 'Parking Ouchy',
            'adresse' => 'Place de la Navigation 1, 1006 Lausanne',
            'latitude' => 46.5072,
            'longitude' => 6.6267,
        ])->assertRedirect(route('sites.index'));

        $site = Site::where('nom', 'Parking Ouchy')->firstOrFail();
        $this->assertEquals(46.5072, $site->latitude);
        $this->assertEquals(6.6267, $site->longitude);
        $this->assertStringContainsString('46.5072000%2C6.6267000', $site->mapsUrl());

        $this->assertTrue(
            AuditLog::where('entity_type', 'site')
                ->where('entity_id', $site->id)
                ->where('action', 'create')
                ->exists()
        );
    }
}
