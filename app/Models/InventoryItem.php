<?php

namespace App\Models;

use App\Domain\Inventory\Enums\InventoryUnit;
use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * El stock solo cambia con movimientos (RecordInventoryMovement); no es editable a mano.
 *
 * @property InventoryUnit $unit
 */
#[Fillable(['branch_id', 'material_variant_id', 'name', 'sku', 'unit', 'min_stock', 'unit_cost_cents', 'is_active'])]
class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'unit' => InventoryUnit::class,
            'stock' => 'float',
            'min_stock' => 'float',
            'unit_cost_cents' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function isBelowMinimum(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    /**
     * @param  Builder<InventoryItem>  $query
     */
    public function scopeBelowMinimum(Builder $query): void
    {
        $query->where('is_active', true)->whereColumn('stock', '<=', 'min_stock');
    }

    /**
     * @return BelongsTo<MaterialVariant, $this>
     */
    public function materialVariant(): BelongsTo
    {
        return $this->belongsTo(MaterialVariant::class);
    }

    /**
     * @return HasMany<InventoryMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->latest('id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'min_stock', 'unit_cost_cents', 'is_active'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
