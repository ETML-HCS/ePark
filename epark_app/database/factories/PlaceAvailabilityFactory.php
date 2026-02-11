<?php

namespace Database\Factories;

use App\Models\Place;
use App\Models\PlaceAvailability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlaceAvailability>
 */
class PlaceAvailabilityFactory extends Factory
{
    protected $model = PlaceAvailability::class;

    public function definition(): array
    {
        return [
            'place_id' => Place::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'start_time' => '08:00',
            'end_time' => '12:00',
        ];
    }

    /**
     * Créneau bloqué spécifique.
     */
    public function forDay(int $day, string $start = '08:00', string $end = '18:00'): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }
}
