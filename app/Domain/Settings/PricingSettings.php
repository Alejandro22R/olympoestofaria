<?php

namespace App\Domain\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Ajustes > Ventas > Cotizaciones y precios (lo que usa el rango estimado del constructor).
 */
class PricingSettings extends Settings
{
    /** ± % sobre el subtotal para el rango estimado. */
    public int $estimate_margin_percent;

    /** Redondeo del rango, en centavos (5000 = R$ 50). */
    public int $rounding_cents;

    /** Mostrar el estimado al cliente en el constructor. */
    public bool $show_estimate;

    public static function group(): string
    {
        return 'pricing';
    }
}
