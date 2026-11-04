<?php

use App\Domain\WorkOrders\Actions\CancelWorkOrder;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => seedProduction());

it('cancels an open order with a reason and keeps it in history', function () {
    $order = WorkOrder::factory()->inStatus(WorkOrderStatus::InProduction)->create();

    app(CancelWorkOrder::class)->execute($order, 'Cliente desistiu');

    expect($order->fresh()->status)->toBe(WorkOrderStatus::Cancelled)
        ->and($order->updates()->first()->note)->toBe('Cliente desistiu');
});

it('cannot cancel a ready or delivered order', function (WorkOrderStatus $status) {
    app(CancelWorkOrder::class)->execute(WorkOrder::factory()->inStatus($status)->create(), 'x');
})->with([WorkOrderStatus::Ready, WorkOrderStatus::Delivered])->throws(ValidationException::class);

it('requires a reason', function () {
    app(CancelWorkOrder::class)->execute(WorkOrder::factory()->create(), '  ');
})->throws(ValidationException::class);
