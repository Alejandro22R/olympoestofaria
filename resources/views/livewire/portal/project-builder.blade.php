@php
    use App\Filament\Support\Money;
    $steps = [1 => __('portal.builder.steps.vehicle'), 2 => __('portal.builder.steps.pieces'), 3 => __('portal.builder.steps.materials'), 4 => __('portal.builder.steps.summary')];
    $selectedServices = collect($selection)->keys()->map(fn ($id) => $this->services->get($id))->filter();
@endphp

<div class="mx-auto max-w-6xl px-4 pb-32 pt-10 md:pt-16">
    <header class="mb-8 flex flex-col gap-3">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-500">{{ __('portal.builder.kicker') }}</p>
        <h1 class="text-3xl font-bold md:text-5xl">{{ __('portal.builder.title') }}</h1>
    </header>

    {{-- Pasos --}}
    <ol class="mb-8 grid grid-cols-4 gap-2" aria-label="{{ __('portal.builder.progress') }}">
        @foreach ($steps as $n => $label)
            <li>
                <button type="button" wire:click="goTo({{ $n }})" @class([
                    'flex w-full flex-col gap-2 text-left text-xs md:text-sm',
                    'text-paper' => $step >= $n,
                    'text-steel-300' => $step < $n,
                ]) @if ($step === $n) aria-current="step" @endif>
                    <span class="block h-1 w-full origin-left rounded-full bg-ink-700">
                        <span class="block h-1 rounded-full bg-brand-500 transition-transform duration-500 ease-brand" style="transform: scaleX({{ $step >= $n ? 1 : 0 }}); transform-origin: left"></span>
                    </span>
                    <span><span class="font-display">{{ $n }}.</span> {{ $label }}</span>
                </button>
            </li>
        @endforeach
    </ol>

    <div class="grid gap-8 lg:grid-cols-[1fr_380px]">
        <section class="card min-h-[420px]">
            {{-- Paso 1: vehículo --}}
            @if ($step === 1)
                <h2 class="mb-1 text-xl font-semibold">{{ __('portal.builder.vehicle.title') }}</h2>
                <p class="mb-6 text-steel-300">{{ __('portal.builder.vehicle.help') }}</p>

                <div class="mb-6 grid gap-3 sm:grid-cols-3">
                    @foreach ($this->vehicleModels as $model)
                        <button type="button" wire:click="selectModel({{ $model->id }})" @class([
                            'rounded-xl border p-4 text-left transition-colors duration-200',
                            'border-brand-500 bg-brand-500/10' => $vehicle['vehicle_model_id'] === $model->id,
                            'border-ink-700 hover:border-steel-500' => $vehicle['vehicle_model_id'] !== $model->id,
                        ])>
                            <span class="block font-display font-semibold">{{ $model->brand }}</span>
                            <span class="text-sm text-steel-300">{{ $model->model }}</span>
                        </button>
                    @endforeach
                    <button type="button" wire:click="selectModel(null)" class="rounded-xl border border-dashed border-ink-700 p-4 text-left text-steel-300 hover:border-steel-500">
                        {{ __('portal.builder.vehicle.other') }}
                    </button>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="flex flex-col gap-1 text-sm">
                        {{ __('vehicles.fields.brand') }}
                        <input type="text" wire:model.blur="vehicle.brand" class="input" autocomplete="off">
                        @error('vehicle.brand') <span class="text-danger">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        {{ __('vehicles.fields.model') }}
                        <input type="text" wire:model.blur="vehicle.model" class="input" autocomplete="off">
                        @error('vehicle.model') <span class="text-danger">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        {{ __('vehicles.fields.year') }}
                        <input type="number" inputmode="numeric" wire:model.blur="vehicle.year" class="input" min="1950">
                        @error('vehicle.year') <span class="text-danger">{{ $message }}</span> @enderror
                    </label>
                </div>
            @endif

            {{-- Paso 2: piezas --}}
            @if ($step === 2)
                <h2 class="mb-1 text-xl font-semibold">{{ __('portal.builder.pieces.title') }}</h2>
                <p class="mb-6 text-steel-300">{{ __('portal.builder.pieces.help') }}</p>
                @error('selection') <p class="mb-4 text-danger">{{ $message }}</p> @enderror
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($this->services as $service)
                        @php($on = array_key_exists($service->id, $selection))
                        <button type="button" wire:click="toggleService({{ $service->id }})" aria-pressed="{{ $on ? 'true' : 'false' }}" @class([
                            'flex items-start gap-3 rounded-xl border p-4 text-left transition-colors duration-200',
                            'border-brand-500 bg-brand-500/10' => $on,
                            'border-ink-700 hover:border-steel-500' => ! $on,
                        ])>
                            <span @class(['mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-md border', 'border-brand-500 bg-brand-500' => $on, 'border-steel-500' => ! $on])>
                                @if ($on)<svg viewBox="0 0 20 20" class="size-4 fill-paper"><path d="M7.7 13.3 4.4 10l-1.1 1.1 4.4 4.4 9-9-1.1-1.1z"/></svg>@endif
                            </span>
                            <span>
                                <span class="block font-semibold">{{ $service->name }}</span>
                                @if ($service->description)<span class="text-sm text-steel-300">{{ $service->description }}</span>@endif
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Paso 3: material y color --}}
            @if ($step === 3)
                <h2 class="mb-1 text-xl font-semibold">{{ __('portal.builder.materials.title') }}</h2>
                <p class="mb-6 text-steel-300">{{ __('portal.builder.materials.help') }}</p>

                <div class="flex flex-col gap-6">
                    @foreach ($selectedServices as $service)
                        <div>
                            <h3 class="mb-2 font-semibold">{{ $service->name }}</h3>
                            @if ($service->material_m2 === null)
                                <p class="text-sm text-steel-300">{{ __('portal.builder.materials.no_material') }}</p>
                            @else
                                @foreach ($this->materials as $material)
                                    @continue($material->variants->isEmpty())
                                    <p class="mb-1 mt-3 text-xs uppercase tracking-wide text-steel-300">{{ $material->name }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($material->variants as $variant)
                                            @php($active = ($selection[$service->id] ?? null) === $variant->id)
                                            <button type="button" wire:click="selectVariant({{ $service->id }}, {{ $variant->id }})"
                                                title="{{ $variant->name }}" aria-label="{{ $material->name }} — {{ $variant->name }}" aria-pressed="{{ $active ? 'true' : 'false' }}"
                                                @class(['size-10 rounded-full border-2 transition-transform duration-200 ease-brand hover:scale-110', 'border-brand-500 scale-110' => $active, 'border-ink-700' => ! $active])
                                                style="background-color: {{ $variant->color_hex }}"></button>
                                        @endforeach
                                    </div>
                                @endforeach
                                @error('selection.'.$service->id) <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
                            @endif
                        </div>
                    @endforeach

                    <div>
                        <h3 class="mb-2 font-semibold">{{ __('portal.builder.materials.stitching') }}</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($stitchingColors as $color)
                                <button type="button" wire:click="$set('stitching', '{{ $color }}')" aria-label="{{ $color }}" aria-pressed="{{ $stitching === $color ? 'true' : 'false' }}"
                                    @class(['size-8 rounded-full border-2 transition-transform duration-200 hover:scale-110', 'border-brand-500 scale-110' => $stitching === $color, 'border-ink-700' => $stitching !== $color])
                                    style="background-color: {{ $color }}"></button>
                            @endforeach
                        </div>
                    </div>

                    <label class="flex flex-col gap-1 text-sm">
                        {{ __('portal.builder.materials.notes') }}
                        <textarea wire:model.blur="notes" rows="3" class="input" maxlength="1000"></textarea>
                    </label>
                </div>
            @endif

            {{-- Paso 4: resumen y contacto --}}
            @if ($step === 4)
                <h2 class="mb-4 text-xl font-semibold">{{ __('portal.builder.summary.title') }}</h2>
                <dl class="mb-6 grid gap-2 text-sm">
                    <div class="flex justify-between gap-4 border-b border-ink-700 pb-2"><dt class="text-steel-300">{{ __('vehicles.model') }}</dt><dd>{{ $vehicle['brand'] }} {{ $vehicle['model'] }} {{ $vehicle['year'] }}</dd></div>
                    @foreach ($selectedServices as $service)
                        @php($variant = $selection[$service->id] ? $this->materials->flatMap->variants->firstWhere('id', $selection[$service->id]) : null)
                        <div class="flex justify-between gap-4 border-b border-ink-700 pb-2">
                            <dt>{{ $service->name }}</dt>
                            <dd class="flex items-center gap-2 text-steel-300">
                                @if ($variant)<span class="inline-block size-3 rounded-full" style="background-color: {{ $variant->color_hex }}"></span>{{ $variant->material->name }} · {{ $variant->name }}@endif
                            </dd>
                        </div>
                    @endforeach
                </dl>

                <form wire:submit="submit" class="grid gap-4 sm:grid-cols-2">
                    {{-- Honeypot --}}
                    <div class="hidden" aria-hidden="true">
                        <label>Website <input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label>
                    </div>
                    <label class="flex flex-col gap-1 text-sm sm:col-span-2">
                        {{ __('customers.fields.name') }}
                        <input type="text" wire:model.blur="contact.name" class="input" autocomplete="name" required>
                        @error('contact.name') <span class="text-danger">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        WhatsApp
                        <input type="tel" wire:model.blur="contact.phone" class="input" autocomplete="tel" placeholder="+55 11 98765-4321" required>
                        @error('contact.phone') <span class="text-danger">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        {{ __('customers.fields.email') }}
                        <input type="email" wire:model.blur="contact.email" class="input" autocomplete="email">
                        @error('contact.email') <span class="text-danger">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex items-start gap-2 text-sm sm:col-span-2">
                        <input type="checkbox" wire:model="privacy" class="mt-1 accent-brand-500">
                        <span>{{ __('portal.builder.summary.privacy') }}</span>
                    </label>
                    @error('privacy') <span class="text-sm text-danger sm:col-span-2">{{ $message }}</span> @enderror
                    <label class="flex items-start gap-2 text-sm sm:col-span-2">
                        <input type="checkbox" wire:model="contact_opt_in" class="mt-1 accent-brand-500">
                        <span>{{ __('portal.builder.summary.opt_in') }}</span>
                    </label>
                    @if ($turnstileSiteKey)
                        {{-- Cloudflare Turnstile: el script se carga solo aquí y solo si está activo. --}}
                        <div class="sm:col-span-2" wire:ignore
                             x-data="{ id: null, render() { this.id = window.turnstile.render($refs.box, { sitekey: @js($turnstileSiteKey), theme: 'dark', callback: (t) => $wire.set('turnstileToken', t, false) }) } }"
                             x-init="window.turnstile ? render() : (() => { const s = document.createElement('script'); s.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit'; s.async = true; s.onload = () => render(); document.head.appendChild(s) })()"
                             x-on:turnstile-reset.window="window.turnstile && window.turnstile.reset(id)">
                            <div x-ref="box"></div>
                        </div>
                        @error('turnstile') <span class="text-sm text-danger sm:col-span-2">{{ $message }}</span> @enderror
                    @endif
                    <div class="sm:col-span-2">
                        <button type="submit" class="btn-primary w-full sm:w-auto" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="submit">{{ __('portal.builder.summary.submit') }}</span>
                            <span wire:loading wire:target="submit">{{ __('portal.builder.summary.sending') }}</span>
                        </button>
                    </div>
                </form>
            @endif

            @if ($step < 4)
                <div class="mt-8 flex justify-between gap-3">
                    <button type="button" wire:click="back" @class(['btn-secondary', 'invisible' => $step === 1])>{{ __('portal.builder.back') }}</button>
                    <button type="button" wire:click="next" class="btn-primary">{{ __('portal.builder.next') }}</button>
                </div>
            @else
                <button type="button" wire:click="back" class="btn-secondary mt-6">{{ __('portal.builder.back') }}</button>
            @endif
        </section>

        {{-- Visualizador + estimado (fijo en escritorio) --}}
        <aside class="flex flex-col gap-4 lg:sticky lg:top-24 lg:self-start">
            <div class="card" wire:ignore>
                <x-portal.interior colors="$wire.colors" class="mx-auto max-w-[280px]" />
            </div>
            @if ($showEstimate)
                <div class="card" aria-live="polite">
                    <p class="text-sm text-steel-300">{{ __('portal.builder.estimate') }}</p>
                    @if ($this->estimate->isEmpty())
                        <p class="font-display text-2xl font-semibold text-steel-300">—</p>
                    @else
                        <p class="font-display text-2xl font-semibold md:text-3xl">{{ Money::format($this->estimate->minCents) }} – {{ Money::format($this->estimate->maxCents) }}</p>
                    @endif
                    <p class="mt-2 text-xs text-steel-300">{{ __('portal.builder.estimate_help') }}</p>
                </div>
            @endif
        </aside>
    </div>
</div>
