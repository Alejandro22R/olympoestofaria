<?php

use App\Domain\Auth\Role;
use App\Domain\Inspections\Actions\InspectionReportLink;
use App\Domain\Inspections\Actions\RegisterCheckIn;
use App\Domain\Inspections\Actions\SendInspectionReport;
use App\Domain\Inspections\Enums\ReportChannel;
use App\Domain\Settings\CheckinSettings;
use App\Mail\InspectionReportMail;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Vehicle;
use Database\Seeders\CheckinCatalogSeeder;
use Illuminate\Mail\Attachment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake('private');
    Mail::fake();
    $this->seed(CheckinCatalogSeeder::class);
    $this->reception = userWithRole(Role::Reception);
    $customer = Customer::factory()->create(['email' => 'ana@example.com', 'phone' => '+5511987654321', 'locale' => 'es']);
    $appointment = Appointment::factory()->for(Vehicle::factory()->for($customer))->create();
    $this->inspection = app(RegisterCheckIn::class)->execute($appointment, [
        'mileage_km' => 1000, 'fuel_level' => 'full', 'lgpd_consent' => true, 'marks' => [],
    ], $this->reception);
});

it('emails the report with the PDF and logs who, when and how', function () {
    $result = app(SendInspectionReport::class)->execute($this->inspection, ReportChannel::Email, $this->reception);

    Mail::assertQueued(InspectionReportMail::class, fn (InspectionReportMail $m) => $m->hasTo('ana@example.com')
        && $m->locale === 'es');

    $send = $result['send'];
    expect($send->channel)->toBe(ReportChannel::Email)
        ->and($send->recipient)->toBe('ana@example.com')
        ->and($send->sent_by)->toBe($this->reception->id)
        ->and($send->sent_at)->not->toBeNull();
});

it('attaches the PDF to the email', function () {
    $mail = new InspectionReportMail($this->inspection);

    expect($mail->attachments())->toHaveCount(1);
    $mail->assertHasAttachment(
        Attachment::fromStorageDisk('private', $this->inspection->report_path)
            ->as('acta-'.$this->inspection->vehicle->plate.'.pdf')
            ->withMime('application/pdf'),
    );
});

it('fails to email when the customer has no email', function () {
    $this->inspection->vehicle->customer->update(['email' => null]);

    app(SendInspectionReport::class)->execute($this->inspection->fresh(), ReportChannel::Email, $this->reception);
})->throws(ValidationException::class);

it('prepares the WhatsApp message with a signed link in the customer language and logs it', function () {
    $result = app(SendInspectionReport::class)->execute($this->inspection, ReportChannel::WhatsApp, $this->reception);

    expect($result['url'])->toStartWith('https://wa.me/5511987654321?text=')
        ->and(rawurldecode($result['url']))->toContain('Recibimos tu vehículo')
        ->and(rawurldecode($result['url']))->toContain('/actas/'.$this->inspection->id)
        ->and($result['send']->channel)->toBe(ReportChannel::WhatsApp)
        ->and($result['send']->recipient)->toBe('+5511987654321');
});

it('lets the customer open the report with the signed link until it expires', function () {
    $url = app(InspectionReportLink::class)->forCustomer($this->inspection);

    $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');

    $this->travel(app(CheckinSettings::class)->report_link_days + 1)->days();
    $this->get($url)->assertForbidden();
});

it('rejects an unsigned or tampered report link', function () {
    $this->get(route('inspections.report', $this->inspection))->assertForbidden();

    // Alargar la vigencia invalida la firma.
    $url = app(InspectionReportLink::class)->forCustomer($this->inspection);
    $tampered = preg_replace('/expires=(\d+)/', 'expires='.(now()->addYear()->timestamp), $url);
    $this->get($tampered)->assertForbidden();
});

it('serves the staff link only to staff with permission', function () {
    $url = app(InspectionReportLink::class)->forStaff($this->inspection);

    $this->actingAs($this->reception)->get($url)->assertOk();
    $this->actingAs(userWithRole(Role::Upholsterer))->get($url)->assertOk();
    $this->actingAs(userWithRole(Role::Customer))->get($url)->assertForbidden();
});
