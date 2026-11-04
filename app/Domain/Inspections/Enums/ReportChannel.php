<?php

namespace App\Domain\Inspections\Enums;

use Filament\Support\Contracts\HasLabel;

enum ReportChannel: string implements HasLabel
{
    case WhatsApp = 'whatsapp';
    case Email = 'email';

    public function getLabel(): string
    {
        return __('inspections.channels.'.$this->value);
    }
}
