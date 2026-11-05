<?php

namespace App\Domain\Deliveries\Actions;

use App\Domain\Deliveries\Enums\MarkOutcome;
use App\Domain\Reports\SendReport;
use App\Domain\Settings\GeneralSettings;
use App\Domain\Settings\NotificationSettings;
use App\Domain\Settings\WarrantySettings;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Domain\WorkOrders\Events\WorkOrderMoved;
use App\Models\Delivery;
use App\Models\User;
use App\Models\WarrantyTerm;
use App\Models\WorkOrder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Entrega del vehículo: solo desde "lista". Compara cada daño del check-in con su estado final,
 * crea la garantía (plazo por servicio, Ajustes > Garantías), cierra la orden y genera el acta.
 * Sin firma del cliente: el acta se envía con registro de envío, igual que en el check-in.
 */
class RegisterDelivery
{
    public function __construct(
        private readonly WarrantySettings $warranty,
        private readonly GeneralSettings $general,
        private readonly GenerateDeliveryReport $report,
    ) {}

    /**
     * @param  array{mileage_km?: mixed, marks_status?: array<int|string, mixed>, notes?: ?string}  $data
     */
    public function execute(WorkOrder $workOrder, array $data, ?User $user = null): Delivery
    {
        if (! $workOrder->status->canTransitionTo(WorkOrderStatus::Delivered)) {
            throw ValidationException::withMessages(['work_order' => __('deliveries.errors.not_ready', ['status' => $workOrder->status->getLabel()])]);
        }

        $markIds = $workOrder->inspection?->marks()->pluck('id')->all() ?? [];
        $statuses = [];
        foreach ($markIds as $id) {
            $value = $data['marks_status'][$id] ?? $data['marks_status'][(string) $id] ?? null;
            $outcome = $value instanceof MarkOutcome ? $value : MarkOutcome::tryFrom((string) $value);
            if ($outcome === null) {
                throw ValidationException::withMessages(["marks_status.{$id}" => __('deliveries.errors.mark_outcome_required')]);
            }
            $statuses[(string) $id] = $outcome->value;
        }

        $mileage = $data['mileage_km'] ?? null;
        if ($mileage !== null && $mileage !== '' && (! is_numeric($mileage) || (int) $mileage < 0)) {
            throw ValidationException::withMessages(['mileage_km' => __('inspections.errors.mileage_invalid')]);
        }
        $mileage = $mileage === null || $mileage === '' ? null : (int) $mileage;

        $delivery = DB::transaction(function () use ($workOrder, $statuses, $mileage, $data, $user): Delivery {
            $delivery = $workOrder->delivery()->create([
                'mileage_km' => $mileage,
                'marks_status' => $statuses,
                'notes' => $data['notes'] ?? null,
                'delivered_by' => $user?->id,
                'delivered_at' => now(),
            ]);

            $months = $this->warrantyMonths($workOrder);
            $start = CarbonImmutable::now($this->general->timezone)->startOfDay();
            $workOrder->warranty()->create([
                'starts_at' => $start->toDateString(),
                'ends_at' => $start->addMonths($months)->toDateString(),
                'conditions' => $this->warranty->conditionsText($workOrder->vehicle->customer->locale),
            ]);

            $workOrder->status = WorkOrderStatus::Delivered;
            $workOrder->delivered_at = now();
            $workOrder->save();

            $workOrder->updates()->create([
                'workflow_stage_id' => $workOrder->workflow_stage_id,
                'status' => WorkOrderStatus::Delivered,
                'visible_to_customer' => true,
                'user_id' => $user?->id,
            ]);

            if ($mileage !== null) {
                $workOrder->vehicle->update(['mileage_km' => $mileage]);
            }

            return $delivery;
        });

        WorkOrderMoved::dispatch($workOrder->id, $workOrder->workflow_stage_id, WorkOrderStatus::Delivered->value);

        $delivery = $this->report->execute($delivery);

        if (app(NotificationSettings::class)->auto_send_delivery_report) {
            app(SendReport::class)->automatic($delivery);
        }

        return $delivery;
    }

    /**
     * Mayor plazo entre los servicios de la orden; si ninguno tiene plazo propio, el predeterminado.
     */
    public function warrantyMonths(WorkOrder $workOrder): int
    {
        $serviceIds = $workOrder->items()->whereNotNull('service_type_id')->pluck('service_type_id');
        $max = WarrantyTerm::query()->whereIn('service_type_id', $serviceIds)->max('months');

        return $max !== null ? (int) $max : $this->warranty->default_months;
    }
}
