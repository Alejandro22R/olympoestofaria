@extends('layouts.portal', ['title' => __('portal.sent.title').' · Olympo'])

@section('content')
<div class="mx-auto max-w-2xl px-4 py-16 md:py-24">
    <div class="card">
        <p class="mb-2 text-sm font-semibold uppercase tracking-[0.2em] text-success">{{ $project->number() }}</p>
        <h1 class="mb-3 text-3xl font-bold">{{ __('portal.sent.title') }}</h1>
        <p class="mb-6 text-steel-300">{{ __('portal.sent.text') }}</p>

        <dl class="mb-6 grid gap-2 text-sm">
            <div class="flex justify-between gap-4 border-b border-ink-700 pb-2"><dt class="text-steel-300">{{ __('vehicles.model') }}</dt><dd>{{ $project->vehicleLabel() }}</dd></div>
            @foreach ($lines as $line)
                <div class="flex justify-between gap-4 border-b border-ink-700 pb-2">
                    <dt>{{ $line['service'] }}</dt>
                    <dd class="text-steel-300">{{ $line['material'] ? $line['material'].' · '.$line['color'] : '' }}</dd>
                </div>
            @endforeach
        </dl>

        @if ($project->estimate_max_cents > 0 && app(\App\Domain\Settings\PricingSettings::class)->show_estimate)
            <p class="text-sm text-steel-300">{{ __('portal.builder.estimate') }}</p>
            <p class="mb-2 font-display text-3xl font-semibold">{{ \App\Filament\Support\Money::format($project->estimate_min_cents) }} – {{ \App\Filament\Support\Money::format($project->estimate_max_cents) }}</p>
            <p class="mb-6 text-xs text-steel-300">{{ __('portal.builder.estimate_help') }}</p>
        @endif

        <div class="flex flex-wrap gap-3">
            @if ($whatsappUrl)
                <a href="{{ $whatsappUrl }}" class="btn-primary" rel="noopener" target="_blank">{{ __('portal.sent.whatsapp') }}</a>
            @endif
            <a href="{{ route('home') }}" class="btn-secondary">{{ __('portal.sent.home') }}</a>
        </div>
    </div>
</div>
@endsection
