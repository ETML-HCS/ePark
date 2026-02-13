<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

/**
 * Policy pour gérer les autorisations sur les réservations.
 * Remplace les vérifications inline dans les contrôleurs.
 */
class ReservationPolicy
{
    /**
     * L'utilisateur peut voir la réservation s'il en est le créateur,
     * le propriétaire de la place, ou un admin.
     */
    public function view(User $user, Reservation $reservation): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->id === $reservation->user_id) {
            return true;
        }

        return $this->isPlaceOwner($user, $reservation);
    }

    /**
     * L'utilisateur peut valider la réservation s'il est propriétaire de la place.
     */
    public function validate(User $user, Reservation $reservation): bool
    {
        return $this->isPlaceOwner($user, $reservation);
    }

    /**
     * L'utilisateur peut refuser la réservation s'il est propriétaire de la place.
     */
    public function refuse(User $user, Reservation $reservation): bool
    {
        return $this->isPlaceOwner($user, $reservation);
    }

    /**
     * L'utilisateur peut annuler la réservation s'il en est le créateur ou un admin.
     */
    public function cancel(User $user, Reservation $reservation): bool
    {
        if ($user->role === 'admin') {
            return true;
        }
        if ($user->id !== $reservation->user_id) {
            return false;
        }

        return $this->canCancelWithDeadline($reservation);
    }

    /**
     * L'utilisateur peut payer la réservation s'il en est le créateur.
     */
    public function pay(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->user_id;
    }

    /**
     * L'utilisateur peut modifier sa reservation si elle est en attente et dans le delai.
     */
    public function update(User $user, Reservation $reservation): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->id !== $reservation->user_id) {
            return false;
        }

        if ($reservation->statut !== 'en_attente') {
            return false;
        }

        if ($reservation->payment_status !== 'pending') {
            return false;
        }

        return $this->canCancelWithDeadline($reservation);
    }

    /**
     * Vérifie si l'utilisateur est propriétaire de la place.
     */
    private function isPlaceOwner(User $user, Reservation $reservation): bool
    {
        $ownerId = $reservation->place->user_id 
            ?? $reservation->place->site?->user_id;

        return $user->id === $ownerId;
    }

    private function canCancelWithDeadline(Reservation $reservation): bool
    {
        $deadlineHours = (int) ($reservation->place?->cancel_deadline_hours ?? 12);
        $deadline = $reservation->date_debut->copy()->subHours($deadlineHours);

        return now()->lt($deadline);
    }
}
