<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Auth\Permission;
use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Notifications\StaffNotifier;
use App\Filament\Resources\InventoryItems\InventoryItemResource;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Único punto que cambia el stock. Entrada y consumo reciben cantidades positivas;
 * el ajuste recibe la diferencia con signo. El stock nunca queda negativo y, al cruzar
 * el mínimo, se avisa en el momento a quien gestiona el inventario.
 */
class RecordInventoryMovement
{
    public function __construct(private readonly StaffNotifier $notifier) {}

    public function execute(
        InventoryItem $item,
        MovementType $type,
        float $quantity,
        ?User $user = null,
        ?WorkOrder $workOrder = null,
        ?string $note = null,
    ): InventoryMovement {
        if ($type !== MovementType::Adjustment && $quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => __('inventory.errors.quantity_positive')]);
        }
        if ($type === MovementType::Adjustment && $quantity == 0.0) {
            throw ValidationException::withMessages(['quantity' => __('inventory.errors.quantity_zero')]);
        }

        $delta = match ($type) {
            MovementType::In => $quantity,
            MovementType::Consumption => -$quantity,
            MovementType::Adjustment => $quantity,
        };

        return DB::transaction(function () use ($item, $type, $delta, $user, $workOrder, $note): InventoryMovement {
            /** @var InventoryItem $locked */
            $locked = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);
            $before = $locked->stock;
            $after = round($before + $delta, 3);

            if ($after < 0) {
                throw ValidationException::withMessages(['quantity' => __('inventory.errors.insufficient', [
                    'item' => $locked->name, 'stock' => self::format($before), 'unit' => $locked->unit->getLabel(),
                ])]);
            }

            $locked->forceFill(['stock' => $after])->save();
            $item->setRawAttributes($locked->getAttributes(), true);

            $movement = $locked->movements()->create([
                'type' => $type,
                'quantity' => $delta,
                'stock_after' => $after,
                'work_order_id' => $workOrder?->id,
                'user_id' => $user?->id,
                'note' => $note,
            ]);

            if ($before > $locked->min_stock && $after <= $locked->min_stock && $locked->is_active) {
                $this->notifier->notify(Permission::InventoryManage, new StaffAlert(
                    type: 'low_stock',
                    titleKey: 'notifications.low_stock',
                    params: ['item' => $locked->name, 'stock' => self::format($after), 'unit' => $locked->unit->getLabel(), 'min' => self::format($locked->min_stock)],
                    bodyKey: 'notifications.low_stock_body',
                    url: InventoryItemResource::getUrl('edit', ['record' => $locked]),
                    color: 'warning',
                    icon: 'heroicon-o-archive-box-x-mark',
                ));
            }

            return $movement;
        });
    }

    public static function format(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }
}
