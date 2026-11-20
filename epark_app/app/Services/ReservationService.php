<?php

namespace App\Services;

use App\Models\Place;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\PaymentStatusChanged;
use App\Notifications\ReservationOwnerActionRequired;
use App\Notifications\ReservationStatusChanged;
use App\Support\BookingPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service pour la gestion des réservations.
 * Centralise la logique métier et les opérations liées aux réservations.
 */
class ReservationService
{
    public function __construct(
        private NotificationDelivery $notificationDelivery,
        private AuditLogger $auditLogger
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
        bool $paid = false,
        bool $autoConfirm = false,
        string $paymentProvider = 'manual',
        ?string $vehiclePlate = null
    ): Reservation {
        [$start, $end] = BookingPeriod::range($date, $segment);
        if ($start->lte(now())) {
            throw new \InvalidArgumentException('Ce créneau a déjà commencé. Choisissez un créneau à venir.');
        }

        $normalizedPlate = trim((string) ($vehiclePlate ?? $user->quick_vehicle_plate ?? ''));
        $normalizedPlate = $normalizedPlate !== '' ? mb_strtoupper($normalizedPlate) : null;

        $reservation = DB::transaction(function () use ($user, $place, $start, $end, $segment, $battement, $paid, $autoConfirm, $paymentProvider, $normalizedPlate) {
            // Serialize competing bookings on the same place before checking overlaps.
            $place = Place::query()->lockForUpdate()->findOrFail($place->id);

            // Vérifie la disponibilité sur la plage exacte (sans battement)
            if (! $place->isAvailableFor($start, $end)) {
                throw new \InvalidArgumentException('La place n\'est pas disponible sur ce créneau.');
            }

            // Vérifie les chevauchements
            if (Reservation::overlaps($place->id, $start, $end, $battement)) {
                throw new \InvalidArgumentException('Le créneau sélectionné chevauche une réservation existante.');
            }

            $isConfirmed = $paid && $autoConfirm;

            // Crée la réservation
            $reservation = Reservation::create([
                'user_id' => $user->id,
                'place_id' => $place->id,
                'date_debut' => $start,
                'date_fin' => $end,
                'statut' => $isConfirmed ? 'confirmée' : 'en_attente',
                'battement_minutes' => $battement,
                'vehicle_plate' => $normalizedPlate,
                'amount_cents' => BookingPeriod::amount($place->hourly_price_cents ?? 0, $segment),
                'payment_status' => $paid ? 'paid' : 'pending',
                'paiement_effectue' => $paid,
            ]);

            // Crée le paiement associé
            $reservation->payment()->create([
                'amount_cents' => $reservation->amount_cents ?? 0,
                'provider' => $paymentProvider,
                'provider_status' => $paid ? 'succeeded' : 'pending',
                'provider_ref' => $paid ? ($paymentProvider.'-'.now()->timestamp) : null,
            ]);

            return $reservation;
        });

        $this->auditLogger->log('create', 'reservation', $reservation->id, null, [
            'place_id' => $reservation->place_id,
            'statut' => $reservation->statut,
            'amount_cents' => $reservation->amount_cents,
            'payment_status' => $reservation->payment_status,
            'vehicle_plate' => $reservation->vehicle_plate,
        ], $user->id);

        // A mail outage must never turn a saved booking into an apparent failure.
        if ($paid) {
            $this->notificationDelivery->send($reservation->user, new PaymentStatusChanged($reservation, 'paid'));
        }
        if ($reservation->statut === 'confirmée') {
            $this->notificationDelivery->send($reservation->user, new ReservationStatusChanged($reservation, 'confirmée'));
        } else {
            $this->notifyOwnerForPendingValidation($reservation);
        }

        $this->flushDashboardCacheForReservation($reservation);

        return $reservation;
    }

    /**
     * Confirme une réservation (par le propriétaire).
     *
     * @param  string|null  $message  Message optionnel du propriétaire
     */
    public function confirmReservation(Reservation $reservation, ?string $message = null): void
    {
        if ($reservation->statut !== 'en_attente' || $reservation->date_fin->isPast()) {
            throw new \InvalidArgumentException('Cette réservation ne peut plus être confirmée.');
        }
        if ($reservation->payment_status !== 'paid') {
            throw new \InvalidArgumentException('Le paiement doit être validé avant confirmation.');
        }

        $before = ['statut' => $reservation->statut, 'owner_message' => $reservation->owner_message];
        $reservation->statut = 'confirmée';
        $reservation->owner_message = $message;
        $reservation->save();

        $this->auditLogger->log('confirm', 'reservation', $reservation->id, $before, [
            'statut' => $reservation->statut,
            'owner_message' => $reservation->owner_message,
        ]);

        $this->notificationDelivery->send($reservation->user, new ReservationStatusChanged($reservation, 'confirmée'));
        $this->flushDashboardCacheForReservation($reservation);
    }

    /**
     * Annule/Refuse une réservation avec remboursement si nécessaire.
     *
     * @param  string|null  $message  Message optionnel du propriétaire
     */
    public function cancelReservation(Reservation $reservation, ?string $message = null): void
    {
        $before = ['statut' => $reservation->statut, 'payment_status' => $reservation->payment_status];
        $reservation->statut = 'annulée';
        $reservation->owner_message = $message;

        if ($reservation->payment_status === 'paid') {
            $this->processRefund($reservation);
        }

        $reservation->save();

        $this->auditLogger->log('cancel', 'reservation', $reservation->id, $before, [
            'statut' => $reservation->statut,
            'payment_status' => $reservation->payment_status,
            'owner_message' => $reservation->owner_message,
        ]);

        $this->notificationDelivery->send($reservation->user, new ReservationStatusChanged($reservation, 'annulée'));
        $this->flushDashboardCacheForReservation($reservation);
    }

    /**
     * Traite le paiement d'une réservation.
     */
    public function processPayment(Reservation $reservation): void
    {
        if ($reservation->statut === 'annulée' || $reservation->date_fin->isPast()) {
            throw new \InvalidArgumentException('Cette réservation ne peut plus recevoir de paiement.');
        }
        if ($reservation->payment_status === 'paid') {
            return;
        }

        $before = ['payment_status' => $reservation->payment_status];

        if (! $reservation->payment) {
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
            'provider_ref' => 'manual-'.now()->timestamp,
        ]);
        $reservation->save();

        $this->auditLogger->log('pay', 'reservation', $reservation->id, $before, [
            'payment_status' => $reservation->payment_status,
        ]);

        $this->notificationDelivery->send($reservation->user, new PaymentStatusChanged($reservation, 'paid'));
        $this->flushDashboardCacheForReservation($reservation);
    }

    /**
     * Confirme la réception effective du paiement manuel par le propriétaire.
     */
    public function confirmPaymentReceived(Reservation $reservation): void
    {
        if ($reservation->statut === 'annulée') {
            throw new \InvalidArgumentException('Impossible de confirmer le règlement d\'une réservation annulée.');
        }

        $before = [
            'payment_status' => $reservation->payment_status,
            'payment_received_at' => $reservation->payment_received_at?->toIso8601String(),
        ];

        $wasPending = $reservation->payment_status !== 'paid';

        if (! $reservation->payment) {
            $reservation->payment()->create([
                'amount_cents' => $reservation->amount_cents ?? 0,
                'provider' => 'manual',
                'provider_status' => 'succeeded',
                'provider_ref' => 'owner-confirmed-'.now()->timestamp,
            ]);
            $reservation->refresh();
        } elseif ($wasPending) {
            $reservation->payment->update([
                'provider_status' => 'succeeded',
                'provider_ref' => 'owner-confirmed-'.now()->timestamp,
            ]);
        }

        $reservation->payment_status = 'paid';
        $reservation->paiement_effectue = true;
        $reservation->payment_received_at = $reservation->payment_received_at ?? now();
        $reservation->save();

        $this->auditLogger->log('confirm_payment', 'reservation', $reservation->id, $before, [
            'payment_status' => $reservation->payment_status,
            'payment_received_at' => $reservation->payment_received_at->toIso8601String(),
        ]);

        if ($wasPending) {
            $this->notificationDelivery->send($reservation->user, new PaymentStatusChanged($reservation, 'paid'));
        }

        $this->flushDashboardCacheForReservation($reservation);
    }

    /**
     * Traite le remboursement d'une réservation.
     */
    private function processRefund(Reservation $reservation): void
    {
        $reservation->payment_status = 'refunded';
        $reservation->paiement_effectue = false;
        $reservation->payment?->update(['provider_status' => 'refunded']);

        $this->notificationDelivery->send($reservation->user, new PaymentStatusChanged($reservation, 'refunded'));
    }

    /**
     * Enregistre l'heure de fin reelle et applique la penalite de depassement.
     */
    public function registerActualEnd(Reservation $reservation, Carbon $actualEnd): void
    {
        $before = [
            'actual_end_at' => $reservation->actual_end_at?->toIso8601String(),
            'overstay_minutes' => $reservation->overstay_minutes,
            'penalty_cents' => $reservation->penalty_cents,
        ];

        $effectiveEnd = $reservation->getEffectiveEndTime();
        $overstayMinutes = max(0, $effectiveEnd->diffInMinutes($actualEnd, false));
        $penaltyCents = $reservation->calculateOverstayPenaltyCents($actualEnd);

        $reservation->actual_end_at = $actualEnd;
        $reservation->overstay_minutes = (int) $overstayMinutes;
        $reservation->penalty_cents = $penaltyCents;
        $reservation->save();

        $this->auditLogger->log('overstay', 'reservation', $reservation->id, $before, [
            'actual_end_at' => $reservation->actual_end_at->toIso8601String(),
            'overstay_minutes' => $reservation->overstay_minutes,
            'penalty_cents' => $reservation->penalty_cents,
        ]);

        $this->flushDashboardCacheForReservation($reservation);
    }

    /**
     * Modifie une reservation en attente (changement date/segment).
     */
    public function rescheduleReservation(Reservation $reservation, Carbon $date, string $segment): void
    {
        [$start, $end] = BookingPeriod::range($date, $segment);

        if ($start->lte(now())) {
            throw new \InvalidArgumentException('Ce créneau a déjà commencé. Choisissez un créneau à venir.');
        }

        if ($end->toDateString() !== $start->toDateString()) {
            throw new \InvalidArgumentException('La réservation doit rester sur la même journée.');
        }

        $before = [
            'date_debut' => $reservation->date_debut->toIso8601String(),
            'date_fin' => $reservation->date_fin->toIso8601String(),
            'amount_cents' => $reservation->amount_cents,
        ];

        DB::transaction(function () use ($reservation, $start, $end, $segment) {
            $place = Place::query()->lockForUpdate()->findOrFail($reservation->place_id);
            $lockedReservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if ($lockedReservation->statut !== 'en_attente' || $lockedReservation->payment_status !== 'pending') {
                throw new \InvalidArgumentException('Cette réservation ne peut plus être modifiée.');
            }

            $battement = (int) ($lockedReservation->battement_minutes ?? 0);

            if (! $place->isAvailableFor($start, $end)) {
                throw new \InvalidArgumentException('La place n\'est pas disponible sur ce créneau.');
            }

            if (Reservation::overlaps($place->id, $start, $end, $battement, $lockedReservation->id)) {
                throw new \InvalidArgumentException('Le créneau sélectionné chevauche une réservation existante.');
            }

            $lockedReservation->date_debut = $start;
            $lockedReservation->date_fin = $end;
            $lockedReservation->amount_cents = BookingPeriod::amount($place->hourly_price_cents ?? 0, $segment);
            $lockedReservation->save();
            $lockedReservation->payment?->update(['amount_cents' => $lockedReservation->amount_cents]);
        });

        $reservation->refresh();

        $this->auditLogger->log('reschedule', 'reservation', $reservation->id, $before, [
            'date_debut' => $reservation->date_debut->toIso8601String(),
            'date_fin' => $reservation->date_fin->toIso8601String(),
            'amount_cents' => $reservation->amount_cents,
        ]);

        $this->notifyOwnerForPendingValidation($reservation);
        $this->flushDashboardCacheForReservation($reservation);
    }

    private function notifyOwnerForPendingValidation(Reservation $reservation): void
    {
        $reservation->loadMissing(['place.user', 'place.site.user', 'user']);

        $place = $reservation->place;
        if ($place === null) {
            return;
        }

        $owner = $place->user ?? $place->site?->user;
        if (! $owner instanceof User || $owner->id === $reservation->user_id) {
            return;
        }

        $this->notificationDelivery->send($owner, new ReservationOwnerActionRequired($reservation));
    }

    private function flushDashboardCacheForReservation(Reservation $reservation): void
    {
        $reservation->loadMissing(['place.user', 'place.site.user']);

        Cache::forget('dashboard_stats_'.$reservation->user_id);

        $place = $reservation->place;
        $ownerId = $place !== null ? ($place->user_id ?? $place->site?->user_id) : null;

        if (! empty($ownerId)) {
            Cache::forget('dashboard_stats_'.$ownerId);
        }

        Cache::put('admin_stats_version', ((int) Cache::get('admin_stats_version', 1)) + 1);
    }
}
