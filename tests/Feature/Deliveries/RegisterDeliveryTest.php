<?php

use App\Domain\Auth\Role;
use App\Domain\Deliveries\Actions\RegisterDelivery;
use App\Domain\Inspections\Actions\RegisterCheckIn;
use App\Domain\Inspections\Enums\ReportChannel;
use App\Domain\Reports\SendReport;
use App\Domain\Settings\NotificationSettings;
use App\Domain\Settings\WarrantySettings;
use App\Domain\WorkOrders\Actions\CreateWorkOrder;
use App\Domain\WorkOrders\Actions\MoveWorkOrder;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\DamageType;
use App\Models\ReportSend;
use App\Models\ServiceType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake('private');
    seedProduction();
    $this->user = userWithRole(Role::Reception);
    $appointment = Appointment::factory()->create();
    $this->inspection = app(RegisterCheckIn::class)->execute($appointment, [
        'mileage_km' => 1000, 'fuel_level' => 'half', 'lgpd_consent' => true,
        'marks' => [
            ['zone' => 'driver_seat', 'damage_type_id' => DamageType::first()->id],
            ['zone' => 'hood', 'damage_type_id' => DamageType::first()->id],
        ],
    ], $this->user);

    $this->order = app(CreateWorkOrder::class)->execute([], $this->inspection, $this->user);
    $this->order->items()->create(['service_type_id' => ServiceType::where('slug', 'seats')->value('id'), 'price_cents' => 280000]);
    $this->toReady = function (): void {
        app(MoveWorkOrder::class)->execute($this->order->fresh(), stage('Costura'), $this->user);
        app(MoveWorkOrder::class)->execute($this->order->fresh(), stage('Pronto para entrega'), $this->user);
    };
    $this->outcomes = fn (string $value = 'repaired') => $this->inspection->marks->mapWithKeys(fn ($m) => [$m->id => $value])->all();
});

it('creates the order from the check-in with vehicle, appointment and initial stage', function () {
    expect($this->order->vehicle_id)->toBe($this->inspection->vehicle_id)
        ->and($this->order->appointment_id)->toBe($this->inspection->appointment_id)
        ->and($this->order->status)->toBe(WorkOrderStatus::Received)
        ->and($this->order->stage->getTranslation('name', 'pt_BR'))->toBe('Recepção');
});

it('does not open two orders for the same check-in', function () {
    app(CreateWorkOrder::class)->execute([], $this->inspection, $this->user);
})->throws(ValidationException::class);

it('delivers a ready order: compares marks, creates the warranty and the report', function () {
    ($this->toReady)();

    $delivery = app(RegisterDelivery::class)->execute($this->order->fresh(), [
        'mileage_km' => 1010,
        'marks_status' => ($this->outcomes)(),
        'notes' => 'Cliente satisfeito',
    ], $this->user);

    $order = $this->order->fresh();
    expect($order->status)->toBe(WorkOrderStatus::Delivered)
        ->and($order->delivered_at)->not->toBeNull()
        ->and($delivery->marks_status)->toHaveCount(2)
        ->and($order->warranty->ends_at->diffInMonths($order->warranty->starts_at, true))->toEqual(12) // bancos = 12 meses
        ->and($order->vehicle->fresh()->mileage_km)->toBe(1010);

    Storage::disk('private')->assertExists($delivery->report_path);
    expect(Storage::disk('private')->get($delivery->report_path))->toStartWith('%PDF');
});

it('uses the default warranty when no service has its own term', function () {
    $this->order->items()->delete();
    $settings = app(WarrantySettings::class);
    $settings->default_months = 2;
    $settings->save();

    expect(app(RegisterDelivery::class)->warrantyMonths($this->order->fresh()))->toBe(2);
});

it('cannot deliver an order that is not ready', function () {
    app(RegisterDelivery::class)->execute($this->order->fresh(), ['marks_status' => ($this->outcomes)()], $this->user);
})->throws(ValidationException::class);

it('requires the final state of every check-in mark', function () {
    ($this->toReady)();
    $outcomes = ($this->outcomes)();
    array_pop($outcomes);

    app(RegisterDelivery::class)->execute($this->order->fresh(), ['marks_status' => $outcomes], $this->user);
})->throws(ValidationException::class);

it('sends the delivery report by WhatsApp with a signed link and logs it', function () {
    // Solo el envío manual (el automático tiene su propia prueba).
    $settings = app(NotificationSettings::class);
    $settings->auto_send_delivery_report = false;
    $settings->save();

    ($this->toReady)();
    $delivery = app(RegisterDelivery::class)->execute($this->order->fresh(), ['marks_status' => ($this->outcomes)()], $this->user);

    $result = app(SendReport::class)->execute($delivery, ReportChannel::WhatsApp, $this->user);

    expect(rawurldecode($result['url']))->toContain('/entregas/'.$delivery->id)
        ->and(ReportSend::query()->where('sendable_type', $delivery->getMorphClass())->count())->toBe(1);

    preg_match('#https?://[^\s]+/entregas/[^\s]+#', rawurldecode($result['url']), $m);
    $this->get($m[0])->assertOk()->assertHeader('Content-Type', 'application/pdf');
});
