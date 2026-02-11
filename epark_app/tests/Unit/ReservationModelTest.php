<?php

namespace Tests\Unit;

use App\Models\Feedback;
use App\Models\Payment;
use App\Models\Place;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReservationModelTest extends TestCase
{
    use RefreshDatabase;

    private function createReservation(array $attrs = []): Reservation
    {
        $owner = User::factory()->create(['role' => 'proprietaire', 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $owner->id]);
        $place = Place::factory()->create(['user_id' => $owner->id, 'site_id' => $site->id]);
        $tenant = User::factory()->create(['role' => 'locataire', 'onboarded' => true]);

        return Reservation::factory()->create(array_merge([
            'user_id' => $tenant->id,
            'place_id' => $place->id,
        ], $attrs));
    }

    // ─── Relations ───────────────────────────────────────

    public function test_reservation_belongs_to_user(): void
    {
        $reservation = $this->createReservation();
        $this->assertInstanceOf(User::class, $reservation->user);
    }

    public function test_reservation_belongs_to_place(): void
    {
        $reservation = $this->createReservation();
        $this->assertInstanceOf(Place::class, $reservation->place);
    }

    public function test_reservation_has_one_payment(): void
    {
        $reservation = $this->createReservation();
        Payment::factory()->create(['reservation_id' => $reservation->id]);

        $this->assertInstanceOf(Payment::class, $reservation->payment);
    }

    public function test_reservation_has_one_feedback(): void
    {
        $reservation = $this->createReservation();
        Feedback::factory()->create([
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
        ]);

        $this->assertInstanceOf(Feedback::class, $reservation->feedback);
    }

    // ─── Scopes ──────────────────────────────────────────

    public function test_scope_pending(): void
    {
        $this->createReservation(['statut' => 'en_attente']);
        $this->createReservation(['statut' => 'confirmée']);

        $this->assertCount(1, Reservation::pending()->get());
    }

    public function test_scope_confirmed(): void
    {
        $this->createReservation(['statut' => 'confirmée']);
        $this->createReservation(['statut' => 'en_attente']);

        $this->assertCount(1, Reservation::confirmed()->get());
    }

    public function test_scope_active_excludes_cancelled(): void
    {
        $this->createReservation(['statut' => 'en_attente']);
        $this->createReservation(['statut' => 'confirmée']);
        $this->createReservation(['statut' => 'annulée']);

        $this->assertCount(2, Reservation::active()->get());
    }

    public function test_scope_completed(): void
    {
        $this->createReservation([
            'statut' => 'confirmée',
            'date_fin' => Carbon::yesterday(),
        ]);
        $this->createReservation([
            'statut' => 'confirmée',
            'date_fin' => Carbon::tomorrow(),
        ]);

        $this->assertCount(1, Reservation::completed()->get());
    }

    public function test_scope_for_place(): void
    {
        $r1 = $this->createReservation();
        $this->createReservation();

        $this->assertCount(1, Reservation::forPlace($r1->place_id)->get());
    }

    public function test_scope_for_owner(): void
    {
        $owner = User::factory()->create(['role' => 'proprietaire', 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $owner->id]);
        $place = Place::factory()->create(['user_id' => $owner->id, 'site_id' => $site->id]);
        $tenant = User::factory()->create(['role' => 'locataire', 'onboarded' => true]);
        Reservation::factory()->create(['user_id' => $tenant->id, 'place_id' => $place->id]);
        $this->createReservation(); // Different owner

        $this->assertCount(1, Reservation::forOwner($owner->id)->get());
    }

    // ─── Méthodes métier ─────────────────────────────────

    public function test_get_effective_end_time_includes_battement(): void
    {
        $reservation = $this->createReservation([
            'date_fin' => Carbon::parse('2025-01-15 12:00:00'),
            'battement_minutes' => 15,
        ]);

        $effective = $reservation->getEffectiveEndTime();
        $this->assertEquals('2025-01-15 12:15:00', $effective->format('Y-m-d H:i:s'));
    }

    public function test_get_effective_end_time_zero_battement(): void
    {
        $reservation = $this->createReservation([
            'date_fin' => Carbon::parse('2025-01-15 12:00:00'),
            'battement_minutes' => 0,
        ]);

        $effective = $reservation->getEffectiveEndTime();
        $this->assertEquals('2025-01-15 12:00:00', $effective->format('Y-m-d H:i:s'));
    }

    public function test_calculate_overstay_penalty_no_penalty_under_60_min(): void
    {
        $reservation = $this->createReservation([
            'date_fin' => Carbon::parse('2025-01-15 12:00:00'),
            'battement_minutes' => 0,
        ]);

        $penalty = $reservation->calculateOverstayPenaltyCents(Carbon::parse('2025-01-15 12:30:00'));
        $this->assertEquals(0, $penalty);
    }

    public function test_calculate_overstay_penalty_4000_at_1h(): void
    {
        $reservation = $this->createReservation([
            'date_fin' => Carbon::parse('2025-01-15 12:00:00'),
            'battement_minutes' => 0,
        ]);

        $penalty = $reservation->calculateOverstayPenaltyCents(Carbon::parse('2025-01-15 13:30:00'));
        $this->assertEquals(4000, $penalty);
    }

    public function test_calculate_overstay_penalty_8000_at_3h(): void
    {
        $reservation = $this->createReservation([
            'date_fin' => Carbon::parse('2025-01-15 12:00:00'),
            'battement_minutes' => 0,
        ]);

        $penalty = $reservation->calculateOverstayPenaltyCents(Carbon::parse('2025-01-15 15:30:00'));
        $this->assertEquals(8000, $penalty);
    }

    public function test_calculate_overstay_penalty_12000_at_24h(): void
    {
        $reservation = $this->createReservation([
            'date_fin' => Carbon::parse('2025-01-15 12:00:00'),
            'battement_minutes' => 0,
        ]);

        $penalty = $reservation->calculateOverstayPenaltyCents(Carbon::parse('2025-01-16 13:00:00'));
        $this->assertEquals(12000, $penalty);
    }

    public function test_is_paid_returns_true_when_paid(): void
    {
        $reservation = $this->createReservation(['payment_status' => 'paid']);
        $this->assertTrue($reservation->isPaid());
    }

    public function test_is_paid_returns_false_when_pending(): void
    {
        $reservation = $this->createReservation(['payment_status' => 'pending']);
        $this->assertFalse($reservation->isPaid());
    }

    public function test_can_be_confirmed_when_paid_and_pending(): void
    {
        $reservation = $this->createReservation([
            'statut' => 'en_attente',
            'payment_status' => 'paid',
        ]);
        $this->assertTrue($reservation->canBeConfirmed());
    }

    public function test_cannot_be_confirmed_when_not_paid(): void
    {
        $reservation = $this->createReservation([
            'statut' => 'en_attente',
            'payment_status' => 'pending',
        ]);
        $this->assertFalse($reservation->canBeConfirmed());
    }

    public function test_cannot_be_confirmed_when_already_confirmed(): void
    {
        $reservation = $this->createReservation([
            'statut' => 'confirmée',
            'payment_status' => 'paid',
        ]);
        $this->assertFalse($reservation->canBeConfirmed());
    }

    // ─── overlaps() ─────────────────────────────────────

    public function test_overlaps_returns_true_when_overlapping(): void
    {
        $reservation = $this->createReservation([
            'date_debut' => Carbon::parse('2025-01-20 10:00:00'),
            'date_fin' => Carbon::parse('2025-01-20 12:00:00'),
            'battement_minutes' => 0,
            'statut' => 'en_attente',
        ]);

        $result = Reservation::overlaps(
            $reservation->place_id,
            Carbon::parse('2025-01-20 11:00:00'),
            Carbon::parse('2025-01-20 13:00:00'),
        );

        $this->assertTrue($result);
    }

    public function test_overlaps_returns_false_when_adjacent(): void
    {
        $reservation = $this->createReservation([
            'date_debut' => Carbon::parse('2025-01-20 10:00:00'),
            'date_fin' => Carbon::parse('2025-01-20 12:00:00'),
            'battement_minutes' => 0,
            'statut' => 'en_attente',
        ]);

        $result = Reservation::overlaps(
            $reservation->place_id,
            Carbon::parse('2025-01-20 12:00:00'),
            Carbon::parse('2025-01-20 14:00:00'),
        );

        $this->assertFalse($result);
    }

    public function test_overlaps_considers_battement_of_existing(): void
    {
        $reservation = $this->createReservation([
            'date_debut' => Carbon::parse('2025-01-20 10:00:00'),
            'date_fin' => Carbon::parse('2025-01-20 12:00:00'),
            'battement_minutes' => 30,
            'statut' => 'en_attente',
        ]);

        $result = Reservation::overlaps(
            $reservation->place_id,
            Carbon::parse('2025-01-20 12:15:00'),
            Carbon::parse('2025-01-20 14:00:00'),
        );

        $this->assertTrue($result);
    }

    public function test_overlaps_excludes_cancelled_reservations(): void
    {
        $reservation = $this->createReservation([
            'date_debut' => Carbon::parse('2025-01-20 10:00:00'),
            'date_fin' => Carbon::parse('2025-01-20 12:00:00'),
            'battement_minutes' => 0,
            'statut' => 'annulée',
        ]);

        $result = Reservation::overlaps(
            $reservation->place_id,
            Carbon::parse('2025-01-20 10:00:00'),
            Carbon::parse('2025-01-20 12:00:00'),
        );

        $this->assertFalse($result);
    }

    public function test_overlaps_exclude_id_ignores_self(): void
    {
        $reservation = $this->createReservation([
            'date_debut' => Carbon::parse('2025-01-20 10:00:00'),
            'date_fin' => Carbon::parse('2025-01-20 12:00:00'),
            'statut' => 'en_attente',
        ]);

        $result = Reservation::overlaps(
            $reservation->place_id,
            Carbon::parse('2025-01-20 10:00:00'),
            Carbon::parse('2025-01-20 12:00:00'),
            0,
            $reservation->id,
        );

        $this->assertFalse($result);
    }

    // ─── Casts ───────────────────────────────────────────

    public function test_date_debut_is_cast_to_datetime(): void
    {
        $reservation = $this->createReservation();
        $this->assertInstanceOf(Carbon::class, $reservation->date_debut);
    }

    public function test_amount_cents_is_cast_to_integer(): void
    {
        $reservation = $this->createReservation(['amount_cents' => 500]);
        $this->assertIsInt($reservation->amount_cents);
    }

    public function test_paiement_effectue_is_cast_to_boolean(): void
    {
        $reservation = $this->createReservation(['paiement_effectue' => 1]);
        $this->assertIsBool($reservation->paiement_effectue);
    }
}
