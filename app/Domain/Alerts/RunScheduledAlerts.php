<?php

namespace App\Domain\Alerts;

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Appointments\Services\AgendaService;
use App\Domain\Auth\Permission;
use App\Domain\Inventory\Actions\RecordInventoryMovement;
use App\Domain\Notifications\StaffNotifier;
use App\Domain\Settings\ProductionSettings;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\InventoryItems\InventoryItemResource;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Models\AlertLog;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\WorkOrder;
use App\Notifications\StaffAlert;
use Carbon\CarbonImmutable;

/**
 * Alertas internas programadas (cada hora). Cada condición se avisa una sola vez por clave
 * (AlertLog) y los umbrales salen de Ajustes > Producción.
 *
 * @return array<string, int> cantidad de avisos por tipo
 */
class RunScheduledAlerts
{
    public function __construct(
        private readonly StaffNotifier $notifier,
        private readonly ProductionSettings $settings,
        private readonly AgendaService $agenda,
    ) {}

    /**
     * @return array<string, int>
     */
    public function execute(): array
    {
        return [
            'low_stock' => $this->lowStock(),
            'late' => $this->lateOrders(),
            'due_soon' => $this->dueSoon(),
            'stalled' => $this->stalled(),
            'unconfirmed' => $this->unconfirmedAppointments(),
            ...app(CommercialAlerts::class)->execute(),
            ...app(TechnicalAlerts::class)->execute(),
            'escalated' => app(EscalateAlerts::class)->execute(),
        ];
    }

    private function today(): string
    {
        return CarbonImmutable::now($this->agenda->timezone())->toDateString();
    }

    private function lowStock(): int
    {
        $count = 0;
        foreach (InventoryItem::query()->belowMinimum()->get() as $item) {
            $key = "low_stock:{$item->id}:{$this->today()}";
            if (! AlertLog::claim($key, 'low_stock')) {
                continue;
            }
            $this->notifier->notify(Permission::InventoryManage, new StaffAlert(
                alertKey: $key,
                type: 'low_stock',
                titleKey: 'notifications.low_stock',
                params: ['item' => $item->name, 'stock' => RecordInventoryMovement::format($item->stock), 'unit' => $item->unit->getLabel(), 'min' => RecordInventoryMovement::format($item->min_stock)],
                bodyKey: 'notifications.low_stock_body',
                url: InventoryItemResource::getUrl('edit', ['record' => $item]),
                color: 'warning',
                icon: 'heroicon-o-archive-box-x-mark',
            ));
            $count++;
        }

        return $count;
    }

    private function lateOrders(): int
    {
        $count = 0;
        $orders = WorkOrder::query()->with('vehicle')
            ->whereIn('status', WorkOrderStatus::onBoard())
            ->whereNotNull('due_at')->where('due_at', '<', now())
            ->get();

        foreach ($orders as $order) {
            $key = "late:{$order->id}:{$this->today()}";
            if (! AlertLog::claim($key, 'late')) {
                continue;
            }
            $this->orderAlert($order, 'work_order_late', 'danger', Permission::WorkOrdersManage, $key);
            $count++;
        }

        return $count;
    }

    private function dueSoon(): int
    {
        $count = 0;
        $orders = WorkOrder::query()->with('vehicle')
            ->whereIn('status', [WorkOrderStatus::Received, WorkOrderStatus::InProduction])
            ->whereNotNull('due_at')
            ->where('due_at', '>=', now())
            ->where('due_at', '<=', now()->addHours($this->settings->late_warning_hours))
            ->get();

        foreach ($orders as $order) {
            if (! AlertLog::claim("due_soon:{$order->id}:{$order->due_at?->timestamp}", 'due_soon')) {
                continue;
            }
            $this->orderAlert($order, 'work_order_due_soon', 'warning', Permission::WorkOrdersProgress);
            $count++;
        }

        return $count;
    }

    private function stalled(): int
    {
        $count = 0;
        $hours = $this->settings->stalled_after_hours;
        $orders = WorkOrder::query()->with(['vehicle', 'stage'])
            ->whereIn('status', WorkOrderStatus::onBoard())
            ->whereNotNull('stage_changed_at')
            ->where('stage_changed_at', '<', now()->subHours($hours))
            ->get();

        foreach ($orders as $order) {
            if (! AlertLog::claim("stalled:{$order->id}:{$order->stage_changed_at?->timestamp}", 'stalled')) {
                continue;
            }
            $this->notifier->notify(Permission::WorkOrdersManage, new StaffAlert(
                type: 'work_order_stalled',
                titleKey: 'notifications.work_order_stalled',
                params: ['number' => $order->number(), 'plate' => $order->vehicle->formattedPlate(), 'hours' => $hours, 'stage' => (string) $order->stage?->name],
                bodyKey: 'notifications.work_order_stalled_body',
                url: WorkOrderResource::getUrl('edit', ['record' => $order]),
                color: 'warning',
                icon: 'heroicon-o-pause-circle',
            ));
            $count++;
        }

        return $count;
    }

    private function unconfirmedAppointments(): int
    {
        if (! $this->settings->unconfirmed_appointment_alert) {
            return 0;
        }

        $tz = $this->agenda->timezone();
        $tomorrow = CarbonImmutable::now($tz)->addDay()->startOfDay();
        $count = 0;

        $appointments = Appointment::query()->with('vehicle')
            ->where('status', AppointmentStatus::Scheduled)
            ->where('scheduled_at', '>=', $tomorrow->utc())
            ->where('scheduled_at', '<', $tomorrow->addDay()->utc())
            ->get();

        foreach ($appointments as $appointment) {
            $key = "unconfirmed:{$appointment->id}:{$appointment->scheduled_at->timestamp}";
            if (! AlertLog::claim($key, 'unconfirmed')) {
                continue;
            }
            $this->notifier->notify(Permission::AppointmentsManage, new StaffAlert(
                alertKey: $key,
                type: 'unconfirmed_appointment',
                titleKey: 'notifications.unconfirmed_appointment',
                params: ['plate' => $appointment->vehicle->formattedPlate(), 'time' => $appointment->scheduled_at->setTimezone($tz)->format('H:i')],
                bodyKey: 'notifications.unconfirmed_appointment_body',
                url: AppointmentResource::getUrl('edit', ['record' => $appointment]),
                color: 'warning',
                icon: 'heroicon-o-calendar',
            ));
            $count++;
        }

        return $count;
    }

    private function orderAlert(WorkOrder $order, string $key, string $color, Permission $permission, ?string $alertKey = null): void
    {
        $this->notifier->notify($permission, new StaffAlert(
            alertKey: $alertKey,
            type: $key,
            titleKey: 'notifications.'.$key,
            params: [
                'number' => $order->number(),
                'plate' => $order->vehicle->formattedPlate(),
                'date' => $order->due_at?->setTimezone($this->agenda->timezone())->format('d/m H:i') ?? '—',
            ],
            bodyKey: 'notifications.work_order_due_body',
            url: WorkOrderResource::getUrl('edit', ['record' => $order]),
            color: $color,
            icon: 'heroicon-o-clock',
        ));
    }
}
