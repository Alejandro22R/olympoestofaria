<?php

namespace App\Domain\Payments\Gateways;

/**
 * Estado de un pago consultado en la pasarela (fuente de verdad, nunca el cuerpo del webhook).
 */
final readonly class GatewayPayment
{
    public function __construct(
        public string $gatewayId,
        public string $status,
        public ?string $statusDetail,
        public int $amountCents,
        public ?string $externalReference,
        public bool $liveMode,
    ) {}
}
