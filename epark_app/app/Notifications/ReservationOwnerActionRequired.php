<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationOwnerActionRequired extends Notification
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

        $start = $this->reservation->date_debut?->format('d/m/Y H:i');
        $placeName = optional($this->reservation->place)->nom ?? 'votre place';

        return (new MailMessage)
            ->subject('Action requise: valider une réservation ePark')
            ->greeting('Bonjour ' . $user->name)
            ->line('Une réservation en attente nécessite votre réponse pour la place "' . $placeName . '".')
            ->line('Début de la réservation: ' . $start)
            ->line('Si vous ne répondez pas à temps, la règle automatique configurée sur la place sera appliquée.')
            ->action('Gérer mes réservations', url('/reservations?status=en_attente'))
            ->line('Merci d\'utiliser ePark.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'type' => 'owner_action_required',
            'place_name' => optional($this->reservation->place)->nom,
            'date_debut' => optional($this->reservation->date_debut)?->toIso8601String(),
            'url' => '/reservations?status=en_attente',
        ];
    }
}
