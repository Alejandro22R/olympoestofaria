<?php

use App\Domain\Auth\Role;
use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Enums\PaymentType;
use App\Domain\Quotes\Enums\QuoteStatus;
use App\Domain\Reports\BusinessMetrics;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Filament\Pages\Reports;
use App\Filament\Widgets\OwnerStatsOverview;
use App\Filament\Widgets\RevenueChart;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\ServiceType;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Livewire\Livewire;

beforeEach(function () {
    seedProduction();
    freezeMonday();
    InventoryItem::query()->update(['is_active' => false]);
    $this->metrics = app(BusinessMetrics::class);
});

function approvedPayment(int $cents, $paidAt): Payment
{
    return Payment::query()->create([
        'customer_id' => Customer::factory()->create()->id,
        'type' => PaymentType::Deposit, 'method' => PaymentMethod::Pix, 'provider' => 'manual',
        'status' => PaymentStatus::Approved, 'amount_cents' => $cents, 'paid_at' => $paidAt,
    ]);
}

it('sums the approved payments of the month in the workshop timezone', function () {
    approvedPayment(150000, now());
    approvedPayment(50000, local('2026-11-01 00:30')->utc());      // 1/nov en São Paulo
    approvedPayment(99900, local('2026-10-31 23:30')->utc());      // octubre
    Payment::query()->create(['customer_id' => Customer::factory()->create()->id, 'type' => PaymentType::Deposit, 'method' => PaymentMethod::Pix,
        'provider' => 'manual', 'status' => PaymentStatus::Pending, 'amount_cents' => 70000]);

    [$from, $to] = $this->metrics->monthRange();

    expect($this->metrics->revenueCents($from, $to))->toBe(200000)
        ->and(array_column($this->metrics->revenueByMonth(2), 'cents'))->toBe([99900, 200000]);
});

it('computes the quote conversion over decided quotes', function () {
    $approved = sentQuote();
    $approved->forceFill(['status' => QuoteStatus::Approved])->save();
    $rejected = sentQuote(1000, ['email' => 'b@example.com']);
    $rejected->forceFill(['status' => QuoteStatus::Rejected])->save();
    sentQuote(1000, ['email' => 'c@example.com']); // sin decidir

    [$from, $to] = $this->metrics->monthRange();
    expect($this->metrics->quoteConversion($from, $to))->toBe(['approved' => 1, 'decided' => 2, 'rate' => 50.0]);
});

it('computes profitability with the material consumed', function () {
    $order = WorkOrder::factory()->inStatus(WorkOrderStatus::Delivered)->create();
    $order->forceFill(['delivered_at' => now()])->save();
    $order->items()->create(['service_type_id' => ServiceType::where('slug', 'seats')->value('id'), 'price_cents' => 200000]);
    $item = InventoryItem::factory()->create(['unit_cost_cents' => 10000]);
    $item->movements()->create(['type' => MovementType::Consumption, 'quantity' => -3.5, 'stock_after' => 0, 'work_order_id' => $order->id]);

    [$from, $to] = $this->metrics->monthRange();
    expect($this->metrics->profitability($from, $to))->toMatchArray(['revenue' => 200000, 'material' => 35000, 'margin' => 165000, 'percent' => 82.5]);
    expect($this->metrics->topServices($from, $to)->first()->name)->toBe('Bancos');
});

it('shows the owner dashboard with revenue, conversion and critical stock', function () {
    $this->actingAs(userWithRole(Role::Owner));
    approvedPayment(123400, now());
    InventoryItem::factory()->withStock(1)->create(['min_stock' => 5]);

    Livewire::test(OwnerStatsOverview::class)->assertOk()
        ->assertSee('R$ 1.234,00')->assertSee('Conversão de orçamentos')->assertSee('Estoque crítico');
    Livewire::test(RevenueChart::class)->assertOk();
    $this->get('/admin')->assertOk()->assertSeeLivewire(OwnerStatsOverview::class);
});

it('hides business numbers from reception and the upholsterer', function (Role $role) {
    $this->actingAs(userWithRole($role));

    expect(OwnerStatsOverview::canView())->toBeFalse();
    $this->get('/admin')->assertOk()->assertDontSeeLivewire(OwnerStatsOverview::class);
    $this->get('/admin/reports')->assertForbidden();
})->with([Role::Reception, Role::Upholsterer]);

it('renders the reports page for every period', function (string $period) {
    $this->actingAs(userWithRole(Role::Owner));
    $order = WorkOrder::factory()->for(Vehicle::factory()->state(['brand' => 'Volkswagen']))->create();
    $order->items()->create(['service_type_id' => ServiceType::where('slug', 'seats')->value('id'), 'price_cents' => 100000]);

    $page = Livewire::test(Reports::class)->set('period', $period)->assertOk()->assertSee('Serviços mais vendidos');
    $period === 'last_month' ? $page->assertDontSee('Volkswagen') : $page->assertSee('Volkswagen');
})->with(Reports::PERIODS);
