<?php

namespace Database\Factories;

use App\Domain\Inventory\Enums\InventoryUnit;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Couro '.fake()->colorName(),
            'unit' => InventoryUnit::SquareMeter,
            'min_stock' => 5,
            'unit_cost_cents' => 18000,
            'is_active' => true,
        ];
    }

    public function withStock(float $stock): static
    {
        return $this->afterCreating(function (InventoryItem $item) use ($stock): void {
            $item->forceFill(['stock' => $stock])->save();
        });
    }
}
