<?php

namespace App\Filament\Support;

/**
 * Formato de importes en centavos para pantallas (BRL).
 */
final class Money
{
    public static function format(?int $cents, string $currency = 'BRL'): string
    {
        $value = number_format(((int) $cents) / 100, 2, ',', '.');

        return $currency === 'BRL' ? 'R$ '.$value : $currency.' '.$value;
    }
}
