<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationEndReminder extends Notification
{
    use Queueable;

    public function __construct(private Reservation $reservation)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        /** @var \App\Models\User $user */
        $user = $notifiable;
        $placeName = optional($this->reservation->place)->nom ?? '';
        $endTime = $this->reservation->getEffectiveEndTime()->format('H:i');

        return (new MailMessage)
            ->subject('Rappel: fin de reservation dans 15 minutes')
            ->greeting('Bonjour ' . $user->name)
            ->line('Votre reservation pour la place "' . $placeName . '" se termine a ' . $endTime . '.')
            ->line('Merci de liberer la place a temps pour eviter une penalite.')
            ->action('Voir ma reservation', url('/reservations/' . $this->reservation->id))
            ->line('Merci d\'utiliser ePark !');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'type' => 'end_reminder',
            'place_name' => optional($this->reservation->place)->nom,
            'effective_end_at' => $this->reservation->getEffectiveEndTime()->toIso8601String(),
        ];
    }
}
