<?php

namespace App\Domain\WorkOrders\Actions;

use App\Domain\Quotes\Enums\QuoteStatus;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Domain\WorkOrders\Events\WorkOrderMoved;
use App\Models\Branch;
use App\Models\Inspection;
use App\Models\Quote;
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
     * Orden a partir de una cotización aprobada: hereda vehículo, cita, entrega prometida e ítems.
     */
    public function fromQuote(Quote $quote, ?User $user = null): WorkOrder
    {
        if ($quote->status !== QuoteStatus::Approved || $quote->vehicle_id === null) {
            throw ValidationException::withMessages(['quote' => __('quotes.errors.not_approved_vehicle')]);
        }

        return $this->execute([
            'vehicle_id' => $quote->vehicle_id,
            'appointment_id' => $quote->appointment_id,
            'quote_id' => $quote->id,
            'due_at' => $quote->appointment?->promised_delivery_at,
        ], null, $user);
    }

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
            // Si la cita viene de una cotización aprobada, la orden la hereda (y sus ítems).
            $data['quote_id'] ??= Quote::query()->where('appointment_id', $inspection->appointment_id)
                ->where('status', QuoteStatus::Approved)->value('id');
        }

        if (($data['quote_id'] ?? null) !== null && WorkOrder::query()->where('quote_id', $data['quote_id'])->exists()) {
            throw ValidationException::withMessages(['quote' => __('work_orders.errors.already_exists')]);
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

            $quote = $workOrder->quote_id !== null ? Quote::query()->with('items')->find($workOrder->quote_id) : null;
            foreach ($quote->items ?? [] as $item) {
                $workOrder->items()->create([
                    'service_type_id' => $item->service_type_id,
                    'description' => $item->description,
                    'price_cents' => $item->total_cents,
                    'hours' => 0,
                    'quantity' => 0,
                ]);
            }

            return $workOrder;
        });

        WorkOrderMoved::dispatch($workOrder->id, $stage->id, $workOrder->status->value);

        return $workOrder;
    }
}
