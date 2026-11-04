<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Servicio/material de la orden. quantity = material a consumir del inventario.
 */
#[Fillable(['work_order_id', 'service_type_id', 'inventory_item_id', 'description', 'quantity', 'hours', 'price_cents'])]
class WorkOrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'hours' => 'float',
            'price_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<WorkOrder, $this>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * @return BelongsTo<ServiceType, $this>
     */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
