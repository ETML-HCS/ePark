<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Throwable;

class NotificationDelivery
{
    public function send(?User $recipient, Notification $notification): bool
    {
        if (! $recipient) {
            return false;
        }
        // A stable ID lets us distinguish a stored in-app message from a mail failure.
        $notification->id = (string) Str::uuid();
        try {
            $recipient->notify($notification);

            return true;
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            return $recipient->notifications()->whereKey($notification->id)->exists();
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
