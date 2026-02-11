<?php

namespace Database\Factories;

use App\Models\Place;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Place>
 */
class PlaceFactory extends Factory
{
    protected $model = Place::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'site_id' => Site::factory(),
            'nom' => 'Place ' . fake()->bothify('??-##'),
            'type' => fake()->randomElement(['voiture', 'moto', 'vélo']),
            'dimensions_json' => ['longueur' => 5, 'largeur' => 2.5],
            'equipments_json' => ['borne_electrique' => false],
            'hourly_price_cents' => fake()->randomElement([200, 350, 500, 750, 1000]),
            'is_active' => true,
            'disponible' => true,
            'availability_start_date' => null,
            'availability_end_date' => null,
        ];
    }

    /**
     * Place inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Place avec plage de disponibilité limitée.
     */
    public function withDateRange(\DateTimeInterface $start, \DateTimeInterface $end): static
    {
        return $this->state(fn (array $attributes) => [
            'availability_start_date' => $start,
            'availability_end_date' => $end,
        ]);
    }
}
