{{-- Panel lateral del material: entra con translateX; textura en macro con parallax suave. --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true" :aria-label="item?.material" x-on:keydown.escape.window="close()">
    <div class="absolute inset-0 bg-ink-950/70 transition-opacity duration-300" x-on:click="close()"
         x-transition:enter-start="opacity-0" x-transition:leave-end="opacity-0"></div>
    <aside class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col overflow-y-auto border-l border-ink-700 bg-ink-900"
           x-transition:enter="transition-transform duration-500 ease-brand" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition-transform duration-300 ease-brand" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
        <div data-parallax class="relative aspect-square overflow-hidden">
            <div data-parallax-layer class="absolute -inset-8 scale-110"
                 :style="item && `background: repeating-radial-gradient(circle at 40% 40%, rgba(0,0,0,.08) 0 2px, transparent 2px 6px), radial-gradient(circle at 30% 25%, rgba(255,255,255,.22), transparent 50%), ${item.color}`"></div>
        </div>
        <div class="flex flex-col gap-3 p-6">
            <p class="text-sm uppercase tracking-[0.2em] text-brand-500" x-text="item?.type"></p>
            <h3 class="text-2xl font-semibold" x-text="item ? `${item.material} · ${item.variant}` : ''"></h3>
            <p class="text-steel-300" x-show="item?.origin" x-text="item ? '{{ __('catalog.fields.origin') }}: ' + item.origin : ''"></p>
            <p class="text-steel-300" x-show="item?.description" x-text="item?.description"></p>
            <a href="{{ route('portal.builder') }}" class="btn-primary mt-4">{{ __('portal.home.cta_project') }}</a>
            <button type="button" x-ref="close" class="btn-secondary" x-on:click="close()">{{ __('portal.catalog.close') }}</button>
        </div>
    </aside>
</div>
