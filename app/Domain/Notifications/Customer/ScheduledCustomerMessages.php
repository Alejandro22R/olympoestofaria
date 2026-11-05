<?php

namespace App\Domain\Notifications\Customer;

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Settings\NotificationSettings;
use App\Domain\Settings\WarrantySettings;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\Warranty;
use App\Models\WorkOrder;

/**
 * Avisos programados al cliente: recordatorios de cita (ej. 24 h y 2 h antes), revisión de garantía
 * y pedido de reseña. Cada uno sale una sola vez (clave de idempotencia).
 *
 * @return array<string, int>
 */
class ScheduledCustomerMessages
{
    public function __construct(
        private readonly NotificationSettings $settings,
        private readonly WarrantySettings $warranty,
        private readonly CustomerEvents $events,
    ) {}

    /**
     * @return array<string, int>
     */
    public function execute(): array
    {
        return [
            'reminders' => $this->reminders(),
            'warranty' => $this->warranties(),
            'reviews' => $this->reviews(),
        ];
    }

    private function reminders(): int
    {
        $hours = $this->settings->reminder_hours;
        sort($hours);
        if ($hours === []) {
            return 0;
        }

        $count = 0;
        $appointments = Appointment::query()->with('vehicle.customer')
            ->whereIn('status', [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed])
            ->where('scheduled_at', '>', now())->where('scheduled_at', '<=', now()->addHours(max($hours)))->get();

        foreach ($appointments as $appointment) {
            // Se envía solo el recordatorio más cercano que corresponda (no 24 h y 2 h a la vez).
            $minutesLeft = now()->diffInMinutes($appointment->scheduled_at, false);
            $window = collect($hours)->first(fn (int $h) => $minutesLeft <= $h * 60);
            if ($window !== null) {
                $this->events->appointment($appointment, MessageEvent::AppointmentReminder, "h{$window}:".$appointment->scheduled_at->timestamp);
                $count++;
            }
        }

        return $count;
    }

    private function warranties(): int
    {
        if ($this->warranty->review_reminder_days <= 0) {
            return 0;
        }

        $count = 0;
        $due = Warranty::query()->with('workOrder.vehicle.customer', 'workOrder.warranty')->whereNull('reminder_sent_at')
            ->whereDate('ends_at', '<=', now()->addDays($this->warranty->review_reminder_days)->toDateString())
            ->whereDate('ends_at', '>=', now()->toDateString())->get();

        foreach ($due as $warranty) {
            $this->events->warrantyReview($warranty->workOrder);
            $warranty->forceFill(['reminder_sent_at' => now()])->save();
            $count++;
        }

        return $count;
    }

    private function reviews(): int
    {
        $days = $this->settings->review_request_days;
        if ($days <= 0) {
            return 0;
        }

        $count = 0;
        $orders = WorkOrder::query()->with('vehicle.customer')->where('status', WorkOrderStatus::Delivered)
            ->whereDoesntHave('review')
            ->where('delivered_at', '<=', now()->subDays($days))
            ->where('delivered_at', '>', now()->subDays($days + 14))->get();

        foreach ($orders as $order) {
            $this->events->reviewRequest($order);
            $count++;
        }

        return $count;
    }
}
