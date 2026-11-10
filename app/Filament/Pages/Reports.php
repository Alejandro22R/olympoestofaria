<?php

namespace App\Filament\Pages;

use App\Domain\Auth\Permission;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Reports\BusinessMetrics;
use App\Domain\Settings\GeneralSettings;
use App\Filament\Concerns\HasModuleIntro;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;

/**
 * Reportes del dueño: servicios más vendidos, marcas frecuentes, tiempo por trabajo,
 * rentabilidad y embudo de leads con referidos, por periodo.
 */
class Reports extends Page
{
    use HasModuleIntro;

    protected static function moduleKey(): string
    {
        return 'reports';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.reports';

    public const PERIODS = ['month', 'last_month', 'quarter', 'year'];

    #[Url]
    public string $period = 'month';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::ReportsView->value);
    }

    public static function getNavigationLabel(): string
    {
        return __('reports.title');
    }

    public function getTitle(): string|Htmlable
    {
        return __('reports.title');
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function range(BusinessMetrics $metrics): array
    {
        $now = CarbonImmutable::now(app(GeneralSettings::class)->timezone);

        return match (in_array($this->period, self::PERIODS, true) ? $this->period : 'month') {
            'last_month' => $metrics->monthRange(1),
            'quarter' => [$metrics->monthRange(2)[0], $metrics->monthRange()[1]],
            'year' => [$now->startOfYear()->utc(), $now->startOfYear()->addYear()->utc()],
            default => $metrics->monthRange(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $metrics = app(BusinessMetrics::class);
        [$from, $to] = $this->range($metrics);

        return [
            'periods' => collect(self::PERIODS)->mapWithKeys(fn (string $p) => [$p => __('reports.periods.'.$p)])->all(),
            'revenue' => $metrics->revenueCents($from, $to),
            'conversion' => $metrics->quoteConversion($from, $to),
            'services' => $metrics->topServices($from, $to),
            'brands' => $metrics->topBrands($from, $to),
            'averageHours' => $metrics->averageJobHours($from, $to),
            'profit' => $metrics->profitability($from, $to),
            'funnel' => collect($metrics->leadFunnel($from, $to))->mapWithKeys(fn (int $n, string $s) => [ProjectStatus::from($s)->getLabel() => $n])->all(),
            'referred' => $metrics->referredLeads($from, $to),
        ];
    }
}
