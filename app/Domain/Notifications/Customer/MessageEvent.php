<?php

namespace App\Domain\Notifications\Customer;

use Filament\Support\Contracts\HasLabel;

/**
 * Catálogo de avisos al cliente (PLAN.md §2.5). Cada evento define su categoría y variables.
 * - transactional: obligatorio (pago, cita, actas); no espera el horario silencioso.
 * - updates: avance del trabajo; respeta preferencias y horario silencioso.
 * - marketing: reseñas y revisiones; requiere opt-in y respeta la baja.
 */
enum MessageEvent: string implements HasLabel
{
    case ProjectReceived = 'project_received';
    case QuoteSent = 'quote_sent';
    case QuoteReminder = 'quote_reminder';
    case QuoteApproved = 'quote_approved';
    case PaymentApproved = 'payment_approved';
    case PaymentRejected = 'payment_rejected';
    case AppointmentConfirmed = 'appointment_confirmed';
    case AppointmentMoved = 'appointment_moved';
    case AppointmentCancelled = 'appointment_cancelled';
    case AppointmentReminder = 'appointment_reminder';
    case CheckinReport = 'checkin_report';
    case WorkOrderStage = 'work_order_stage';
    case WorkOrderReady = 'work_order_ready';
    case DeliveryReport = 'delivery_report';
    case WarrantyReview = 'warranty_review';
    case ReviewRequest = 'review_request';

    public function category(): string
    {
        return match ($this) {
            self::WorkOrderStage, self::QuoteReminder => 'updates',
            self::WarrantyReview, self::ReviewRequest => 'marketing',
            default => 'transactional',
        };
    }

    public function isTransactional(): bool
    {
        return $this->category() === 'transactional';
    }

    /**
     * Variables disponibles en las plantillas ({{nome}}…).
     *
     * @return list<string>
     */
    public function variables(): array
    {
        $base = ['nome', 'oficina'];

        return [...$base, ...match ($this) {
            self::ProjectReceived => ['numero', 'veiculo'],
            self::QuoteSent, self::QuoteReminder => ['numero', 'veiculo', 'total', 'validade', 'link'],
            self::QuoteApproved => ['numero', 'total', 'sinal', 'link'],
            self::PaymentApproved, self::PaymentRejected => ['valor', 'tipo', 'link'],
            self::AppointmentConfirmed, self::AppointmentMoved, self::AppointmentReminder => ['placa', 'data', 'hora', 'endereco'],
            self::AppointmentCancelled => ['placa', 'data', 'hora'],
            self::CheckinReport, self::DeliveryReport => ['placa', 'link'],
            self::WorkOrderStage => ['placa', 'etapa', 'link'],
            self::WorkOrderReady => ['placa', 'saldo', 'link'],
            self::WarrantyReview => ['placa', 'vencimento', 'link'],
            self::ReviewRequest => ['placa', 'link'],
        }];
    }

    public function getLabel(): string
    {
        return __('communication.events.'.$this->value);
    }
}
