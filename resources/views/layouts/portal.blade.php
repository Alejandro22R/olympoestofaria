@php
    $portalSettings = app(\App\Domain\Settings\PortalSettings::class);
    $general = app(\App\Domain\Settings\GeneralSettings::class);
    $pageTitle = $title ?? $portalSettings->text($portalSettings->seo_title);
    $pageDescription = $description ?? $portalSettings->text($portalSettings->seo_description);
    $whatsapp = $general->whatsapp ? 'https://wa.me/'.ltrim($general->whatsapp, '+') : null;
    $brand = \App\Support\Brand::settings();
    $brandCss = \App\Support\Brand::portalCss();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" @if ($brand?->reduce_motion) data-reduce-motion @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ url()->current() }}">
    @foreach (['pt_BR' => 'pt-BR', 'es' => 'es'] as $code => $hreflang)
        <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ url()->current() }}?lang={{ $code }}">
    @endforeach
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="{{ app()->getLocale() }}">
    <link rel="icon" href="{{ $brand?->faviconUrl() ?? asset('favicon.svg') }}">
    @if ($brandCss)<style>{{ $brandCss }}</style>@endif
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-full focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-paper">{{ __('portal.nav.skip') }}</a>

    <header class="fixed inset-x-0 top-0 z-40 border-b border-ink-700/40 bg-ink-950/70 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ route('home') }}" class="no-underline" aria-label="Olympo">
                <img src="{{ $brand?->logoDarkUrl() ?? asset('images/brand/logo.svg') }}" alt="Olympo" class="h-8 w-auto md:h-9" width="150" height="40">
            </a>
            <nav class="flex items-center gap-4 text-sm" aria-label="{{ __('portal.nav.main') }}">
                <a href="{{ route('portal.catalog') }}" class="hidden text-steel-300 no-underline hover:text-paper sm:inline">{{ __('portal.nav.catalog') }}</a>
                <a href="{{ route('portal.builder') }}" class="hidden text-steel-300 no-underline hover:text-paper sm:inline">{{ __('portal.nav.builder') }}</a>
                <span class="flex items-center gap-2">
                    @foreach (['pt_BR' => 'PT', 'es' => 'ES'] as $code => $label)
                        <a href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}"
                           @class(['no-underline', 'font-semibold text-paper' => app()->getLocale() === $code, 'text-steel-300' => app()->getLocale() !== $code])
                           hreflang="{{ str_replace('_', '-', $code) }}" @if (app()->getLocale() === $code) aria-current="true" @endif>{{ $label }}</a>
                    @endforeach
                </span>
            </nav>
        </div>
    </header>

    <main id="main" class="pt-14">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <footer class="border-t border-ink-700/60 pb-28 pt-10 text-sm text-steel-300 md:pb-10">
        <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 md:flex-row md:items-center md:justify-between">
            <p>© {{ date('Y') }} {{ $general->business_name }} · {{ $general->city }}</p>
            <div class="flex flex-wrap gap-x-4 gap-y-2">
                @if ($portalSettings->instagram_url)<a href="{{ $portalSettings->instagram_url }}" rel="noopener" target="_blank">Instagram</a>@endif
                @if ($whatsapp)<a href="{{ $whatsapp }}" rel="noopener" target="_blank">WhatsApp</a>@endif
                @if ($general->maps_url)<a href="{{ $general->maps_url }}" rel="noopener" target="_blank">{{ __('portal.nav.map') }}</a>@endif
                <a href="{{ route('portal.privacy') }}">{{ __('privacy.legal.privacy_title') }}</a>
                <a href="{{ route('portal.terms') }}">{{ __('privacy.legal.terms_title') }}</a>
            </div>
        </div>
    </footer>

    {{-- CTA fijo en móvil --}}
    @unless (request()->routeIs('portal.builder', 'portal.quote', 'portal.tracking'))
        <div class="fixed inset-x-0 bottom-0 z-40 flex gap-2 border-t border-ink-700/60 bg-ink-950/90 p-3 backdrop-blur-md md:hidden" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))">
            <a href="{{ route('portal.builder') }}" class="btn-primary flex-1">{{ __('portal.home.cta_project') }}</a>
            @if ($whatsapp)<a href="{{ $whatsapp }}" class="btn-secondary" rel="noopener" target="_blank" aria-label="WhatsApp">WhatsApp</a>@endif
        </div>
    @endunless

    @stack('scripts')
</body>
</html>
