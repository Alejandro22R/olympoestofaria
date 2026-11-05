<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\BillingSummary;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentProvider;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Enums\PaymentType;
use App\Domain\Settings\PaymentSettings;
use App\Filament\Support\Money;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Validation\ValidationException;

/**
 * Pago registrado a mano por el taller (efectivo, Pix directo, transferencia…).
 * Sirve con Mercado Pago desactivado. Nunca supera el saldo pendiente.
 */
class RecordManualPayment
{
    public function __construct(
        private readonly PaymentSettings $settings,
        private readonly ApplyPaymentStatus $apply,
    ) {}

    public function execute(Quote|WorkOrder $target, PaymentType $type, PaymentMethod $method, int $amountCents, ?User $user = null, ?string $note = null): Payment
    {
        if (! in_array($method->value, $this->settings->manual_methods, true)) {
            throw ValidationException::withMessages(['method' => __('payments.errors.method_disabled')]);
        }

        $summary = $target instanceof Quote ? BillingSummary::forQuote($target) : BillingSummary::forWorkOrder($target);
        if ($amountCents <= 0) {
            throw ValidationException::withMessages(['amount_cents' => __('payments.errors.amount_positive')]);
        }
        if ($amountCents > $summary->outstandingCents()) {
            throw ValidationException::withMessages(['amount_cents' => __('payments.errors.above_outstanding', ['outstanding' => Money::format($summary->outstandingCents())])]);
        }

        $quote = $target instanceof Quote ? $target : $target->quote;
        $order = $target instanceof WorkOrder ? $target : null;
        $customerId = $quote !== null ? $quote->customer_id : $order?->vehicle->customer_id;

        $payment = Payment::query()->create([
            'customer_id' => $customerId,
            'quote_id' => $quote?->id,
            'work_order_id' => $order?->id,
            'type' => $type,
            'method' => $method,
            'provider' => PaymentProvider::Manual,
            'status' => PaymentStatus::Pending,
            'amount_cents' => $amountCents,
            'recorded_by' => $user?->id,
            'note' => $note,
        ]);

        $this->apply->execute($payment, PaymentStatus::Approved, 'manual');

        return $payment->refresh();
    }
}
