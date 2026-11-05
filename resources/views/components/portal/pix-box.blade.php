@props(['payment'])
{{-- Pago Pix: QR, "copia e cola" y vencimiento. Se actualiza en vivo; sondeo de respaldo cada 10 s. --}}
<div wire:poll.10s class="rounded-2xl border border-brand-500/40 bg-ink-950 p-5"
     x-data="{
        copied: false,
        left: Math.max(0, Math.floor((new Date(@js($payment->expires_at?->toIso8601String())) - new Date()) / 1000)),
        init() { setInterval(() => { if (this.left > 0) this.left-- }, 1000) },
        clock() { const m = Math.floor(this.left / 60), s = this.left % 60; return `${m}:${String(s).padStart(2, '0')}` },
        copy() { navigator.clipboard?.writeText(this.$refs.code.value); this.copied = true; setTimeout(() => this.copied = false, 2000) },
     }">
    <p class="mb-1 font-display text-lg font-semibold">{{ __('payments.portal.pix_title') }}</p>
    <p class="mb-4 text-sm text-steel-300">{{ __('payments.portal.pix_help') }}</p>
    <div class="grid gap-5 sm:grid-cols-[180px_1fr] sm:items-center">
        @if ($payment->pix_qr_base64)
            <img src="data:image/png;base64,{{ $payment->pix_qr_base64 }}" alt="{{ __('payments.portal.qr_alt') }}" class="mx-auto size-44 rounded-xl bg-paper p-2" width="176" height="176">
        @endif
        <div class="flex flex-col gap-3">
            <p class="font-display text-3xl font-bold">{{ \App\Filament\Support\Money::format($payment->amount_cents) }}</p>
            <label class="text-xs text-steel-300" for="pix-code-{{ $payment->id }}">{{ __('payments.portal.copy_paste') }}</label>
            <textarea id="pix-code-{{ $payment->id }}" x-ref="code" readonly rows="3" class="input font-mono text-xs">{{ $payment->pix_qr_code }}</textarea>
            <button type="button" class="btn-primary" x-on:click="copy()">
                <span x-show="! copied">{{ __('payments.portal.copy') }}</span>
                <span x-show="copied" x-cloak>{{ __('payments.portal.copied') }}</span>
            </button>
            @if ($payment->expires_at)
                <p class="text-sm text-steel-300" x-show="left > 0">{{ __('payments.portal.expires_in') }} <span class="font-display" x-text="clock()"></span></p>
                <p class="text-sm text-warning" x-show="left <= 0" x-cloak>{{ __('payments.portal.expired') }}</p>
            @endif
        </div>
    </div>
</div>
