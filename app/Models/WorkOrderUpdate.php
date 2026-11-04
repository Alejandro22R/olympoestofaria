<?php

namespace App\Models;

use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historial de la orden (cambios de etapa, notas y fotos de avance).
 *
 * @property WorkOrderStatus $status
 * @property list<string> $photos
 */
#[Fillable(['work_order_id', 'workflow_stage_id', 'status', 'note', 'photos', 'visible_to_customer', 'user_id'])]
class WorkOrderUpdate extends Model
{
    protected function casts(): array
    {
        return [
            'status' => WorkOrderStatus::class,
            'photos' => 'array',
            'visible_to_customer' => 'boolean',
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
     * @return BelongsTo<WorkflowStage, $this>
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(WorkflowStage::class, 'workflow_stage_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
