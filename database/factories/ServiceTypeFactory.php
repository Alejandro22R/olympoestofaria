<?php

namespace Database\Factories;

use App\Domain\Catalog\Enums\PricingUnit;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceType>
 */
class ServiceTypeFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => str($name)->slug()->toString(),
            'name' => ['pt_BR' => $name, 'es' => $name],
            'base_price_cents' => fake()->numberBetween(10_000, 300_000),
            'pricing_unit' => PricingUnit::Vehicle,
            'estimated_hours' => fake()->numberBetween(1, 20),
            'sort' => 0,
            'is_active' => true,
        ];
    }
}
