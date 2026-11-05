<?php

namespace App\Domain\Payments\Gateways;

use Carbon\CarbonImmutable;

/**
 * Cobro Pix creado en la pasarela: código "copia e cola", QR en base64 y vencimiento.
 */
final readonly class PixCharge
{
    public function __construct(
        public string $gatewayId,
        public string $status,
        public string $qrCode,
        public ?string $qrCodeBase64,
        public ?string $ticketUrl,
        public ?CarbonImmutable $expiresAt,
    ) {}
}
