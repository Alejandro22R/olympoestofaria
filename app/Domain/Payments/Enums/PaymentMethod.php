<?php

namespace App\Domain\Payments\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case Pix = 'pix';
    case Cash = 'cash';
    case Transfer = 'transfer';
    case Card = 'card';

    public function getLabel(): string
    {
        return __('payments.methods.'.$this->value);
    }
}
