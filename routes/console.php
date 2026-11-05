<?php

use App\Domain\Alerts\RunScheduledAlerts;
use App\Domain\Auth\Role;
use App\Domain\Notifications\Customer\ScheduledCustomerMessages;
use App\Domain\Payments\Actions\ReconcilePayments;
use App\Domain\Privacy\ApplyRetention;
use App\Domain\Quotes\Actions\ExpireQuotes;
use App\Domain\Settings\AuditSettings;
use App\Domain\Settings\BackupSettings;
use App\Domain\Settings\GeneralSettings;
use App\Domain\System\RunBackup;
use App\Domain\System\SystemHealth;
use App\Domain\System\VerifyBackup;
use App\Models\NotificationLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

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

// Fase 7: respaldos, prueba de restauración, retención LGPD, auditoría y latido del planificador.
Artisan::command('olympo:backup {--force : Ejecutar aunque no toque según Ajustes}', function (BackupSettings $settings, GeneralSettings $general, RunBackup $backup) {
    if (! $this->option('force') && ! $settings->isDue(CarbonImmutable::now($general->timezone))) {
        return 0;
    }
    $ok = $backup->execute();
    $this->line($ok ? 'backup: ok' : 'backup: failed');

    return $ok ? 0 : 1;
})->purpose('Copia de seguridad según Ajustes > Respaldos (frecuencia, destinos y retención)');

Artisan::command('olympo:backup:verify', function (VerifyBackup $verify) {
    $check = $verify->execute();
    $this->line(($check->ok ? 'ok: ' : 'failed: ').$check->message);

    return $check->ok ? 0 : 1;
})->purpose('Prueba de restauración de la copia más reciente');

Artisan::command('olympo:privacy:retention', function (ApplyRetention $retention) {
    foreach ($retention->execute() as $key => $count) {
        $this->line("{$key}: {$count}");
    }
})->purpose('Aplica la política de retención de datos (Ajustes > Privacidad)');

Artisan::command('olympo:audit:clean', function (AuditSettings $settings) {
    $this->call('activitylog:clean', ['--days' => max(30, $settings->retention_days), '--force' => true]);
})->purpose('Borra el registro de auditoría más antiguo que la retención configurada');

Schedule::call(fn () => Cache::forever(SystemHealth::SCHEDULER_HEARTBEAT, now()->toIso8601String()))->everyMinute()->name('scheduler-heartbeat');
Schedule::command('olympo:backup')->everyMinute()->withoutOverlapping(120)->runInBackground();
Schedule::command('olympo:backup:verify')->monthlyOn(1, '05:00');
Schedule::command('olympo:privacy:retention')->dailyAt('04:30');
Schedule::command('olympo:audit:clean')->dailyAt('04:45');

// Lanzamiento: primer dueño en producción (los usuarios demo solo existen fuera de producción).
Artisan::command('olympo:create-owner {email} {name}', function (string $email, string $name) {
    $password = $this->secret('Contraseña (mínimo 12 caracteres)');
    if (! is_string($password) || mb_strlen($password) < 12) {
        $this->error('La contraseña debe tener al menos 12 caracteres.');

        return 1;
    }

    $user = User::query()->firstOrNew(['email' => Str::lower($email)]);
    $user->forceFill(['name' => $name, 'password' => $password, 'is_active' => true, 'locale' => $user->locale ?? 'pt_BR'])->save();
    $user->syncRoles([Role::Owner->value]);
    activity('system')->performedOn($user)->event('owner_created')->log('system.owner_created');
    $this->info("Dueño listo: {$user->email}. Activa el 2FA en Mi perfil al entrar.");

    return 0;
})->purpose('Crea o actualiza el usuario dueño (primer acceso en producción)');
