<?php

use App\Models\Reservation;
use App\Notifications\ReservationOwnerDeadlineReminder;
use App\Notifications\ReservationEndReminder;
use App\Services\ReservationService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reservations:send-end-reminders', function () {
    $now = now();
    $targetStart = $now->copy()->addMinutes(15)->startOfMinute();
    $targetEnd = $now->copy()->addMinutes(15)->endOfMinute();

    $reservations = Reservation::query()
        ->confirmed()
        ->whereNull('end_reminder_sent_at')
        ->whereRaw(
            'DATE_ADD(date_fin, INTERVAL COALESCE(battement_minutes, 0) MINUTE) BETWEEN ? AND ?',
            [$targetStart, $targetEnd]
        )
        ->with(['user', 'place'])
        ->get();

    foreach ($reservations as $reservation) {
        $reservation->user->notify(new ReservationEndReminder($reservation));
        $reservation->end_reminder_sent_at = $now;
        $reservation->save();
    }

    $this->info('End reminders sent: ' . $reservations->count());
})->purpose('Send 15-minute end reminders for reservations');

Artisan::command('reservations:send-owner-validation-reminders', function () {
    $now = now();
    $reminderThreshold = $now->copy()->addHours(25)->addMinutes(30);
    $deadlineThreshold = $now->copy()->addHours(24);

    $reservations = Reservation::query()
        ->pending()
        ->where('payment_status', 'paid')
        ->whereNull('owner_deadline_reminder_sent_at')
        ->where('date_debut', '<=', $reminderThreshold)
        ->where('date_debut', '>', $deadlineThreshold)
        ->whereHas('place', fn ($query) => $query->whereIn('pending_validation_default', ['confirm', 'refuse']))
        ->with(['place.user', 'place.site.user'])
        ->get();

    $sent = 0;

    foreach ($reservations as $reservation) {
        $owner = $reservation->place?->user ?? $reservation->place?->site?->user;
        if (!$owner || $owner->id === $reservation->user_id) {
            continue;
        }

        $owner->notify(new ReservationOwnerDeadlineReminder($reservation));
        $reservation->owner_deadline_reminder_sent_at = $now;
        $reservation->save();
        $sent++;
    }

    $this->info('Owner validation reminders sent: ' . $sent);
})->purpose('Send owner reminder 90 minutes before 24h default validation deadline');

Artisan::command('reservations:apply-default-validation', function () {
    $now = now();
    $deadline = $now->copy()->addHours(24);

    /** @var ReservationService $reservationService */
    $reservationService = app(ReservationService::class);

    $reservations = Reservation::query()
        ->pending()
        ->where('payment_status', 'paid')
        ->where('date_debut', '<=', $deadline)
        ->whereHas('place', fn ($query) => $query->whereIn('pending_validation_default', ['confirm', 'refuse']))
        ->with(['place', 'user'])
        ->get();

    $confirmed = 0;
    $refused = 0;

    foreach ($reservations as $reservation) {
        $defaultAction = (string) ($reservation->place?->pending_validation_default ?? 'manual');

        if ($defaultAction === 'confirm') {
            $reservationService->confirmReservation(
                $reservation,
                'Validation automatique appliquée à J-1 selon la règle par défaut de la place.'
            );
            $confirmed++;
            continue;
        }

        if ($defaultAction === 'refuse') {
            $reservationService->cancelReservation(
                $reservation,
                'Refus automatique appliqué à J-1 selon la règle par défaut de la place.'
            );
            $refused++;
        }
    }

    $this->info('Default validations applied - confirmed: ' . $confirmed . ', refused: ' . $refused);
})->purpose('Apply default owner decision to pending paid reservations 24h before start');

app()->booted(function () {
    $schedule = app(Schedule::class);

    $schedule->command('reservations:send-end-reminders')
        ->everyMinute()
        ->withoutOverlapping(1)
        ->runInBackground();

    $schedule->command('reservations:send-owner-validation-reminders')
        ->everyFiveMinutes()
        ->withoutOverlapping(5)
        ->runInBackground();

    $schedule->command('reservations:apply-default-validation')
        ->everyFifteenMinutes()
        ->withoutOverlapping(10)
        ->runInBackground();
});
