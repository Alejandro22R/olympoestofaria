<?php

use App\Domain\Auth\Role;
use App\Domain\Settings\CheckinSettings;
use App\Filament\Clusters\Settings\Pages\ManageCheckinSettings;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(userWithRole(Role::Owner)));

it('saves the check-in settings and texts per language', function () {
    Livewire::test(ManageCheckinSettings::class)
        ->fillForm(['max_photos_per_mark' => 6, 'legal_text.es' => 'Texto nuevo'])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(CheckinSettings::class)->refresh();
    expect($settings->max_photos_per_mark)->toBe(6)
        ->and($settings->legalText('es'))->toBe('Texto nuevo')
        ->and($settings->legalText('pt_BR'))->toContain('Este termo');
});

it('rejects out of range values', function () {
    Livewire::test(ManageCheckinSettings::class)
        ->fillForm(['report_link_days' => 0])
        ->call('save')
        ->assertHasFormErrors(['report_link_days']);
});

it('keeps damage types and checklist items owner-only', function (string $path) {
    $this->get($path)->assertOk();
    $this->actingAs(userWithRole(Role::Reception))->get($path)->assertForbidden();
})->with(['/admin/settings/damage-types', '/admin/settings/checklist-items', '/admin/settings/checkin']);
