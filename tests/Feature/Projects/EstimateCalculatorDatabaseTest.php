<?php

use App\Domain\Pricing\EstimateCalculator;
use App\Domain\Settings\PricingSettings;
use App\Models\Material;
use App\Models\ServiceType;
use Database\Seeders\CatalogSeeder;

beforeEach(fn () => $this->seed(CatalogSeeder::class));

it('uses the catalog prices and the settings margin', function () {
    $seats = ServiceType::where('slug', 'seats')->first();          // 1.800,00 + 6,5 m²
    $leather = Material::where('type', 'leather')->first();          // 320,00/m²

    $estimate = app(EstimateCalculator::class)->calculate([['service_type_id' => $seats->id, 'material_id' => $leather->id]]);

    expect($estimate->subtotalCents)->toBe(180000 + 208000)
        ->and($estimate->minCents)->toBe(325000)
        ->and($estimate->maxCents)->toBe(450000);

    $settings = app(PricingSettings::class);
    $settings->estimate_margin_percent = 0;
    $settings->rounding_cents = 1;
    $settings->save();

    $estimate = app(EstimateCalculator::class)->calculate([['service_type_id' => $seats->id, 'material_id' => $leather->id]]);
    expect($estimate->minCents)->toBe(388000)->and($estimate->maxCents)->toBe(388000);
});

it('refuses inactive services', function () {
    $service = ServiceType::first();
    $service->update(['is_active' => false]);

    app(EstimateCalculator::class)->calculate([['service_type_id' => $service->id]]);
})->throws(InvalidArgumentException::class);

it('ignores inactive materials in the price', function () {
    $seats = ServiceType::where('slug', 'seats')->first();
    $leather = Material::where('type', 'leather')->first();
    $leather->update(['is_active' => false]);

    $estimate = app(EstimateCalculator::class)->calculate([['service_type_id' => $seats->id, 'material_id' => $leather->id]]);

    expect($estimate->lines[0]->materialCents)->toBe(0);
});
