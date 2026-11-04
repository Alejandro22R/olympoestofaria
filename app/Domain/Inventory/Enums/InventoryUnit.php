<?php

namespace App\Domain\Inventory\Enums;

use Filament\Support\Contracts\HasLabel;

enum InventoryUnit: string implements HasLabel
{
    case SquareMeter = 'm2';
    case Meter = 'm';
    case Unit = 'unit';
    case Liter = 'l';
    case Kilogram = 'kg';

    public function getLabel(): string
    {
        return __('inventory.units.'.$this->value);
    }
}
