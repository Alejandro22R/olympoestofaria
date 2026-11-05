<?php

namespace App\Domain\System;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

/**
 * Modo mantenimiento del portal público. El panel /admin, los webhooks de pago y /up siguen
 * funcionando (ver bootstrap/app.php), así el dueño puede volver a activarlo y no se pierden pagos.
 */
class MaintenanceMode
{
    /** Rutas que no se bloquean durante el mantenimiento. */
    public const EXCEPT = ['admin', 'admin/*', 'livewire-*', 'webhooks/*', 'broadcasting/*', 'horizon', 'horizon/*', 'up'];

    public function active(): bool
    {
        return app()->isDownForMaintenance();
    }

    public function enable(User $by, int $retryMinutes = 30): void
    {
        Artisan::call('down', ['--retry' => $retryMinutes * 60, '--refresh' => 60]);
        activity('system')->causedBy($by)->event('maintenance_on')->log('system.maintenance_on');
    }

    public function disable(User $by): void
    {
        Artisan::call('up');
        activity('system')->causedBy($by)->event('maintenance_off')->log('system.maintenance_off');
    }
}
