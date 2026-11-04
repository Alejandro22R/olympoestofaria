<?php

namespace App\Domain\Inventory\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MovementType: string implements HasColor, HasLabel
{
    case In = 'in';
    case Consumption = 'consumption';
    case Adjustment = 'adjustment';

    public function getLabel(): string
    {
        return __('inventory.movement_types.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::In => 'success',
            self::Consumption => 'warning',
            self::Adjustment => 'gray',
        };
    }
}
