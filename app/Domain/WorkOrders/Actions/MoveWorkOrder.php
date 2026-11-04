<?php

namespace App\Domain\WorkOrders\Actions;

use App\Domain\Inventory\Actions\ConsumeWorkOrderMaterials;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Domain\WorkOrders\Events\WorkOrderMoved;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mueve una orden a una etapa del tablero. El estado de la orden sigue al de la etapa
 * y respeta la máquina de estados. Al entrar en producción se consume el material.
 * Cada movimiento queda en el historial y se emite en tiempo real.
 */
class MoveWorkOrder
{
    public function __construct(private readonly ConsumeWorkOrderMaterials $consume) {}

    /**
     * @param  list<string>  $photos
     */
    public function execute(WorkOrder $workOrder, WorkflowStage $stage, ?User $user = null, ?string $note = null, array $photos = []): WorkOrder
    {
        if (! $stage->is_active) {
            throw ValidationException::withMessages(['stage' => __('work_orders.errors.inactive_stage')]);
        }

        $from = $workOrder->status;
        $to = $stage->status;

        if ($from !== $to && ! $from->canTransitionTo($to)) {
            throw ValidationException::withMessages(['stage' => __('work_orders.errors.invalid_transition', [
                'from' => $from->getLabel(), 'to' => $to->getLabel(),
            ])]);
        }

        if ($workOrder->workflow_stage_id === $stage->id && $note === null && $photos === []) {
            return $workOrder;
        }

        DB::transaction(function () use ($workOrder, $stage, $user, $note, $photos, $from, $to): void {
            if ($from === WorkOrderStatus::Received && $to === WorkOrderStatus::InProduction) {
                $this->consume->execute($workOrder, $user);
                $workOrder->started_at ??= now();
            }

            if ($to === WorkOrderStatus::Ready) {
                $workOrder->finished_at = now();
            } elseif ($from === WorkOrderStatus::Ready) {
                $workOrder->finished_at = null; // retrabajo
            }

            if ($workOrder->workflow_stage_id !== $stage->id) {
                $workOrder->stage_changed_at = now();
            }

            $workOrder->status = $to;
            $workOrder->workflow_stage_id = $stage->id;
            $workOrder->save();

            $workOrder->updates()->create([
                'workflow_stage_id' => $stage->id,
                'status' => $to,
                'note' => $note,
                'photos' => $photos,
                'visible_to_customer' => $stage->visible_to_customer,
                'user_id' => $user?->id,
            ]);
        });

        WorkOrderMoved::dispatch($workOrder->id, $stage->id, $to->value);

        return $workOrder;
    }
}
