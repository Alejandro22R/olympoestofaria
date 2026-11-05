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

    /** Días de vigencia de la cotización final. */
    public int $quote_validity_days;

    /** Descuento máximo permitido sobre el subtotal, en %. */
    public int $max_discount_percent;

    /** @var list<string> Roles que pueden fijar el precio final de la cotización. */
    public array $final_price_roles;

    /** Exigir que el lead esté "contactado" antes de cotizar. */
    public bool $require_contact_before_quote;

    public static function group(): string
    {
        return 'pricing';
    }
}
