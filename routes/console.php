<?php

use App\Domain\Alerts\RunScheduledAlerts;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Alertas internas del taller (PLAN.md §2.5): stock mínimo, órdenes atrasadas o paradas y citas sin confirmar.
Artisan::command('olympo:alerts', function (RunScheduledAlerts $alerts) {
    foreach ($alerts->execute() as $type => $count) {
        $this->line("{$type}: {$count}");
    }
})->purpose('Revisa condiciones del taller y avisa al personal (sin repetir avisos)');

Schedule::command('olympo:alerts')->hourly()->withoutOverlapping();
