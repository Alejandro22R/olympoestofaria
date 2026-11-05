<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Customers\Enums\DocumentType;
use App\Domain\Settings\GeneralSettings;
use App\Domain\Settings\PaymentSettings;
use App\Models\Customer;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Mercado Pago (API v1 de pagos) con el cliente HTTP de Laravel. Solo Pix por ahora.
 * Credenciales y modo salen de Ajustes > Pagos (cifradas), nunca de constantes.
 */
class MercadoPagoGateway implements PaymentGateway
{
    public const BASE_URL = 'https://api.mercadopago.com';

    /** Tolerancia de la marca de tiempo del webhook (protección contra repetición). */
    public const WEBHOOK_TOLERANCE_SECONDS = 600;

    public function __construct(
        private readonly PaymentSettings $settings,
        private readonly GeneralSettings $general,
    ) {}

    private function http(): PendingRequest
    {
        $token = $this->settings->mercadopago_access_token;
        if (blank($token)) {
            throw new PaymentGatewayException(__('payments.errors.not_configured'));
        }

        return Http::baseUrl(self::BASE_URL)->withToken((string) $token)->acceptJson()->timeout(20)->retry(2, 300, throw: false);
    }

    public function createPixCharge(Payment $payment, Customer $customer, string $payerEmail, string $description): PixCharge
    {
        $expiresAt = CarbonImmutable::now($this->general->timezone)->addMinutes($this->settings->pix_expiration_minutes);
        [$first, $last] = array_pad(explode(' ', trim($customer->name), 2), 2, '');

        $payer = ['email' => $payerEmail, 'first_name' => $first, 'last_name' => $last];
        if (in_array($customer->document_type, [DocumentType::Cpf, DocumentType::Cnpj], true) && filled($customer->document_number)) {
            $payer['identification'] = [
                'type' => strtoupper($customer->document_type->value),
                'number' => preg_replace('/\D/', '', (string) $customer->document_number),
            ];
        }

        try {
            $response = $this->http()
                ->withHeaders(['X-Idempotency-Key' => $payment->uuid])
                ->post('/v1/payments', array_filter([
                    'transaction_amount' => round($payment->amount_cents / 100, 2),
                    'description' => mb_substr($description, 0, 200),
                    'payment_method_id' => 'pix',
                    'payer' => $payer,
                    'external_reference' => $payment->uuid,
                    'notification_url' => $this->notificationUrl(),
                    'date_of_expiration' => $expiresAt->format('Y-m-d\TH:i:s.vP'),
                    'statement_descriptor' => $this->settings->statement_descriptor,
                    'metadata' => ['payment_uuid' => $payment->uuid, 'type' => $payment->type->value],
                ], fn ($v) => $v !== null && $v !== ''));
        } catch (ConnectionException $e) {
            throw new PaymentGatewayException(__('payments.errors.gateway_unreachable'), previous: $e);
        }

        if ($response->failed()) {
            throw new PaymentGatewayException(__('payments.errors.gateway_error', ['message' => (string) ($response->json('message') ?? $response->status())]));
        }

        $data = $response->json('point_of_interaction.transaction_data') ?? [];
        if (blank($data['qr_code'] ?? null)) {
            throw new PaymentGatewayException(__('payments.errors.pix_missing'));
        }

        return new PixCharge(
            gatewayId: (string) $response->json('id'),
            status: (string) $response->json('status', 'pending'),
            qrCode: (string) $data['qr_code'],
            qrCodeBase64: $data['qr_code_base64'] ?? null,
            ticketUrl: $data['ticket_url'] ?? null,
            expiresAt: $response->json('date_of_expiration') ? CarbonImmutable::parse((string) $response->json('date_of_expiration')) : $expiresAt,
        );
    }

    public function fetchPayment(string $gatewayId): GatewayPayment
    {
        try {
            $response = $this->http()->get('/v1/payments/'.rawurlencode($gatewayId));
        } catch (ConnectionException $e) {
            throw new PaymentGatewayException(__('payments.errors.gateway_unreachable'), previous: $e);
        }

        if ($response->failed()) {
            throw new PaymentGatewayException(__('payments.errors.gateway_error', ['message' => (string) $response->status()]));
        }

        return new GatewayPayment(
            gatewayId: (string) $response->json('id'),
            status: (string) $response->json('status'),
            statusDetail: $response->json('status_detail'),
            amountCents: (int) round(((float) $response->json('transaction_amount')) * 100),
            externalReference: $response->json('external_reference'),
            liveMode: (bool) $response->json('live_mode', false),
        );
    }

    /**
     * Valida la firma "x-signature" (ts=…,v1=…) con el secreto del webhook:
     * HMAC-SHA256 de "id:{data.id};request-id:{x-request-id};ts:{ts};".
     */
    public function verifyWebhook(Request $request): bool
    {
        $secret = $this->settings->mercadopago_webhook_secret;
        $header = (string) $request->header('x-signature', '');
        if (blank($secret) || $header === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $chunk) {
            [$k, $v] = array_pad(explode('=', trim($chunk), 2), 2, null);
            if ($k !== null && $v !== null) {
                $parts[$k] = $v;
            }
        }
        if (! isset($parts['ts'], $parts['v1']) || ! ctype_digit($parts['ts'])) {
            return false;
        }

        $ts = (int) $parts['ts'];
        $seconds = $ts > 9_999_999_999 ? intdiv($ts, 1000) : $ts; // acepta ms o s
        if (abs(now()->timestamp - $seconds) > self::WEBHOOK_TOLERANCE_SECONDS) {
            return false;
        }

        $dataId = (string) ($request->query('data_id') ?? $request->query('data.id') ?? $request->input('data.id', ''));
        if (ctype_alnum($dataId)) {
            $dataId = strtolower($dataId);
        }

        $manifest = '';
        if ($dataId !== '') {
            $manifest .= "id:{$dataId};";
        }
        if ($request->header('x-request-id')) {
            $manifest .= 'request-id:'.$request->header('x-request-id').';';
        }
        $manifest .= "ts:{$parts['ts']};";

        return hash_equals(hash_hmac('sha256', $manifest, (string) $secret), $parts['v1']);
    }

    public function testConnection(): ConnectionResult
    {
        try {
            $response = $this->http()->get('/users/me');
        } catch (PaymentGatewayException $e) {
            return new ConnectionResult(false, $e->getMessage());
        } catch (ConnectionException) {
            return new ConnectionResult(false, __('payments.errors.gateway_unreachable'));
        }

        if ($response->failed()) {
            return new ConnectionResult(false, __('payments.errors.gateway_error', ['message' => (string) ($response->json('message') ?? $response->status())]));
        }

        return new ConnectionResult(true, __('payments.messages.connection_ok', [
            'account' => (string) ($response->json('nickname') ?? $response->json('id')),
            'site' => (string) $response->json('site_id', ''),
        ]));
    }

    public function notificationUrl(): string
    {
        return route('webhooks.mercadopago');
    }
}
