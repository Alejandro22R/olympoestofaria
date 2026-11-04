<?php

use App\Domain\Auth\Role;
use App\Domain\Customers\Actions\CustomerDocumentUrl;
use App\Domain\Customers\Actions\StoreCustomerDocument;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Models\Customer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('private');
    $this->reception = userWithRole(Role::Reception);
    $this->customer = Customer::factory()->withConsent()->create();
});

function fakeJpeg(): UploadedFile
{
    return UploadedFile::fake()->image('rg.jpg', 600, 400);
}

it('stores the document encrypted on the private disk', function () {
    $document = app(StoreCustomerDocument::class)->execute($this->customer, $file = fakeJpeg(), $this->reception);

    $stored = Storage::disk('private')->get($document->path);

    expect($document->mime_type)->toBe('image/jpeg')
        ->and($stored)->not->toBe(file_get_contents($file->getRealPath()))
        ->and(Crypt::decryptString($stored))->toBe(file_get_contents($file->getRealPath()));
});

it('requires LGPD consent', function () {
    $customer = Customer::factory()->create();

    app(StoreCustomerDocument::class)->execute($customer, fakeJpeg());
})->throws(ValidationException::class);

it('checks the real MIME type, not the extension', function () {
    $fake = UploadedFile::fake()->createWithContent('rg.jpg', '<?php echo "hi";');

    app(StoreCustomerDocument::class)->execute($this->customer, $fake);
})->throws(ValidationException::class);

it('keeps a single document per customer and deletes the previous file', function () {
    $first = app(StoreCustomerDocument::class)->execute($this->customer, fakeJpeg());
    $second = app(StoreCustomerDocument::class)->execute($this->customer, fakeJpeg());

    expect($this->customer->document()->count())->toBe(1)
        ->and($second->id)->toBe($first->id);
    Storage::disk('private')->assertMissing($first->getOriginal('path') === $second->path ? 'none' : $first->path);
});

it('serves the document only through a valid signed URL to authorized staff', function () {
    $document = app(StoreCustomerDocument::class)->execute($this->customer, $file = fakeJpeg());
    $url = app(CustomerDocumentUrl::class)->for($document);

    $this->actingAs($this->reception)->get($url)
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg')
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private');

    // Sin firma.
    $this->get(route('admin.customer-documents.show', $document))->assertForbidden();

    // Sin permiso (tapicero).
    $this->actingAs(userWithRole(Role::Upholsterer))->get($url)->assertForbidden();
});

it('expires the signed URL after a few minutes', function () {
    $document = app(StoreCustomerDocument::class)->execute($this->customer, fakeJpeg());
    $url = app(CustomerDocumentUrl::class)->for($document);

    $this->travel(CustomerDocumentUrl::TTL_MINUTES + 1)->minutes();

    $this->actingAs($this->reception)->get($url)->assertForbidden();
});

it('redirects guests to the login', function () {
    $document = app(StoreCustomerDocument::class)->execute($this->customer, fakeJpeg());

    $this->get(app(CustomerDocumentUrl::class)->for($document))->assertRedirect('/admin/login');
});

it('uploads the document from the customer page', function () {
    $this->actingAs($this->reception);

    Livewire::test(ViewCustomer::class, ['record' => $this->customer->getRouteKey()])
        ->callAction('uploadDocument', data: ['file' => fakeJpeg()])
        ->assertHasNoActionErrors();

    expect($this->customer->document()->exists())->toBeTrue();
});

it('disables the upload without consent', function () {
    $this->actingAs($this->reception);
    $customer = Customer::factory()->create();

    Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
        ->assertActionDisabled('uploadDocument');
});
