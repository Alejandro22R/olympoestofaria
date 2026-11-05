@php use App\Filament\Support\Money; @endphp
@push('scripts')
    @vite('resources/js/echo.js')
@endpush

<div class="mx-auto max-w-3xl px-4 pb-24 pt-10 md:pt-16" wire:poll.30s>
    <header class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-500">{{ $workOrder->number() }}</p>
        <h1 class="text-3xl font-bold md:text-4xl">{{ $workOrder->vehicle->label() }}</h1>
        <p class="mt-2 text-steel-300">
            {{ __('tracking.status') }}: <strong class="text-paper">{{ $workOrder->status->getLabel() }}</strong>
            @if ($workOrder->due_at) · {{ __('tracking.due', ['date' => $workOrder->due_at->setTimezone(app(\App\Domain\Settings\GeneralSettings::class)->timezone)->format('d/m/Y')]) }}@endif
        </p>
    </header>

    {{-- Etapas visibles al cliente --}}
    <ol class="mb-10 grid gap-3" aria-label="{{ __('tracking.stages') }}">
        @foreach ($stages as $i => $stage)
            @php($state = $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'current' : 'todo'))
            <li class="flex items-center gap-3">
                <span @class([
                    'flex size-8 shrink-0 items-center justify-center rounded-full border font-display text-sm',
                    'border-success bg-success text-ink-950' => $state === 'done',
                    'border-brand-500 bg-brand-700 text-paper' => $state === 'current',
                    'border-ink-700 text-steel-300' => $state === 'todo',
                ])>{{ $state === 'done' ? '✓' : $i + 1 }}</span>
                <span @class(['font-semibold text-paper' => $state === 'current', 'text-steel-300' => $state !== 'current'])>{{ $stage->name }}</span>
                @if ($state === 'current')<span class="text-xs text-brand-500">{{ __('tracking.now') }}</span>@endif
            </li>
        @endforeach
    </ol>

    {{-- Avances --}}
    <section class="mb-10" aria-labelledby="updates-title">
        <h2 id="updates-title" class="mb-4 text-xl font-semibold">{{ __('tracking.updates') }}</h2>
        <ul class="flex flex-col gap-4">
            @forelse ($updates as $update)
                <li class="card">
                    <p class="text-xs text-steel-300">{{ $update->created_at->setTimezone(app(\App\Domain\Settings\GeneralSettings::class)->timezone)->format('d/m/Y H:i') }} · {{ $update->stage?->name ?? $update->status->getLabel() }}</p>
                    @if ($update->note)<p class="mt-1">{{ $update->note }}</p>@endif
                    @if (count($update->photos))
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            @foreach ($update->photos as $index => $photo)
                                <a href="{{ route('portal.tracking.photo', [$workOrder, $update, $index]) }}" target="_blank" rel="noopener" class="block aspect-square overflow-hidden rounded-xl">
                                    <img src="{{ route('portal.tracking.photo', [$workOrder, $update, $index]) }}" alt="{{ __('tracking.photo') }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-300 hover:scale-105">
                                </a>
                            @endforeach
                        </div>
                    @endif
                </li>
            @empty
                <li class="text-steel-300">{{ __('tracking.no_updates') }}</li>
            @endforelse
        </ul>
    </section>

    {{-- Pagos --}}
    <section class="card" aria-labelledby="billing-title" aria-live="polite">
        <h2 id="billing-title" class="mb-3 text-xl font-semibold">{{ __('tracking.billing') }}</h2>
        <dl class="mb-4 grid gap-1 text-sm">
            <div class="flex justify-between"><dt class="text-steel-300">{{ __('quotes.fields.total') }}</dt><dd>{{ Money::format($billing->totalCents) }}</dd></div>
            <div class="flex justify-between"><dt class="text-steel-300">{{ __('tracking.paid') }}</dt><dd>{{ Money::format($billing->paidCents) }}</dd></div>
            <div class="flex justify-between text-lg"><dt class="font-semibold">{{ __('tracking.outstanding') }}</dt><dd class="font-display font-bold">{{ Money::format($billing->outstandingCents()) }}</dd></div>
        </dl>
        @if ($billing->outstandingCents() > 0)
            @if ($pendingPix)
                <x-portal.pix-box :payment="$pendingPix" />
            @elseif ($balanceOpen)
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <label class="flex flex-1 flex-col gap-1 text-sm">
                        {{ __('customers.fields.email') }}
                        <input type="email" wire:model="email" class="input" autocomplete="email">
                    </label>
                    <button type="button" wire:click="payBalance" class="btn-primary">{{ __('payments.portal.pay_balance') }}</button>
                </div>
                @error('email') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
                @error('payment') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
            @else
                <p class="text-sm text-steel-300">{{ __('tracking.balance_later') }}</p>
            @endif
        @endif
    </section>
</div>
