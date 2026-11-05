<?php

use App\Http\Controllers\MercadoPagoWebhookController;
use Illuminate\Support\Facades\Route;

// Webhooks de pago: firma verificada e idempotentes (sin CSRF ni sesión).
Route::post('webhooks/mercadopago', MercadoPagoWebhookController::class)
    ->middleware('throttle:webhooks')
    ->name('webhooks.mercadopago');
