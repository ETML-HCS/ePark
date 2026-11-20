<?php

namespace App\Notifications;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationStatusChanged extends Notification
{
    use Queueable;

    protected Reservation $reservation;

    protected string $statut;

    /**
     * Create a new notification instance.
     */
    public function __construct(Reservation $reservation, string $statut)
    {
        $this->reservation = $reservation;
        $this->statut = $statut;
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
        /** @var User $user */
        $user = $notifiable;

        return (new MailMessage)
            ->subject('Statut de votre réservation modifié')
            ->greeting('Bonjour '.$user->name)
            ->line('Le statut de votre réservation pour la place "'.(optional($this->reservation->place)->nom ?? '').'" a été modifié.')
            ->line('Nouveau statut : '.$this->statut)
            ->action('Voir mes réservations', url('/reservations/'.$this->reservation->id))
            ->line('Merci d’utiliser ePark !');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reservation_status_changed',
            'reservation_id' => $this->reservation->id,
            'statut' => $this->statut,
            'place_name' => optional($this->reservation->place)->nom,
            'owner_message' => $this->reservation->owner_message,
            'url' => '/reservations/'.$this->reservation->id,
        ];
    }
}
