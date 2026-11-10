<x-filament-widgets::widget>
    <div class="flex flex-col gap-6">
        {{-- Saludo --}}
        <div class="flex flex-wrap items-end justify-between gap-3" data-tour="dash-greeting">
            <div>
                <h2 class="font-display text-2xl font-bold text-gray-950 dark:text-white">{{ $greeting }}</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $date }}</p>
            </div>
        </div>

        {{-- Accesos rápidos --}}
        @if (count($actions))
            <section aria-labelledby="qa-title" data-tour="dash-actions">
                <h3 id="qa-title" class="sr-only">{{ __('dashboard.actions.title') }}</h3>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($actions as $action)
                        <a href="{{ $action['url'] }}" data-tour="{{ $action['tour'] }}"
                           class="olympo-tone olympo-tone-{{ $action['tone'] }} group flex items-center gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 transition hover:ring-primary-500 dark:bg-gray-900 dark:ring-white/10 dark:hover:ring-primary-500">
                            <span class="olympo-tone-icon flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary-600 text-white transition-transform group-hover:scale-105">
                                <x-filament::icon :icon="$action['icon']" class="size-6" />
                            </span>
                            <span class="flex flex-col">
                                <span class="font-semibold text-gray-950 dark:text-white">{{ $action['label'] }}</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $action['hint'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Recepciones de hoy --}}
            @if ($calendarUrl)
                <x-filament::section class="lg:col-span-2" data-tour="dash-today">
                    <x-slot name="heading">{{ __('dashboard.today.appointments') }}</x-slot>
                    <x-slot name="afterHeader">
                        <x-filament::link :href="$calendarUrl" size="sm">{{ __('dashboard.today.see_calendar') }}</x-filament::link>
                    </x-slot>

                    @forelse ($appointments as $appointment)
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 py-3 last:border-0 dark:border-white/10">
                            <div class="flex items-center gap-3">
                                <span class="font-display text-lg font-semibold tabular-nums text-gray-950 dark:text-white">{{ $appointment->scheduled_at->setTimezone($tz)->format('H:i') }}</span>
                                <span class="flex flex-col">
                                    <span class="font-medium text-gray-950 dark:text-white">{{ $appointment->vehicle->label() }}</span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $appointment->vehicle->customer->name }}</span>
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-filament::badge :color="$appointment->status->getColor()">{{ $appointment->status->getLabel() }}</x-filament::badge>
                                @if ($canCheckIn && $appointment->status !== \App\Domain\Appointments\Enums\AppointmentStatus::CheckedIn)
                                    <x-filament::button size="sm" tag="a" :href="\App\Filament\Resources\Appointments\AppointmentResource::getUrl('check-in', ['record' => $appointment])" icon="heroicon-m-clipboard-document-check">
                                        {{ __('dashboard.today.check_in') }}
                                    </x-filament::button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-gray-600 dark:text-gray-400">{{ __('dashboard.today.no_appointments') }}</p>
                    @endforelse
                </x-filament::section>
            @endif

            {{-- Pendientes --}}
            @if (count($cards))
                <div @class(['grid content-start gap-3', 'sm:grid-cols-2 lg:col-span-3 lg:grid-cols-4' => ! $calendarUrl]) data-tour="dash-pending">
                    @foreach ($cards as $card)
                        <a href="{{ $card['url'] }}" class="olympo-tone olympo-tone-{{ $card['color'] === 'info' ? 'primary' : $card['color'] }} flex items-center justify-between gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 transition hover:ring-primary-500 dark:bg-gray-900 dark:ring-white/10">
                            <span class="flex items-center gap-3">
                                <x-filament::icon :icon="$card['icon']" @class(['size-6', 'text-success-600 dark:text-success-400' => $card['color'] === 'success', 'text-danger-600 dark:text-danger-400' => $card['color'] === 'danger', 'text-warning-600 dark:text-warning-400' => $card['color'] === 'warning', 'text-primary-600 dark:text-primary-400' => $card['color'] === 'info']) />
                                <span class="text-sm font-medium text-gray-950 dark:text-white">{{ $card['label'] }}</span>
                            </span>
                            <span class="olympo-tone-text font-display text-2xl font-bold tabular-nums text-gray-950 dark:text-white">{{ $card['count'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
