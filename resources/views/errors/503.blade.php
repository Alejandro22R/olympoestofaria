@php
    try {
        $general = app(\App\Domain\Settings\GeneralSettings::class);
        $whatsapp = $general->whatsapp ? 'https://wa.me/'.ltrim($general->whatsapp, '+') : null;
    } catch (\Throwable) {
        $whatsapp = null;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('system.maintenance.title') }} · Olympo</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center px-4">
    <main class="card max-w-md text-center">
        <img src="{{ asset('images/brand/emblem.svg') }}" alt="Olympo Estofaria" class="mx-auto mb-8 size-24" width="96" height="96">
        <h1 class="mb-3 text-3xl font-bold">{{ __('system.maintenance.title') }}</h1>
        <p class="mb-6 text-steel-300">{{ __('system.maintenance.text') }}</p>
        @if ($whatsapp)
            <a href="{{ $whatsapp }}" class="btn-primary" rel="noopener" target="_blank">{{ __('system.maintenance.whatsapp') }}</a>
        @endif
    </main>
</body>
</html>
