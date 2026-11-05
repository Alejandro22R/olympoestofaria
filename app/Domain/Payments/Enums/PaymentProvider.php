<?php

namespace App\Domain\Payments\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentProvider: string implements HasLabel
{
    case MercadoPago = 'mercadopago';
    case Manual = 'manual';

    public function getLabel(): string
    {
        return __('payments.providers.'.$this->value);
    }
}
