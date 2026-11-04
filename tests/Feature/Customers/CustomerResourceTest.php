<?php

use App\Domain\Auth\Role;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(fn () => $this->actingAs(userWithRole(Role::Reception)));

it('creates a customer with a normalized phone', function () {
    Livewire::test(CreateCustomer::class)
        ->fillForm(['name' => 'Ana Souza', 'phone' => '+55 (11) 98765-4321', 'locale' => 'pt_BR'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Customer::sole()->phone)->toBe('+5511987654321');
});

it('rejects an invalid phone', function () {
    Livewire::test(CreateCustomer::class)
        ->fillForm(['name' => 'Ana', 'phone' => '987654', 'locale' => 'pt_BR'])
        ->call('create')
        ->assertHasFormErrors(['phone']);
});

it('stores the document encrypted only with LGPD consent', function () {
    Livewire::test(CreateCustomer::class)
        ->fillForm(['name' => 'Ana', 'phone' => '+5511987654321', 'locale' => 'pt_BR',
            'document_type' => 'cpf', 'document_number' => '529.982.247-25'])
        ->call('create')
        ->assertHasFormErrors(['lgpd_consent' => 'accepted']);

    expect(Customer::count())->toBe(0);

    Livewire::test(CreateCustomer::class)
        ->fillForm(['name' => 'Ana', 'phone' => '+5511987654321', 'locale' => 'pt_BR',
            'document_type' => 'cpf', 'document_number' => '529.982.247-25', 'lgpd_consent' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $customer = Customer::sole();
    $raw = DB::table('customers')->where('id', $customer->id)->value('document_number');

    expect($customer->document_number)->toBe('529.982.247-25')
        ->and($raw)->not->toContain('529')
        ->and($customer->lgpd_consent_at)->not->toBeNull()
        ->and($customer->maskedDocument())->toBe('•••••••4725');
});

it('validates the CPF check digits', function () {
    Livewire::test(CreateCustomer::class)
        ->fillForm(['name' => 'Ana', 'phone' => '+5511987654321', 'locale' => 'pt_BR',
            'document_type' => 'cpf', 'document_number' => '529.982.247-24', 'lgpd_consent' => true])
        ->call('create')
        ->assertHasFormErrors(['document_number']);
});

it('rejects a document that already belongs to another customer', function () {
    Customer::factory()->withCpf('52998224725')->create();

    Livewire::test(CreateCustomer::class)
        ->fillForm(['name' => 'Outra', 'phone' => '+5511987654322', 'locale' => 'pt_BR',
            'document_type' => 'cpf', 'document_number' => '529.982.247-25', 'lgpd_consent' => true])
        ->call('create')
        ->assertHasFormErrors(['document_number']);
});

it('never puts the decrypted document in the edit form and keeps it when left blank', function () {
    $customer = Customer::factory()->withCpf('52998224725')->create();

    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->assertSchemaStateSet(['document_number' => null], 'form')
        ->fillForm(['name' => 'Nome novo'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($customer->fresh()->name)->toBe('Nome novo')
        ->and($customer->fresh()->document_number)->toBe('52998224725');
});

it('does not log the document number in the audit trail', function () {
    Customer::factory()->withCpf('52998224725')->create();

    $properties = Activity::query()->pluck('properties')->toJson();

    expect($properties)->not->toContain('52998224725');
});

it('lets the upholsterer list but not create customers', function () {
    $this->actingAs(userWithRole(Role::Upholsterer));

    Livewire::test(ListCustomers::class)->assertOk();
    Livewire::test(CreateCustomer::class)->assertForbidden();
});
