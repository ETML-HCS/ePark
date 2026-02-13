<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Models\Place;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use App\Notifications\PaymentStatusChanged;
use App\Notifications\ReservationStatusChanged;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReservationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReservationService $service;
    private User $owner;
    private User $tenant;
    private Place $place;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReservationService::class);

        $this->owner = User::factory()->create(['role' => 'proprietaire', 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $this->owner->id]);
        $this->place = Place::factory()->create([
            'user_id' => $this->owner->id,
            'site_id' => $site->id,
            'hourly_price_cents' => 500,
        ]);
        $this->tenant = User::factory()->create(['role' => 'locataire', 'onboarded' => true]);
    }

    // ─── createReservation() ─────────────────────────────

    public function test_create_reservation_success(): void
    {
        Notification::fake();

        $reservation = $this->service->createReservation(
            $this->tenant,
            $this->place,
            Carbon::tomorrow(),
            'matin_travail',
            15,
        );

        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertEquals('en_attente', $reservation->statut);
        $this->assertEquals('pending', $reservation->payment_status);
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id]);
        $this->assertDatabaseHas('payments', ['reservation_id' => $reservation->id]);
    }

    public function test_create_reservation_with_payment(): void
    {
        Notification::fake();

        $reservation = $this->service->createReservation(
            $this->tenant,
            $this->place,
            Carbon::tomorrow(),
            'matin_travail',
            15,
            true,
        );

        $this->assertEquals('paid', $reservation->payment_status);
        $this->assertTrue($reservation->paiement_effectue);
        Notification::assertSentTo($this->tenant, PaymentStatusChanged::class);
    }

    public function test_create_reservation_invalid_segment_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Moment de la journée invalide.');

        $this->service->createReservation(
            $this->tenant,
            $this->place,
            Carbon::tomorrow(),
            'invalid_segment',
            15,
        );
    }

    public function test_create_reservation_overlap_throws(): void
    {
        Notification::fake();

        // First reservation
        $this->service->createReservation(
            $this->tenant,
            $this->place,
            Carbon::tomorrow(),
            'matin_travail',
            0,
        );

        // Second reservation on same segment
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('chevauche');

        $newTenant = User::factory()->create(['role' => 'locataire', 'onboarded' => true]);
        $this->service->createReservation(
            $newTenant,
            $this->place,
            Carbon::tomorrow(),
            'matin_travail',
            0,
        );
    }

    public function test_create_reservation_creates_payment_record(): void
    {
        Notification::fake();

        $reservation = $this->service->createReservation(
            $this->tenant,
            $this->place,
            Carbon::tomorrow(),
            'aprem_travail',
            0,
        );

        $this->assertNotNull($reservation->payment);
        $this->assertEquals('manual', $reservation->payment->provider);
        $this->assertEquals('pending', $reservation->payment->provider_status);
    }

    // ─── confirmReservation() ────────────────────────────

    public function test_confirm_reservation_success(): void
    {
        Notification::fake();

        $reservation = $this->service->createReservation(
            $this->tenant,
            $this->place,
            Carbon::tomorrow(),
            'soir',
            0,
            true,
        );

        $this->service->confirmReservation($reservation, 'Bienvenue!');

        $reservation->refresh();
        $this->assertEquals('confirmée', $reservation->statut);
        $this->assertEquals('Bienvenue!', $reservation->owner_message);
        Notification::assertSentTo($this->tenant, ReservationStatusChanged::class);
    }

    public function test_confirm_reservation_fails_without_payment(): void
    {
        Notification::fake();

        $reservation = $this->service->createReservation(
            $this->tenant,
            $this->place,
            Carbon::tomorrow(),
            'soir',
            0,
            false,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('paiement');

        $this->service->confirmReservation($reservation);
    }

    // ─── cancelReservation() ─────────────────────────────

    public function test_cancel_reservation_success(): void
    {
        Notification::fake();

        $reservation = $this->service->createReservation(
            $this->tenant,
            $this->place,
            Carbon::tomorrow(),
            'nuit',
            0,
        );

        $this->service->cancelReservation($reservation, 'Annulé pour raison X');

        $reservation->refresh();
        $this->assertEquals('annulée', $reservation->statut);
        $this->assertEquals('Annulé pour raison X', $reservation->owner_message);
        Notification::assertSentTo($this->tenant, ReservationStatusChanged::class);
    }

    public function test_cancel_reservation_with_refund(): void
    {
        Notification::fake();

        $reservation = $this->service->createReservation(
            $this->tenant,
            $this->place,
            Carbon::tomorrow(),
            'matin_travail',
            0,
            true,
        );

        $this->service->cancelReservation($reservation);

        $reservation->refresh();
        $this->assertEquals('annulée', $reservation->statut);
        $this->assertEquals('refunded', $reservation->payment_status);
        $this->assertFalse($reservation->paiement_effectue);
    }

    // ─── processPayment() ────────────────────────────────

    public function test_process_payment_success(): void
    {
        Notification::fake();

        $reservation = $this->service->createReservation(
            $this->tenant,
            $this->place,
            Carbon::tomorrow(),
            'aprem_travail',
            0,
        );

        $this->service->processPayment($reservation);

        $reservation->refresh();
        $this->assertEquals('paid', $reservation->payment_status);
        $this->assertTrue($reservation->paiement_effectue);
        $this->assertEquals('succeeded', $reservation->payment->provider_status);
        Notification::assertSentTo($this->tenant, PaymentStatusChanged::class);
    }

    public function test_process_payment_noop_when_already_paid(): void
    {
        Notification::fake();

        $reservation = $this->service->createReservation(
            $this->tenant,
            $this->place,
            Carbon::tomorrow(),
            'aprem_travail',
            0,
            true,
        );

        Notification::fake(); // Reset
        $this->service->processPayment($reservation);

        // Should not send another notification
        Notification::assertNothingSent();
    }

    // ─── registerActualEnd() ─────────────────────────────

    public function test_register_actual_end_no_overstay(): void
    {
        Notification::fake();

        $reservation = $this->service->createReservation(
            $this->tenant,
            $this->place,
            Carbon::tomorrow(),
            'matin_travail',
            15,
            true,
        );

        $actualEnd = $reservation->date_fin->copy();
        $this->service->registerActualEnd($reservation, $actualEnd);

        $reservation->refresh();
        $this->assertEquals($actualEnd->toDateTimeString(), $reservation->actual_end_at->toDateTimeString());
        $this->assertEquals(0, $reservation->penalty_cents);
    }

    public function test_register_actual_end_with_overstay_penalty(): void
    {
        Notification::fake();

        $reservation = $this->service->createReservation(
            $this->tenant,
            $this->place,
            Carbon::tomorrow(),
            'matin_travail',
            0,
            true,
        );

        $actualEnd = $reservation->date_fin->copy()->addHours(2);
        $this->service->registerActualEnd($reservation, $actualEnd);

        $reservation->refresh();
        $this->assertGreaterThan(0, $reservation->penalty_cents);
        $this->assertGreaterThan(0, $reservation->overstay_minutes);
    }
}
