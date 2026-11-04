<?php

namespace App\Domain\Inspections\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Nivel de combustible según el indicador del tablero.
 */
enum FuelLevel: string implements HasLabel
{
    case Empty = 'empty';
    case Quarter = 'quarter';
    case Half = 'half';
    case ThreeQuarters = 'three_quarters';
    case Full = 'full';

    public function getLabel(): string
    {
        return __('inspections.fuel.'.$this->value);
    }
}
