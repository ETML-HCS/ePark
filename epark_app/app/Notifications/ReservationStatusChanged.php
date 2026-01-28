<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationStatusChanged extends Notification
{
    use Queueable;

    protected $reservation;

    protected $statut;

    /**
     * Create a new notification instance.
     */
    public function __construct($reservation, $statut)
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Statut de votre réservation modifié')
            ->greeting('Bonjour '.$notifiable->name)
            ->line('Le statut de votre réservation pour la place "'.($this->reservation->place->nom ?? '').'" a été modifié.')
            ->line('Nouveau statut : '.$this->statut)
            ->action('Voir mes réservations', url('/reservations'))
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
            //
        ];
    }
}
