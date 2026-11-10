<x-filament-panels::page>
    {{-- Respaldo si el socket cae: refresco ligero cada 30 s. --}}
    <div wire:poll.30s
         x-data="{ dragging: null, over: null }"
         class="flex gap-4 overflow-x-auto pb-4">
        @foreach ($columns as $column)
            <section
                class="flex w-72 shrink-0 flex-col rounded-xl border border-gray-200 bg-gray-50 transition-colors duration-200 dark:border-white/10 dark:bg-white/5"
                :class="over === {{ $column['id'] }} ? 'ring-2 ring-primary-500' : ''"
                x-on:dragover.prevent="over = {{ $column['id'] }}"
                x-on:dragleave="over = null"
                x-on:drop.prevent="over = null; if (dragging) { $wire.moveCard(dragging, {{ $column['id'] }}); dragging = null }"
                data-stage="{{ $column['id'] }}"
                @if ($loop->first) data-tour="board-column" @endif
            >
                <header class="flex items-center justify-between gap-2 border-b border-gray-200 px-3 py-2 dark:border-white/10">
                    <div class="flex items-center gap-2">
                        <span class="inline-block size-2.5 rounded-full" style="background-color: {{ $column['color'] }}"></span>
                        <h3 class="font-display text-sm font-semibold">{{ $column['name'] }}</h3>
                    </div>
                    <x-filament::badge size="sm" color="gray">{{ count($column['cards']) }}</x-filament::badge>
                </header>

                <ul class="flex min-h-24 flex-col gap-2 p-2">
                    @forelse ($column['cards'] as $card)
                        <li wire:key="card-{{ $card['id'] }}"
                            @if ($card['can_move']) draggable="true" x-on:dragstart="dragging = {{ $card['id'] }}" x-on:dragend="dragging = null" @endif
                            class="olympo-card rounded-lg border border-gray-200 bg-white p-3 text-sm shadow-sm transition-transform duration-200 hover:-translate-y-0.5 dark:border-white/10 dark:bg-gray-900 {{ $card['can_move'] ? 'cursor-grab active:cursor-grabbing' : '' }}"
                            data-card="{{ $card['id'] }}" @if ($loop->parent->first && $loop->first) data-tour="board-card" @endif>
                            <div class="flex items-center justify-between gap-2">
                                <a href="{{ $card['url'] }}" class="font-display font-semibold hover:underline">{{ $card['plate'] }}</a>
                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ $card['number'] }}</span>
                            </div>
                            <p class="truncate text-gray-600 dark:text-gray-300">{{ $card['vehicle'] }} · {{ $card['customer'] }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                @if ($card['due'])
                                    <x-filament::badge size="sm" :color="$card['late'] ? 'danger' : 'gray'" icon="heroicon-m-clock">{{ $card['due'] }}</x-filament::badge>
                                @endif
                                @if ($card['responsible'])
                                    <span class="text-gray-600 dark:text-gray-400">{{ $card['responsible'] }}</span>
                                @endif
                            </div>
                            @if ($card['can_move'] && $card['targets'] !== [])
                                {{-- Alternativa al arrastre para pantallas táctiles y teclado. --}}
                                <label class="mt-2 block">
                                    <span class="sr-only">{{ __('work_orders.actions.move') }}</span>
                                    <select class="w-full rounded-md border-gray-300 bg-transparent py-1 text-xs dark:border-white/10"
                                            x-on:change="if ($event.target.value) { $wire.moveCard({{ $card['id'] }}, Number($event.target.value)); $event.target.value = '' }">
                                        <option value="">{{ __('work_orders.actions.move') }}…</option>
                                        @foreach ($card['targets'] as $stageId => $stageName)
                                            <option value="{{ $stageId }}">{{ $stageName }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif
                        </li>
                    @empty
                        <li class="rounded-lg border border-dashed border-gray-300 p-4 text-center text-xs text-gray-600 dark:border-white/10 dark:text-gray-400">{{ __('work_orders.empty_column') }}</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
