<?php

use App\Domain\Auth\Role;
use App\Domain\WorkOrders\Actions\MoveWorkOrder;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Domain\WorkOrders\Events\WorkOrderMoved;
use App\Models\InventoryItem;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    seedProduction();
    $this->user = userWithRole(Role::Upholsterer);
    $this->leather = InventoryItem::factory()->withStock(20)->create(['min_stock' => 2]);
    $this->order = WorkOrder::factory()->create();
    $this->order->items()->create(['inventory_item_id' => $this->leather->id, 'quantity' => 6.5, 'price_cents' => 100000]);
    $this->move = fn (string $stage) => app(MoveWorkOrder::class)->execute($this->order->fresh(), stage($stage), $this->user);
});

it('starts production, consumes the materials once and records the history', function () {
    Event::fake([WorkOrderMoved::class]);

    ($this->move)('Desmontagem');
    ($this->move)('Corte');

    $order = $this->order->fresh();
    expect($order->status)->toBe(WorkOrderStatus::InProduction)
        ->and($order->started_at)->not->toBeNull()
        ->and($order->materials_consumed_at)->not->toBeNull()
        ->and($this->leather->fresh()->stock)->toBe(13.5)
        ->and($this->leather->movements()->count())->toBe(1)
        ->and($order->updates()->count())->toBe(2);

    Event::assertDispatched(WorkOrderMoved::class, 2);
});

it('cannot jump from received to ready or delivered', function () {
    ($this->move)('Pronto para entrega');
})->throws(ValidationException::class);

it('marks finished when ready and clears it on rework', function () {
    ($this->move)('Costura');
    ($this->move)('Pronto para entrega');
    expect($this->order->fresh()->finished_at)->not->toBeNull()
        ->and($this->order->fresh()->status)->toBe(WorkOrderStatus::Ready);

    ($this->move)('Montagem');
    expect($this->order->fresh()->finished_at)->toBeNull()
        ->and($this->order->fresh()->status)->toBe(WorkOrderStatus::InProduction);
});

it('blocks production when material is missing and changes nothing', function () {
    $this->leather->forceFill(['stock' => 3])->save();

    try {
        ($this->move)('Desmontagem');
        $this->fail('Expected a validation error');
    } catch (ValidationException $e) {
        expect($e->errors()['materials'][0])->toContain($this->leather->name);
    }

    expect($this->order->fresh()->status)->toBe(WorkOrderStatus::Received)
        ->and($this->leather->fresh()->stock)->toBe(3.0)
        ->and($this->order->fresh()->materials_consumed_at)->toBeNull();
});

it('refuses inactive stages', function () {
    $stage = stage('Corte');
    $stage->update(['is_active' => false]);

    app(MoveWorkOrder::class)->execute($this->order, $stage, $this->user);
})->throws(ValidationException::class);

it('broadcasts only identifiers on the private workshop channel', function () {
    $event = new WorkOrderMoved($this->order->id, 3, 'in_production');

    expect($event->broadcastOn()[0]->name)->toBe('private-workshop')
        ->and($event->broadcastAs())->toBe('work-order.moved')
        ->and(array_keys(get_object_vars($event)))->toContain('workOrderId')
        ->and(json_encode(get_object_vars($event)))->not->toContain($this->order->vehicle->plate);
});
