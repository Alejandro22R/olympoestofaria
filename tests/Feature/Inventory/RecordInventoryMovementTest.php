<?php

use App\Domain\Auth\Role;
use App\Domain\Inventory\Actions\RecordInventoryMovement;
use App\Domain\Inventory\Enums\MovementType;
use App\Models\InventoryItem;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Notification::fake();
    $this->owner = userWithRole(Role::Owner);
    $this->reception = userWithRole(Role::Reception);
    $this->upholsterer = userWithRole(Role::Upholsterer);
    $this->item = InventoryItem::factory()->withStock(10)->create(['min_stock' => 5]);
    $this->record = fn (MovementType $t, float $q) => app(RecordInventoryMovement::class)->execute($this->item, $t, $q, $this->owner);
});

it('records entries, consumption and adjustments with the running balance', function () {
    ($this->record)(MovementType::In, 4.5);
    ($this->record)(MovementType::Consumption, 2);
    $last = ($this->record)(MovementType::Adjustment, -0.5);

    expect($this->item->fresh()->stock)->toBe(12.0)
        ->and($last->stock_after)->toBe(12.0)
        ->and($this->item->movements()->count())->toBe(3);
});

it('never lets the stock go negative', function () {
    ($this->record)(MovementType::Consumption, 10.5);
})->throws(ValidationException::class);

it('rejects zero or negative quantities where they make no sense', function (MovementType $type, float $qty) {
    ($this->record)($type, $qty);
})->with([
    [MovementType::In, 0],
    [MovementType::In, -3],
    [MovementType::Consumption, 0],
    [MovementType::Adjustment, 0],
])->throws(ValidationException::class);

it('alerts inventory managers the moment the stock crosses the minimum', function () {
    ($this->record)(MovementType::Consumption, 4); // 6 → todavía sobre el mínimo
    Notification::assertNothingSent();

    ($this->record)(MovementType::Consumption, 2); // 4 → cruza el mínimo

    Notification::assertSentTo([$this->owner, $this->reception], StaffAlert::class, fn (StaffAlert $n) => $n->type === 'low_stock');
    Notification::assertNotSentTo($this->upholsterer, StaffAlert::class);

    ($this->record)(MovementType::Consumption, 1); // ya estaba bajo: no repite
    Notification::assertSentToTimes($this->owner, StaffAlert::class, 1);
});
