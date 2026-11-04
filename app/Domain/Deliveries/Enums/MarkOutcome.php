<?php

namespace App\Domain\Deliveries\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Estado final de cada daño registrado en el check-in, para el acta de entrega.
 */
enum MarkOutcome: string implements HasLabel
{
    case Repaired = 'repaired';
    case Unchanged = 'unchanged';

    public function getLabel(): string
    {
        return __('deliveries.outcomes.'.$this->value);
    }
}
