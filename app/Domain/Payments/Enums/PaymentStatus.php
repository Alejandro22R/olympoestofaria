<?php

namespace App\Domain\Payments\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Estado del pago. Los finales no cambian (un webhook repetido o atrasado no los revierte),
 * salvo aprobado → reembolsado.
 */
enum PaymentStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Pending => $to !== self::Pending,
            self::Approved => $to === self::Refunded,
            default => false,
        };
    }

    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }

    /**
     * Traducción de los estados de Mercado Pago.
     */
    public static function fromMercadoPago(string $status): self
    {
        return match ($status) {
            'approved', 'authorized' => self::Approved,
            'rejected' => self::Rejected,
            'cancelled' => self::Cancelled,
            'refunded', 'charged_back' => self::Refunded,
            default => self::Pending, // pending, in_process, in_mediation
        };
    }

    public function getLabel(): string
    {
        return __('payments.status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected, self::Cancelled, self::Expired => 'danger',
            self::Refunded => 'gray',
        };
    }
}
