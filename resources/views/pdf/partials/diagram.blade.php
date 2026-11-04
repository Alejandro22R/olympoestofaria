@php
    [, , $vw, $vh] = array_map('floatval', explode(' ', $viewBox));
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="{{ $viewBox }}" width="{{ $vw }}" height="{{ $vh }}">
    <path d="{{ $outline }}" fill="#F4F4F4" stroke="#737373" stroke-width="2"/>
    @foreach ($zones as $zone)
        @php($s = $zone['shape'])
        <rect x="{{ $s['x'] }}" y="{{ $s['y'] }}" width="{{ $s['w'] }}" height="{{ $s['h'] }}" rx="{{ $s['rx'] }}" fill="#FFFFFF" stroke="#C9C9C9" stroke-width="1.5"/>
    @endforeach
    @foreach ($marks as $i => $mark)
        @continue(! isset($zones[$mark->zone]))
        <circle cx="{{ $mark->x / 100 * $vw }}" cy="{{ $mark->y / 100 * $vh }}" r="9" fill="{{ $mark->damageType->color_hex }}" stroke="#FFFFFF" stroke-width="2"/>
        <text x="{{ $mark->x / 100 * $vw }}" y="{{ $mark->y / 100 * $vh + 4 }}" font-size="11" font-family="Helvetica" font-weight="bold" fill="#FFFFFF" text-anchor="middle">{{ $i + 1 }}</text>
    @endforeach
</svg>
