<?php

namespace Database\Factories;

use App\Domain\Catalog\Enums\MaterialType;
use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'type' => MaterialType::Leather,
            'name' => ['pt_BR' => $name, 'es' => $name],
            'supplier' => fake()->company(),
            'cost_per_m2_cents' => 10_000,
            'price_per_m2_cents' => 20_000,
            'is_active' => true,
        ];
    }
}
