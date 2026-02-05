<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Place;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\PaymentStatusChanged;
use App\Notifications\ReservationStatusChanged;
use Illuminate\Support\Carbon;

/**
 * Service pour la gestion des réservations.
 * Centralise la logique métier et les opérations liées aux réservations.
 */
class ReservationService
{
    public function __construct(
        private PlaceAvailabilityService $availabilityService
    ) {}

    /**
     * Crée une nouvelle réservation.
     *
     * @throws \InvalidArgumentException Si le créneau n'est pas disponible
     */
    public function createReservation(
        User $user,
        Place $place,
        Carbon $date,
        string $segment,
        int $battement,
        ?string $startHour = null,
        bool $paid = false
    ): Reservation {
        $availableHours = $this->availabilityService->computeAvailableHours($place, $date);

        // Détermine l'heure de début
        $resolvedStartHour = $startHour;
        if (!$resolvedStartHour || !in_array($resolvedStartHour, $availableHours, true)) {
            $resolvedStartHour = $this->availabilityService->findFirstHourInSegment($availableHours, $segment);
        }

        if (!$resolvedStartHour) {
            throw new \InvalidArgumentException('Aucun créneau disponible pour ce moment de la journée.');
        }

        $start = Carbon::parse($date->toDateString() . ' ' . $resolvedStartHour);
        $end = $start->copy()->addHour();
        $endWithBattement = $end->copy()->addMinutes($battement);

        // Vérifie la disponibilité
        if (!$place->isAvailableFor($start, $endWithBattement)) {
            throw new \InvalidArgumentException('La place n\'est pas disponible sur ce créneau.');
        }

        // Vérifie les chevauchements
        if (Reservation::overlaps($place->id, $start, $end, $battement)) {
            throw new \InvalidArgumentException('Le créneau sélectionné chevauche une réservation existante.');
        }

        // Crée la réservation
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'place_id' => $place->id,
            'date_debut' => $start,
            'date_fin' => $end,
            'statut' => 'en_attente',
            'battement_minutes' => $battement,
            'amount_cents' => $place->hourly_price_cents ?? 0,
            'payment_status' => $paid ? 'paid' : 'pending',
            'paiement_effectue' => $paid,
        ]);

        // Crée le paiement associé
        $reservation->payment()->create([
            'amount_cents' => $reservation->amount_cents ?? 0,
            'provider' => 'manual',
            'provider_status' => $paid ? 'succeeded' : 'pending',
            'provider_ref' => $paid ? 'manual-' . now()->timestamp : null,
        ]);

        if ($paid) {
            $reservation->user->notify(new PaymentStatusChanged($reservation, 'paid'));
        }

        return $reservation;
    }

    /**
     * Confirme une réservation (par le propriétaire).
     * 
     * @param Reservation $reservation
     * @param string|null $message Message optionnel du propriétaire
     */
    public function confirmReservation(Reservation $reservation, ?string $message = null): void
    {
        if ($reservation->payment_status !== 'paid') {
            throw new \InvalidArgumentException('Le paiement doit être validé avant confirmation.');
        }

        $reservation->statut = 'confirmée';
        $reservation->owner_message = $message;
        $reservation->save();

        $reservation->user->notify(new ReservationStatusChanged($reservation, 'confirmée'));
    }

    /**
     * Annule/Refuse une réservation avec remboursement si nécessaire.
     * 
     * @param Reservation $reservation
     * @param string|null $message Message optionnel du propriétaire
     */
    public function cancelReservation(Reservation $reservation, ?string $message = null): void
    {
        $reservation->statut = 'annulée';
        $reservation->owner_message = $message;

        if ($reservation->payment_status === 'paid') {
            $this->processRefund($reservation);
        }

        $reservation->save();
        $reservation->user->notify(new ReservationStatusChanged($reservation, 'annulée'));
    }

    /**
     * Traite le paiement d'une réservation.
     */
    public function processPayment(Reservation $reservation): void
    {
        if ($reservation->payment_status === 'paid') {
            return;
        }

        if (!$reservation->payment) {
            $reservation->payment()->create([
                'amount_cents' => $reservation->amount_cents ?? 0,
                'provider' => 'manual',
                'provider_status' => 'pending',
                'provider_ref' => null,
            ]);
            $reservation->refresh();
        }

        $reservation->payment_status = 'paid';
        $reservation->paiement_effectue = true;
        $reservation->payment?->update([
            'provider_status' => 'succeeded',
            'provider_ref' => 'manual-' . now()->timestamp,
        ]);
        $reservation->save();

        $reservation->user->notify(new PaymentStatusChanged($reservation, 'paid'));
    }

    /**
     * Traite le remboursement d'une réservation.
     */
    private function processRefund(Reservation $reservation): void
    {
        $reservation->payment_status = 'refunded';
        $reservation->paiement_effectue = false;
        $reservation->payment?->update(['provider_status' => 'refunded']);

        $reservation->user->notify(new PaymentStatusChanged($reservation, 'refunded'));
    }
}
