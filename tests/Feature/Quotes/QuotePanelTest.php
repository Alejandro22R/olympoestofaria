<?php

use App\Domain\Auth\Role;
use App\Domain\Inspections\Enums\ReportChannel;
use App\Domain\Quotes\Actions\DecideQuote;
use App\Domain\Quotes\Enums\QuoteStatus;
use App\Filament\Resources\Quotes\Pages\CreateQuote;
use App\Filament\Resources\Quotes\Pages\EditQuote;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Database\Seeders\ProductionSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    Storage::fake('private');
    $this->actingAs(userWithRole(Role::Reception));
});

it('creates, edits and sends a quote from the panel', function () {
    $customer = Customer::factory()->create();

    Livewire::test(CreateQuote::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'valid_until' => now()->addDays(5)->toDateString(),
            'items' => [['description' => 'Volante', 'quantity' => 1, 'unit_price_cents' => '450.00']],
        ])
        ->call('create')->assertHasNoFormErrors();

    $quote = Quote::sole();
    expect($quote->total_cents)->toBe(45000);

    Livewire::test(EditQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('send', data: ['channel' => ReportChannel::WhatsApp->value])
        ->assertHasNoActionErrors();

    expect($quote->fresh()->status)->toBe(QuoteStatus::Sent);
});

it('records a manual deposit and schedules a confirmed appointment', function () {
    $this->seed(ProductionSeeder::class);
    freezeMonday();
    $quote = sentQuote(100000);
    app(DecideQuote::class)->approve($quote);
    Vehicle::factory()->create(['customer_id' => $quote->customer_id, 'plate' => 'ABC1D23']);
    $quote->forceFill(['vehicle_id' => Vehicle::sole()->id])->save();

    Livewire::test(EditQuote::class, ['record' => $quote->getRouteKey()])
        ->callAction('manualPayment', data: ['type' => 'deposit', 'method' => 'cash', 'amount_cents' => '300.00'])
        ->assertHasNoActionErrors()
        ->callAction('schedule', data: ['scheduled_at' => '2026-11-10 09:00:00'])
        ->assertHasNoActionErrors();

    $quote->refresh();
    expect($quote->deposit_paid_at)->not->toBeNull()
        ->and($quote->appointment->status->value)->toBe('confirmed');
});

it('opens a work order with the quote items', function () {
    $this->seed(ProductionSeeder::class);
    $quote = sentQuote(100000);
    app(DecideQuote::class)->approve($quote);
    $quote->forceFill(['vehicle_id' => Vehicle::factory()->create(['customer_id' => $quote->customer_id])->id])->save();

    Livewire::test(EditQuote::class, ['record' => $quote->getRouteKey()])->callAction('workOrder')->assertRedirect();

    $order = WorkOrder::sole();
    expect($order->quote_id)->toBe($quote->id)
        ->and($order->items()->sum('price_cents'))->toEqual(100000);
});

it('hides quotes from the upholsterer', function () {
    $this->actingAs(userWithRole(Role::Upholsterer))->get('/admin/quotes')->assertForbidden();
});
