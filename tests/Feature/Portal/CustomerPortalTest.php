<?php

use App\Domain\Auth\Role;
use App\Domain\Customers\Access\EnsureCustomerUser;
use App\Domain\Customers\Access\TrackingLink;
use App\Domain\Quotes\Actions\DecideQuote;
use App\Domain\Quotes\Enums\QuoteStatus;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Livewire\Portal\QuotePage;
use App\Livewire\Portal\TrackingPage;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Database\Seeders\ProductionSeeder;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(fn () => Notification::fake());

it('opens the quote with the signed link and logs the customer in (no password)', function () {
    $quote = sentQuote();
    $url = $quote->customerReportUrl(7);

    $this->get($url)->assertOk()->assertSee($quote->number())->assertSee('Aprovar orçamento');

    $user = auth()->user();
    expect($user->hasRole(Role::Customer->value))->toBeTrue()
        ->and($quote->customer->fresh()->user_id)->toBe($user->id);
});

it('blocks the quote without a signature or for another customer', function () {
    $quote = sentQuote();

    $this->get(route('portal.quote', $quote))->assertForbidden();

    $other = Customer::factory()->create();
    $this->actingAs(app(EnsureCustomerUser::class)->execute($other))
        ->get(route('portal.quote', $quote))->assertForbidden();
});

it('does not replace a staff session when staff opens the customer link', function () {
    $owner = userWithRole(Role::Owner);
    $quote = sentQuote();

    $this->actingAs($owner)->get($quote->customerReportUrl(7))->assertOk()->assertSee(__('quotes.portal.staff_preview'));
    expect(auth()->id())->toBe($owner->id);
});

it('lets the customer approve and pay the deposit with Pix', function () {
    enableMercadoPago();
    Http::fake(['api.mercadopago.com/v1/payments' => Http::response(mpPixResponse(), 201)]);
    $quote = sentQuote(100000);
    $this->actingAs(app(EnsureCustomerUser::class)->execute($quote->customer));

    Livewire::test(QuotePage::class, ['quote' => $quote])
        ->call('approve')->assertHasErrors('accepted')
        ->set('accepted', true)
        ->call('approve')
        ->assertSee(__('payments.portal.pix_title'))
        ->assertSee('000201');

    expect($quote->fresh()->status)->toBe(QuoteStatus::Approved)
        ->and($quote->fresh()->approved_via)->toBe('portal')
        ->and(Payment::sole()->amount_cents)->toBe(30000);
});

it('tells the customer the workshop will arrange payment when Mercado Pago is off', function () {
    $quote = sentQuote(100000);
    $this->actingAs(app(EnsureCustomerUser::class)->execute($quote->customer));

    Livewire::test(QuotePage::class, ['quote' => $quote])
        ->set('accepted', true)->call('approve')
        ->assertSee('A oficina vai combinar com você o pagamento do sinal de R$ 300,00.');
});

it('lets the customer ask for changes', function () {
    $quote = sentQuote();
    $this->actingAs(app(EnsureCustomerUser::class)->execute($quote->customer));

    Livewire::test(QuotePage::class, ['quote' => $quote])
        ->set('rejectReason', 'Prefiro couro preto')->call('reject');

    expect($quote->fresh()->status)->toBe(QuoteStatus::Rejected)
        ->and($quote->fresh()->rejection_reason)->toBe('Prefiro couro preto');
});

it('shows the work progress with only customer-visible updates and photos', function () {
    Storage::fake('private');
    $this->seed(ProductionSeeder::class);
    $order = WorkOrder::factory()->create();
    Storage::disk('private')->put('work-orders/progress/a.jpg', 'img');
    $order->updates()->create(['status' => 'in_production', 'note' => 'Costura iniciada', 'photos' => ['work-orders/progress/a.jpg'], 'visible_to_customer' => true]);
    $order->updates()->create(['status' => 'in_production', 'note' => 'Nota interna', 'visible_to_customer' => false]);

    $this->get(app(TrackingLink::class)->url($order))
        ->assertOk()->assertSee('Costura iniciada')->assertDontSee('Nota interna')->assertSee('Recepção');

    $update = $order->updates()->where('visible_to_customer', true)->first();
    $this->get(route('portal.tracking.photo', [$order, $update, 0]))->assertOk();

    // Otro cliente no ve las fotos.
    $this->actingAs(app(EnsureCustomerUser::class)->execute(Customer::factory()->create()))
        ->get(route('portal.tracking.photo', [$order, $update, 0]))->assertForbidden();
});

it('lets the customer pay the balance online when the car is ready', function () {
    $this->seed(ProductionSeeder::class);
    enableMercadoPago();
    Http::fake(['api.mercadopago.com/v1/payments' => Http::response(mpPixResponse('777'), 201)]);
    $quote = sentQuote(100000);
    app(DecideQuote::class)->approve($quote);
    $order = WorkOrder::factory()->inStatus(WorkOrderStatus::Ready)->create([
        'quote_id' => $quote->id,
        'vehicle_id' => Vehicle::factory()->create(['customer_id' => $quote->customer_id])->id,
    ]);
    $this->actingAs(app(EnsureCustomerUser::class)->execute($quote->customer));

    Livewire::test(TrackingPage::class, ['workOrder' => $order])->call('payBalance')->assertSee('000201');

    expect(Payment::sole()->amount_cents)->toBe(100000)->and(Payment::sole()->type->value)->toBe('balance');
});

it('authorizes the customer channel only for its owner', function () {
    config(['broadcasting.default' => 'reverb']);
    Broadcast::forgetDrivers();
    require base_path('routes/channels.php');
    $customer = Customer::factory()->create();
    $user = app(EnsureCustomerUser::class)->execute($customer);

    $this->actingAs($user)->post('/broadcasting/auth', ['channel_name' => 'private-customers.'.$customer->id, 'socket_id' => '1.1'])->assertOk();
    $this->actingAs($user)->post('/broadcasting/auth', ['channel_name' => 'private-customers.'.Customer::factory()->create()->id, 'socket_id' => '1.1'])->assertForbidden();
    $this->actingAs($user)->post('/broadcasting/auth', ['channel_name' => 'private-workshop', 'socket_id' => '1.1'])->assertForbidden();
});

it('keeps customers out of the admin panel', function () {
    $user = app(EnsureCustomerUser::class)->execute(Customer::factory()->create());

    $this->actingAs($user)->get('/admin')->assertForbidden();
});
