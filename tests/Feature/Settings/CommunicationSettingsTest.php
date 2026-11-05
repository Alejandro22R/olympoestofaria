<?php

use App\Domain\Auth\Role;
use App\Domain\Notifications\Actions\SaveMessageTemplate;
use App\Domain\Settings\BrandSettings;
use App\Domain\Settings\IntegrationSettings;
use App\Domain\Settings\NotificationSettings;
use App\Domain\Settings\WhatsAppSettings;
use App\Filament\Clusters\Settings\Pages\ManageAlertSettings;
use App\Filament\Clusters\Settings\Pages\ManageBrandSettings;
use App\Filament\Clusters\Settings\Pages\ManageIntegrationSettings;
use App\Filament\Clusters\Settings\Pages\ManageNotificationSettings;
use App\Filament\Clusters\Settings\Pages\ManageWhatsAppSettings;
use App\Filament\Resources\MessageTemplates\Pages\ManageMessageTemplates;
use App\Filament\Resources\NotificationLogs\Pages\ListNotificationLogs;
use App\Models\MessageTemplate;
use App\Models\NotificationLog;
use App\Support\Brand;
use Database\Seeders\MessageTemplateSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(fn () => $this->actingAs(userWithRole(Role::Owner)));

it('renders every new settings page for the owner and hides them from reception', function (string $slug) {
    $this->get("/admin/settings/{$slug}")->assertOk();
    $this->actingAs(userWithRole(Role::Reception))->get("/admin/settings/{$slug}")->assertForbidden();
})->with(['notifications', 'whatsapp', 'alerts', 'brand', 'integrations', 'message-templates', 'notification-logs']);

it('saves the notification settings with channel order and reminder hours', function () {
    Livewire::test(ManageNotificationSettings::class)
        ->fillForm(['reminder_hours' => ['2', 'x']])->call('save')->assertHasFormErrors(['reminder_hours.1']);

    Livewire::test(ManageNotificationSettings::class)
        ->fillForm(['primary_channel' => 'mail', 'reminder_hours' => ['2', '48', '2'], 'quiet_start' => '22:00', 'quiet_end' => '07:30'])
        ->call('save')->assertHasNoFormErrors();

    $s = app(NotificationSettings::class)->refresh();
    expect($s->channel_priority)->toBe(['mail', 'whatsapp'])
        ->and($s->reminder_hours)->toBe([48, 2])
        ->and($s->quiet_start)->toBe('22:00')
        ->and($s->quiet_end)->toBe('07:30');
});

it('never sends the WhatsApp token back and keeps it when left blank', function () {
    $s = app(WhatsAppSettings::class);
    $s->access_token = 'EAAG-super-secret';
    $s->save();

    $this->get('/admin/settings/whatsapp')->assertOk()->assertDontSee('EAAG-super-secret');

    Livewire::test(ManageWhatsAppSettings::class)
        ->assertFormSet(['access_token' => null])
        ->fillForm(['enabled' => true, 'phone_number_id' => '123456', 'access_token' => ''])
        ->call('save')->assertHasNoFormErrors();

    expect(app(WhatsAppSettings::class)->refresh()->access_token)->toBe('EAAG-super-secret');
    $log = Activity::query()->where('description', 'settings.updated')->latest('id')->first();
    expect(json_encode($log->properties))->not->toContain('EAAG');
});

it('tests the WhatsApp connection', function () {
    $s = app(WhatsAppSettings::class);
    $s->phone_number_id = '123456';
    $s->access_token = 'EAAG-test';
    $s->save();
    Http::fake(['graph.facebook.com/*' => Http::response(['display_phone_number' => '+55 11 4000-0000', 'verified_name' => 'Olympo'])]);

    Livewire::test(ManageWhatsAppSettings::class)->callAction('testConnection')->assertNotified('Conectado: +55 11 4000-0000 (Olympo)');
});

it('validates alert thresholds', function () {
    Livewire::test(ManageAlertSettings::class)->fillForm(['lead_unanswered_hours' => 0])->call('save')->assertHasFormErrors(['lead_unanswered_hours']);
});

it('rejects a brand color with poor contrast for white text', function () {
    Livewire::test(ManageBrandSettings::class)->fillForm(['primary_color' => '#FFE066'])->call('save')->assertHasFormErrors(['primary_color']);

    Livewire::test(ManageBrandSettings::class)->fillForm(['primary_color' => '#1E6B3A'])->call('save')->assertHasNoFormErrors();
    expect(app(BrandSettings::class)->refresh()->primary_color)->toBe('#1E6B3A');
});

it('applies a custom brand color to the portal tokens', function () {
    $s = app(BrandSettings::class);
    $s->primary_color = '#1E6B3A';
    $s->reduce_motion = true;
    $s->save();

    $this->get('/catalogo')->assertOk()
        ->assertSee('--color-brand-500:#1E6B3A', false)
        ->assertSee('data-reduce-motion', false);
    expect(Brand::contrastWithWhite(Brand::shades('#1E6B3A')[700]))->toBeGreaterThanOrEqual(4.5)
        ->and(Brand::shades(Brand::BRAND_500)[700])->toBe(Brand::BRAND_700);
});

it('shows the status of each integration and tests storage', function () {
    Livewire::test(ManageIntegrationSettings::class)->assertOk()
        ->assertSee('Mercado Pago (Pix)')->assertSee('Desativado')
        ->call('testService', 'storage')->assertNotified('Funcionando.');
});

it('keeps the Turnstile secret write-only', function () {
    Livewire::test(ManageIntegrationSettings::class)
        ->fillForm(['turnstile_enabled' => true, 'turnstile_site_key' => 'site', 'turnstile_secret' => 'shh'])
        ->call('save')->assertHasNoFormErrors();

    $s = app(IntegrationSettings::class)->refresh();
    expect($s->turnstile_secret)->toBe('shh')->and($s->turnstileReady())->toBeTrue();
    Livewire::test(ManageIntegrationSettings::class)->assertFormSet(['turnstile_secret' => null]);
});

it('edits a template and rejects unknown variables', function () {
    $this->seed(MessageTemplateSeeder::class);
    $template = MessageTemplate::query()->where(['event' => 'work_order_ready', 'channel' => 'mail', 'locale' => 'pt_BR'])->firstOrFail();

    expect(fn () => app(SaveMessageTemplate::class)->execute($template, ['subject' => 'Pronto', 'body' => 'Oi {{nome}}, total {{total}}']))
        ->toThrow(ValidationException::class);

    Livewire::test(ManageMessageTemplates::class)
        ->callTableAction('edit', $template, data: ['subject' => 'Seu carro está pronto', 'body' => 'Oi {{nome}}! {{placa}} pronto. {{link}}', 'is_active' => true])
        ->assertHasNoTableActionErrors();

    expect($template->fresh()->body)->toBe('Oi {{nome}}! {{placa}} pronto. {{link}}');

    Livewire::test(ManageMessageTemplates::class)
        ->callTableAction('edit', $template, data: ['subject' => 'x', 'body' => '{{senha}}'])
        ->assertHasTableActionErrors(['body']);
});

it('validates the WhatsApp parameter list', function () {
    $this->seed(MessageTemplateSeeder::class);
    $template = MessageTemplate::query()->where(['event' => 'work_order_ready', 'channel' => 'whatsapp', 'locale' => 'pt_BR'])->firstOrFail();

    app(SaveMessageTemplate::class)->execute($template, ['body' => '{{nome}}', 'whatsapp_params' => ['nome', 'inventada']]);
})->throws(ValidationException::class);

it('lists the send log for support', function () {
    $log = NotificationLog::query()->create(['event' => 'payment_approved', 'channel' => 'mail', 'recipient' => 'a@b.c', 'status' => 'failed', 'error' => 'SMTP down']);

    Livewire::test(ListNotificationLogs::class)->assertCanSeeTableRecords([$log])->assertSee('SMTP down');
});
