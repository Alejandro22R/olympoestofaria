<?php

use App\Domain\Alerts\RunScheduledAlerts;
use App\Domain\Auth\Role;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\WorkOrder;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    seedProduction();
    InventoryItem::query()->update(['is_active' => false]); // el stock inicial del seeder es 0
    freezeMonday();
    $this->owner = userWithRole(Role::Owner);
    $this->reception = userWithRole(Role::Reception);
    $this->upholsterer = userWithRole(Role::Upholsterer);
    $this->run = fn () => app(RunScheduledAlerts::class)->execute();
});

it('stays quiet when everything is fine', function () {
    expect(array_sum(($this->run)()))->toBe(0);
    Notification::assertNothingSent();
});

it('alerts low stock once per day', function () {
    InventoryItem::factory()->withStock(1)->create(['min_stock' => 5]);

    expect(($this->run)()['low_stock'])->toBe(1)
        ->and(($this->run)()['low_stock'])->toBe(0);

    $this->travel(1)->days();
    expect(($this->run)()['low_stock'])->toBe(1);
});

it('alerts late orders but not finished ones', function () {
    WorkOrder::factory()->create(['due_at' => now()->subHour()]);
    WorkOrder::factory()->inStatus(WorkOrderStatus::Delivered)->create(['due_at' => now()->subHour()]);

    expect(($this->run)()['late'])->toBe(1);
    Notification::assertSentTo($this->reception, StaffAlert::class, fn (StaffAlert $n) => $n->type === 'work_order_late');
    expect(($this->run)()['late'])->toBe(0);
});

it('warns about orders close to the promised date, including the upholsterer', function () {
    WorkOrder::factory()->create(['due_at' => now()->addHours(10)]);

    expect(($this->run)()['due_soon'])->toBe(1);
    Notification::assertSentTo($this->upholsterer, StaffAlert::class, fn (StaffAlert $n) => $n->type === 'work_order_due_soon');
});

it('alerts orders stalled in the same stage', function () {
    WorkOrder::factory()->create(['due_at' => now()->addDays(10)]);
    $this->travel(49)->hours();

    expect(($this->run)()['stalled'])->toBe(1)
        ->and(($this->run)()['stalled'])->toBe(0);
});

it('alerts tomorrow appointments that are still unconfirmed', function () {
    Appointment::factory()->create(['scheduled_at' => local('2026-11-10 09:00')]);
    Appointment::factory()->create(['scheduled_at' => local('2026-11-10 10:00'), 'status' => 'confirmed']);

    expect(($this->run)()['unconfirmed'])->toBe(1);
});

it('runs from the scheduler command', function () {
    $this->artisan('olympo:alerts')->assertSuccessful();
});
