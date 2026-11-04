{{-- Diagrama SVG de daños: un toque agrega una marca a la lista "marks" con zona y posición. --}}
@php
    $centers = collect($zones)->map(fn ($z) => $z['center'])->all();
    [, , $vw, $vh] = array_map('floatval', explode(' ', $viewBox));
@endphp
<div
    x-data="{
        centers: @js($centers),
        colors: @js($colors),
        labels: @js($zoneLabels),
        hover: null,
        marks() {
            return Object.values($wire.data?.marks ?? {}).map((m, i) => {
                const c = this.centers[m.zone];
                if (! c) return null;
                const x = (m.x === null || m.x === undefined || m.x === '') ? c[0] : Number(m.x);
                const y = (m.y === null || m.y === undefined || m.y === '') ? c[1] : Number(m.y);
                return { n: i + 1, x, y, color: this.colors[m.damage_type_id] ?? '#0B8FD9' };
            }).filter(Boolean);
        },
        add(event) {
            const zone = event.target.dataset?.zone;
            if (! zone) return;
            const svg = this.$refs.svg;
            const pt = svg.createSVGPoint();
            pt.x = event.clientX;
            pt.y = event.clientY;
            const p = pt.matrixTransform(svg.getScreenCTM().inverse());
            const key = (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()));
            $wire.set('data.marks.' + key, {
                zone,
                damage_type_id: null,
                note: null,
                photos: [],
                x: Math.round(p.x / {{ $vw }} * 10000) / 100,
                y: Math.round(p.y / {{ $vh }} * 10000) / 100,
            });
        },
    }"
    class="flex flex-col items-center gap-2"
>
    <div class="relative w-full max-w-[260px]">
        <svg x-ref="svg" viewBox="{{ $viewBox }}" class="block h-auto w-full cursor-crosshair select-none touch-manipulation" role="img"
             aria-label="{{ __('inspections.sections.diagram') }}" x-on:click="add($event)">
            <path d="{{ $outline }}" class="fill-gray-100 stroke-gray-400 dark:fill-white/5 dark:stroke-white/30" stroke-width="2"/>
            @foreach ($zones as $key => $zone)
                @php($s = $zone['shape'])
                <rect data-zone="{{ $key }}" x="{{ $s['x'] }}" y="{{ $s['y'] }}" width="{{ $s['w'] }}" height="{{ $s['h'] }}" rx="{{ $s['rx'] }}"
                      class="fill-white stroke-gray-300 transition-colors duration-200 hover:fill-primary-100 dark:fill-gray-900 dark:stroke-white/20 dark:hover:fill-primary-900"
                      stroke-width="1.5"
                      x-on:mouseenter="hover = '{{ $key }}'" x-on:mouseleave="hover = null">
                    <title>{{ $zoneLabels[$key] }}</title>
                </rect>
            @endforeach
        </svg>
        {{-- Marcadores en una capa HTML: Alpine no admite <template x-for> dentro de <svg>. --}}
        <div class="pointer-events-none absolute inset-0">
            <template x-for="mark in marks()" :key="mark.n">
                <span class="absolute flex size-5 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border-2 border-white text-[11px] font-bold text-white shadow"
                      :style="`left: ${mark.x}%; top: ${mark.y}%; background-color: ${mark.color}`"
                      x-text="mark.n"></span>
            </template>
        </div>
    </div>
    <p class="h-5 text-sm text-gray-500 dark:text-gray-400" x-text="hover ? labels[hover] : ''"></p>
</div>
