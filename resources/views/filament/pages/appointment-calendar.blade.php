<x-filament-panels::page>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-display text-xl font-semibold capitalize">{{ $monthLabel }}</h2>
        <div class="flex gap-2">
            <x-filament::button color="gray" size="sm" icon="heroicon-m-chevron-left" wire:click="previousMonth" :label-sr-only="true">
                {{ __('appointments.previous_month') }}
            </x-filament::button>
            <x-filament::button color="gray" size="sm" wire:click="goToToday">{{ __('appointments.today') }}</x-filament::button>
            <x-filament::button color="gray" size="sm" icon="heroicon-m-chevron-right" wire:click="nextMonth" :label-sr-only="true">
                {{ __('appointments.next_month') }}
            </x-filament::button>
        </div>
    </div>

    {{-- Respaldo si el socket cae: refresco ligero cada 60 s. --}}
    <div wire:poll.60s class="overflow-x-auto">
        <div class="grid min-w-[720px] grid-cols-7 gap-px overflow-hidden rounded-xl border border-gray-200 bg-gray-200 dark:border-white/10 dark:bg-white/10">
            @foreach ($weekdays as $weekday)
                <div class="bg-white px-2 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-900 dark:text-gray-400">{{ $weekday }}</div>
            @endforeach

            @foreach ($weeks as $week)
                @foreach ($week as $day)
                    @php
                        $closed = $day['capacity'] === 0;
                        $full = ! $closed && $day['booked'] >= $day['capacity'];
                    @endphp
                    <div @class([
                        'flex min-h-28 flex-col gap-1 p-2 text-sm',
                        'bg-white dark:bg-gray-900' => $day['in_month'],
                        'bg-gray-50 text-gray-400 dark:bg-gray-950 dark:text-gray-600' => ! $day['in_month'],
                    ]) data-date="{{ $day['date'] }}">
                        <div class="flex items-center justify-between gap-1">
                            <span @class([
                                'inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold',
                                'bg-primary-600 text-white' => $day['is_today'],
                            ])>{{ $day['day'] }}</span>

                            @if ($closed)
                                <x-filament::badge color="gray" size="sm">{{ $day['holiday'] ?? __('appointments.closed') }}</x-filament::badge>
                            @else
                                <x-filament::badge :color="$full ? 'danger' : ($day['booked'] > 0 ? 'warning' : 'success')" size="sm">
                                    {{ __('appointments.booked', ['booked' => $day['booked'], 'capacity' => $day['capacity']]) }}
                                </x-filament::badge>
                            @endif
                        </div>

                        @if ($day['holiday'] && ! $closed)
                            <span class="text-xs text-gray-600 dark:text-gray-400">{{ $day['holiday'] }}</span>
                        @endif

                        <ul class="flex flex-col gap-1">
                            @foreach ($day['appointments'] as $appointment)
                                <li>
                                    <a href="{{ $appointment['url'] }}" class="flex items-center gap-1 rounded-md bg-gray-100 px-1.5 py-0.5 text-xs hover:bg-gray-200 dark:bg-white/5 dark:hover:bg-white/10">
                                        <span class="font-semibold tabular-nums">{{ $appointment['time'] }}</span>
                                        <span class="truncate">{{ $appointment['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        @if ($day['create_url'] && $day['in_month'])
                            <a href="{{ $day['create_url'] }}" class="mt-auto text-xs text-primary-600 hover:underline dark:text-primary-400">
                                + {{ __('appointments.new_for_day') }}
                            </a>
                        @endif
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
