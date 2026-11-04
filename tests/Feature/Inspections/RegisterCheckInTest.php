<?php

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Auth\Role;
use App\Domain\Inspections\Actions\RegisterCheckIn;
use App\Domain\Settings\CheckinSettings;
use App\Models\Appointment;
use App\Models\ChecklistItem;
use App\Models\DamageType;
use Database\Seeders\CheckinCatalogSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake('private');
    $this->seed(CheckinCatalogSeeder::class);
    $this->appointment = Appointment::factory()->create();
    $this->user = userWithRole(Role::Reception);
    $this->tear = DamageType::where('slug', 'tear')->first();
});

function checkInData(array $overrides = []): array
{
    return [
        'mileage_km' => 45_000,
        'fuel_level' => 'half',
        'items_present' => ChecklistItem::query()->limit(2)->pluck('id')->all(),
        'observations' => 'Banco com rasgo.',
        'lgpd_consent' => true,
        'marks' => [
            ['zone' => 'driver_seat', 'x' => 35, 'y' => 48, 'damage_type_id' => test()->tear->id, 'note' => 'Rasgo 5 cm', 'photos' => []],
        ],
        ...$overrides,
    ];
}

function checkInErrors(array $data): array
{
    try {
        app(RegisterCheckIn::class)->execute(test()->appointment, $data, test()->user);
    } catch (ValidationException $e) {
        return $e->errors();
    }

    return [];
}

it('registers the check-in, moves the appointment and generates the report', function () {
    $inspection = app(RegisterCheckIn::class)->execute($this->appointment, checkInData(), $this->user);

    expect($inspection->marks)->toHaveCount(1)
        ->and($inspection->lgpd_consent_at)->not->toBeNull()
        ->and($inspection->inspected_by)->toBe($this->user->id)
        ->and($this->appointment->fresh()->status)->toBe(AppointmentStatus::CheckedIn)
        ->and($this->appointment->vehicle->fresh()->mileage_km)->toBe(45_000)
        ->and($inspection->report_path)->not->toBeNull();

    Storage::disk('private')->assertExists($inspection->report_path);
    expect(Storage::disk('private')->get($inspection->report_path))->toStartWith('%PDF');
});

it('places manual marks at the center of their zone', function () {
    $inspection = app(RegisterCheckIn::class)->execute($this->appointment, checkInData([
        'marks' => [['zone' => 'headliner', 'damage_type_id' => $this->tear->id]],
    ]), $this->user);

    expect($inspection->marks->first()->x)->toBe(50.0)
        ->and($inspection->marks->first()->y)->toBe(50.0);
});

it('rejects an incomplete check-in', function (array $overrides, string $field) {
    expect(checkInErrors(checkInData($overrides)))->toHaveKey($field);
})->with([
    'no consent' => [['lgpd_consent' => false], 'lgpd_consent'],
    'no mileage' => [['mileage_km' => null], 'mileage_km'],
    'negative mileage' => [['mileage_km' => -1], 'mileage_km'],
    'no fuel' => [['fuel_level' => null], 'fuel_level'],
    'unknown fuel' => [['fuel_level' => 'overflow'], 'fuel_level'],
    'unknown item' => [['items_present' => [999999]], 'items_present'],
]);

it('requires zone and type on every damage mark', function (array $mark, string $field) {
    expect(checkInErrors(checkInData(['marks' => [$mark]])))->toHaveKey($field);
})->with([
    'no zone' => [['damage_type_id' => 1], 'marks.0.zone'],
    'unknown zone' => [['zone' => 'wing', 'damage_type_id' => 1], 'marks.0.zone'],
    'no type' => [['zone' => 'hood'], 'marks.0.damage_type_id'],
    'out of diagram' => [['zone' => 'hood', 'damage_type_id' => 1, 'x' => 150, 'y' => 10], 'marks.0.zone'],
]);

it('limits photos per mark using the settings', function () {
    $settings = app(CheckinSettings::class);
    $settings->max_photos_per_mark = 1;
    $settings->save();

    Storage::disk('private')->put('inspections/photos/a.jpg', 'x');
    Storage::disk('private')->put('inspections/photos/b.jpg', 'x');

    $errors = checkInErrors(checkInData(['marks' => [
        ['zone' => 'hood', 'damage_type_id' => $this->tear->id, 'photos' => ['inspections/photos/a.jpg', 'inspections/photos/b.jpg']],
    ]]));

    expect($errors)->toHaveKey('marks.0.photos');
});

it('refuses photo paths outside the inspection folder', function () {
    Storage::disk('private')->put('customers/1/documents/secret.bin', 'x');

    $errors = checkInErrors(checkInData(['marks' => [
        ['zone' => 'hood', 'damage_type_id' => $this->tear->id, 'photos' => ['customers/1/documents/secret.bin']],
    ]]));

    expect($errors)->toHaveKey('marks.0.photos');
});

it('makes mileage optional when the owner disables it', function () {
    $settings = app(CheckinSettings::class);
    $settings->mileage_required = false;
    $settings->save();

    $inspection = app(RegisterCheckIn::class)->execute($this->appointment, checkInData(['mileage_km' => null]), $this->user);

    expect($inspection->mileage_km)->toBeNull();
});

it('does not allow two check-ins for the same appointment', function () {
    app(RegisterCheckIn::class)->execute($this->appointment, checkInData(), $this->user);

    expect(checkInErrors(checkInData()))->toHaveKey('appointment');
});

it('does not check in a cancelled appointment', function () {
    $this->appointment->update(['status' => AppointmentStatus::Cancelled]);

    expect(checkInErrors(checkInData()))->toHaveKey('appointment');
});

it('embeds mark photos in the PDF report', function () {
    $photo = UploadedFile::fake()->image('dano.jpg', 400, 300);
    Storage::disk('private')->putFileAs('inspections/photos', $photo, 'dano.jpg');

    $inspection = app(RegisterCheckIn::class)->execute($this->appointment, checkInData(['marks' => [
        ['zone' => 'hood', 'damage_type_id' => $this->tear->id, 'photos' => ['inspections/photos/dano.jpg']],
    ]]), $this->user);

    expect(strlen(Storage::disk('private')->get($inspection->report_path)))->toBeGreaterThan(5_000);
});
