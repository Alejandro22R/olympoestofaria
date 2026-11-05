<?php

namespace App\Domain\Payments\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentType: string implements HasLabel
{
    case Deposit = 'deposit';
    case Balance = 'balance';

    public function getLabel(): string
    {
        return __('payments.types.'.$this->value);
    }
}
