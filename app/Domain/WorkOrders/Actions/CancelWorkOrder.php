<?php

namespace App\Domain\WorkOrders\Actions;

use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Domain\WorkOrders\Events\WorkOrderMoved;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cancela una orden (las órdenes no se borran). El material ya consumido no vuelve
 * al stock automáticamente: si se recupera, se registra una entrada manual.
 */
class CancelWorkOrder
{
    public function execute(WorkOrder $workOrder, string $reason, ?User $user = null): WorkOrder
    {
        if (! $workOrder->status->canTransitionTo(WorkOrderStatus::Cancelled)) {
            throw ValidationException::withMessages(['reason' => __('work_orders.errors.invalid_transition', [
                'from' => $workOrder->status->getLabel(), 'to' => WorkOrderStatus::Cancelled->getLabel(),
            ])]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => __('validation.required', ['attribute' => __('work_orders.fields.cancel_reason')])]);
        }

        DB::transaction(function () use ($workOrder, $reason, $user): void {
            $workOrder->status = WorkOrderStatus::Cancelled;
            $workOrder->save();

            $workOrder->updates()->create([
                'workflow_stage_id' => $workOrder->workflow_stage_id,
                'status' => WorkOrderStatus::Cancelled,
                'note' => $reason,
                'visible_to_customer' => false,
                'user_id' => $user?->id,
            ]);
        });

        WorkOrderMoved::dispatch($workOrder->id, $workOrder->workflow_stage_id, WorkOrderStatus::Cancelled->value);

        return $workOrder;
    }
}
