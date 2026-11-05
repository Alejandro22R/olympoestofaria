<?php

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Auth\Role;
use App\Domain\Payments\Actions\RequestPixPayment;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Events\PaymentUpdated;
use App\Domain\Quotes\Actions\DecideQuote;
use App\Domain\Settings\PaymentSettings;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Models\WebhookEvent;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Notification::fake();
    enableMercadoPago();
    $this->reception = userWithRole(Role::Reception);
    $this->quote = sentQuote(100000);
    app(DecideQuote::class)->approve($this->quote);

    $vehicle = Vehicle::factory()->create(['customer_id' => $this->quote->customer_id]);
    $this->appointment = Appointment::factory()->create(['vehicle_id' => $vehicle->id]);
    $this->quote->forceFill(['appointment_id' => $this->appointment->id])->save();

    Http::fake(['api.mercadopago.com/v1/payments' => Http::response(mpPixResponse('555'), 201)]);
    $this->payment = app(RequestPixPayment::class)->deposit($this->quote->fresh());
});

function fakeRemote(string $status, int $amountCents, ?string $reference): void
{
    Http::fake(['api.mercadopago.com/v1/payments/555' => Http::response([
        'id' => 555, 'status' => $status, 'status_detail' => 'accredited',
        'transaction_amount' => $amountCents / 100, 'external_reference' => $reference, 'live_mode' => false,
    ])]);
}

function postWebhook(array $headers, string $dataId = '555'): TestResponse
{
    return test()->postJson('/webhooks/mercadopago?data.id='.$dataId.'&type=payment', [
        'action' => 'payment.updated', 'type' => 'payment', 'data' => ['id' => $dataId],
    ], $headers);
}

it('confirms the payment from a signed webhook, marks the deposit and confirms the appointment', function () {
    Event::fake([PaymentUpdated::class]);
    fakeRemote('approved', 30000, $this->payment->uuid);

    postWebhook(mpSignedHeaders('555'))->assertOk()->assertJson(['result' => 'applied']);

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Approved)
        ->and($this->payment->fresh()->paid_at)->not->toBeNull()
        ->and($this->quote->fresh()->deposit_paid_at)->not->toBeNull()
        ->and($this->appointment->fresh()->status)->toBe(AppointmentStatus::Confirmed);

    Event::assertDispatched(PaymentUpdated::class, fn (PaymentUpdated $e) => $e->status === 'approved'
        && collect($e->broadcastOn())->pluck('name')->contains('private-customers.'.$this->quote->customer_id));
    Notification::assertSentTo($this->reception, StaffAlert::class, fn (StaffAlert $n) => $n->type === 'payment_approved');
});

it('processes each event only once', function () {
    fakeRemote('approved', 30000, $this->payment->uuid);

    postWebhook(mpSignedHeaders('555', 'req-A'))->assertOk();
    postWebhook(mpSignedHeaders('555', 'req-A'))->assertOk()->assertJson(['result' => 'duplicate']);

    expect(WebhookEvent::count())->toBe(1);
});

it('rejects an invalid or old signature', function () {
    postWebhook(mpSignedHeaders('555', 'req-1', 'wrong-secret'))->assertUnauthorized();
    postWebhook(mpSignedHeaders('555', 'req-2', 'whsec-test', now()->subHour()->timestamp))->assertUnauthorized();
    postWebhook([])->assertUnauthorized();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('never trusts the webhook body: amount mismatches are not applied', function () {
    fakeRemote('approved', 1, $this->payment->uuid);

    postWebhook(mpSignedHeaders('555'))->assertOk()->assertJson(['result' => 'amount_mismatch']);

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('does not revert a final status with a late webhook', function () {
    fakeRemote('approved', 30000, $this->payment->uuid);
    postWebhook(mpSignedHeaders('555', 'r1'))->assertOk();

    fakeRemote('pending', 30000, $this->payment->uuid);
    postWebhook(mpSignedHeaders('555', 'r2'))->assertOk()->assertJson(['result' => 'unchanged']);

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Approved);
});

it('ignores unknown payments and other topics', function () {
    fakeRemote('approved', 30000, 'not-ours');
    postWebhook(mpSignedHeaders('555'))->assertOk()->assertJson(['result' => 'unknown_payment']);
});

it('answers but does nothing while Mercado Pago is disabled', function () {
    $settings = app(PaymentSettings::class);
    $settings->mercadopago_enabled = false;
    $settings->save();

    postWebhook(mpSignedHeaders('555'))->assertOk()->assertJson(['result' => 'disabled']);
});

it('reconciles pending payments and expires old ones', function () {
    fakeRemote('approved', 30000, $this->payment->uuid);
    $this->artisan('olympo:payments:reconcile')->assertSuccessful();
    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Approved);

    $other = Payment::create([
        'customer_id' => $this->quote->customer_id, 'quote_id' => $this->quote->id, 'type' => 'balance', 'method' => 'pix',
        'provider' => 'mercadopago', 'status' => 'pending', 'amount_cents' => 1000, 'expires_at' => now()->subHour(),
    ]);
    $this->artisan('olympo:payments:reconcile')->assertSuccessful();
    expect($other->fresh()->status)->toBe(PaymentStatus::Expired);
});
