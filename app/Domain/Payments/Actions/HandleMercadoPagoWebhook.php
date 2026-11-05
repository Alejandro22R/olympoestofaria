<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Enums\PaymentProvider;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Gateways\PaymentGateway;
use App\Models\Payment;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Webhook de Mercado Pago: firma verificada, idempotente (cada evento se procesa una vez)
 * y el estado se consulta a la API (nunca se confía en el cuerpo recibido).
 */
class HandleMercadoPagoWebhook
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly ApplyPaymentStatus $apply,
    ) {}

    /**
     * @return array{status: int, result: string}
     */
    public function execute(Request $request): array
    {
        if (! $this->gateway->verifyWebhook($request)) {
            return ['status' => 401, 'result' => 'invalid_signature'];
        }

        $topic = (string) ($request->input('type') ?? $request->query('type') ?? $request->input('topic', ''));
        $resourceId = (string) ($request->input('data.id') ?? $request->query('data_id') ?? '');
        $eventKey = (string) ($request->header('x-request-id') ?: $request->input('id', '')).':'.$resourceId;

        $inserted = WebhookEvent::query()->insertOrIgnore([
            'provider' => PaymentProvider::MercadoPago->value,
            'event_key' => $eventKey,
            'topic' => $topic,
            'resource_id' => $resourceId,
            'payload' => json_encode($request->all()),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if ($inserted === 0) {
            return ['status' => 200, 'result' => 'duplicate'];
        }

        $event = WebhookEvent::query()->where('provider', 'mercadopago')->where('event_key', $eventKey)->firstOrFail();

        if ($topic !== 'payment' || $resourceId === '') {
            $event->update(['result' => 'ignored', 'processed_at' => now()]);

            return ['status' => 200, 'result' => 'ignored'];
        }

        $remote = $this->gateway->fetchPayment($resourceId);
        $reference = (string) $remote->externalReference;
        $payment = Str::isUuid($reference) ? Payment::query()->where('uuid', $reference)->first() : null;

        if ($payment === null) {
            $event->update(['result' => 'unknown_payment', 'processed_at' => now()]);

            return ['status' => 200, 'result' => 'unknown_payment'];
        }

        if ($remote->amountCents !== $payment->amount_cents) {
            $event->update(['result' => 'amount_mismatch', 'processed_at' => now()]);
            report(new \RuntimeException("Mercado Pago amount mismatch for payment {$payment->id}"));

            return ['status' => 200, 'result' => 'amount_mismatch'];
        }

        $payment->forceFill(['gateway_id' => $remote->gatewayId])->save();
        $changed = $this->apply->execute($payment, PaymentStatus::fromMercadoPago($remote->status), $remote->statusDetail);

        $event->update(['result' => $changed ? 'applied' : 'unchanged', 'processed_at' => now()]);

        return ['status' => 200, 'result' => $changed ? 'applied' : 'unchanged'];
    }
}
