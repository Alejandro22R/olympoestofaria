<?php

namespace App\Filament\Widgets;

use App\Domain\Auth\Permission;
use App\Domain\Reports\BusinessMetrics;
use App\Filament\Resources\InventoryItems\InventoryItemResource;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Filament\Support\Money;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dashboard del dueño (aceptación de la Fase 6): ingresos, conversión, órdenes abiertas y stock crítico.
 */
class OwnerStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '120s';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permission::ReportsView->value);
    }

    protected function getStats(): array
    {
        $metrics = app(BusinessMetrics::class);
        [$from, $to] = $metrics->monthRange();
        [$prevFrom, $prevTo] = $metrics->monthRange(1);

        $revenue = $metrics->revenueCents($from, $to);
        $previous = $metrics->revenueCents($prevFrom, $prevTo);
        $delta = $previous > 0 ? round(($revenue - $previous) / $previous * 100) : null;

        [$q90From] = $metrics->monthRange(2);
        $conversion = $metrics->quoteConversion($q90From, $to);
        $funnel = $metrics->leadFunnel($from, $to);
        $critical = $metrics->criticalStock();

        return [
            Stat::make(__('reports.stats.revenue_month'), Money::format($revenue))
                ->description($delta === null ? __('reports.stats.no_previous') : __('reports.stats.vs_previous', ['delta' => ($delta >= 0 ? '+' : '').$delta]))
                ->descriptionIcon($delta !== null && $delta < 0 ? Heroicon::ArrowTrendingDown : Heroicon::ArrowTrendingUp)
                ->color($delta !== null && $delta < 0 ? 'danger' : 'success')
                ->chart(array_map(fn (array $m) => $m['cents'] / 100, $metrics->revenueByMonth(6))),
            Stat::make(__('reports.stats.conversion'), $conversion['rate'] === null ? '—' : $conversion['rate'].'%')
                ->description(__('reports.stats.conversion_help', ['approved' => $conversion['approved'], 'decided' => $conversion['decided']])),
            Stat::make(__('reports.stats.leads_month'), (string) array_sum($funnel))
                ->description(__('reports.stats.referred', ['count' => $metrics->referredLeads($from, $to)])),
            Stat::make(__('reports.stats.open_orders'), (string) $metrics->openOrders())
                ->url(WorkOrderResource::getUrl()),
            Stat::make(__('reports.stats.critical_stock'), (string) $critical)
                ->color($critical > 0 ? 'danger' : 'success')
                ->descriptionIcon($critical > 0 ? Heroicon::ExclamationTriangle : Heroicon::CheckCircle)
                ->description($critical > 0 ? __('reports.stats.critical_help') : __('reports.stats.stock_ok'))
                ->url(InventoryItemResource::getUrl()),
        ];
    }
}
