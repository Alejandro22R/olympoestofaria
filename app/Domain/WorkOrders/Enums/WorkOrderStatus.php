<?php

namespace App\Domain\WorkOrders\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Máquina de estados de la orden de trabajo. Una orden no pasa de "recibida" a "entregada"
 * sin pasar por "en producción" y "lista".
 */
enum WorkOrderStatus: string implements HasColor, HasLabel
{
    case Received = 'received';
    case InProduction = 'in_production';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Received => [self::InProduction, self::Cancelled],
            self::InProduction => [self::Ready, self::Cancelled],
            self::Ready => [self::InProduction, self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * Estados que se muestran como columnas del tablero (vía sus etapas).
     *
     * @return list<self>
     */
    public static function onBoard(): array
    {
        return [self::Received, self::InProduction, self::Ready];
    }

    public function isOpen(): bool
    {
        return in_array($this, self::onBoard(), true);
    }

    public function getLabel(): string
    {
        return __('work_orders.status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Received => 'gray',
            self::InProduction => 'primary',
            self::Ready => 'success',
            self::Delivered => 'info',
            self::Cancelled => 'danger',
        };
    }
}
