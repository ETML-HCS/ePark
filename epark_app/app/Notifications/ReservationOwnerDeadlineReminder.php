<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationOwnerDeadlineReminder extends Notification
{
    use Queueable;

    public function __construct(private Reservation $reservation)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var \App\Models\User $user */
        $user = $notifiable;

        $placeName = optional($this->reservation->place)->nom ?? 'votre place';
        $start = $this->reservation->date_debut?->format('d/m/Y H:i');

        return (new MailMessage)
            ->subject('Rappel: validation requise sous 90 minutes')
            ->greeting('Bonjour ' . $user->name)
            ->line('La réservation pour la place "' . $placeName . '" est toujours en attente.')
            ->line('Début de la réservation: ' . $start)
            ->line('Il reste environ 90 minutes avant l’échéance de validation à 24h.')
            ->line('Sans action, la règle automatique de la place sera appliquée (accord/refus).')
            ->action('Valider ou refuser maintenant', url('/reservations?status=en_attente'))
            ->line('Merci d\'utiliser ePark.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'type' => 'owner_deadline_reminder',
            'place_name' => optional($this->reservation->place)->nom,
            'date_debut' => optional($this->reservation->date_debut)?->toIso8601String(),
            'url' => '/reservations?status=en_attente',
        ];
    }
}
