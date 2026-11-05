<?php

use App\Domain\Auth\Role;
use App\Domain\Projects\Actions\SubmitProject;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Settings\PortalSettings;
use App\Domain\Settings\PricingSettings;
use App\Filament\Clusters\Settings\Pages\ManagePortalSettings;
use App\Filament\Clusters\Settings\Pages\ManagePricingSettings;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Models\MaterialVariant;
use App\Models\ServiceType;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->seed(CatalogSeeder::class);
    $this->project = app(SubmitProject::class)->execute([
        'vehicle' => ['brand' => 'Fiat', 'model' => 'Uno'],
        'pieces' => [['service_type_id' => ServiceType::where('slug', 'seats')->value('id'), 'material_variant_id' => MaterialVariant::first()->id]],
        'contact' => ['name' => 'Ana', 'phone' => '+5511987654321', 'locale' => 'pt_BR'],
        'privacy' => true,
    ]);
});

it('lists leads for reception and owner only', function (Role $role, int $status) {
    $this->actingAs(userWithRole($role))->get('/admin/projects')->assertStatus($status);
})->with([[Role::Owner, 200], [Role::Reception, 200], [Role::Upholsterer, 403]]);

it('marks a lead as contacted from the panel', function () {
    $this->actingAs(userWithRole(Role::Reception));

    Livewire::test(ViewProject::class, ['record' => $this->project->getRouteKey()])
        ->assertSee('Fiat Uno')
        ->callAction('contacted');

    expect($this->project->fresh()->status)->toBe(ProjectStatus::Contacted);
});

it('keeps the media library for staff that manages the portal', function (Role $role, int $status) {
    $this->actingAs(userWithRole($role))->get('/admin/gallery-items')->assertStatus($status);
})->with([[Role::Owner, 200], [Role::Reception, 200], [Role::Upholsterer, 403]]);

it('saves pricing and portal settings (owner only)', function () {
    $this->actingAs(userWithRole(Role::Owner));

    Livewire::test(ManagePricingSettings::class)->fillForm(['estimate_margin_percent' => 20, 'rounding_cents' => '100.00'])->call('save')->assertHasNoFormErrors();
    Livewire::test(ManagePortalSettings::class)->fillForm(['headline.es' => 'Nuevo titular', 'sections' => ['services', 'gallery']])->call('save')->assertHasNoFormErrors();

    expect(app(PricingSettings::class)->refresh()->rounding_cents)->toBe(10000)
        ->and(app(PricingSettings::class)->estimate_margin_percent)->toBe(20)
        ->and(app(PortalSettings::class)->refresh()->text(app(PortalSettings::class)->headline, 'es'))->toBe('Nuevo titular');

    $this->actingAs(userWithRole(Role::Reception))->get('/admin/settings/pricing')->assertForbidden();
});

it('rejects an out of range margin', function () {
    $this->actingAs(userWithRole(Role::Owner));

    Livewire::test(ManagePricingSettings::class)->fillForm(['estimate_margin_percent' => 150])->call('save')->assertHasFormErrors(['estimate_margin_percent']);
});
