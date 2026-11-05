<?php

use App\Domain\Auth\Role;
use App\Domain\Settings\BackupSettings;
use App\Domain\Settings\GeneralSettings;
use App\Domain\Settings\PaymentSettings;
use App\Domain\Settings\SettingsTransfer;
use App\Domain\System\MaintenanceMode;
use App\Domain\System\RunBackup;
use App\Domain\System\SystemHealth;
use App\Domain\System\VerifyBackup;
use App\Filament\Clusters\Settings\Pages\ManageBackupSettings;
use App\Models\HealthCheck;
use App\Notifications\StaffAlert;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->owner = userWithRole(Role::Owner);
});

it('makes a database backup on the configured disk and the restore test checks it', function () {
    Storage::fake('backups');
    $s = app(BackupSettings::class);
    $s->include_files = false;
    $s->save();

    expect(app(RunBackup::class)->execute())->toBeTrue();

    $files = Storage::disk('backups')->allFiles('olympo');
    expect($files)->toHaveCount(1)->and($files[0])->toEndWith('.zip')
        ->and(HealthCheck::latestFor('backup')->ok)->toBeTrue();

    $check = app(VerifyBackup::class)->execute();
    expect($check->ok)->toBeTrue()
        ->and($check->details['dump_bytes'])->toBeGreaterThan(1000)
        ->and($check->details['restored'])->toBeFalse();
});

it('records a failed backup and alerts the owner by mail', function () {
    Notification::fake();
    Storage::fake('backups');
    config(['backup.backup.source.databases' => ['missing_connection']]);

    expect(app(RunBackup::class)->execute())->toBeFalse()
        ->and(HealthCheck::latestFor('backup')->ok)->toBeFalse();
    Notification::assertSentTo($this->owner, StaffAlert::class, fn (StaffAlert $a, array $channels) => $a->titleKey === 'notifications.backup_failed' && in_array('mail', $channels, true));
});

it('fails the restore test when there is no backup', function () {
    Storage::fake('backups');

    expect(app(VerifyBackup::class)->execute()->ok)->toBeFalse();
});

it('runs the backup only at the configured time and day', function () {
    $s = app(BackupSettings::class);
    $s->run_at = '03:00';
    $s->frequency = 'weekly';
    $s->weekday = 7;

    expect($s->isDue(CarbonImmutable::parse('2026-11-15 03:00')))->toBeTrue()  // domingo
        ->and($s->isDue(CarbonImmutable::parse('2026-11-15 03:01')))->toBeFalse()
        ->and($s->isDue(CarbonImmutable::parse('2026-11-16 03:00')))->toBeFalse();

    $this->artisan('olympo:backup')->assertSuccessful(); // no toca: no hace nada
    expect(HealthCheck::latestFor('backup'))->toBeNull();
});

it('closes only the public portal during maintenance', function () {
    app(MaintenanceMode::class)->enable($this->owner);

    $this->get('/')->assertStatus(503)->assertSee('Voltamos já');
    expect($this->postJson('/webhooks/mercadopago', [])->status())->not->toBe(503);
    $this->get('/up')->assertOk();
    $this->actingAs($this->owner)->get('/admin')->assertOk();

    app(MaintenanceMode::class)->disable($this->owner);
    $this->get('/')->assertOk();
    expect(Activity::query()->whereIn('event', ['maintenance_on', 'maintenance_off'])->count())->toBe(2);
});

it('shows the health checks and backup settings to the owner only', function () {
    $this->actingAs($this->owner)->get('/admin/settings/backups')->assertOk()
        ->assertSee('Saúde do sistema')->assertSee('Banco de dados')->assertSee('Teste de restauração');
    $this->actingAs(userWithRole(Role::Reception))->get('/admin/settings/backups')->assertForbidden();

    $checks = app(SystemHealth::class)->checks();
    expect($checks['database']['ok'])->toBeTrue()
        ->and($checks['scheduler']['ok'])->toBeFalse()
        ->and($checks['maintenance']['ok'])->toBeTrue();
});

it('saves backup settings and starts tasks from the panel', function () {
    Storage::fake('backups');
    $this->actingAs($this->owner);

    Livewire::test(ManageBackupSettings::class)
        ->fillForm(['frequency' => 'weekly', 'weekday' => 1, 'run_at' => '02:30', 'keep_days' => 2])
        ->call('save')->assertHasFormErrors(['keep_days']);

    Livewire::test(ManageBackupSettings::class)
        ->fillForm(['frequency' => 'weekly', 'weekday' => 1, 'run_at' => '02:30', 'keep_days' => 30, 'include_files' => false])
        ->call('save')->assertHasNoFormErrors();
    expect(app(BackupSettings::class)->refresh()->run_at)->toBe('02:30');

    Livewire::test(ManageBackupSettings::class)->callAction('backupNow')->assertNotified();
    expect(HealthCheck::latestFor('backup')?->ok)->toBeTrue();
});

it('exports settings without secrets and imports them back', function () {
    $this->actingAs($this->owner);
    enableMercadoPago();

    $export = app(SettingsTransfer::class)->export();
    expect($export['groups']['payments'])->not->toHaveKey('mercadopago_access_token')
        ->and(json_encode($export))->not->toContain('TEST-token-123');

    $export['groups']['general']['business_name'] = 'Olympo Produção';
    $export['groups']['unknown'] = ['x' => 1];
    $groups = app(SettingsTransfer::class)->import($export);

    expect($groups)->toContain('general')->not->toContain('unknown')
        ->and(app(GeneralSettings::class)->refresh()->business_name)->toBe('Olympo Produção')
        ->and(app(PaymentSettings::class)->refresh()->mercadopago_access_token)->toBe('TEST-token-123');

    expect(fn () => app(SettingsTransfer::class)->import(['format' => 'other']))->toThrow(ValidationException::class);
});

it('marks the scheduler as alive with the heartbeat', function () {
    $this->artisan('schedule:run')->assertSuccessful();

    expect(app(SystemHealth::class)->checks()['scheduler']['ok'])->toBeTrue();
});
