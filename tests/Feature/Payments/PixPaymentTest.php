<?php

use App\Domain\Payments\Actions\RecordManualPayment;
use App\Domain\Payments\Actions\RequestPixPayment;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Enums\PaymentType;
use App\Domain\Quotes\Actions\DecideQuote;
use App\Models\Payment;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Notification::fake();
    $this->quote = sentQuote(100000);
    app(DecideQuote::class)->approve($this->quote);
});

it('refuses online Pix while Mercado Pago is disabled', function () {
    app(RequestPixPayment::class)->deposit($this->quote->fresh());
})->throws(ValidationException::class);

it('creates the deposit Pix in Mercado Pago with idempotency key and external reference', function () {
    enableMercadoPago();
    Http::fake(['api.mercadopago.com/v1/payments' => Http::response(mpPixResponse(), 201)]);

    $payment = app(RequestPixPayment::class)->deposit($this->quote->fresh());

    expect($payment->amount_cents)->toBe(30000)
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->gateway_id)->toBe('987654')
        ->and($payment->pix_qr_code)->toStartWith('000201')
        ->and($payment->mode)->toBe('sandbox')
        ->and($payment->isPayableOnline())->toBeTrue();

    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.mercadopago.com/v1/payments'
        && $r->hasHeader('Authorization', 'Bearer TEST-token-123')
        && $r->hasHeader('X-Idempotency-Key', $payment->uuid)
        && $r['payment_method_id'] === 'pix'
        && $r['transaction_amount'] == 300.0
        && $r['external_reference'] === $payment->uuid
        && $r['payer']['email'] === 'cliente@example.com'
        && str_ends_with($r['notification_url'], '/webhooks/mercadopago'));
});

it('reuses a pending Pix instead of charging twice', function () {
    enableMercadoPago();
    Http::fake(['api.mercadopago.com/v1/payments' => Http::response(mpPixResponse(), 201)]);

    $first = app(RequestPixPayment::class)->deposit($this->quote->fresh());
    $second = app(RequestPixPayment::class)->deposit($this->quote->fresh());

    expect($second->id)->toBe($first->id);
    Http::assertSentCount(1);
});

it('asks for an email when the customer has none', function () {
    enableMercadoPago();
    $this->quote->customer->update(['email' => null]);

    app(RequestPixPayment::class)->deposit($this->quote->fresh());
})->throws(ValidationException::class);

it('cancels the local payment when Mercado Pago fails', function () {
    enableMercadoPago();
    Http::fake(['api.mercadopago.com/*' => Http::response(['message' => 'invalid token'], 401)]);

    expect(fn () => app(RequestPixPayment::class)->deposit($this->quote->fresh()))->toThrow(ValidationException::class);

    $payment = Payment::sole();
    expect($payment->status)->toBe(PaymentStatus::Cancelled)
        ->and($payment->status_detail)->toContain('invalid token');
});

it('only charges approved quotes and never above what is due', function () {
    enableMercadoPago();
    Http::fake(['api.mercadopago.com/v1/payments' => Http::response(mpPixResponse(), 201)]);

    expect(fn () => app(RequestPixPayment::class)->deposit(sentQuote()))->toThrow(ValidationException::class);

    app(RecordManualPayment::class)->execute(
        $this->quote->fresh(), PaymentType::Deposit, PaymentMethod::Cash, 30000,
    );
    expect(fn () => app(RequestPixPayment::class)->deposit($this->quote->fresh()))->toThrow(ValidationException::class);
});
