<?php

namespace App\Domain\Settings;

use Illuminate\Support\Facades\Storage;
use Spatie\LaravelSettings\Settings;

/**
 * Ajustes > Sistema > Marca y apariencia. Sin archivo subido se usan los del repositorio.
 */
class BrandSettings extends Settings
{
    public ?string $logo_dark_path;

    public ?string $logo_light_path;

    public ?string $favicon_path;

    public string $primary_color;

    /** dark | light | system */
    public string $default_theme;

    public bool $reduce_motion;

    public static function group(): string
    {
        return 'brand';
    }

    /** Emblema oficial del taller (el logo por defecto si no se subió uno propio). */
    public const EMBLEM = 'images/brand/emblem.svg';

    public function logoDarkUrl(): string
    {
        return $this->logo_dark_path ? Storage::disk('public')->url($this->logo_dark_path) : asset(self::EMBLEM);
    }

    public function hasCustomLogo(): bool
    {
        return filled($this->logo_dark_path) || filled($this->logo_light_path);
    }

    public function logoLightUrl(): ?string
    {
        return $this->logo_light_path ? Storage::disk('public')->url($this->logo_light_path) : null;
    }

    public function faviconUrl(): string
    {
        return $this->favicon_path ? Storage::disk('public')->url($this->favicon_path) : asset('favicon.svg');
    }
}
