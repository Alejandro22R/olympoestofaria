<?php

use App\Livewire\Portal\ProjectBuilder;
use App\Models\MaterialVariant;
use App\Models\Project;
use App\Models\ServiceType;
use App\Models\VehicleModel;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->seed(CatalogSeeder::class);
    $this->seats = ServiceType::where('slug', 'seats')->first();
    $this->caramel = MaterialVariant::where('color_hex', '#B5651D')->first();
    RateLimiter::clear('builder-submit:127.0.0.1');
});

function readyBuilder(): Testable
{
    return Livewire::test(ProjectBuilder::class)
        ->call('selectModel', VehicleModel::where('model', 'Fusca')->value('id'))
        ->call('next')
        ->call('toggleService', test()->seats->id)
        ->call('next')
        ->call('selectVariant', test()->seats->id, test()->caramel->id)
        ->call('next')
        ->assertSet('step', 4);
}

it('renders the builder page', function () {
    $this->get('/projeto')->assertOk()->assertSee('Monte seu projeto')->assertSeeLivewire(ProjectBuilder::class);
});

it('requires the vehicle before moving on', function () {
    Livewire::test(ProjectBuilder::class)->call('next')->assertHasErrors(['vehicle.brand', 'vehicle.model'])->assertSet('step', 1);
});

it('requires at least one piece and the material of each piece', function () {
    Livewire::test(ProjectBuilder::class)
        ->set('vehicle.brand', 'Fiat')->set('vehicle.model', 'Uno')
        ->call('next')->assertSet('step', 2)
        ->call('next')->assertHasErrors('selection')->assertSet('step', 2)
        ->call('toggleService', $this->seats->id)
        ->call('next')->assertSet('step', 3)
        ->call('next')->assertHasErrors('selection.'.$this->seats->id)->assertSet('step', 3);
});

it('updates the preview colors and the estimate live', function () {
    Livewire::test(ProjectBuilder::class)
        ->call('toggleService', $this->seats->id)
        ->call('selectVariant', $this->seats->id, $this->caramel->id)
        ->assertSet('colors.seats', '#B5651D')
        ->set('stitching', '#0B8FD9')
        ->assertSet('colors.stitching', '#0B8FD9')
        ->assertSee('R$ 3.250,00')
        ->assertSee('R$ 4.500,00');
});

it('preselects the service from the landing card', function () {
    Livewire::withQueryParams(['servico' => 'seats'])->test(ProjectBuilder::class)
        ->assertSet('selection', [$this->seats->id => null]);
});

it('submits the project, clears the draft and redirects to a signed confirmation', function () {
    readyBuilder()
        ->set('contact.name', 'Ana')->set('contact.phone', '+55 11 98765-4321')->set('privacy', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirectContains('/projeto/enviado/');

    expect(Project::count())->toBe(1)
        ->and(session('builder.selection'))->toBeEmpty();
});

it('requires the privacy consent', function () {
    readyBuilder()
        ->set('contact.name', 'Ana')->set('contact.phone', '+5511987654321')
        ->call('submit')->assertHasErrors('privacy');
});

it('silently drops spam caught by the honeypot', function () {
    readyBuilder()
        ->set('contact.name', 'Bot')->set('contact.phone', '+5511987654321')->set('privacy', true)->set('website', 'http://spam')
        ->call('submit')->assertRedirect(route('home'));

    expect(Project::count())->toBe(0);
});

it('rate limits submissions per IP', function () {
    foreach (range(1, ProjectBuilder::MAX_SUBMITS_PER_HOUR) as $i) {
        RateLimiter::hit('builder-submit:127.0.0.1', 3600);
    }

    readyBuilder()
        ->set('contact.name', 'Ana')->set('contact.phone', '+5511987654321')->set('privacy', true)
        ->call('submit')->assertHasErrors('contact.phone');

    expect(Project::count())->toBe(0);
});

it('shows the confirmation only with a valid signature', function () {
    readyBuilder()
        ->set('contact.name', 'Ana')->set('contact.phone', '+5511987654321')->set('privacy', true)
        ->call('submit');
    $project = Project::sole();

    $this->get(route('portal.project.sent', $project))->assertForbidden();
    $this->get(URL::temporarySignedRoute('portal.project.sent', now()->addHour(), ['project' => $project->id]))
        ->assertOk()->assertSee($project->number())->assertSee('Couro natural Nappa');
});
