<?php

use App\Domain\Auth\Role;
use App\Domain\Notifications\Customer\MessageEvent;
use App\Domain\Projects\Actions\ChangeProjectStatus;
use App\Domain\Projects\Actions\SubmitProject;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Events\LeadCreated;
use App\Models\Customer;
use App\Models\MaterialVariant;
use App\Models\Project;
use App\Models\ServiceType;
use App\Notifications\CustomerMessage;
use App\Notifications\StaffAlert;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
    $this->seats = ServiceType::where('slug', 'seats')->first();
    $this->colortek = ServiceType::where('slug', 'colortek')->first();
    $this->caramel = MaterialVariant::where('color_hex', '#B5651D')->first();
});

function projectData(array $overrides = []): array
{
    return array_replace_recursive([
        'vehicle' => ['brand' => 'Volkswagen', 'model' => 'Fusca', 'year' => 1972],
        'pieces' => [['service_type_id' => test()->seats->id, 'material_variant_id' => test()->caramel->id]],
        'stitching' => '#FFFFFF',
        'contact' => ['name' => 'Ana', 'phone' => '+55 (11) 98765-4321', 'email' => 'ana@example.com', 'locale' => 'pt_BR'],
        'privacy' => true,
        'contact_opt_in' => true,
    ], $overrides);
}

it('creates the lead with a server-side estimate and notifies the team in real time', function () {
    Notification::fake();
    Event::fake([LeadCreated::class]);
    $reception = userWithRole(Role::Reception);
    $upholsterer = userWithRole(Role::Upholsterer);

    $project = app(SubmitProject::class)->execute(projectData([
        'estimate_min_cents' => 1, // el navegador no decide el precio
    ]));

    expect($project->status)->toBe(ProjectStatus::Submitted)
        ->and($project->estimate_min_cents)->toBe(325000)
        ->and($project->estimate_max_cents)->toBe(450000)
        ->and($project->customer->phone)->toBe('+5511987654321')
        ->and($project->customer->contact_opt_in_at)->not->toBeNull()
        ->and($project->configuration['pieces'][0]['color_hex'])->toBe('#B5651D')
        ->and($project->configuration['privacy_accepted_at'])->not->toBeNull();

    Event::assertDispatched(LeadCreated::class, fn (LeadCreated $e) => $e->projectId === $project->id);
    Notification::assertSentTo($reception, StaffAlert::class, fn (StaffAlert $n) => $n->type === 'lead_created');
    Notification::assertNotSentTo($upholsterer, StaffAlert::class);
});

it('reuses the customer by phone without overwriting the name', function () {
    Notification::fake();
    $customer = Customer::factory()->create(['phone' => '+5511987654321', 'name' => 'Ana Original']);

    $project = app(SubmitProject::class)->execute(projectData(['contact' => ['name' => 'Outro nome']]));

    expect($project->customer_id)->toBe($customer->id)
        ->and($customer->fresh()->name)->toBe('Ana Original')
        ->and(Customer::count())->toBe(1);
});

it('validates the project on the server', function (array $overrides, string $field) {
    Notification::fake();

    try {
        app(SubmitProject::class)->execute(projectData($overrides));
        $this->fail('Expected validation error');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey($field);
    }

    expect(Project::count())->toBe(0);
})->with([
    'no privacy' => [['privacy' => false], 'privacy'],
    'bad phone' => [['contact' => ['phone' => '123']], 'contact.phone'],
    'no brand' => [['vehicle' => ['brand' => '']], 'vehicle.brand'],
    'old year' => [['vehicle' => ['year' => 1900]], 'vehicle.year'],
    'bad stitching' => [['stitching' => 'red'], 'stitching'],
    'unknown variant' => [['pieces' => [['service_type_id' => 1, 'material_variant_id' => 999999]]], 'pieces.0.material_variant_id'],
]);

it('requires a material for services that use one', function () {
    app(SubmitProject::class)->execute(projectData(['pieces' => [['service_type_id' => $this->seats->id, 'material_variant_id' => null]]]));
})->throws(ValidationException::class);

it('accepts services without material', function () {
    Notification::fake();
    $project = app(SubmitProject::class)->execute(projectData(['pieces' => [['service_type_id' => $this->colortek->id, 'material_variant_id' => null]]]));

    expect($project->estimate_min_cents)->toBeGreaterThan(0);
});

it('requires unique pieces', function () {
    $piece = ['service_type_id' => $this->seats->id, 'material_variant_id' => $this->caramel->id];
    app(SubmitProject::class)->execute(array_merge(projectData(), ['pieces' => [$piece, $piece]]));
})->throws(ValidationException::class);

it('follows the lead status machine', function () {
    Notification::fake();
    $project = app(SubmitProject::class)->execute(projectData());

    app(ChangeProjectStatus::class)->execute($project, ProjectStatus::Contacted);
    expect($project->fresh()->status)->toBe(ProjectStatus::Contacted);

    expect(fn () => app(ChangeProjectStatus::class)->execute($project, ProjectStatus::Approved))->toThrow(ValidationException::class)
        ->and(fn () => app(ChangeProjectStatus::class)->execute($project, ProjectStatus::Lost))->toThrow(ValidationException::class);

    app(ChangeProjectStatus::class)->execute($project, ProjectStatus::Lost, 'Achou caro');
    expect($project->fresh()->lost_reason)->toBe('Achou caro');
});

it('ignores an unknown or self referral code', function () {
    Notification::fake();
    $project = app(SubmitProject::class)->execute(projectData(['referral' => 'NOPE99']));

    expect($project->referred_by_customer_id)->toBeNull()
        ->and($project->customer->referral_code)->toHaveLength(6);

    $again = app(SubmitProject::class)->execute(projectData(['referral' => $project->customer->referral_code]));
    expect($again->referred_by_customer_id)->toBeNull();
});

it('tells the customer the project was received', function () {
    Notification::fake();
    $project = app(SubmitProject::class)->execute(projectData());

    Notification::assertSentTo($project->customer, CustomerMessage::class,
        fn ($n) => $n->event === MessageEvent::ProjectReceived && $n->idempotencyKey === "project:{$project->id}:received");
});
