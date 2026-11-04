<?php

namespace App\Domain\WorkOrders\Actions;

use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Domain\WorkOrders\Events\WorkOrderMoved;
use App\Models\Branch;
use App\Models\Inspection;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Abre una orden de trabajo, normalmente desde el check-in (hereda vehículo, cita y entrega prometida).
 */
class CreateWorkOrder
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Inspection $inspection = null, ?User $user = null): WorkOrder
    {
        if ($inspection !== null) {
            if (WorkOrder::query()->where('inspection_id', $inspection->id)->exists()) {
                throw ValidationException::withMessages(['inspection' => __('work_orders.errors.already_exists')]);
            }

            $data['vehicle_id'] = $inspection->vehicle_id;
            $data['appointment_id'] = $inspection->appointment_id;
            $data['inspection_id'] = $inspection->id;
            $data['due_at'] ??= $inspection->appointment->promised_delivery_at;
        }

        $stage = WorkflowStage::firstFor(WorkOrderStatus::Received);
        if ($stage === null) {
            throw ValidationException::withMessages(['stage' => __('work_orders.errors.no_initial_stage')]);
        }

        $workOrder = DB::transaction(function () use ($data, $stage, $user): WorkOrder {
            $workOrder = new WorkOrder($data);
            $workOrder->status = WorkOrderStatus::Received;
            $workOrder->workflow_stage_id = $stage->id;
            $workOrder->stage_changed_at = now();
            $workOrder->branch_id ??= Branch::default()?->id;
            $workOrder->save();

            $workOrder->updates()->create([
                'workflow_stage_id' => $stage->id,
                'status' => WorkOrderStatus::Received,
                'visible_to_customer' => $stage->visible_to_customer,
                'user_id' => $user?->id,
            ]);

            return $workOrder;
        });

        WorkOrderMoved::dispatch($workOrder->id, $stage->id, $workOrder->status->value);

        return $workOrder;
    }
}
