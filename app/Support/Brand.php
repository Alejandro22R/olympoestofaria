<?php

namespace App\Support;

use Filament\Support\Colors\Color;

/**
 * Paleta de marca para Filament (PLAN.md §2.1). Los tonos 500/700/800 son los
 * tokens exactos; el resto se genera a partir del azul Olympo.
 */
final class Brand
{
    public const BRAND_500 = '#0B8FD9';

    public const BRAND_700 = '#0877B5';

    public const BRAND_800 = '#066A9F';

    /**
     * @return array<int, string>
     */
    public static function primary(): array
    {
        $palette = Color::generatePalette(self::BRAND_500);
        $palette[500] = Color::convertToOklch(self::BRAND_500);
        $palette[600] = Color::convertToOklch(self::BRAND_700);
        $palette[700] = Color::convertToOklch(self::BRAND_700);
        $palette[800] = Color::convertToOklch(self::BRAND_800);

        return $palette;
    }

    /**
     * @return array<int, string>
     */
    public static function gray(): array
    {
        return Color::Neutral;
    }
}
