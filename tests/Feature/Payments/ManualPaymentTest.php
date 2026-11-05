<?php

use App\Domain\Auth\Role;
use App\Domain\Payments\Actions\RecordManualPayment;
use App\Domain\Payments\BillingSummary;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentProvider;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Enums\PaymentType;
use App\Domain\Quotes\Actions\DecideQuote;
use App\Domain\Settings\PaymentSettings;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Notification::fake();
    $this->quote = sentQuote(100000);
    app(DecideQuote::class)->approve($this->quote);
    $this->user = userWithRole(Role::Reception);
});

it('records a manual payment as approved (works with Mercado Pago off)', function () {
    $payment = app(RecordManualPayment::class)->execute($this->quote->fresh(), PaymentType::Deposit, PaymentMethod::Cash, 30000, $this->user, 'Recebido no balcão');

    expect($payment->status)->toBe(PaymentStatus::Approved)
        ->and($payment->provider)->toBe(PaymentProvider::Manual)
        ->and($payment->recorded_by)->toBe($this->user->id)
        ->and($this->quote->fresh()->deposit_paid_at)->not->toBeNull()
        ->and(BillingSummary::forQuote($this->quote->fresh())->outstandingCents())->toBe(70000);
});

it('never accepts more than the outstanding balance', function () {
    app(RecordManualPayment::class)->execute($this->quote->fresh(), PaymentType::Balance, PaymentMethod::Pix, 100001, $this->user);
})->throws(ValidationException::class);

it('rejects methods the owner did not enable', function () {
    $settings = app(PaymentSettings::class);
    $settings->manual_methods = ['cash'];
    $settings->save();

    app(RecordManualPayment::class)->execute($this->quote->fresh(), PaymentType::Deposit, PaymentMethod::Card, 1000, $this->user);
})->throws(ValidationException::class);

it('rejects zero amounts', function () {
    app(RecordManualPayment::class)->execute($this->quote->fresh(), PaymentType::Deposit, PaymentMethod::Cash, 0, $this->user);
})->throws(ValidationException::class);
