<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentStatusChanged extends Notification
{
    use Queueable;

    protected Reservation $reservation;

    protected string $status;

    public function __construct(Reservation $reservation, string $status)
    {
        $this->reservation = $reservation;
        $this->status = $status;
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

    public function toMail(object $notifiable): MailMessage
    {
        /** @var \App\Models\User $user */
        $user = $notifiable;

        $label = match ($this->status) {
            'paid' => 'Payé',
            'refunded' => 'Remboursé',
            'failed' => 'Échec',
            default => ucfirst($this->status),
        };

        return (new MailMessage)
            ->subject('Statut de votre paiement')
            ->greeting('Bonjour '.$user->name)
            ->line('Le paiement de votre réservation #'.$this->reservation->id.' a changé de statut.')
            ->line('Nouveau statut : '.$label)
            ->action('Voir la réservation', url('/reservations/'.$this->reservation->id))
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
            'reservation_id' => $this->reservation->id,
            'status' => $this->status,
        ];
    }
}
