<?php

namespace App\Http\Controllers;

use App\Domain\Payments\Actions\HandleMercadoPagoWebhook;
use App\Domain\Payments\Gateways\PaymentGatewayException;
use App\Domain\Settings\PaymentSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Recibe las notificaciones de Mercado Pago. Responde 2xx rápido para que no reintente
 * eventos ya procesados; ante un error temporal responde 500 para que reintente.
 */
class MercadoPagoWebhookController
{
    public function __invoke(Request $request, HandleMercadoPagoWebhook $handler, PaymentSettings $settings): JsonResponse
    {
        if (! $settings->mercadopago_enabled) {
            return response()->json(['result' => 'disabled'], 200);
        }

        try {
            $outcome = $handler->execute($request);
        } catch (PaymentGatewayException $e) {
            report($e);

            return response()->json(['result' => 'retry'], 500);
        }

        return response()->json(['result' => $outcome['result']], $outcome['status']);
    }
}
