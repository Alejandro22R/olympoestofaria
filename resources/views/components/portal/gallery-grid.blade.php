@props(['items'])
{{-- Galería filtrable (FLIP). Filtros por marca, servicio y material. --}}
@php
    $tagsOf = fn ($item) => collect([
        $item->brand ? 'marca:'.str($item->brand)->slug() : null,
        $item->serviceType ? 'servico:'.$item->serviceType->slug : null,
        $item->material ? 'material:'.$item->material->type->value : null,
    ])->filter()->implode(' ');
    $filters = collect()
        ->merge($items->pluck('brand')->filter()->unique()->mapWithKeys(fn ($b) => ['marca:'.str($b)->slug() => $b]))
        ->merge($items->pluck('serviceType')->filter()->unique('id')->mapWithKeys(fn ($s) => ['servico:'.$s->slug => $s->name]))
        ->merge($items->pluck('material')->filter()->unique('type')->mapWithKeys(fn ($m) => ['material:'.$m->type->value => $m->type->getLabel()]));
@endphp
<div x-data="flipGallery">
    <div class="mb-6 flex flex-wrap gap-2" role="toolbar" aria-label="{{ __('portal.gallery.filters') }}">
        <button type="button" class="chip" :aria-pressed="(active === 'all').toString()" x-on:click="filter('all')" aria-pressed="true">{{ __('portal.gallery.all') }}</button>
        @foreach ($filters as $key => $label)
            <button type="button" class="chip" :aria-pressed="(active === '{{ $key }}').toString()" x-on:click="filter('{{ $key }}')" aria-pressed="false">{{ $label }}</button>
        @endforeach
    </div>
    <ul x-ref="grid" class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-4">
        @forelse ($items as $item)
            <li data-tags="{{ $tagsOf($item) }}" class="group relative aspect-[4/3] overflow-hidden rounded-2xl border border-ink-700 bg-ink-900" @if ($item->hasVideo()) data-hover-video @endif>
                <div class="h-full w-full transition-transform duration-500 ease-brand group-hover:scale-105">
                    <x-portal.media-image :item="$item" class="h-full w-full object-cover" sizes="(min-width: 768px) 33vw, 50vw" />
                </div>
                @if ($item->hasVideo())
                    <video class="absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-300 group-hover:opacity-100" muted loop playsinline preload="none" poster="{{ $item->posterUrl() }}">
                        <source data-src="{{ $item->videoUrl('webm') }}" type="video/webm">
                        <source data-src="{{ $item->videoUrl('mp4') }}" type="video/mp4">
                    </video>
                @endif
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-ink-950/90 to-transparent p-3">
                    <p class="text-sm font-semibold">{{ $item->title }}</p>
                    <p class="text-xs text-steel-300">{{ trim($item->brand.' '.$item->model) }}</p>
                </div>
            </li>
        @empty
            <li class="col-span-full text-steel-300">{{ __('portal.gallery.empty') }}</li>
        @endforelse
    </ul>
</div>
