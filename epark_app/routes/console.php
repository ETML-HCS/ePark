<?php

use App\Models\Reservation;
use App\Notifications\ReservationEndReminder;
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

app()->booted(function () {
    $schedule = app(Schedule::class);

    $schedule->command('reservations:send-end-reminders')
        ->everyMinute()
        ->withoutOverlapping(1)
        ->runInBackground();
});
