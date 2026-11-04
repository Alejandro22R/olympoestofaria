<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'plate' => fake()->unique()->bothify('???#?##'),
            'brand' => fake()->randomElement(['Volkswagen', 'Chevrolet', 'Fiat', 'Toyota']),
            'model' => fake()->randomElement(['Gol', 'Onix', 'Argo', 'Corolla']),
            'year' => fake()->numberBetween(1990, (int) date('Y')),
            'color' => fake()->safeColorName(),
            'mileage_km' => fake()->numberBetween(0, 250_000),
        ];
    }

    public function configure(): static
    {
        return $this->state(fn (array $attributes) => ['plate' => strtoupper($attributes['plate'])]);
    }
}
