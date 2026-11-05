@php use App\Filament\Support\Money; use App\Domain\Quotes\Enums\QuoteStatus; @endphp
@push('scripts')
    @vite('resources/js/echo.js')
@endpush

<div class="mx-auto max-w-3xl px-4 pb-24 pt-10 md:pt-16">
    @if ($isStaffPreview)
        <p class="mb-4 rounded-xl border border-warning/40 bg-warning/10 px-4 py-2 text-sm">{{ __('quotes.portal.staff_preview') }}</p>
    @endif

    <header class="mb-6">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-500">{{ $quote->number() }}</p>
        <h1 class="text-3xl font-bold md:text-4xl">{{ __('quotes.portal.title', ['name' => $quote->customer->name]) }}</h1>
        <p class="mt-2 text-steel-300">{{ $quote->vehicle_label ?? $quote->vehicle?->label() }}</p>
    </header>

    <section class="card mb-6" aria-labelledby="items-title">
        <h2 id="items-title" class="sr-only">{{ __('quotes.fields.items') }}</h2>
        <ul class="divide-y divide-ink-700">
            @foreach ($quote->items as $item)
                <li class="flex justify-between gap-4 py-3">
                    <span>{{ $item->description }} @if ($item->quantity != 1)<span class="text-steel-300">× {{ rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',') }}</span>@endif</span>
                    <span class="font-display tabular-nums">{{ Money::format($item->total_cents) }}</span>
                </li>
            @endforeach
        </ul>
        <dl class="mt-4 grid gap-1 border-t border-ink-700 pt-4 text-sm">
            @if ($quote->discount_cents > 0)
                <div class="flex justify-between"><dt class="text-steel-300">{{ __('quotes.fields.subtotal') }}</dt><dd>{{ Money::format($quote->subtotal_cents) }}</dd></div>
                <div class="flex justify-between"><dt class="text-steel-300">{{ __('quotes.fields.discount') }}</dt><dd>− {{ Money::format($quote->discount_cents) }}</dd></div>
            @endif
            <div class="flex justify-between text-lg"><dt class="font-semibold">{{ __('quotes.fields.total') }}</dt><dd class="font-display font-bold">{{ Money::format($quote->total_cents) }}</dd></div>
            @if ($quote->deposit_cents > 0)
                <div class="flex justify-between"><dt class="text-steel-300">{{ __('quotes.fields.deposit') }}</dt><dd>{{ Money::format($quote->deposit_cents) }}</dd></div>
            @endif
        </dl>
        @if ($quote->notes)<p class="mt-4 text-sm text-steel-300">{{ $quote->notes }}</p>@endif
        @if ($quote->valid_until)
            <p class="mt-4 text-xs text-steel-300">{{ __('quotes.portal.valid_until', ['date' => $quote->valid_until->format('d/m/Y')]) }}</p>
        @endif
    </section>

    {{-- Decisión --}}
    @if ($canDecide)
        <section class="card mb-6">
            <label class="mb-4 flex items-start gap-2 text-sm">
                <input type="checkbox" wire:model="accepted" class="mt-1 accent-brand-500">
                <span>{{ __('quotes.portal.accept') }}</span>
            </label>
            @error('accepted') <p class="mb-3 text-sm text-danger">{{ $message }}</p> @enderror
            <div class="flex flex-wrap gap-3">
                <button type="button" wire:click="approve" class="btn-primary" wire:loading.attr="disabled">{{ __('quotes.portal.approve') }}</button>
                <button type="button" wire:click="$toggle('showReject')" class="btn-secondary">{{ __('quotes.portal.request_changes') }}</button>
            </div>
            @if ($showReject)
                <div class="mt-4 flex flex-col gap-2">
                    <label class="text-sm" for="reject-reason">{{ __('quotes.portal.reject_reason') }}</label>
                    <textarea id="reject-reason" wire:model="rejectReason" rows="3" class="input" maxlength="255"></textarea>
                    @error('rejectReason') <p class="text-sm text-danger">{{ $message }}</p> @enderror
                    <button type="button" wire:click="reject" class="btn-secondary self-start">{{ __('quotes.portal.send_reason') }}</button>
                </div>
            @endif
        </section>
    @elseif ($quote->status === QuoteStatus::Sent && $quote->isExpired())
        <p class="card mb-6 text-warning">{{ __('quotes.errors.expired') }}</p>
    @elseif (in_array($quote->status, [QuoteStatus::Rejected, QuoteStatus::Superseded, QuoteStatus::Expired], true))
        <p class="card mb-6 text-steel-300">{{ __('quotes.portal.closed', ['status' => $quote->status->getLabel()]) }}</p>
    @endif

    {{-- Pago del anticipo --}}
    @if ($quote->status === QuoteStatus::Approved)
        <section class="card mb-6" aria-live="polite">
            <p class="mb-1 text-sm font-semibold uppercase tracking-[0.2em] text-success">{{ __('quotes.portal.approved') }}</p>
            @if ($billing->depositOutstandingCents() === 0 && $quote->deposit_cents > 0)
                <p class="font-display text-2xl font-semibold">{{ __('payments.portal.deposit_paid') }}</p>
                <p class="text-steel-300">{{ __('payments.portal.deposit_paid_help') }}</p>
            @elseif ($quote->deposit_cents === 0)
                <p class="text-steel-300">{{ __('payments.portal.no_deposit') }}</p>
            @elseif ($pendingPix)
                <x-portal.pix-box :payment="$pendingPix" />
            @elseif ($onlinePix)
                <p class="mb-3 text-steel-300">{{ __('payments.portal.deposit_due', ['amount' => Money::format($billing->depositOutstandingCents())]) }}</p>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <label class="flex flex-1 flex-col gap-1 text-sm">
                        {{ __('customers.fields.email') }}
                        <input type="email" wire:model="email" class="input" autocomplete="email">
                    </label>
                    <button type="button" wire:click="payDeposit" class="btn-primary" wire:loading.attr="disabled">{{ __('payments.portal.pay_pix') }}</button>
                </div>
                @error('email') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
                @error('payment') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
            @else
                <p class="text-steel-300">{{ __('payments.portal.offline', ['amount' => Money::format($billing->depositOutstandingCents())]) }}</p>
            @endif
        </section>
    @endif

    @if ($payments->isNotEmpty())
        <section class="card">
            <h2 class="mb-3 font-semibold">{{ __('payments.portal.history') }}</h2>
            <ul class="divide-y divide-ink-700 text-sm">
                @foreach ($payments as $payment)
                    <li class="flex justify-between py-2"><span>{{ $payment->type->getLabel() }} · {{ $payment->method->getLabel() }} · {{ $payment->paid_at?->format('d/m/Y') }}</span><span class="font-display">{{ Money::format($payment->amount_cents) }}</span></li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
