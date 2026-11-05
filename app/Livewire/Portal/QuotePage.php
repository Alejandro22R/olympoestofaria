<?php

namespace App\Livewire\Portal;

use App\Domain\Payments\Actions\RequestPixPayment;
use App\Domain\Payments\BillingSummary;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Quotes\Actions\DecideQuote;
use App\Domain\Quotes\Enums\QuoteStatus;
use App\Domain\Settings\PaymentSettings;
use App\Models\Payment;
use App\Models\Quote;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Cotización para el cliente: revisar, aprobar (o pedir cambios) y pagar el anticipo con Pix.
 * La confirmación del pago llega en vivo por su canal privado (con sondeo de respaldo).
 */
#[Layout('layouts.portal')]
class QuotePage extends Component
{
    public Quote $quote;

    public bool $accepted = false;

    public ?string $email = null;

    public ?string $rejectReason = null;

    public bool $showReject = false;

    public function mount(Quote $quote): void
    {
        Gate::authorize('respond', $quote);
        $this->quote = $quote;
        $this->email = $quote->customer->email;
    }

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return [
            "echo-private:customers.{$this->quote->customer_id},.payment.updated" => '$refresh',
        ];
    }

    public function approve(DecideQuote $decide, PaymentSettings $payments, RequestPixPayment $pix): void
    {
        Gate::authorize('respond', $this->quote);
        $this->validate(['accepted' => ['accepted']], ['accepted.accepted' => __('quotes.portal.accept_required')]);

        $decide->approve($this->quote, 'portal', request()->ip());

        if ($payments->onlinePixAvailable() && $this->quote->deposit_cents > 0 && filled($this->email)) {
            try {
                $pix->deposit($this->quote->refresh(), $this->email);
            } catch (ValidationException) {
                // El cliente puede reintentar con el botón de pago.
            }
        }

        $this->quote->refresh();
    }

    public function reject(DecideQuote $decide): void
    {
        Gate::authorize('respond', $this->quote);
        $this->validate(['rejectReason' => ['required', 'string', 'max:255']]);

        $decide->reject($this->quote, (string) $this->rejectReason);
        $this->quote->refresh();
        $this->showReject = false;
    }

    public function payDeposit(RequestPixPayment $pix): void
    {
        Gate::authorize('respond', $this->quote);
        $this->validate(['email' => ['required', 'email', 'max:160']]);

        $pix->deposit($this->quote, $this->email);
        $this->quote->refresh();
    }

    public function render(PaymentSettings $payments): View
    {
        $this->quote->loadMissing(['items', 'customer']);
        $pending = Payment::query()->where('quote_id', $this->quote->id)->where('status', PaymentStatus::Pending)->latest('id')->first();

        return view('livewire.portal.quote-page', [
            'billing' => BillingSummary::forQuote($this->quote),
            'pendingPix' => $pending?->isPayableOnline() ? $pending : null,
            'payments' => Payment::query()->where('quote_id', $this->quote->id)->where('status', PaymentStatus::Approved)->latest('paid_at')->get(),
            'onlinePix' => $payments->onlinePixAvailable(),
            'canDecide' => $this->quote->status === QuoteStatus::Sent && ! $this->quote->isExpired(),
            'isStaffPreview' => (bool) auth()->user()?->isStaff(),
        ])->title($this->quote->number().' · Olympo');
    }
}
