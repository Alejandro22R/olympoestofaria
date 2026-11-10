@props(['size' => 'md', 'wordmark' => true, 'tone' => 'panel'])
{{-- Logo del taller: el emblema oficial con el nombre en la tipografía de marca (Sora),
     o el logo que el dueño suba en Ajustes > Marca (versión clara y oscura). --}}
@php
    $brand = \App\Support\Brand::settings();
    $custom = $brand?->hasCustomLogo() ?? false;
    $dark = $brand?->logoDarkUrl() ?? asset(\App\Domain\Settings\BrandSettings::EMBLEM);
    $light = $brand?->logoLightUrl();
    $emblem = $size === 'lg' ? 'size-14' : ($size === 'sm' ? 'size-8' : 'size-9');
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-2.5']) }}>
    @if ($custom)
        @if ($light && $tone !== 'portal')
            <img src="{{ $light }}" alt="Olympo Estofaria" class="h-9 w-auto dark:hidden">
            <img src="{{ $dark }}" alt="Olympo Estofaria" class="hidden h-9 w-auto dark:block">
        @else
            <img src="{{ $dark }}" alt="Olympo Estofaria" class="h-9 w-auto">
        @endif
    @else
        <img src="{{ asset(\App\Domain\Settings\BrandSettings::EMBLEM) }}" alt="{{ $wordmark ? '' : 'Olympo Estofaria' }}" @if ($wordmark) aria-hidden="true" @endif class="{{ $emblem }} shrink-0" width="56" height="56">
        @if ($wordmark)
            <span class="flex flex-col leading-none">
                {{-- Portal: siempre oscuro (tokens paper/steel). Panel: sigue el tema claro/oscuro de Filament. --}}
                <span @class(['font-display text-lg font-bold tracking-[0.18em]', 'text-paper' => $tone === 'portal', 'text-gray-950 dark:text-white' => $tone !== 'portal'])>OLYMPO</span>
                <span @class(['mt-0.5 text-[0.6rem] font-semibold tracking-[0.42em]', 'text-steel-300' => $tone === 'portal', 'text-gray-600 dark:text-gray-300' => $tone !== 'portal'])>ESTOFARIA</span>
            </span>
        @endif
    @endif
</span>
