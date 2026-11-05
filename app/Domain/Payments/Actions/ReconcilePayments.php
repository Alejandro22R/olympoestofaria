<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Enums\PaymentProvider;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Gateways\PaymentGateway;
use App\Domain\Payments\Gateways\PaymentGatewayException;
use App\Domain\Settings\PaymentSettings;
use App\Models\Payment;

/**
 * Conciliación: consulta los Pix pendientes en Mercado Pago (por si un webhook se perdió)
 * y marca como vencidos los que pasaron su fecha sin pagarse.
 *
 * @return array{checked: int, updated: int, expired: int}
 */
class ReconcilePayments
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly ApplyPaymentStatus $apply,
        private readonly PaymentSettings $settings,
    ) {}

    /**
     * @return array{checked: int, updated: int, expired: int}
     */
    public function execute(): array
    {
        $result = ['checked' => 0, 'updated' => 0, 'expired' => 0];

        $pending = Payment::query()->where('provider', PaymentProvider::MercadoPago)->where('status', PaymentStatus::Pending)->get();

        foreach ($pending as $payment) {
            if ($payment->gateway_id !== null && filled($this->settings->mercadopago_access_token)) {
                $result['checked']++;
                try {
                    $remote = $this->gateway->fetchPayment($payment->gateway_id);
                    if ($remote->amountCents === $payment->amount_cents
                        && $this->apply->execute($payment, PaymentStatus::fromMercadoPago($remote->status), $remote->statusDetail)) {
                        $result['updated']++;

                        continue;
                    }
                } catch (PaymentGatewayException $e) {
                    report($e);
                }
            }

            if ($payment->fresh()?->status === PaymentStatus::Pending && $payment->expires_at !== null && $payment->expires_at->addMinutes(5)->isPast()) {
                $this->apply->execute($payment, PaymentStatus::Expired, 'expired');
                $result['expired']++;
            }
        }

        return $result;
    }
}
