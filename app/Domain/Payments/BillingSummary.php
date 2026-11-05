<?php

namespace App\Domain\Payments;

use App\Domain\Payments\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\WorkOrder;

/**
 * Total, pagado y saldo de una cotización o de una orden (la orden usa el total de su cotización si existe).
 */
final readonly class BillingSummary
{
    public function __construct(public int $totalCents, public int $paidCents, public int $depositCents) {}

    public function outstandingCents(): int
    {
        return max(0, $this->totalCents - $this->paidCents);
    }

    public function depositOutstandingCents(): int
    {
        return max(0, $this->depositCents - $this->paidCents);
    }

    public static function forQuote(Quote $quote): self
    {
        $paid = (int) Payment::query()->where('quote_id', $quote->id)->where('status', PaymentStatus::Approved)->sum('amount_cents');

        return new self($quote->total_cents, $paid, $quote->deposit_cents);
    }

    public static function forWorkOrder(WorkOrder $order): self
    {
        $quote = $order->quote;
        $paid = (int) Payment::query()
            ->where('status', PaymentStatus::Approved)
            ->where(fn ($q) => $q->where('work_order_id', $order->id)->when($quote, fn ($q) => $q->orWhere('quote_id', $quote->id)))
            ->sum('amount_cents');

        return new self($quote !== null ? $quote->total_cents : $order->totalCents(), $paid, $quote !== null ? $quote->deposit_cents : 0);
    }
}
