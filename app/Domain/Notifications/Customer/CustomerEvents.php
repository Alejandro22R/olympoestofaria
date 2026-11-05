<?php

namespace App\Domain\Notifications\Customer;

use App\Domain\Customers\Access\TrackingLink;
use App\Domain\Payments\BillingSummary;
use App\Domain\Settings\GeneralSettings;
use App\Domain\Settings\NotificationSettings;
use App\Filament\Support\Money;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Quote;
use App\Models\WorkflowStage;
use App\Models\WorkOrder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\URL;

/**
 * Traduce hechos del dominio en avisos al cliente (variables, enlaces y clave de idempotencia).
 * Así las Actions solo dicen "pasó X" y no conocen canales ni plantillas.
 */
class CustomerEvents
{
    public function __construct(
        private readonly CustomerNotifier $notifier,
        private readonly GeneralSettings $general,
        private readonly NotificationSettings $settings,
        private readonly TrackingLink $tracking,
    ) {}

    public function projectReceived(Project $project): void
    {
        $this->notifier->send($project->customer, MessageEvent::ProjectReceived, [
            'numero' => $project->number(),
            'veiculo' => $project->vehicleLabel(),
        ], "project:{$project->id}:received");
    }

    public function quoteApproved(Quote $quote): void
    {
        $this->notifier->send($quote->customer, MessageEvent::QuoteApproved, [
            'numero' => $quote->number(),
            'total' => Money::format($quote->total_cents),
            'sinal' => Money::format($quote->deposit_cents),
            'link' => $quote->customerReportUrl(30),
        ], "quote:{$quote->id}:approved");
    }

    public function quoteReminder(Quote $quote): void
    {
        $this->notifier->send($quote->customer, MessageEvent::QuoteReminder, [
            'numero' => $quote->number(),
            'veiculo' => (string) $quote->vehicle_label,
            'total' => Money::format($quote->total_cents),
            'validade' => $quote->valid_until?->format('d/m/Y'),
            'link' => $quote->customerReportUrl(7),
        ], "quote:{$quote->id}:reminder");
    }

    public function payment(Payment $payment, bool $approved): void
    {
        $link = $payment->workOrder !== null ? $this->tracking->url($payment->workOrder)
            : ($payment->quote?->customerReportUrl(30));

        $this->notifier->send($payment->customer, $approved ? MessageEvent::PaymentApproved : MessageEvent::PaymentRejected, [
            'valor' => Money::format($payment->amount_cents),
            'tipo' => $payment->type->getLabel(),
            'link' => $link,
        ], "payment:{$payment->id}:".($approved ? 'approved' : 'rejected'));
    }

    public function appointment(Appointment $appointment, MessageEvent $event, string $suffix = ''): void
    {
        $local = CarbonImmutable::instance($appointment->scheduled_at)->setTimezone($this->general->timezone);

        $this->notifier->send($appointment->vehicle->customer, $event, [
            'placa' => $appointment->vehicle->formattedPlate(),
            'data' => $local->format('d/m/Y'),
            'hora' => $local->format('H:i'),
            'endereco' => trim($this->general->city.' '.($this->general->maps_url ?? '')),
        ], "appointment:{$appointment->id}:{$event->value}:".($suffix ?: $local->format('YmdHi')));
    }

    public function stageChanged(WorkOrder $order, WorkflowStage $stage, int $updateId): void
    {
        $this->notifier->send($order->vehicle->customer, MessageEvent::WorkOrderStage, [
            'placa' => $order->vehicle->formattedPlate(),
            'etapa' => (string) $stage->getTranslation('name', $order->vehicle->customer->locale),
            'link' => $this->tracking->url($order),
        ], "work_order:{$order->id}:stage:{$updateId}");
    }

    public function ready(WorkOrder $order): void
    {
        $this->notifier->send($order->vehicle->customer, MessageEvent::WorkOrderReady, [
            'placa' => $order->vehicle->formattedPlate(),
            'saldo' => Money::format(BillingSummary::forWorkOrder($order)->outstandingCents()),
            'link' => $this->tracking->url($order),
        ], "work_order:{$order->id}:ready:".$order->finished_at?->timestamp);
    }

    public function warrantyReview(WorkOrder $order): void
    {
        $this->notifier->send($order->vehicle->customer, MessageEvent::WarrantyReview, [
            'placa' => $order->vehicle->formattedPlate(),
            'vencimento' => $order->warranty?->ends_at->format('d/m/Y'),
            'link' => $this->general->whatsapp ? 'https://wa.me/'.ltrim($this->general->whatsapp, '+') : $this->tracking->url($order),
        ], "warranty:{$order->id}:review");
    }

    public function reviewRequest(WorkOrder $order): void
    {
        $this->notifier->send($order->vehicle->customer, MessageEvent::ReviewRequest, [
            'placa' => $order->vehicle->formattedPlate(),
            'link' => URL::temporarySignedRoute('portal.review', now()->addDays(60), ['workOrder' => $order->id]),
        ], "review:{$order->id}:request");
    }

    public function settings(): NotificationSettings
    {
        return $this->settings;
    }
}
