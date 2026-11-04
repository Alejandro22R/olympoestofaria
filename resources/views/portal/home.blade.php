@extends('layouts.portal')

@section('content')
    {{-- Landing provisional (Fase 0). La landing de impacto llega en la Fase 4 (PLAN.md §2.2). --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,var(--color-brand-800)_0%,transparent_60%)] opacity-30" aria-hidden="true"></div>
        <div class="relative mx-auto flex max-w-6xl flex-col items-start gap-6 px-4 py-24 md:py-36">
            <img src="{{ asset('images/brand/logo-mark.svg') }}" alt="" class="h-16 w-16" aria-hidden="true">
            <h1 class="max-w-3xl text-4xl font-bold leading-tight md:text-6xl">{{ __('portal.home.headline') }}</h1>
            <p class="max-w-xl text-lg text-steel-300">{{ __('portal.home.subheadline') }}</p>
            <div class="flex flex-wrap gap-3">
                <a href="#" class="btn-primary">{{ __('portal.home.cta_project') }}</a>
                <a href="#" class="btn-secondary">{{ __('portal.home.cta_whatsapp') }}</a>
            </div>
        </div>
    </section>

    <section class="mx-auto grid max-w-6xl gap-4 px-4 pb-24 sm:grid-cols-2 lg:grid-cols-3">
        @foreach (['seats', 'steering_wheel', 'doors', 'headliner', 'flooring', 'colortek'] as $service)
            <article class="card">
                <h2 class="text-lg font-semibold">{{ __('portal.services.'.$service) }}</h2>
            </article>
        @endforeach
    </section>
@endsection
