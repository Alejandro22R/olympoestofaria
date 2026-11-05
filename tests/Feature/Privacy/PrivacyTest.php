<?php

use App\Domain\Auth\Role;
use App\Domain\Customers\Actions\StoreCustomerDocument;
use App\Domain\Customers\Actions\UpdateContactPreferences;
use App\Domain\Privacy\Actions\AnonymizeCustomer;
use App\Domain\Privacy\Actions\ExportCustomerData;
use App\Domain\Privacy\ApplyRetention;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Settings\PrivacySettings;
use App\Domain\Users\Actions\UpdateRolePermissions;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Filament\Clusters\Settings\Pages\ManagePrivacySettings;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Models\ConsentEvent;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Notification::fake();
    Storage::fake('private');
    seedProduction();
    $this->owner = userWithRole(Role::Owner);
    $this->customer = Customer::factory()->withConsent()->create([
        'name' => 'Joana Prado', 'email' => 'joana@example.com', 'phone' => '+5511955554444',
        'document_type' => 'cpf', 'document_number' => '52998224725', 'notes' => 'Prefere sábado',
    ]);
    $this->vehicle = Vehicle::factory()->for($this->customer)->create(['plate' => 'JOA1N23']);
});

it('exports all the customer data as JSON and audits the export', function () {
    $order = WorkOrder::factory()->for($this->vehicle)->inStatus(WorkOrderStatus::Delivered)->create();
    $order->items()->create(['description' => 'Bancos', 'price_cents' => 150000]);

    $data = app(ExportCustomerData::class)->execute($this->customer, $this->owner);

    expect($data['customer']['email'])->toBe('joana@example.com')
        ->and($data['customer']['document_number'])->toBe('52998224725')
        ->and($data['vehicles'][0]['plate'])->toBe('JOA1N23')
        ->and($data['work_orders'][0]['items'][0]['price_cents'])->toBe(150000)
        ->and(Activity::query()->where('description', 'privacy.exported')->where('causer_id', $this->owner->id)->exists())->toBeTrue();
});

it('downloads the export from the customer page (owner only)', function () {
    $this->actingAs($this->owner);
    Livewire::test(ViewCustomer::class, ['record' => $this->customer->getRouteKey()])
        ->callAction('exportData')->assertFileDownloaded('olympo-cliente-'.$this->customer->id.'.json');

    $this->actingAs(userWithRole(Role::Reception));
    Livewire::test(ViewCustomer::class, ['record' => $this->customer->getRouteKey()])->assertActionHidden('exportData');
});

it('anonymizes the customer, removes identifying files and keeps the money records', function () {
    $user = userWithRole(Role::Customer);
    $this->customer->forceFill(['user_id' => $user->id])->save();
    $order = WorkOrder::factory()->for($this->vehicle)->inStatus(WorkOrderStatus::Delivered)->create();
    $order->items()->create(['description' => 'Bancos', 'price_cents' => 150000]);
    Storage::disk('private')->put('updates/foto.jpg', 'x');
    $order->updates()->create(['status' => WorkOrderStatus::InProduction, 'note' => 'Joana pediu', 'photos' => ['updates/foto.jpg']]);
    app(StoreCustomerDocument::class)->execute($this->customer, UploadedFile::fake()->image('rg.jpg'));
    $documentPath = $this->customer->document()->value('path');

    app(AnonymizeCustomer::class)->execute($this->customer, $this->owner);

    $c = $this->customer->fresh();
    expect($c->name)->toBe('Cliente anonimizado #'.$c->id)
        ->and($c->email)->toBeNull()->and($c->phone)->toBe('')->and($c->document_number)->toBeNull()
        ->and($c->notes)->toBeNull()->and($c->isAnonymized())->toBeTrue()
        ->and($this->vehicle->fresh()->plate)->toStartWith('ANON')
        ->and((int) $order->items()->sum('price_cents'))->toBe(150000)
        ->and($order->updates()->first()->photos)->toBe([])
        ->and($user->fresh())->toBeNull()
        ->and($c->document()->exists())->toBeFalse();
    Storage::disk('private')->assertMissing('updates/foto.jpg');
    Storage::disk('private')->assertMissing($documentPath);

    $logs = Activity::query()->where('subject_type', $c->getMorphClass())->where('subject_id', $c->id)->where('description', '!=', 'privacy.anonymized')->get();
    expect($logs->every(fn ($a) => $a->properties->isEmpty()))->toBeTrue();
    expect(json_encode(Activity::all()->pluck('properties')))->not->toContain('joana@example.com');
});

it('refuses to anonymize a customer with an open order', function () {
    WorkOrder::factory()->for($this->vehicle)->create();

    app(AnonymizeCustomer::class)->execute($this->customer, $this->owner);
})->throws(ValidationException::class);

it('asks to type the confirmation word before anonymizing in the panel', function () {
    $this->actingAs($this->owner);

    Livewire::test(ViewCustomer::class, ['record' => $this->customer->getRouteKey()])
        ->callAction('anonymize', data: ['confirm' => 'sim'])->assertHasActionErrors(['confirm']);
    expect($this->customer->fresh()->isAnonymized())->toBeFalse();

    Livewire::test(ViewCustomer::class, ['record' => $this->customer->getRouteKey()])
        ->callAction('anonymize', data: ['confirm' => 'ANONIMIZAR'])->assertHasNoActionErrors();
    expect($this->customer->fresh()->isAnonymized())->toBeTrue();
});

it('records consents from the check-in, the document and the unsubscribe link', function () {
    app(StoreCustomerDocument::class)->execute($this->customer, UploadedFile::fake()->image('rg.jpg'));
    app(UpdateContactPreferences::class)->unsubscribe($this->customer);

    $events = ConsentEvent::query()->where('customer_id', $this->customer->id)->orderBy('id')->get();
    expect($events->map(fn ($e) => [$e->type, $e->granted, $e->source])->all())
        ->toBe([['document', true, 'panel'], ['contact', false, 'email_link']]);
});

it('does not accept document photos when the owner turns it off', function () {
    $s = app(PrivacySettings::class);
    $s->store_document_photo = false;
    $s->save();

    app(StoreCustomerDocument::class)->execute($this->customer, UploadedFile::fake()->image('rg.jpg'));
})->throws(ValidationException::class);

it('applies the retention policy', function () {
    $lost = Project::query()->create(['customer_id' => $this->customer->id, 'status' => ProjectStatus::Lost, 'configuration' => [], 'submitted_at' => now()]);
    $lost->forceFill(['updated_at' => now()->subMonths(25)])->saveQuietly();
    $recent = Project::query()->create(['customer_id' => $this->customer->id, 'status' => ProjectStatus::Lost, 'configuration' => [], 'submitted_at' => now()]);

    $old = Customer::factory()->create();
    $old->forceFill(['updated_at' => now()->subMonths(40)])->saveQuietly();
    $busy = Customer::factory()->create();
    $busy->forceFill(['updated_at' => now()->subMonths(40)])->saveQuietly();
    WorkOrder::factory()->for(Vehicle::factory()->for($busy))->create();

    expect(app(ApplyRetention::class)->execute())->toBe(['leads_deleted' => 1, 'customers_anonymized' => 0]);

    $s = app(PrivacySettings::class);
    $s->inactive_customer_months = 36;
    $s->save();

    $result = app(ApplyRetention::class)->execute();
    expect($result['customers_anonymized'])->toBe(1)
        ->and($old->fresh()->isAnonymized())->toBeTrue()
        ->and($busy->fresh()->isAnonymized())->toBeFalse()
        ->and(Project::query()->whereKey($recent->id)->exists())->toBeTrue();
});

it('shows the legal pages with the workshop data and links them in the footer', function () {
    $this->get('/privacidade')->assertOk()->assertSee('Política de Privacidade')->assertSee('LGPD')->assertDontSee('{oficina}');
    $this->get('/termos')->assertOk()->assertSee('Termos de Uso');
    $this->get('/termos?lang=es')->assertOk()->assertSee('Términos de Uso');
    $this->get('/catalogo')->assertSee(route('portal.privacy'));
});

it('lets the owner edit the legal texts and strips raw HTML', function () {
    $this->actingAs($this->owner);
    Livewire::test(ManagePrivacySettings::class)
        ->fillForm(['privacy_policy.pt_BR' => "## Texto novo\n\n<script>alert(1)</script>\n\nContato: {contato}", 'contact_email' => 'dpo@olympo.test'])
        ->call('save')->assertHasNoFormErrors();

    $this->get('/privacidade')->assertSee('Texto novo')->assertSee('dpo@olympo.test')->assertDontSee('<script>alert(1)</script>', false);
});

it('keeps privacy actions and pages away from reception', function () {
    $this->actingAs(userWithRole(Role::Reception));

    $this->get('/admin/settings/privacy')->assertForbidden();
    $this->get('/admin/settings/consent-events')->assertForbidden();
    expect(fn () => app(UpdateRolePermissions::class)->execute(Role::Reception, ['privacy.manage']))->not->toThrow(Exception::class)
        ->and(userWithRole(Role::Reception)->can('privacy.manage'))->toBeFalse();
});
