<?php

use App\Domain\Auth\Role;
use App\Domain\Settings\Events\SettingsUpdated;
use App\Domain\Settings\GeneralSettings;
use App\Filament\Clusters\Settings\Pages\ManageGeneralSettings;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->owner = userWithRole(Role::Owner);
    $this->actingAs($this->owner);
});

it('saves the general settings and audits the change', function () {
    Event::fake([SettingsUpdated::class]);

    Livewire::test(ManageGeneralSettings::class)
        ->fillForm(['business_name' => 'Olympo Centro', 'whatsapp' => '+5511987654321'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(GeneralSettings::class)->refresh()->business_name)->toBe('Olympo Centro');

    $log = Activity::query()->where('log_name', 'settings')->latest('id')->first();
    expect($log)->not->toBeNull()
        ->and($log->causer_id)->toBe($this->owner->id)
        ->and($log->properties['group'])->toBe('general')
        ->and($log->properties['old']['business_name'])->toBe('Olympo Estofaria')
        ->and($log->properties['attributes']['business_name'])->toBe('Olympo Centro');

    Event::assertDispatched(SettingsUpdated::class, fn (SettingsUpdated $e) => $e->group === 'general'
        && in_array('business_name', $e->keys, true));
});

it('does not audit when nothing changed', function () {
    Livewire::test(ManageGeneralSettings::class)->call('save')->assertHasNoFormErrors();

    expect(Activity::query()->where('log_name', 'settings')->count())->toBe(0);
});

it('rejects an invalid WhatsApp number', function () {
    Livewire::test(ManageGeneralSettings::class)
        ->fillForm(['whatsapp' => '11 9876'])
        ->call('save')
        ->assertHasFormErrors(['whatsapp']);
});

it('discards unsaved changes', function () {
    Livewire::test(ManageGeneralSettings::class)
        ->fillForm(['business_name' => 'Rascunho'])
        ->call('discard')
        ->assertSchemaStateSet(['business_name' => 'Olympo Estofaria'], 'form');
});

it('blocks saving for non owners even through Livewire', function () {
    $this->actingAs(userWithRole(Role::Reception));

    Livewire::test(ManageGeneralSettings::class)->assertForbidden();
});
