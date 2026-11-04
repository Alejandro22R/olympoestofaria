<?php

namespace App\Domain\Catalog\Enums;

use Filament\Support\Contracts\HasLabel;

enum PricingUnit: string implements HasLabel
{
    case Piece = 'piece';
    case SquareMeter = 'm2';
    case Hour = 'hour';
    case Vehicle = 'vehicle';

    public function getLabel(): string
    {
        return __('catalog.pricing_units.'.$this->value);
    }
}
