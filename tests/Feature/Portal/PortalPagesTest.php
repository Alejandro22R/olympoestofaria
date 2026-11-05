<?php

use App\Domain\Settings\GeneralSettings;
use App\Domain\Settings\PortalSettings;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\ProductionSeeder;

beforeEach(fn () => $this->seed([CatalogSeeder::class, ProductionSeeder::class]));

it('renders the landing with all content in the HTML (SEO, no JS required)', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<title>Olympo Estofaria — Tapeçaria automotiva premium</title>', false)
        ->assertSee('<meta name="description"', false)
        ->assertSee('hreflang="es"', false)
        ->assertSee('costurados')
        ->assertSee('Bancos')
        ->assertSee('Antes e depois')
        ->assertSee('Couro natural Nappa')
        ->assertSee('Desmontagem')
        ->assertSee('landing', false);
});

it('serves the landing in Spanish', function () {
    $this->get('/?lang=es')->assertOk()->assertSee('cosidos')->assertSee('Arma tu proyecto')->assertSee('lang="es"', false);
});

it('hides sections disabled in the settings', function () {
    $settings = app(PortalSettings::class);
    $settings->sections = ['services'];
    $settings->save();

    $this->get('/')->assertOk()->assertDontSee('Antes e depois')->assertDontSee('Materiais em detalhe');
});

it('shows the WhatsApp buttons only when the workshop number is set', function () {
    $this->get('/')->assertDontSee('wa.me', false);

    $general = app(GeneralSettings::class);
    $general->whatsapp = '+5511999990000';
    $general->save();

    $this->get('/')->assertSee('https://wa.me/5511999990000', false);
});

it('does not load the animation bundle outside the landing', function () {
    $this->get('/catalogo')->assertOk()->assertDontSee('landing', false)->assertSee('Materiais e trabalhos');
});

it('renders the catalog with materials and filters', function () {
    $this->get('/catalogo')->assertOk()->assertSee('Couro natural Nappa')->assertSee('Caramelo')->assertSee('Vinil');
});
