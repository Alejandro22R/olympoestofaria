<?php

use App\Domain\Auth\Role;
use App\Domain\Inspections\Actions\RegisterCheckIn;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Filament\Pages\ProductionBoard;
use App\Filament\Resources\Inspections\Pages\ViewInspection;
use App\Filament\Resources\WorkOrders\Pages\EditWorkOrder;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    seedProduction();
    $this->order = WorkOrder::factory()->create();
});

it('shows the open orders in their stage columns', function () {
    $this->actingAs(userWithRole(Role::Upholsterer));
    WorkOrder::factory()->inStatus(WorkOrderStatus::Delivered)->create();

    Livewire::test(ProductionBoard::class)
        ->assertSee($this->order->vehicle->formattedPlate())
        ->assertSee('Recepção')
        ->assertSee('Costura');
});

it('lets the upholsterer move a card', function () {
    $this->actingAs(userWithRole(Role::Upholsterer));

    Livewire::test(ProductionBoard::class)->call('moveCard', $this->order->id, stage('Corte')->id);

    expect($this->order->fresh()->status)->toBe(WorkOrderStatus::InProduction);
});

it('shows an error instead of breaking on a forbidden move', function () {
    $this->actingAs(userWithRole(Role::Reception));

    Livewire::test(ProductionBoard::class)
        ->call('moveCard', $this->order->id, stage('Pronto para entrega')->id)
        ->assertNotified();

    expect($this->order->fresh()->status)->toBe(WorkOrderStatus::Received);
});

it('hides the board from customers', function () {
    $this->actingAs(userWithRole(Role::Customer))->get('/admin/production')->assertForbidden();
});

it('lets the upholsterer move but not edit the order data', function () {
    $user = userWithRole(Role::Upholsterer);

    expect($user->can('progress', $this->order))->toBeTrue()
        ->and($user->can('update', $this->order))->toBeFalse()
        ->and($user->can('deliver', $this->order))->toBeFalse();
});

it('saves items on the order and warns when stock is short', function () {
    $this->actingAs(userWithRole(Role::Reception));
    $item = InventoryItem::factory()->withStock(2)->create();

    Livewire::test(EditWorkOrder::class, ['record' => $this->order->getRouteKey()])
        ->fillForm(['items' => [['inventory_item_id' => $item->id, 'quantity' => 5, 'price_cents' => '150.00', 'description' => 'Couro']]])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified(__('inventory.errors.missing_for_order'));

    expect($this->order->items()->first()->price_cents)->toBe(15000);
});

it('authorizes the presence channel of an order for staff only', function () {
    config(['broadcasting.default' => 'reverb']);
    Broadcast::forgetDrivers();
    require base_path('routes/channels.php');

    $this->actingAs(userWithRole(Role::Upholsterer))
        ->post('/broadcasting/auth', ['channel_name' => 'presence-work-orders.'.$this->order->id, 'socket_id' => '1.2'])
        ->assertOk()
        ->assertJsonPath('channel_data', fn (string $data) => str_contains($data, '"name"'));

    $this->actingAs(userWithRole(Role::Customer))
        ->post('/broadcasting/auth', ['channel_name' => 'presence-work-orders.'.$this->order->id, 'socket_id' => '1.2'])
        ->assertForbidden();
});

it('opens a work order from the inspection page', function () {
    Storage::fake('private');
    $user = userWithRole(Role::Reception);
    $this->actingAs($user);
    $inspection = app(RegisterCheckIn::class)->execute(
        Appointment::factory()->create(),
        ['mileage_km' => 10, 'fuel_level' => 'full', 'lgpd_consent' => true, 'marks' => []],
        $user,
    );

    Livewire::test(ViewInspection::class, ['record' => $inspection->getRouteKey()])
        ->callAction('createWorkOrder')
        ->assertRedirect();

    expect(WorkOrder::query()->where('inspection_id', $inspection->id)->exists())->toBeTrue();
});
