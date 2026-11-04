<?php

namespace App\Models;

use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Models\Contracts\Translatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Etapa del tablero de producción. Cada etapa pertenece a un estado de la orden
 * (recibida, en producción o lista); mover una tarjeta aplica la máquina de estados.
 *
 * @property WorkOrderStatus $status
 */
#[Fillable(['name', 'status', 'color_hex', 'sort', 'visible_to_customer', 'notifies_customer', 'is_active'])]
class WorkflowStage extends Model implements Translatable
{
    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'status' => WorkOrderStatus::class,
            'sort' => 'integer',
            'visible_to_customer' => 'boolean',
            'notifies_customer' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<WorkflowStage>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->where('is_active', true)->orderByRaw("CASE status WHEN 'received' THEN 0 WHEN 'in_production' THEN 1 WHEN 'ready' THEN 2 ELSE 3 END")->orderBy('sort');
    }

    public static function firstFor(WorkOrderStatus $status): ?self
    {
        return static::query()->ordered()->where('status', $status->value)->first();
    }

    /**
     * @return HasMany<WorkOrder, $this>
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
