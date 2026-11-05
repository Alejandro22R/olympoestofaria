@props([
    // Expresión Alpine que devuelve {seats, steering_wheel, doors, headliner, flooring, stitching}.
    'colors' => '{}',
    'label' => __('portal.builder.preview'),
])
{{--
    Interior del auto en SVG (vista superior en corte). Zonas = slugs de service_types.
    Cada zona tiene capa base + capa superior que entra por opacidad (ver resources/js/portal/visualizer.js).
--}}
<div
    x-data="interiorVisualizer(() => ({{ $colors }}))"
    x-effect="const c = {{ $colors }}; Object.entries(c).forEach(([zone, color]) => paint(zone, color))"
    {{ $attributes->merge(['class' => 'relative']) }}
>
    <svg viewBox="0 0 320 520" role="img" aria-label="{{ $label }}" class="h-auto w-full">
        <defs>
            <linearGradient id="sheen" x1="0" x2="1" y1="0" y2="1">
                <stop offset="0" stop-color="#fff" stop-opacity="0.18"/>
                <stop offset="0.5" stop-color="#fff" stop-opacity="0"/>
                <stop offset="1" stop-color="#000" stop-opacity="0.25"/>
            </linearGradient>
        </defs>

        @php
            $layer = function (string $zone, string $shape) {
                return <<<HTML
                    <g data-zone="{$zone}">
                        <g x-bind:style="'fill:' + baseOf('{$zone}')">{$shape}</g>
                        <g x-bind:style="'fill:' + topOf('{$zone}') + ';opacity:' + opacityOf('{$zone}') + ';transition:' + (opacityOf('{$zone}') ? 'opacity .45s cubic-bezier(.22,1,.36,1)' : 'none')">{$shape}</g>
                    </g>
                HTML;
            };
        @endphp

        {{-- Carrocería --}}
        <path d="M96 20 Q160 4 224 20 Q284 38 290 110 L296 410 Q292 492 224 506 Q160 516 96 506 Q28 492 24 410 L30 110 Q36 38 96 20 Z" fill="var(--color-ink-900)" stroke="var(--color-ink-700)" stroke-width="3"/>

        {{-- Techo (forro): marco interior --}}
        {!! $layer('headliner', '<path d="M70 70 Q160 50 250 70 L262 452 Q160 476 58 452 Z M84 96 L236 96 L244 432 Q160 450 76 432 Z" fill-rule="evenodd"/>') !!}

        {{-- Piso --}}
        {!! $layer('flooring', '<rect x="84" y="150" width="152" height="282" rx="16"/>') !!}

        {{-- Paneles de puerta --}}
        {!! $layer('doors', '<rect x="42" y="150" width="30" height="130" rx="8"/><rect x="42" y="292" width="30" height="120" rx="8"/><rect x="248" y="150" width="30" height="130" rx="8"/><rect x="248" y="292" width="30" height="120" rx="8"/>') !!}

        {{-- Tablero (no configurable) --}}
        <path d="M78 104 Q160 86 242 104 L238 140 Q160 126 82 140 Z" fill="var(--color-ink-700)"/>

        {{-- Asientos --}}
        {!! $layer('seats', '<rect x="92" y="176" width="58" height="88" rx="16"/><rect x="100" y="160" width="42" height="22" rx="8"/><rect x="170" y="176" width="58" height="88" rx="16"/><rect x="178" y="160" width="42" height="22" rx="8"/><rect x="92" y="312" width="136" height="96" rx="20"/>') !!}
        <g fill="url(#sheen)" pointer-events="none">
            <rect x="92" y="176" width="58" height="88" rx="16"/><rect x="170" y="176" width="58" height="88" rx="16"/><rect x="92" y="312" width="136" height="96" rx="20"/>
        </g>

        {{-- Costuras --}}
        <g fill="none" stroke-width="1.6" stroke-dasharray="3 3" x-bind:style="'stroke:' + topOf('stitching')" pointer-events="none">
            <rect x="100" y="186" width="42" height="68" rx="10"/><rect x="178" y="186" width="42" height="68" rx="10"/>
            <rect x="102" y="322" width="116" height="76" rx="14"/><line x1="160" y1="322" x2="160" y2="398"/>
        </g>

        {{-- Volante --}}
        {!! $layer('steering_wheel', '<path d="M121 106 a20 20 0 1 0 0.1 0 Z M121 114 a12 12 0 1 1 -0.1 0 Z" fill-rule="evenodd"/>') !!}

    </svg>
</div>
