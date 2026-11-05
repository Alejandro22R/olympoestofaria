<?php

use App\Domain\Auth\Role;
use App\Domain\Settings\PaymentSettings;
use App\Filament\Clusters\Settings\Pages\ManagePaymentSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(fn () => $this->actingAs(userWithRole(Role::Owner)));

it('starts with Mercado Pago disabled', function () {
    expect(app(PaymentSettings::class)->mercadopago_enabled)->toBeFalse()
        ->and(app(PaymentSettings::class)->onlinePixAvailable())->toBeFalse();
});

it('enables Mercado Pago and stores the secrets encrypted, never sending them back', function () {
    Livewire::test(ManagePaymentSettings::class)
        ->fillForm(['mercadopago_enabled' => true, 'mercadopago_access_token' => 'TEST-abc', 'mercadopago_webhook_secret' => 'sec-1', 'mercadopago_public_key' => 'TEST-pk'])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(PaymentSettings::class)->refresh();
    expect($settings->mercadopago_enabled)->toBeTrue()
        ->and($settings->mercadopago_access_token)->toBe('TEST-abc')
        ->and($settings->onlinePixAvailable())->toBeTrue();

    $raw = DB::table('settings')->where('group', 'payments')->where('name', 'mercadopago_access_token')->value('payload');
    expect($raw)->not->toContain('TEST-abc');

    // El formulario no recibe los secretos y dejarlos vacíos conserva el valor guardado.
    Livewire::test(ManagePaymentSettings::class)
        ->assertSchemaStateSet(['mercadopago_access_token' => null, 'mercadopago_webhook_secret' => null], 'form')
        ->fillForm(['deposit_percent' => 40])
        ->call('save')->assertHasNoFormErrors();

    expect(app(PaymentSettings::class)->refresh()->mercadopago_access_token)->toBe('TEST-abc');

    $log = json_encode(Activity::query()->where('log_name', 'settings')->pluck('properties'));
    expect($log)->not->toContain('TEST-abc')->and($log)->not->toContain('sec-1');
});

it('requires a token to enable Mercado Pago', function () {
    Livewire::test(ManagePaymentSettings::class)
        ->fillForm(['mercadopago_enabled' => true])
        ->call('save')
        ->assertHasFormErrors(['mercadopago_access_token']);
});

it('asks for explicit confirmation to switch to production', function () {
    Livewire::test(ManagePaymentSettings::class)
        ->fillForm(['mercadopago_mode' => 'production'])
        ->call('save')
        ->assertHasErrors(['data.confirm_production']);

    expect(app(PaymentSettings::class)->refresh()->mercadopago_mode)->toBe('sandbox');

    Livewire::test(ManagePaymentSettings::class)
        ->fillForm(['mercadopago_mode' => 'production', 'confirm_production' => true])
        ->call('save')->assertHasNoErrors();

    expect(app(PaymentSettings::class)->refresh()->mercadopago_mode)->toBe('production');
});

it('tests the connection with the saved credentials', function () {
    enableMercadoPago();
    Http::fake(['api.mercadopago.com/users/me' => Http::response(['id' => 123, 'nickname' => 'OLYMPO', 'site_id' => 'MLB'])]);

    Livewire::test(ManagePaymentSettings::class)
        ->callAction('testConnection')
        ->assertNotified('Conectado ao Mercado Pago (conta OLYMPO MLB).');
});

it('validates ranges', function () {
    Livewire::test(ManagePaymentSettings::class)
        ->fillForm(['deposit_percent' => 120, 'pix_expiration_minutes' => 1])
        ->call('save')
        ->assertHasFormErrors(['deposit_percent', 'pix_expiration_minutes']);
});

it('is owner only', function () {
    $this->actingAs(userWithRole(Role::Reception))->get('/admin/settings/payments')->assertForbidden();
});
