<?php

namespace App\Domain\Payments\Gateways;

use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Interfaz de pasarela: permite cambiar de proveedor sin tocar el dominio.
 */
interface PaymentGateway
{
    public function createPixCharge(Payment $payment, Customer $customer, string $payerEmail, string $description): PixCharge;

    public function fetchPayment(string $gatewayId): GatewayPayment;

    public function verifyWebhook(Request $request): bool;

    public function testConnection(): ConnectionResult;
}
