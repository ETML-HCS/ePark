<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Place;
use App\Models\PlaceUnavailability;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationEndReminder;
use App\Notifications\ReservationOwnerActionRequired;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingReliabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;

    private Place $place;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setTime(10, 0));
        Notification::fake();
        $this->tenant = User::factory()->create(['onboarded' => true]);
        $this->place = Place::factory()->create(['hourly_price_cents' => 250]);
    }

    public function test_partial_availability_does_not_offer_a_full_morning(): void
    {
        $date = now()->addDay()->toDateString();
        PlaceUnavailability::factory()->create(['place_id' => $this->place->id, 'date' => $date, 'start_time' => '10:30', 'end_time' => '11:00']);
        $response = $this->actingAs($this->tenant)->getJson('/express/availability?date='.$date)->assertOk();
        $place = collect($response->json('places'))->firstWhere('id', $this->place->id);
        $this->assertNotContains('matin_travail', $place['availableSegments']);
        $this->assertContains('aprem_travail', $place['availableSegments']);
    }

    public function test_buffer_from_a_previous_booking_blocks_the_full_segment(): void
    {
        $date = now()->addDay();
        Reservation::factory()->create(['place_id' => $this->place->id, 'date_debut' => $date->copy()->setTime(8, 0), 'date_fin' => $date->copy()->setTime(12, 0), 'battement_minutes' => 5]);
        $response = $this->actingAs($this->tenant)->getJson('/express/availability?date='.$date->toDateString())->assertOk();
        $place = collect($response->json('places'))->firstWhere('id', $this->place->id);
        $this->assertNotContains('aprem_travail', $place['availableSegments']);
        $this->assertContains('soir', $place['availableSegments']);
    }

    public function test_started_segments_are_hidden_and_rejected_on_post(): void
    {
        $response = $this->actingAs($this->tenant)->getJson('/express/availability?date='.now()->toDateString())->assertOk();
        $place = collect($response->json('places'))->firstWhere('id', $this->place->id);
        $this->assertNotContains('matin_travail', $place['availableSegments']);
        $this->postJson('/express/book', $this->booking(['date' => now()->toDateString()]))->assertUnprocessable();
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_duplicate_submission_cannot_create_two_bookings(): void
    {
        $this->actingAs($this->tenant)->postJson('/express/book', $this->booking())->assertOk();
        $this->postJson('/express/book', $this->booking())->assertUnprocessable();
        $this->assertDatabaseCount('reservations', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_quote_matches_persisted_amount_for_afternoon_and_evening(): void
    {
        foreach (['aprem_travail' => 1375, 'soir' => 1500] as $segment => $amount) {
            $date = now()->addDays($segment === 'soir' ? 2 : 1)->toDateString();
            $response = $this->actingAs($this->tenant)->getJson('/express/availability?date='.$date)->assertOk();
            $place = collect($response->json('places'))->firstWhere('id', $this->place->id);
            $this->assertSame($amount, $place['quotes'][$segment]);
            $this->postJson('/express/book', $this->booking(['date' => $date, 'segment' => $segment]))->assertOk();
            $this->assertDatabaseHas('reservations', ['place_id' => $this->place->id, 'amount_cents' => $amount]);
            $this->assertDatabaseHas('payments', ['amount_cents' => $amount]);
        }
    }

    public function test_private_and_inactive_places_cannot_be_booked(): void
    {
        $this->place->update(['is_group_reserved' => true]);
        $this->actingAs($this->tenant)->postJson('/express/book', $this->booking())->assertForbidden();
        $this->place->update(['is_group_reserved' => false, 'is_active' => false]);
        $this->postJson('/express/book', $this->booking())->assertUnprocessable();
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_invalid_dates_return_validation_errors_instead_of_server_errors(): void
    {
        $this->actingAs($this->tenant)->getJson('/express/availability?date=invalid')->assertUnprocessable();
        $this->getJson('/express?date=2026-02-31')->assertUnprocessable();
        $this->getJson('/express?key[]=invalid')->assertUnprocessable();
    }

    public function test_forgetting_a_device_also_ends_the_session(): void
    {
        $this->actingAs($this->tenant)->post('/express/forget')->assertRedirect('/express')->assertCookieExpired('epark_express_key');
        $this->assertGuest();
    }

    public function test_logout_clears_the_express_cookie(): void
    {
        $this->actingAs($this->tenant)->post('/logout')->assertCookieExpired('epark_express_key');
        $this->assertGuest();
    }

    public function test_auto_payment_cannot_be_enabled_without_a_provider(): void
    {
        $this->actingAs($this->tenant)->postJson('/express/toggle-autopay', ['enabled' => true])->assertUnprocessable();
    }

    public function test_new_account_can_register_without_choosing_a_parking(): void
    {
        $this->post('/register', ['name' => 'Nouveau membre', 'email' => 'new@example.test', 'password' => 'a-long-password', 'password_confirmation' => 'a-long-password'])->assertRedirect('/express');
        $this->assertDatabaseHas('users', ['email' => 'new@example.test', 'favorite_site_id' => null, 'onboarded' => true]);
        $this->get('/express')->assertOk();
    }

    public function test_sensitive_access_keys_are_not_serialized(): void
    {
        $this->assertArrayNotHasKey('express_token', $this->tenant->toArray());
        $this->assertArrayNotHasKey('secret_group_codes', $this->tenant->toArray());
    }

    public function test_rescheduling_recalculates_the_booking_and_payment_amounts(): void
    {
        $reservation = app(ReservationService::class)->createReservation($this->tenant, $this->place, now()->addDays(3), 'matin_travail', 5);
        app(ReservationService::class)->rescheduleReservation($reservation, now()->addDays(3), 'aprem_travail');
        $this->assertSame(1375, $reservation->fresh()->amount_cents);
        $this->assertSame(1375, $reservation->fresh()->payment->amount_cents);
    }

    private function booking(array $overrides = []): array
    {
        return array_merge(['place_id' => $this->place->id, 'date' => now()->addDay()->toDateString(), 'segment' => 'matin_travail'], $overrides);
    }

    public function test_cancelled_filter_does_not_hide_cancelled_reservations(): void
    {
        Reservation::factory()->cancelled()->create(['user_id' => $this->tenant->id, 'place_id' => $this->place->id]);
        $this->actingAs($this->tenant)->get('/reservations?status=annulée')->assertOk()->assertSee($this->place->nom);
    }

    public function test_archived_places_remain_readable_in_reservation_history(): void
    {
        $reservation = Reservation::factory()->create(['user_id' => $this->tenant->id, 'place_id' => $this->place->id]);
        $this->place->delete();
        $this->actingAs($this->tenant)->get('/reservations/'.$reservation->id)->assertOk()->assertSee($this->place->nom);
    }

    public function test_guest_booking_choice_is_preserved_through_login(): void
    {
        $target = '/express?date='.now()->addDay()->toDateString().'&place_id='.$this->place->id;
        $this->get($target)->assertOk();
        $this->post('/login', ['email' => $this->tenant->email, 'password' => 'password'])->assertRedirect(url($target));
    }

    public function test_payment_failure_rolls_back_the_entire_booking(): void
    {
        Payment::creating(fn () => throw new \RuntimeException('Payment storage unavailable'));
        try {
            app(ReservationService::class)->createReservation($this->tenant, $this->place, now()->addDays(2), 'matin_travail', 5);
            $this->fail('The simulated storage failure must propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Payment storage unavailable', $exception->getMessage());
        } finally {
            Payment::flushEventListeners();
        }
        $this->assertDatabaseCount('reservations', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_rescheduling_notifies_owner_and_rejects_paid_reservations(): void
    {
        $service = app(ReservationService::class);
        $reservation = $service->createReservation($this->tenant, $this->place, now()->addDays(3), 'matin_travail', 5);

        Notification::fake();
        $service->rescheduleReservation($reservation, now()->addDays(4), 'aprem_travail');
        Notification::assertSentTo($this->place->user, ReservationOwnerActionRequired::class);

        $service->processPayment($reservation);

        $this->expectException(\InvalidArgumentException::class);
        $service->rescheduleReservation($reservation, now()->addDays(5), 'soir');
    }

    public function test_mail_outage_does_not_fail_payment_confirm_or_cancel(): void
    {
        Notification::swap(new class extends ChannelManager
        {
            public function __construct()
            {
                parent::__construct(app());
            }

            public function send($notifiables, $notification)
            {
                throw new \RuntimeException('SMTP down');
            }
        });

        $service = app(ReservationService::class);
        $reservation = $service->createReservation($this->tenant, $this->place, now()->addDays(3), 'matin_travail', 5);

        $service->processPayment($reservation);
        $this->assertSame('paid', $reservation->fresh()->payment_status);

        $service->confirmReservation($reservation, 'OK');
        $this->assertSame('confirmée', $reservation->fresh()->statut);

        $service->cancelReservation($reservation, 'Annulé');
        $this->assertSame('annulée', $reservation->fresh()->statut);
        $this->assertSame('refunded', $reservation->fresh()->payment_status);
    }

    public function test_end_reminders_command_works_on_sqlite_and_stamps_sent_at(): void
    {
        $reservation = Reservation::factory()->confirmed()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $this->place->id,
            'date_debut' => now()->subHours(2),
            'date_fin' => now()->addMinutes(10),
            'battement_minutes' => 5,
            'end_reminder_sent_at' => null,
        ]);

        $this->artisan('reservations:send-end-reminders')
            ->expectsOutput('End reminders sent: 1')
            ->assertExitCode(0);

        $this->assertNotNull($reservation->fresh()->end_reminder_sent_at);
        Notification::assertSentTo($this->tenant, ReservationEndReminder::class);
    }
}
