<?php

use App\Domain\Alerts\RunScheduledAlerts;
use App\Domain\Notifications\Customer\ScheduledCustomerMessages;
use App\Domain\Payments\Actions\ReconcilePayments;
use App\Domain\Quotes\Actions\ExpireQuotes;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Alertas internas del taller (PLAN.md §2.5): stock mínimo, órdenes atrasadas o paradas y citas sin confirmar.
Artisan::command('olympo:alerts', function (RunScheduledAlerts $alerts) {
    foreach ($alerts->execute() as $type => $count) {
        $this->line("{$type}: {$count}");
    }
})->purpose('Revisa condiciones del taller y avisa al personal (sin repetir avisos)');

Schedule::command('olympo:alerts')->hourly()->withoutOverlapping();

// Pagos y cotizaciones (Fase 5): conciliación con Mercado Pago y vencimiento de cotizaciones.
Artisan::command('olympo:payments:reconcile', function (ReconcilePayments $reconcile) {
    foreach ($reconcile->execute() as $key => $count) {
        $this->line("{$key}: {$count}");
    }
})->purpose('Consulta los Pix pendientes en Mercado Pago y vence los que no se pagaron');

Artisan::command('olympo:quotes:expire', function (ExpireQuotes $expire) {
    $this->line('expired: '.$expire->execute());
})->purpose('Marca como vencidas las cotizaciones fuera de vigencia');

Schedule::command('olympo:payments:reconcile')->everyTenMinutes()->withoutOverlapping();
Schedule::command('olympo:quotes:expire')->hourly();

// Avisos programados al cliente (Fase 6): recordatorios de cita, revisión de garantía y pedido de reseña.
Artisan::command('olympo:customer-messages', function (ScheduledCustomerMessages $messages) {
    foreach ($messages->execute() as $key => $count) {
        $this->line("{$key}: {$count}");
    }
})->purpose('Envía recordatorios de cita, revisiones de garantía y pedidos de reseña (sin repetir)');

Schedule::command('olympo:customer-messages')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('model:prune', ['--model' => [NotificationLog::class]])->daily();
