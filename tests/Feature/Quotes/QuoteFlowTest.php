<?php

use App\Domain\Auth\Role;
use App\Domain\Inspections\Enums\ReportChannel;
use App\Domain\Projects\Actions\SubmitProject;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Quotes\Actions\CreateQuote;
use App\Domain\Quotes\Actions\DecideQuote;
use App\Domain\Quotes\Actions\ExpireQuotes;
use App\Domain\Quotes\Actions\ReviseQuote;
use App\Domain\Quotes\Actions\SaveQuote;
use App\Domain\Quotes\Actions\SendQuote;
use App\Domain\Quotes\Enums\QuoteStatus;
use App\Domain\Settings\PricingSettings;
use App\Models\MaterialVariant;
use App\Models\ServiceType;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Notification::fake();
    Storage::fake('private');
    $this->seed(CatalogSeeder::class);
    $this->owner = userWithRole(Role::Owner);
    $this->project = app(SubmitProject::class)->execute([
        'vehicle' => ['brand' => 'Volkswagen', 'model' => 'Fusca', 'year' => 1972],
        'pieces' => [['service_type_id' => ServiceType::where('slug', 'seats')->value('id'), 'material_variant_id' => MaterialVariant::where('color_hex', '#B5651D')->value('id')]],
        'contact' => ['name' => 'Ana', 'phone' => '+5511987654321', 'email' => 'ana@example.com', 'locale' => 'pt_BR'],
        'privacy' => true,
    ]);
});

it('creates a draft quote from the lead with the estimate as starting price', function () {
    $quote = app(CreateQuote::class)->fromProject($this->project, $this->owner);

    expect($quote->status)->toBe(QuoteStatus::Draft)
        ->and($quote->items)->toHaveCount(1)
        ->and($quote->items[0]->unit_price_cents)->toBe(388000)
        ->and($quote->items[0]->description)->toContain('Couro natural Nappa')
        ->and($quote->total_cents)->toBe(388000)
        ->and($quote->deposit_cents)->toBe(116400)
        ->and($quote->vehicle_label)->toBe('Volkswagen Fusca 1972')
        ->and($quote->valid_until->toDateString())->toBe(now()->addDays(7)->toDateString());
});

it('requires contact first when the owner says so', function () {
    $settings = app(PricingSettings::class);
    $settings->require_contact_before_quote = true;
    $settings->save();

    app(CreateQuote::class)->fromProject($this->project, $this->owner);
})->throws(ValidationException::class);

it('limits the discount and recalculates totals on the server', function () {
    $quote = app(CreateQuote::class)->fromProject($this->project, $this->owner);

    expect(fn () => app(SaveQuote::class)->execute($quote, ['discount_cents' => 50000], $this->owner))->toThrow(ValidationException::class);

    app(SaveQuote::class)->execute($quote, ['discount_cents' => 38800, 'items' => [['description' => 'Bancos', 'quantity' => 1, 'unit_price_cents' => 388000]]], $this->owner);
    expect($quote->fresh()->total_cents)->toBe(349200);
});

it('lets only the configured roles change the final price', function () {
    $quote = app(CreateQuote::class)->fromProject($this->project, $this->owner);
    $settings = app(PricingSettings::class);
    $settings->final_price_roles = ['owner'];
    $settings->save();
    $reception = userWithRole(Role::Reception);

    expect(fn () => app(SaveQuote::class)->execute($quote, ['items' => [['description' => 'Bancos', 'quantity' => 1, 'unit_price_cents' => 1]]], $reception))
        ->toThrow(ValidationException::class);

    // Cambiar notas o vigencia sí está permitido.
    app(SaveQuote::class)->execute($quote->fresh('items'), ['notes' => 'Inclui higienização'], $reception);
    expect($quote->fresh()->notes)->toBe('Inclui higienização');
});

it('sends the quote: PDF, status, lead quoted and a WhatsApp link to the approval page', function () {
    $quote = app(CreateQuote::class)->fromProject($this->project, $this->owner);

    $result = app(SendQuote::class)->execute($quote, ReportChannel::WhatsApp, $this->owner);

    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Sent)
        ->and($quote->sent_at)->not->toBeNull()
        ->and($this->project->fresh()->status)->toBe(ProjectStatus::Quoted)
        ->and(rawurldecode($result['url']))->toContain('/orcamento/'.$quote->id)
        ->and($quote->sends()->count())->toBe(1);
    Storage::disk('private')->assertExists($quote->report_path);
});

it('does not edit a sent quote: it creates a new version', function () {
    $quote = app(CreateQuote::class)->fromProject($this->project, $this->owner);
    app(SendQuote::class)->execute($quote, ReportChannel::WhatsApp, $this->owner);

    expect(fn () => app(SaveQuote::class)->execute($quote->fresh(), ['notes' => 'x'], $this->owner))->toThrow(ValidationException::class);

    $v2 = app(ReviseQuote::class)->execute($quote->fresh(), $this->owner);

    expect($v2->version)->toBe(2)
        ->and($v2->status)->toBe(QuoteStatus::Draft)
        ->and($v2->items)->toHaveCount(1)
        ->and($v2->number())->toBe('ORC-'.str_pad((string) $quote->id, 5, '0', STR_PAD_LEFT).' v2')
        ->and($quote->fresh()->status)->toBe(QuoteStatus::Superseded);
});

it('approves and rejects only open, valid quotes', function () {
    $quote = sentQuote();
    app(DecideQuote::class)->approve($quote, 'portal', '1.2.3.4');
    expect($quote->fresh()->status)->toBe(QuoteStatus::Approved)->and($quote->fresh()->approval_ip)->toBe('1.2.3.4');

    expect(fn () => app(DecideQuote::class)->reject($quote->fresh(), 'x'))->toThrow(ValidationException::class);

    $expired = sentQuote();
    $expired->forceFill(['valid_until' => now()->subDay()])->save();
    expect(fn () => app(DecideQuote::class)->approve($expired->fresh()))->toThrow(ValidationException::class);
});

it('expires quotes past their validity', function () {
    $quote = sentQuote();
    $quote->forceFill(['valid_until' => now()->subDays(2)])->save();

    expect(app(ExpireQuotes::class)->execute())->toBe(1)
        ->and($quote->fresh()->status)->toBe(QuoteStatus::Expired);
    $this->artisan('olympo:quotes:expire')->assertSuccessful();
});
