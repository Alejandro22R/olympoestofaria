<?php

namespace App\Support;

use App\Domain\Settings\BrandSettings;
use Filament\Support\Colors\Color;
use Throwable;

/**
 * Paleta de marca (PLAN.md §2.1). Con el azul Olympo por defecto usa los tokens exactos
 * 500/700/800; si el dueño elige otro color en Ajustes > Marca, se derivan los tonos 700 y 800
 * oscureciéndolo (y el formulario exige contraste AA con texto blanco sobre el 700).
 */
final class Brand
{
    public const BRAND_500 = '#0B8FD9';

    public const BRAND_700 = '#0877B5';

    public const BRAND_800 = '#066A9F';

    public static function settings(): ?BrandSettings
    {
        try {
            $settings = app(BrandSettings::class);

            // Los ajustes se cargan al primer acceso: si faltan (sin migrar), falla aquí y no en la vista.
            return $settings->primary_color !== '' ? $settings : null;
        } catch (Throwable) {
            return null; // instalación sin migrar
        }
    }

    /**
     * Lee un ajuste sin romper si la tabla aún no existe (instalación, comandos de consola).
     *
     * @param  class-string  $class
     */
    public static function settingsValue(string $class, string $property, mixed $default = null): mixed
    {
        try {
            return app($class)->{$property};
        } catch (Throwable) {
            return $default;
        }
    }

    public static function primaryHex(): string
    {
        $hex = self::settings()?->primary_color;

        return is_string($hex) && preg_match('/^#[0-9A-Fa-f]{6}$/', $hex) ? strtoupper($hex) : self::BRAND_500;
    }

    public static function isCustom(): bool
    {
        return self::primaryHex() !== self::BRAND_500;
    }

    /**
     * @return array{500: string, 700: string, 800: string}
     */
    public static function shades(?string $hex = null): array
    {
        $hex = strtoupper($hex ?? self::primaryHex());
        if ($hex === self::BRAND_500) {
            return [500 => self::BRAND_500, 700 => self::BRAND_700, 800 => self::BRAND_800];
        }

        return [500 => $hex, 700 => self::darken($hex, 0.18), 800 => self::darken($hex, 0.27)];
    }

    /**
     * @return array<int, string>
     */
    public static function primary(): array
    {
        $shades = self::shades();
        $palette = Color::generatePalette($shades[500]);
        $palette[500] = Color::convertToOklch($shades[500]);
        $palette[600] = Color::convertToOklch($shades[700]);
        $palette[700] = Color::convertToOklch($shades[700]);
        $palette[800] = Color::convertToOklch($shades[800]);

        return $palette;
    }

    /**
     * @return array<int, string>
     */
    public static function gray(): array
    {
        return Color::Neutral;
    }

    /** CSS para el portal cuando el color no es el de fábrica (sobrescribe los tokens de Tailwind). */
    public static function portalCss(): ?string
    {
        if (! self::isCustom()) {
            return null;
        }
        $s = self::shades();

        return ":root{--color-brand-500:{$s[500]};--color-brand-700:{$s[700]};--color-brand-800:{$s[800]};}";
    }

    public static function darken(string $hex, float $amount): string
    {
        [$r, $g, $b] = self::rgb($hex);
        $f = 1 - $amount;

        return sprintf('#%02X%02X%02X', (int) round($r * $f), (int) round($g * $f), (int) round($b * $f));
    }

    /** Contraste WCAG del texto blanco sobre el color dado. */
    public static function contrastWithWhite(string $hex): float
    {
        $l = self::luminance($hex);

        return round(1.05 / ($l + 0.05), 2);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [(int) hexdec(substr($hex, 0, 2)), (int) hexdec(substr($hex, 2, 2)), (int) hexdec(substr($hex, 4, 2))];
    }

    private static function luminance(string $hex): float
    {
        $channels = array_map(function (int $c): float {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hex));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
