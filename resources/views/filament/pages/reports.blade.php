@php use App\Filament\Support\Money; @endphp
<x-filament-panels::page>
    <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('reports.period') }}">
        @foreach ($periods as $key => $label)
            <x-filament::button size="sm" :color="$period === $key ? 'primary' : 'gray'" wire:click="$set('period', '{{ $key }}')">{{ $label }}</x-filament::button>
        @endforeach
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('reports.revenue') }}</p>
            <p class="font-display text-2xl font-semibold">{{ Money::format($revenue) }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('reports.stats.conversion') }}</p>
            <p class="font-display text-2xl font-semibold">{{ $conversion['rate'] === null ? '—' : $conversion['rate'].'%' }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('reports.stats.conversion_period', ['approved' => $conversion['approved'], 'decided' => $conversion['decided']]) }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('reports.average_hours') }}</p>
            <p class="font-display text-2xl font-semibold">{{ $averageHours === null ? '—' : number_format($averageHours, 1, ',', '.').' h' }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('reports.margin') }}</p>
            <p class="font-display text-2xl font-semibold">{{ $profit['percent'] === null ? '—' : $profit['percent'].'%' }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('reports.margin_help', ['orders' => $profit['orders']]) }}</p>
        </x-filament::section>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-filament::section :heading="__('reports.top_services')">
            <table class="w-full text-sm">
                <thead><tr class="text-start text-gray-500 dark:text-gray-400"><th class="py-1 text-start font-medium">{{ __('work_orders.fields.service') }}</th><th class="py-1 text-end font-medium">{{ __('reports.orders') }}</th><th class="py-1 text-end font-medium">{{ __('reports.revenue') }}</th></tr></thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($services as $row)
                        <tr><td class="py-2">{{ $row->name }}</td><td class="py-2 text-end tabular-nums">{{ $row->orders }}</td><td class="py-2 text-end font-display tabular-nums">{{ Money::format($row->cents) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-3 text-gray-500 dark:text-gray-400">{{ __('reports.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section :heading="__('reports.top_brands')">
            <table class="w-full text-sm">
                <thead><tr class="text-gray-500 dark:text-gray-400"><th class="py-1 text-start font-medium">{{ __('vehicles.fields.brand') }}</th><th class="py-1 text-end font-medium">{{ __('reports.orders') }}</th></tr></thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($brands as $row)
                        <tr><td class="py-2">{{ $row->brand }}</td><td class="py-2 text-end tabular-nums">{{ $row->orders }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="py-3 text-gray-500 dark:text-gray-400">{{ __('reports.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section :heading="__('reports.profitability')">
            <dl class="grid gap-2 text-sm">
                <div class="flex justify-between"><dt>{{ __('reports.services_revenue') }}</dt><dd class="font-display tabular-nums">{{ Money::format($profit['revenue']) }}</dd></div>
                <div class="flex justify-between"><dt>{{ __('reports.material_cost') }}</dt><dd class="font-display tabular-nums">− {{ Money::format($profit['material']) }}</dd></div>
                <div class="flex justify-between border-t border-gray-200 pt-2 font-semibold dark:border-white/10"><dt>{{ __('reports.margin') }}</dt><dd class="font-display tabular-nums">{{ Money::format($profit['margin']) }}</dd></div>
            </dl>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('reports.profitability_help') }}</p>
        </x-filament::section>

        <x-filament::section :heading="__('reports.leads')">
            <dl class="grid gap-2 text-sm">
                @foreach ($funnel as $label => $count)
                    <div class="flex justify-between"><dt>{{ $label }}</dt><dd class="tabular-nums">{{ $count }}</dd></div>
                @endforeach
                <div class="flex justify-between border-t border-gray-200 pt-2 dark:border-white/10"><dt>{{ __('reports.referred_leads') }}</dt><dd class="tabular-nums">{{ $referred }}</dd></div>
            </dl>
        </x-filament::section>
    </div>
</x-filament-panels::page>
