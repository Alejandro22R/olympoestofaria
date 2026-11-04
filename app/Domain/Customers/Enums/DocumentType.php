<?php

namespace App\Domain\Customers\Enums;

use Filament\Support\Contracts\HasLabel;

enum DocumentType: string implements HasLabel
{
    case Cpf = 'cpf';
    case Cnpj = 'cnpj';
    case Other = 'other';

    public function getLabel(): string
    {
        return __('customers.document_types.'.$this->value);
    }
}
