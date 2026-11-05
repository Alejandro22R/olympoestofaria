<?php

namespace App\Domain\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Ajustes > Ventas > Catálogo y portal: textos de la landing, secciones, contadores y SEO.
 */
class PortalSettings extends Settings
{
    public bool $show_prices;

    public bool $show_out_of_stock;

    /** @var array<string, string> */
    public array $headline;

    /** @var array<string, string> */
    public array $subheadline;

    /** @var list<string> Secciones visibles de la landing. */
    public array $sections;

    public int $counter_cars;

    public int $counter_years;

    public int $counter_followers;

    public ?string $instagram_url;

    /** @var array<string, string> */
    public array $seo_title;

    /** @var array<string, string> */
    public array $seo_description;

    public const SECTIONS = ['services', 'configurator', 'before_after', 'gallery', 'materials', 'process', 'social_proof'];

    public static function group(): string
    {
        return 'portal';
    }

    /**
     * @param  array<string, string>  $values
     */
    public function text(array $values, ?string $locale = null): string
    {
        return $values[$locale ?? app()->getLocale()] ?? $values['pt_BR'] ?? '';
    }

    public function shows(string $section): bool
    {
        return in_array($section, $this->sections, true);
    }
}
