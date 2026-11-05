<?php

use App\Domain\Appointments\Actions\ChangeAppointmentStatus;
use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Auth\Role;
use App\Domain\Inspections\Actions\RegisterCheckIn;
use App\Domain\Inspections\Enums\ReportChannel;
use App\Domain\Notifications\Customer\MessageEvent;
use App\Domain\Payments\Actions\ApplyPaymentStatus;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Enums\PaymentType;
use App\Domain\Quotes\Actions\DecideQuote;
use App\Domain\Reports\SendReport;
use App\Domain\Settings\WhatsAppSettings;
use App\Domain\WorkOrders\Actions\MoveWorkOrder;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Notifications\CustomerMessage;
use Database\Seeders\MessageTemplateSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

$sent = fn (Customer $customer, MessageEvent $event) => Notification::assertSentTo($customer, CustomerMessage::class, fn (CustomerMessage $m) => $m->event === $event);

beforeEach(function () {
    Notification::fake();
    Storage::fake('private');
    seedProduction();
    $this->user = userWithRole(Role::Reception);
});

it('tells the customer when the quote is approved and when a payment is confirmed', function () use ($sent) {
    $quote = sentQuote(100000);
    app(DecideQuote::class)->approve($quote);
    $sent($quote->customer, MessageEvent::QuoteApproved);

    $payment = Payment::query()->create(['customer_id' => $quote->customer_id, 'quote_id' => $quote->id, 'type' => PaymentType::Deposit,
        'method' => PaymentMethod::Pix, 'provider' => 'mercadopago', 'status' => PaymentStatus::Pending, 'amount_cents' => 30000]);
    app(ApplyPaymentStatus::class)->execute($payment, PaymentStatus::Approved);
    $sent($quote->customer, MessageEvent::PaymentApproved);
});

it('tells the customer when the appointment is cancelled', function () use ($sent) {
    $appointment = Appointment::factory()->create();

    app(ChangeAppointmentStatus::class)->execute($appointment, AppointmentStatus::Cancelled);

    $sent($appointment->vehicle->customer, MessageEvent::AppointmentCancelled);
});

it('announces stages marked to notify and the car being ready, but not silent stages', function () use ($sent) {
    $order = WorkOrder::factory()->create();
    $customer = $order->vehicle->customer;

    app(MoveWorkOrder::class)->execute($order->fresh(), stage('Corte'), $this->user);
    Notification::assertNotSentTo($customer, CustomerMessage::class);

    app(MoveWorkOrder::class)->execute($order->fresh(), stage('Costura'), $this->user);
    $sent($customer, MessageEvent::WorkOrderStage);

    app(MoveWorkOrder::class)->execute($order->fresh(), stage('Pronto para entrega'), $this->user);
    $sent($customer, MessageEvent::WorkOrderReady);
});

it('sends the check-in report automatically after the check-in', function () {
    $customer = Customer::factory()->create(['email' => 'auto@example.com']);
    $appointment = Appointment::factory()->for(Vehicle::factory()->for($customer))->create();

    $inspection = app(RegisterCheckIn::class)->execute($appointment, ['mileage_km' => 10, 'fuel_level' => 'half', 'lgpd_consent' => true, 'marks' => []], $this->user);

    expect($inspection->sends()->where('channel', ReportChannel::Email)->count())->toBe(1);
});

it('sends reports through the WhatsApp Cloud API when it is connected and the customer opted in', function () {
    $this->seed(MessageTemplateSeeder::class);
    $s = app(WhatsAppSettings::class);
    $s->enabled = true;
    $s->phone_number_id = '999';
    $s->access_token = 'EAAG';
    $s->save();
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.9']]])]);
    Notification::swap(new ChannelManager(app())); // envío real (sin fake)

    $customer = Customer::factory()->create(['phone' => '+5511900001111', 'contact_opt_in_at' => now(), 'email' => null]);
    $appointment = Appointment::factory()->for(Vehicle::factory()->for($customer))->create();
    $inspection = app(RegisterCheckIn::class)->execute($appointment, ['mileage_km' => 10, 'fuel_level' => 'half', 'lgpd_consent' => true, 'marks' => []], $this->user);

    $result = app(SendReport::class)->execute($inspection->fresh(), ReportChannel::WhatsApp, $this->user);

    expect($result['url'])->toBeNull();
    Http::assertSent(fn (Request $r) => $r['template']['name'] === 'olympo_checkin_report' && $r['to'] === '5511900001111');
});
