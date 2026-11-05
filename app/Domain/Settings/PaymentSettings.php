<?php

namespace App\Domain\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Ajustes > Ventas > Pagos. Mercado Pago es opcional (interruptor) y hoy solo acepta Pix.
 * Las credenciales se guardan cifradas y nunca se devuelven al navegador.
 */
class PaymentSettings extends Settings
{
    /** Anticipo (seña) sobre el total de la cotización, en %. */
    public int $deposit_percent;

    /** La cita solo queda confirmada cuando se paga el anticipo. */
    public bool $deposit_required_to_confirm;

    /** Momento del saldo: on_delivery | before_delivery. */
    public string $balance_moment;

    /** @var list<string> Métodos para pagos registrados a mano por el taller. */
    public array $manual_methods;

    public bool $mercadopago_enabled;

    /** sandbox | production */
    public string $mercadopago_mode;

    /** @var list<string> Métodos online habilitados (hoy: pix). */
    public array $mercadopago_methods;

    public ?string $mercadopago_public_key;

    public ?string $mercadopago_access_token;

    public ?string $mercadopago_webhook_secret;

    public int $pix_expiration_minutes;

    public ?string $statement_descriptor;

    public const ONLINE_METHODS = ['pix'];

    public const MANUAL_METHODS = ['cash', 'pix', 'transfer', 'card'];

    public static function group(): string
    {
        return 'payments';
    }

    /**
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return ['mercadopago_access_token', 'mercadopago_webhook_secret'];
    }

    /**
     * Pix por Mercado Pago listo para usar: activado, con token y con Pix habilitado.
     */
    public function onlinePixAvailable(): bool
    {
        return $this->mercadopago_enabled
            && filled($this->mercadopago_access_token)
            && in_array('pix', $this->mercadopago_methods, true);
    }

    public function depositFor(int $totalCents): int
    {
        return intdiv($totalCents * $this->deposit_percent, 100);
    }
}
