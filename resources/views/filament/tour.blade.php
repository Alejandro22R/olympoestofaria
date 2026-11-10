{{-- Tutorial guiado de la pantalla actual (driver.js). Se abre solo la primera vez;
     después, con el botón "?" de la barra superior. --}}
@php
    $tourKey = \App\Domain\Tours\TourCatalog::keyForRoute(request()->route()?->getName());
    $tourUser = auth()->user();
    $tourConfig = $tourKey && $tourUser ? [
        'key' => $tourKey,
        'autostart' => ! in_array($tourKey, $tourUser->completed_tours ?? [], true),
        'completeUrl' => route('admin.tours.complete', ['tour' => $tourKey]),
        'steps' => \App\Domain\Tours\TourCatalog::steps($tourKey),
        'labels' => [
            'next' => __('tours.labels.next'),
            'prev' => __('tours.labels.prev'),
            'done' => __('tours.labels.done'),
            'progress' => __('tours.labels.progress'),
        ],
    ] : null;
@endphp
@if ($tourConfig)
    <script type="application/json" id="olympo-tour">{!! json_encode($tourConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    @vite('resources/js/filament/tour.js')
@endif
