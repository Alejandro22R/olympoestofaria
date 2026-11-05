<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\BillingSummary;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentProvider;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Enums\PaymentType;
use App\Domain\Payments\Gateways\PaymentGateway;
use App\Domain\Payments\Gateways\PaymentGatewayException;
use App\Domain\Quotes\Enums\QuoteStatus;
use App\Domain\Settings\PaymentSettings;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Validation\ValidationException;

/**
 * Genera un cobro Pix por Mercado Pago (anticipo de una cotización aprobada o saldo de una orden).
 * Reutiliza un Pix pendiente y vigente del mismo tipo para no duplicar cobros.
 */
class RequestPixPayment
{
    public function __construct(
        private readonly PaymentSettings $settings,
        private readonly PaymentGateway $gateway,
    ) {}

    public function deposit(Quote $quote, ?string $payerEmail = null): Payment
    {
        if ($quote->status !== QuoteStatus::Approved) {
            throw ValidationException::withMessages(['payment' => __('payments.errors.quote_not_approved')]);
        }

        $amount = BillingSummary::forQuote($quote)->depositOutstandingCents();

        return $this->request(PaymentType::Deposit, $amount, $quote, null, $payerEmail, __('payments.description.deposit', ['number' => $quote->number()]));
    }

    public function balance(WorkOrder $order, ?string $payerEmail = null): Payment
    {
        $amount = BillingSummary::forWorkOrder($order)->outstandingCents();

        return $this->request(PaymentType::Balance, $amount, $order->quote, $order, $payerEmail, __('payments.description.balance', ['number' => $order->number()]));
    }

    private function request(PaymentType $type, int $amount, ?Quote $quote, ?WorkOrder $order, ?string $payerEmail, string $description, ?User $user = null): Payment
    {
        if (! $this->settings->onlinePixAvailable()) {
            throw ValidationException::withMessages(['payment' => __('payments.errors.online_disabled')]);
        }
        if ($amount <= 0) {
            throw ValidationException::withMessages(['payment' => __('payments.errors.nothing_due')]);
        }

        $customer = $quote->customer ?? $order?->vehicle->customer;
        abort_if($customer === null, 422);

        $existing = Payment::query()
            ->where('customer_id', $customer->id)
            ->where('type', $type)->where('provider', PaymentProvider::MercadoPago)
            ->where('status', PaymentStatus::Pending)->where('amount_cents', $amount)
            ->when($quote, fn ($q) => $q->where('quote_id', $quote->id))
            ->when($order, fn ($q) => $q->where('work_order_id', $order->id))
            ->latest('id')->first();
        if ($existing?->isPayableOnline()) {
            return $existing;
        }

        $email = $payerEmail ?: $customer->email;
        if (blank($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['email' => __('payments.errors.email_required')]);
        }
        if (blank($customer->email)) {
            $customer->forceFill(['email' => $email])->save();
        }

        $payment = Payment::query()->create([
            'customer_id' => $customer->id,
            'quote_id' => $quote?->id,
            'work_order_id' => $order?->id,
            'type' => $type,
            'method' => PaymentMethod::Pix,
            'provider' => PaymentProvider::MercadoPago,
            'mode' => $this->settings->mercadopago_mode,
            'status' => PaymentStatus::Pending,
            'amount_cents' => $amount,
            'recorded_by' => $user?->id,
        ]);

        try {
            $charge = $this->gateway->createPixCharge($payment, $customer, (string) $email, $description);
        } catch (PaymentGatewayException $e) {
            $payment->forceFill(['status' => PaymentStatus::Cancelled, 'status_detail' => mb_substr($e->getMessage(), 0, 250)])->save();
            report($e);

            throw ValidationException::withMessages(['payment' => __('payments.errors.pix_failed')]);
        }

        $payment->forceFill([
            'gateway_id' => $charge->gatewayId,
            'pix_qr_code' => $charge->qrCode,
            'pix_qr_base64' => $charge->qrCodeBase64,
            'pix_ticket_url' => $charge->ticketUrl,
            'expires_at' => $charge->expiresAt,
            'status_detail' => $charge->status,
        ])->save();

        return $payment;
    }
}
