<?php

namespace Database\Factories;

use App\Models\Place;
use App\Models\PlaceUnavailability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlaceUnavailability>
 */
class PlaceUnavailabilityFactory extends Factory
{
    protected $model = PlaceUnavailability::class;

    public function definition(): array
    {
        return [
            'place_id' => Place::factory(),
            'date' => now()->addDays(fake()->numberBetween(1, 30)),
            'start_time' => null,
            'end_time' => null,
            'reason' => fake()->sentence(4),
        ];
    }

    /**
     * Exception sur un créneau partiel.
     */
    public function partial(string $start = '10:00', string $end = '14:00'): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    /**
     * Exception journée complète.
     */
    public function fullDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => null,
            'end_time' => null,
        ]);
    }
}
