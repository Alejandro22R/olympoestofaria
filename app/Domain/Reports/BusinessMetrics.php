<?php

namespace App\Domain\Reports;

use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Quotes\Enums\QuoteStatus;
use App\Domain\Settings\GeneralSettings;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Quote;
use App\Models\WorkOrder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Métricas del dueño (dashboard y reportes). Dinero en centavos; los periodos se calculan en la
 * zona horaria del taller y se consultan en UTC.
 */
class BusinessMetrics
{
    public function __construct(private readonly GeneralSettings $general) {}

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable} [inicio, fin) en UTC
     */
    public function monthRange(int $monthsAgo = 0): array
    {
        $start = CarbonImmutable::now($this->general->timezone)->startOfMonth()->subMonthsNoOverflow($monthsAgo);

        return [$start->utc(), $start->addMonthNoOverflow()->utc()];
    }

    /** Ingresos: pagos aprobados en el periodo (los reembolsados quedan fuera). */
    public function revenueCents(CarbonImmutable $from, CarbonImmutable $to): int
    {
        return (int) Payment::query()->where('status', PaymentStatus::Approved)
            ->whereBetween('paid_at', [$from, $to->subSecond()])->sum('amount_cents');
    }

    /**
     * Ingresos por mes (últimos N meses, el más antiguo primero).
     *
     * @return list<array{label: string, cents: int}>
     */
    public function revenueByMonth(int $months = 6): array
    {
        $rows = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            [$from, $to] = $this->monthRange($i);
            $rows[] = ['label' => $from->setTimezone($this->general->timezone)->translatedFormat('M/y'), 'cents' => $this->revenueCents($from, $to)];
        }

        return $rows;
    }

    /**
     * Conversión de cotizaciones decididas en el periodo: aprobadas / (aprobadas + rechazadas + vencidas).
     *
     * @return array{approved: int, decided: int, rate: float|null}
     */
    public function quoteConversion(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $sent = Quote::query()->whereNotNull('sent_at')->whereBetween('sent_at', [$from, $to->subSecond()]);
        $approved = (clone $sent)->where('status', QuoteStatus::Approved)->count();
        $decided = (clone $sent)->whereIn('status', [QuoteStatus::Approved, QuoteStatus::Rejected, QuoteStatus::Expired])->count();

        return ['approved' => $approved, 'decided' => $decided, 'rate' => $decided > 0 ? round($approved / $decided * 100, 1) : null];
    }

    /**
     * Embudo de leads del periodo (proyectos enviados por el portal o cargados a mano).
     *
     * @return array<string, int>
     */
    public function leadFunnel(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $counts = Project::query()->whereBetween('submitted_at', [$from, $to->subSecond()])
            ->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        return collect(ProjectStatus::cases())->mapWithKeys(fn (ProjectStatus $s) => [$s->value => (int) ($counts[$s->value] ?? 0)])->all();
    }

    public function referredLeads(CarbonImmutable $from, CarbonImmutable $to): int
    {
        return Project::query()->whereNotNull('referred_by_customer_id')->whereBetween('submitted_at', [$from, $to->subSecond()])->count();
    }

    public function openOrders(): int
    {
        return WorkOrder::query()->whereIn('status', [WorkOrderStatus::Received, WorkOrderStatus::InProduction, WorkOrderStatus::Ready])->count();
    }

    public function criticalStock(): int
    {
        return InventoryItem::query()->where('is_active', true)->belowMinimum()->count();
    }

    /**
     * Servicios más vendidos (por ingresos en órdenes del periodo).
     *
     * @return Collection<int, object{name: string, orders: int, cents: int}&\stdClass>
     */
    public function topServices(CarbonImmutable $from, CarbonImmutable $to, int $limit = 8): Collection
    {
        $locale = app()->getLocale();

        return DB::table('work_order_items')
            ->join('work_orders', 'work_orders.id', '=', 'work_order_items.work_order_id')
            ->join('service_types', 'service_types.id', '=', 'work_order_items.service_type_id')
            ->where('work_orders.status', '!=', WorkOrderStatus::Cancelled->value)
            ->whereBetween('work_orders.created_at', [$from, $to->subSecond()])
            ->groupBy('service_types.id', 'service_types.name')
            ->orderByDesc('cents')->limit($limit)
            ->get([
                'service_types.name',
                DB::raw('count(distinct work_orders.id) as orders'),
                DB::raw('sum(work_order_items.price_cents) as cents'),
            ])
            ->map(function (object $row) use ($locale) {
                $names = json_decode((string) $row->name, true);
                $name = is_array($names) ? ($names[$locale] ?? $names['pt_BR'] ?? reset($names)) : $row->name;

                return (object) [
                    'name' => is_string($name) ? $name : '',
                    'orders' => (int) $row->orders,
                    'cents' => (int) $row->cents,
                ];
            });
    }

    /**
     * Marcas más frecuentes (órdenes del periodo).
     *
     * @return Collection<int, object{brand: string, orders: int}&\stdClass>
     */
    public function topBrands(CarbonImmutable $from, CarbonImmutable $to, int $limit = 8): Collection
    {
        return DB::table('work_orders')
            ->join('vehicles', 'vehicles.id', '=', 'work_orders.vehicle_id')
            ->where('work_orders.status', '!=', WorkOrderStatus::Cancelled->value)
            ->whereBetween('work_orders.created_at', [$from, $to->subSecond()])
            ->groupBy('vehicles.brand')->orderByDesc('orders')->limit($limit)
            ->get(['vehicles.brand', DB::raw('count(*) as orders')])
            ->map(fn (object $r) => (object) ['brand' => (string) $r->brand, 'orders' => (int) $r->orders]);
    }

    /** Horas promedio desde el inicio de producción hasta "listo", en órdenes terminadas en el periodo. */
    public function averageJobHours(CarbonImmutable $from, CarbonImmutable $to): ?float
    {
        $avg = WorkOrder::query()->whereNotNull('started_at')->whereNotNull('finished_at')
            ->whereBetween('finished_at', [$from, $to->subSecond()])
            ->avg(DB::raw('extract(epoch from (finished_at - started_at)) / 3600'));

        return $avg === null ? null : round((float) $avg, 1);
    }

    /**
     * Rentabilidad de las órdenes entregadas en el periodo: precio de los servicios menos el costo
     * de los materiales consumidos (costo unitario actual del inventario).
     *
     * @return array{revenue: int, material: int, margin: int, percent: float|null, orders: int}
     */
    public function profitability(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $orders = WorkOrder::query()->where('status', WorkOrderStatus::Delivered)
            ->whereBetween('delivered_at', [$from, $to->subSecond()])->pluck('id');

        $revenue = (int) DB::table('work_order_items')->whereIn('work_order_id', $orders)->sum('price_cents');
        $material = (int) round((float) DB::table('inventory_movements')
            ->join('inventory_items', 'inventory_items.id', '=', 'inventory_movements.inventory_item_id')
            ->whereIn('inventory_movements.work_order_id', $orders)
            ->where('inventory_movements.type', MovementType::Consumption->value)
            ->sum(DB::raw('abs(inventory_movements.quantity) * coalesce(inventory_items.unit_cost_cents, 0)')));

        $margin = $revenue - $material;

        return [
            'revenue' => $revenue,
            'material' => $material,
            'margin' => $margin,
            'percent' => $revenue > 0 ? round($margin / $revenue * 100, 1) : null,
            'orders' => $orders->count(),
        ];
    }
}
