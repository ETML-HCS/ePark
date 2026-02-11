<?php

namespace Database\Factories;

use App\Models\Place;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $date = Carbon::tomorrow();
        $start = $date->copy()->setTime(8, 0);
        $end = $date->copy()->setTime(12, 0);

        return [
            'user_id' => User::factory(),
            'place_id' => Place::factory(),
            'date_debut' => $start,
            'date_fin' => $end,
            'statut' => 'en_attente',
            'battement_minutes' => 15,
            'amount_cents' => 500,
            'payment_status' => 'pending',
            'paiement_effectue' => false,
        ];
    }

    /**
     * Réservation confirmée.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'confirmée',
            'payment_status' => 'paid',
            'paiement_effectue' => true,
        ]);
    }

    /**
     * Réservation annulée.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'annulée',
        ]);
    }

    /**
     * Réservation payée.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
            'paiement_effectue' => true,
        ]);
    }

    /**
     * Réservation pour un créneau spécifique.
     */
    public function forSlot(Carbon $start, Carbon $end): static
    {
        return $this->state(fn (array $attributes) => [
            'date_debut' => $start,
            'date_fin' => $end,
        ]);
    }
}
