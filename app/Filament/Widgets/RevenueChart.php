<?php

namespace App\Filament\Widgets;

use App\Domain\Auth\Permission;
use App\Domain\Reports\BusinessMetrics;
use App\Support\Brand;
use Filament\Widgets\ChartWidget;

/**
 * Ingresos de los últimos 12 meses (pagos aprobados).
 */
class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permission::ReportsView->value);
    }

    public function getHeading(): ?string
    {
        return __('reports.revenue_chart');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $months = app(BusinessMetrics::class)->revenueByMonth(12);
        $color = Brand::shades()[500];

        return [
            'datasets' => [[
                'label' => __('reports.revenue'),
                'data' => array_map(fn (array $m) => round($m['cents'] / 100, 2), $months),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'borderRadius' => 6,
            ]],
            'labels' => array_column($months, 'label'),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true]],
        ];
    }
}
