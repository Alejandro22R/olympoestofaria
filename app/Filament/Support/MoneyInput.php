<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;

/**
 * Campo de dinero: se edita en reales (1.234,56) y se guarda en centavos (enteros).
 */
final class MoneyInput
{
    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->prefix('R$')
            ->numeric()
            ->minValue(0)
            ->step('0.01')
            ->formatStateUsing(fn ($state) => $state === null ? null : number_format(((int) $state) / 100, 2, '.', ''))
            ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? 0 : (int) round(((float) $state) * 100));
    }
}
