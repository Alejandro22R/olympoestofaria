<?php

namespace App\Models;

use App\Domain\Inventory\Enums\MovementType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property MovementType $type
 */
#[Fillable(['inventory_item_id', 'type', 'quantity', 'stock_after', 'work_order_id', 'user_id', 'note'])]
class InventoryMovement extends Model
{
    protected function casts(): array
    {
        return [
            'type' => MovementType::class,
            'quantity' => 'float',
            'stock_after' => 'float',
        ];
    }

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * @return BelongsTo<WorkOrder, $this>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
