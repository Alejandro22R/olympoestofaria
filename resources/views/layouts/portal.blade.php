<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#000000">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <header class="sticky top-0 z-30 border-b border-ink-700/60 bg-ink-950/80 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ route('home') }}" class="no-underline" aria-label="Olympo">
                <img src="{{ asset('images/brand/logo.svg') }}" alt="Olympo" class="h-9 w-auto">
            </a>
            <nav class="flex items-center gap-3 text-sm">
                @foreach (['pt_BR' => 'PT', 'es' => 'ES'] as $code => $label)
                    <a href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}"
                       @class(['no-underline', 'text-paper font-semibold' => app()->getLocale() === $code, 'text-steel-500' => app()->getLocale() !== $code])
                       hreflang="{{ str_replace('_', '-', $code) }}">{{ $label }}</a>
                @endforeach
            </nav>
        </div>
    </header>

    <main>
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <footer class="border-t border-ink-700/60 py-8 text-center text-sm text-steel-500">
        © {{ date('Y') }} Olympo Estofaria
    </footer>
</body>
</html>
