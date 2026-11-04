<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Enums\MovementType;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Consumo automático del material de la orden al iniciar la producción (una sola vez).
 * Si falta material, avisa qué falta y no consume nada.
 */
class ConsumeWorkOrderMaterials
{
    public function __construct(private readonly RecordInventoryMovement $record) {}

    public function execute(WorkOrder $workOrder, ?User $user = null): void
    {
        if ($workOrder->materials_consumed_at !== null) {
            return;
        }

        $needed = $workOrder->items()
            ->whereNotNull('inventory_item_id')
            ->where('quantity', '>', 0)
            ->selectRaw('inventory_item_id, SUM(quantity) as total')
            ->groupBy('inventory_item_id')
            ->pluck('total', 'inventory_item_id');

        DB::transaction(function () use ($workOrder, $user, $needed): void {
            $items = InventoryItem::query()->whereIn('id', $needed->keys())->lockForUpdate()->get()->keyBy('id');

            $missing = [];
            foreach ($needed as $itemId => $qty) {
                $item = $items[$itemId];
                if ($item->stock < (float) $qty) {
                    $missing[] = __('inventory.errors.missing_line', [
                        'item' => $item->name,
                        'needed' => RecordInventoryMovement::format((float) $qty),
                        'stock' => RecordInventoryMovement::format($item->stock),
                        'unit' => $item->unit->getLabel(),
                    ]);
                }
            }

            if ($missing !== []) {
                throw ValidationException::withMessages(['materials' => __('inventory.errors.missing_for_order').' '.implode('; ', $missing)]);
            }

            foreach ($needed as $itemId => $qty) {
                $this->record->execute($items[$itemId], MovementType::Consumption, (float) $qty, $user, $workOrder, $workOrder->number());
            }

            $workOrder->forceFill(['materials_consumed_at' => now()])->save();
        });
    }

    /**
     * Material faltante sin consumir nada (para avisar al armar la orden).
     *
     * @return list<string>
     */
    public function shortages(WorkOrder $workOrder): array
    {
        if ($workOrder->materials_consumed_at !== null) {
            return [];
        }

        $lines = [];
        $needed = $workOrder->items()->whereNotNull('inventory_item_id')->with('inventoryItem')->get()
            ->groupBy('inventory_item_id');

        foreach ($needed as $rows) {
            $item = $rows->first()?->inventoryItem;
            $qty = (float) $rows->sum('quantity');
            if ($item !== null && $item->stock < $qty) {
                $lines[] = __('inventory.errors.missing_line', [
                    'item' => $item->name,
                    'needed' => RecordInventoryMovement::format($qty),
                    'stock' => RecordInventoryMovement::format($item->stock),
                    'unit' => $item->unit->getLabel(),
                ]);
            }
        }

        return $lines;
    }
}
