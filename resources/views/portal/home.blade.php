@extends('layouts.portal')

@push('head')
    {{-- Fuentes visibles en la primera pantalla: precarga para evitar saltos de diseño (CLS). --}}
    <link rel="preload" as="font" type="font/woff2" href="{{ Vite::asset('resources/fonts/sora-latin-700-normal.woff2') }}" crossorigin>
    <link rel="preload" as="font" type="font/woff2" href="{{ Vite::asset('resources/fonts/manrope-latin-400-normal.woff2') }}" crossorigin>
    @if ($hero?->posterUrl())
        <link rel="preload" as="image" href="{{ $hero->posterUrl() }}" fetchpriority="high">
    @endif
@endpush

@push('scripts')
    @vite('resources/js/landing.js')
@endpush

@php
    $whatsapp = $general->whatsapp ? 'https://wa.me/'.ltrim($general->whatsapp, '+') : null;
    $words = preg_split('/\s+/', $portal->text($portal->headline));
@endphp

@section('content')
<div data-landing>
    {{-- 1. Hero: video de costura en bucle (póster mientras carga) --}}
    <section class="relative -mt-14 flex min-h-[100svh] items-end overflow-hidden pb-20 md:items-center md:pb-0" aria-labelledby="hero-title">
        <div class="absolute inset-0" aria-hidden="true">
            @if ($hero)
                <video class="h-full w-full object-cover" data-autoplay muted loop playsinline preload="metadata" poster="{{ $hero->posterUrl() }}">
                    <source src="{{ $hero->videoUrl('webm') }}" type="video/webm">
                    <source src="{{ $hero->videoUrl('mp4') }}" type="video/mp4">
                </video>
            @else
                <div class="h-full w-full bg-[radial-gradient(ellipse_at_30%_20%,var(--color-brand-800),transparent_55%),radial-gradient(ellipse_at_80%_80%,#3a1a08,transparent_50%)]"></div>
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-ink-950 via-ink-950/55 to-ink-950/40"></div>
        </div>

        <div class="relative mx-auto flex w-full max-w-6xl flex-col items-start gap-6 px-4 pt-28">
            <img data-hero-logo src="{{ asset('images/brand/logo-mark.svg') }}" alt="" aria-hidden="true" class="h-16 w-16 md:h-20 md:w-20" width="80" height="80">
            <h1 id="hero-title" class="max-w-4xl text-4xl font-bold leading-[1.05] md:text-7xl">
                @foreach ($words as $i => $word)
                    <span class="hero-word" style="--i: {{ $i }}">{{ $word }}</span>
                @endforeach
            </h1>
            <p class="hero-fade max-w-xl text-lg text-steel-300" style="--i: 6">{{ $portal->text($portal->subheadline) }}</p>
            <div class="hero-fade flex flex-wrap gap-3" style="--i: 8">
                <a href="{{ route('portal.builder') }}" class="btn-primary">{{ __('portal.home.cta_project') }}</a>
                @if ($whatsapp)<a href="{{ $whatsapp }}" class="btn-secondary" rel="noopener" target="_blank">{{ __('portal.home.cta_whatsapp') }}</a>@endif
            </div>
        </div>
    </section>

    {{-- Marcas atendidas: cinta infinita --}}
    @if ($brands->isNotEmpty())
        <div class="overflow-hidden border-y border-ink-700/60 py-4" aria-label="{{ __('portal.home.brands') }}">
            <div class="flex w-max gap-12 whitespace-nowrap font-display text-lg uppercase tracking-[0.3em] text-steel-300 motion-safe:animate-[marquee_30s_linear_infinite]">
                @foreach ([$brands, $brands, $brands, $brands] as $set)
                    @foreach ($set as $brand)<span>{{ $brand }}</span>@endforeach
                @endforeach
            </div>
        </div>
    @endif

    {{-- 3. Servicios --}}
    @if ($portal->shows('services'))
        <section id="servicos" data-circle-section class="relative overflow-hidden py-24" aria-labelledby="services-title">
            {{-- 2. Transición: el círculo del logo crece con el scroll detrás de la sección (solo transform). --}}
            <div class="pointer-events-none absolute left-1/2 top-24 -z-0 -translate-x-1/2" aria-hidden="true">
                <div data-circle class="aspect-square w-[140vmax] rounded-full bg-[radial-gradient(circle,var(--color-brand-700)_0%,transparent_65%)] opacity-40" style="transform: scale(0.05)"></div>
            </div>
            <div class="relative mx-auto max-w-6xl px-4">
            <h2 id="services-title" data-reveal class="mb-10 text-3xl font-bold md:text-5xl">{{ __('portal.home.services_title') }}</h2>
            <ul data-stagger class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    @php($media = $serviceMedia->get($service->id))
                    <li>
                        <a href="{{ route('portal.builder', ['servico' => $service->slug]) }}" data-tilt @if ($media?->hasVideo()) data-hover-video @endif
                           class="group relative block aspect-[4/5] overflow-hidden rounded-2xl border border-ink-700 bg-ink-900 no-underline">
                            <div class="absolute inset-0 transition-transform duration-500 ease-brand group-hover:scale-110">
                                <x-portal.media-image :item="$media" alt="" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" />
                            </div>
                            @if ($media?->hasVideo())
                                <video class="absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-300 group-hover:opacity-100" muted loop playsinline preload="none">
                                    <source data-src="{{ $media->videoUrl('webm') }}" type="video/webm">
                                    <source data-src="{{ $media->videoUrl('mp4') }}" type="video/mp4">
                                </video>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-ink-950 via-ink-950/20 to-transparent"></div>
                            <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 p-5">
                                <h3 class="text-xl font-semibold text-paper">{{ $service->name }}</h3>
                                <span class="translate-x-0 text-brand-500 transition-transform duration-300 group-hover:translate-x-1" aria-hidden="true">→</span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
            </div>
        </section>
    @endif

    {{-- 4. Configurador en vivo (demo del constructor) --}}
    @if ($portal->shows('configurator'))
        <section class="border-y border-ink-700/60 bg-ink-900/60 py-24" aria-labelledby="configurator-title" x-data="configuratorDemo({})">
            <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 md:grid-cols-2">
                <div data-reveal>
                    <p class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-brand-500">{{ __('portal.home.configurator_kicker') }}</p>
                    <h2 id="configurator-title" class="mb-4 text-3xl font-bold md:text-5xl">{{ __('portal.home.configurator_title') }}</h2>
                    <p class="mb-8 text-steel-300">{{ __('portal.home.configurator_text') }}</p>
                    @php($leatherVariants = $materials->flatMap->variants->take(8))
                    <p class="mb-2 text-sm text-steel-300">{{ __('portal.home.configurator_leather') }}</p>
                    <div class="mb-6 flex flex-wrap gap-2">
                        @foreach ($leatherVariants as $variant)
                            <button type="button" class="size-10 rounded-full border-2 border-ink-700 transition-transform duration-200 ease-brand hover:scale-110"
                                style="background-color: {{ $variant->color_hex }}" aria-label="{{ $variant->name }}"
                                x-on:click="set('seats', '{{ $variant->color_hex }}'); set('doors', '{{ $variant->color_hex }}')"></button>
                        @endforeach
                    </div>
                    <p class="mb-2 text-sm text-steel-300">{{ __('portal.builder.materials.stitching') }}</p>
                    <div class="mb-8 flex flex-wrap gap-2">
                        @foreach (['#C9C9C9', '#FFFFFF', '#0B8FD9', '#D64545', '#E0A100'] as $color)
                            <button type="button" class="size-8 rounded-full border-2 border-ink-700 transition-transform duration-200 hover:scale-110" style="background-color: {{ $color }}" aria-label="{{ $color }}" x-on:click="set('stitching', '{{ $color }}')"></button>
                        @endforeach
                    </div>
                    <a href="{{ route('portal.builder') }}" class="btn-primary">{{ __('portal.home.cta_project') }}</a>
                </div>
                <div data-reveal class="mx-auto w-full max-w-[340px]">
                    <x-portal.interior colors="demo" />
                </div>
            </div>
        </section>
    @endif

    {{-- 5. Antes y después --}}
    @if ($portal->shows('before_after'))
        <section class="mx-auto max-w-6xl px-4 py-24" aria-labelledby="ba-title">
            <h2 id="ba-title" data-reveal class="mb-8 text-3xl font-bold md:text-5xl">{{ __('portal.home.before_after_title') }}</h2>
            <div data-reveal x-data="beforeAfter" class="relative aspect-[16/10] select-none overflow-hidden rounded-2xl border border-ink-700 touch-pan-y"
                 x-on:pointerdown="dragging = true; set($event.clientX)" x-on:pointermove="dragging && set($event.clientX)" x-on:pointerup.window="dragging = false">
                <div class="absolute inset-0"><x-portal.media-image :item="$beforeAfter" collection="image" :alt="__('portal.home.after')" /></div>
                <div class="absolute inset-0 overflow-hidden" :style="outer()" style="transform: translateX(-50%)">
                    <div class="absolute inset-0" :style="inner()" style="transform: translateX(50%)">
                        <x-portal.media-image :item="$beforeAfter" collection="before" :alt="__('portal.home.before')" class="h-full w-full object-cover grayscale" />
                    </div>
                </div>
                <div class="pointer-events-none absolute inset-y-0 left-0 w-full" :style="handle()" style="transform: translateX(50%)">
                    <div class="absolute inset-y-0 -left-px w-0.5 bg-paper"></div>
                    <div class="absolute left-0 top-1/2 flex size-11 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-paper text-ink-950 shadow-lg">⇆</div>
                </div>
                <span class="absolute left-3 top-3 rounded-full bg-ink-950/70 px-3 py-1 text-xs">{{ __('portal.home.before') }}</span>
                <span class="absolute right-3 top-3 rounded-full bg-ink-950/70 px-3 py-1 text-xs">{{ __('portal.home.after') }}</span>
                <label class="sr-only" for="ba-range">{{ __('portal.home.before_after_title') }}</label>
                <input id="ba-range" type="range" min="0" max="100" x-model.number="pos" class="absolute inset-x-0 bottom-0 h-8 w-full cursor-ew-resize opacity-0">
            </div>
        </section>
    @endif

    {{-- 6. Galería filtrable (FLIP) --}}
    @if ($portal->shows('gallery'))
        <section id="galeria" class="mx-auto max-w-6xl px-4 py-24" aria-labelledby="gallery-title">
            <h2 id="gallery-title" data-reveal class="mb-8 text-3xl font-bold md:text-5xl">{{ __('portal.home.gallery_title') }}</h2>
            <x-portal.gallery-grid :items="$galleryItems" />
        </section>
    @endif

    {{-- Reels destacados --}}
    @if ($reels->isNotEmpty())
        <section class="py-12" aria-label="Reels">
            <ul class="flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-4">
                @foreach ($reels as $reel)
                    <li class="aspect-[9/16] w-48 shrink-0 snap-start overflow-hidden rounded-2xl border border-ink-700 md:w-60">
                        <video class="h-full w-full object-cover" data-autoplay muted loop playsinline preload="none" poster="{{ $reel->posterUrl() }}" aria-label="{{ $reel->title }}">
                            <source data-src="{{ $reel->videoUrl('webm') }}" type="video/webm">
                            <source data-src="{{ $reel->videoUrl('mp4') }}" type="video/mp4">
                        </video>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- 7. Material en detalle --}}
    @if ($portal->shows('materials'))
        <section class="border-y border-ink-700/60 bg-ink-900/60 py-24" aria-labelledby="materials-title" x-data="materialPanel">
            <div class="mx-auto max-w-6xl px-4">
                <h2 id="materials-title" data-reveal class="mb-8 text-3xl font-bold md:text-5xl">{{ __('portal.home.materials_title') }}</h2>
                @include('portal.partials.materials-grid', ['materials' => $materials])
            </div>
            @include('portal.partials.material-panel')
        </section>
    @endif

    {{-- Proceso del taller --}}
    @if ($portal->shows('process') && $stages->isNotEmpty())
        <section class="mx-auto max-w-3xl px-4 py-24" aria-labelledby="process-title">
            <h2 id="process-title" data-reveal class="mb-12 text-3xl font-bold md:text-5xl">{{ __('portal.home.process_title') }}</h2>
            <ol data-process class="relative flex flex-col gap-10 pl-10">
                <span class="absolute left-3 top-0 h-full w-px bg-ink-700" aria-hidden="true"></span>
                <span data-process-line class="absolute left-3 top-0 h-full w-px origin-top bg-brand-500" aria-hidden="true"></span>
                @foreach ($stages as $i => $stage)
                    <li data-reveal class="relative">
                        <span class="absolute -left-10 top-0 flex size-7 items-center justify-center rounded-full border border-brand-500 bg-ink-950 font-display text-xs">{{ $i + 1 }}</span>
                        <h3 class="text-xl font-semibold">{{ $stage->name }}</h3>
                        <p class="text-steel-300">{{ __('portal.process.'.($i % 6)) }}</p>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    {{-- 8. Prueba social --}}
    @if ($portal->shows('social_proof'))
        <section class="mx-auto max-w-6xl px-4 py-24" aria-label="{{ __('portal.home.social_title') }}">
            <dl data-stagger class="grid gap-6 text-center sm:grid-cols-3">
                @foreach (['cars' => $portal->counter_cars, 'years' => $portal->counter_years, 'followers' => $portal->counter_followers] as $key => $value)
                    <div class="card">
                        <dt class="text-steel-300">{{ __('portal.home.counters.'.$key) }}</dt>
                        <dd class="font-display text-5xl font-bold text-paper"><span data-count="{{ $value }}">{{ number_format($value, 0, ',', '.') }}</span>+</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    {{-- 9. CTA final --}}
    <section class="relative overflow-hidden py-28 text-center" aria-labelledby="cta-title">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,var(--color-brand-800),transparent_60%)] opacity-40" aria-hidden="true"></div>
        <div data-reveal class="relative mx-auto max-w-2xl px-4">
            <h2 id="cta-title" class="mb-6 text-3xl font-bold md:text-5xl">{{ __('portal.home.cta_title') }}</h2>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="{{ route('portal.builder') }}" class="btn-primary">{{ __('portal.home.cta_project') }}</a>
                @if ($whatsapp)<a href="{{ $whatsapp }}" class="btn-secondary" rel="noopener" target="_blank">{{ __('portal.home.cta_whatsapp') }}</a>@endif
            </div>
        </div>
    </section>
</div>
@endsection
