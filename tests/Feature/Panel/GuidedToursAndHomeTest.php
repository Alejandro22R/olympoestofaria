<?php

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Auth\Role;
use App\Domain\Tours\TourCatalog;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Widgets\QuickStart;
use App\Models\Appointment;
use Illuminate\Support\Facades\Lang;
use Livewire\Livewire;

it('has a title and text for every tutorial step in both languages', function () {
    foreach (TourCatalog::definitions() as $key => $steps) {
        foreach ($steps as [$step]) {
            foreach (['pt_BR', 'es'] as $locale) {
                $base = Lang::has("tours.{$key}.{$step}", $locale, false) ? "tours.{$key}.{$step}" : "tours.list.{$step}";
                expect(Lang::has("{$base}.title", $locale, false))->toBeTrue("{$locale} {$key}.{$step}")
                    ->and(Lang::has("{$base}.body", $locale, false))->toBeTrue("{$locale} {$key}.{$step}");
            }
        }
    }
});

it('maps screens to their tutorial', function (string $route, ?string $tour) {
    expect(TourCatalog::keyForRoute($route))->toBe($tour);
})->with([
    ['filament.admin.pages.dashboard', 'dashboard'],
    ['filament.admin.resources.customers.index', 'customers'],
    ['filament.admin.resources.appointments.check-in', 'checkin'],
    ['filament.admin.settings.pages.general', 'settings'],
    ['filament.admin.resources.customers.create', null],
]);

it('opens the tutorial by itself the first time and not after it is done', function () {
    $user = userWithRole(Role::Reception);
    $this->actingAs($user);

    $this->get('/admin/customers')->assertOk()
        ->assertSee('id="olympo-tour"', false)->assertSee('"autostart":true', false)
        ->assertSee('data-olympo-tour-start', false);

    $this->postJson(route('admin.tours.complete', ['tour' => 'customers']))->assertOk();
    expect($user->fresh()->completed_tours)->toBe(['customers']);

    $this->get('/admin/customers')->assertSee('"autostart":false', false);
    $this->postJson(route('admin.tours.complete', ['tour' => 'nope']))->assertStatus(422);
});

it('lets each person see the tutorials again from the profile', function () {
    $user = userWithRole(Role::Owner, ['completed_tours' => ['dashboard', 'customers']]);
    $this->actingAs($user);

    Livewire::test(EditProfile::class)->callAction('resetTours')->assertNotified();

    expect($user->fresh()->completed_tours)->toBe([]);
});

it('requires a session to mark tutorials', function () {
    $this->postJson(route('admin.tours.complete', ['tour' => 'dashboard']))->assertUnauthorized();
});

it('shows quick actions and today on the home page, by role', function () {
    seedProduction();
    freezeMonday();
    $appointment = Appointment::factory()->create(['scheduled_at' => now()->addHours(2), 'status' => AppointmentStatus::Confirmed]);

    $this->actingAs(userWithRole(Role::Reception));
    Livewire::test(QuickStart::class)
        ->assertSee('Agendar recepção')->assertSee('Novo orçamento')
        ->assertSee('Recepções de hoje')->assertSee($appointment->vehicle->label())->assertSee('Receber veículo');

    $this->actingAs(userWithRole(Role::Upholsterer));
    Livewire::test(QuickStart::class)
        ->assertSee('Quadro de produção')->assertDontSee('Novo orçamento')->assertDontSee('Receber veículo');

    $this->get('/admin')->assertOk()->assertSee('Início');
});

it('shows the official emblem in the panel and the portal', function () {
    $this->get('/admin/login')->assertSee('images/brand/emblem.svg', false);
    $this->get('/')->assertSee('images/brand/emblem.svg', false)->assertSee('apple-touch-icon.png', false);
    expect(file_exists(public_path('images/brand/emblem-256.png')))->toBeTrue();
});

it('shows lists as cards with a friendly empty state', function () {
    $this->actingAs(userWithRole(Role::Reception));

    $this->get('/admin/customers')->assertOk()
        ->assertSee('Ainda não há clientes')
        ->assertSee('As fichas dos clientes');
});

it('puts the emblem on the quote PDF', function () {
    \Illuminate\Support\Facades\Storage::fake('private');
    $quote = sentQuote();

    app(\App\Domain\Quotes\Actions\GenerateQuotePdf::class)->execute($quote);

    $pdf = \Illuminate\Support\Facades\Storage::disk($quote->fresh()->report_disk)->get($quote->fresh()->report_path);
    expect($pdf)->toContain('/Subtype /Image');
});
