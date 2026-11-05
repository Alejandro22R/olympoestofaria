{{-- Materiales con sus colores; al elegir uno se abre el panel de detalle (materialPanel). --}}
<ul data-stagger class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($materials as $material)
        @foreach ($material->variants as $variant)
            <li data-material-type="{{ $material->type->value }}">
                <button type="button" class="group w-full overflow-hidden rounded-2xl border border-ink-700 bg-ink-900 text-left transition-colors duration-200 hover:border-steel-500"
                    x-on:click="show(@js([
                        'material' => $material->name,
                        'variant' => $variant->name,
                        'color' => $variant->color_hex,
                        'type' => $material->type->getLabel(),
                        'origin' => $material->origin,
                        'description' => $material->description,
                    ]))">
                    <span class="block aspect-[4/3] overflow-hidden">
                        <span class="block h-full w-full transition-transform duration-500 ease-brand group-hover:scale-110"
                              style="background: radial-gradient(circle at 30% 25%, rgba(255,255,255,.18), transparent 45%), {{ $variant->color_hex }}"></span>
                    </span>
                    <span class="block p-4">
                        <span class="block font-semibold">{{ $variant->name }}</span>
                        <span class="text-sm text-steel-300">{{ $material->name }}</span>
                    </span>
                </button>
            </li>
        @endforeach
    @endforeach
</ul>
