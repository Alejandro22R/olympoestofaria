<?php

use App\Domain\Auth\Role;
use App\Domain\Quotes\Actions\CreateQuote;
use App\Domain\Quotes\Actions\SaveQuote;
use App\Domain\Quotes\Enums\QuoteStatus;
use App\Domain\Settings\PaymentSettings;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\User;
use App\Models\WorkflowStage;
use Carbon\CarbonImmutable;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\CheckinCatalogSeeder;
use Database\Seeders\ProductionSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SilhouetteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class))
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

function userWithRole(Role $role, array $attributes = []): User
{
    return User::factory()->withRole($role)->create($attributes);
}

/**
 * Instante expresado en la hora local del taller (America/Sao_Paulo por defecto).
 */
function local(string $datetime): CarbonImmutable
{
    return CarbonImmutable::parse($datetime, 'America/Sao_Paulo');
}

/**
 * Congela el reloj en un lunes laborable: 2026-11-09 08:00 en São Paulo.
 */
function freezeMonday(): void
{
    test()->travelTo(local('2026-11-09 08:00'));
}

/**
 * Siembra las etapas, plazos de garantía y catálogo de check-in usados por la producción.
 */
function seedProduction(): void
{
    test()->seed([
        CatalogSeeder::class,
        CheckinCatalogSeeder::class,
        SilhouetteSeeder::class,
        ProductionSeeder::class,
    ]);
}

function stage(string $namePt): WorkflowStage
{
    return WorkflowStage::query()->where('name->pt_BR', $namePt)->firstOrFail();
}

/**
 * Activa Mercado Pago (modo prueba) con credenciales ficticias para las pruebas.
 */
function enableMercadoPago(): void
{
    $settings = app(PaymentSettings::class);
    $settings->mercadopago_enabled = true;
    $settings->mercadopago_mode = 'sandbox';
    $settings->mercadopago_methods = ['pix'];
    $settings->mercadopago_access_token = 'TEST-token-123';
    $settings->mercadopago_webhook_secret = 'whsec-test';
    $settings->save();
}

/**
 * Respuesta simulada de Mercado Pago al crear un Pix.
 *
 * @return array<string, mixed>
 */
function mpPixResponse(string $id = '987654', string $status = 'pending'): array
{
    return [
        'id' => (int) $id,
        'status' => $status,
        'status_detail' => 'pending_waiting_transfer',
        'date_of_expiration' => now()->addMinutes(30)->toIso8601String(),
        'point_of_interaction' => ['transaction_data' => [
            'qr_code' => '00020126580014br.gov.bcb.pix0136-TESTE',
            'qr_code_base64' => base64_encode('png'),
            'ticket_url' => 'https://www.mercadopago.com.br/payments/'.$id.'/ticket',
        ]],
    ];
}

/**
 * Cabeceras de un webhook firmado como lo hace Mercado Pago.
 *
 * @return array<string, string>
 */
function mpSignedHeaders(string $dataId, string $requestId = 'req-1', string $secret = 'whsec-test', ?int $ts = null): array
{
    $ts ??= now()->timestamp;
    $hash = hash_hmac('sha256', "id:{$dataId};request-id:{$requestId};ts:{$ts};", $secret);

    return ['x-signature' => "ts={$ts},v1={$hash}", 'x-request-id' => $requestId];
}

/**
 * Cotización enviada lista para aprobar (con cliente, ítems y total).
 */
function sentQuote(int $totalCents = 100000, array $customer = []): Quote
{
    $c = Customer::factory()->create(['email' => 'cliente@example.com', ...$customer]);
    $quote = app(CreateQuote::class)->blank($c);
    app(SaveQuote::class)->execute($quote, ['items' => [['description' => 'Bancos', 'quantity' => 1, 'unit_price_cents' => $totalCents]]]);
    $quote->forceFill(['status' => QuoteStatus::Sent, 'sent_at' => now()])->save();

    return $quote->fresh();
}
