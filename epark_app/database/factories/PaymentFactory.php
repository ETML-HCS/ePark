<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'amount_cents' => 500,
            'provider' => 'manual',
            'provider_status' => 'pending',
            'provider_ref' => null,
        ];
    }

    /**
     * Paiement réussi.
     */
    public function succeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_status' => 'succeeded',
            'provider_ref' => 'manual-'.now()->timestamp,
        ]);
    }

    /**
     * Paiement remboursé.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_status' => 'refunded',
        ]);
    }
}
