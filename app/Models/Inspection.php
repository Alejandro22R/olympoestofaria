<?php

namespace App\Models;

use App\Domain\Inspections\Enums\FuelLevel;
use App\Models\Contracts\SendableReport;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Inspección de recepción (check-in). No lleva firma: guarda consentimiento LGPD
 * y el acta en PDF se envía al cliente con registro de envío.
 *
 * @property FuelLevel $fuel_level
 * @property list<int> $items_present
 * @property Carbon $lgpd_consent_at
 */
#[Fillable(['appointment_id', 'vehicle_id', 'mileage_km', 'fuel_level', 'items_present', 'observations', 'silhouette', 'lgpd_consent_at', 'inspected_by', 'report_disk', 'report_path', 'report_generated_at'])]
class Inspection extends Model implements SendableReport
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'mileage_km' => 'integer',
            'fuel_level' => FuelLevel::class,
            'items_present' => 'array',
            'lgpd_consent_at' => 'datetime',
            'report_generated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    /**
     * @return HasMany<InspectionMark, $this>
     */
    public function marks(): HasMany
    {
        return $this->hasMany(InspectionMark::class);
    }

    /**
     * @return MorphMany<ReportSend, $this>
     */
    public function sends(): MorphMany
    {
        return $this->morphMany(ReportSend::class, 'sendable')->latest('sent_at');
    }

    public function recordSend(array $attributes): ReportSend
    {
        return $this->sends()->create($attributes);
    }

    public function reportCustomer(): Customer
    {
        return $this->vehicle->customer;
    }

    public function reportVehicle(): Vehicle
    {
        return $this->vehicle;
    }

    public function reportDisk(): ?string
    {
        return $this->report_disk;
    }

    public function reportPath(): ?string
    {
        return $this->report_path;
    }

    public function reportKind(): string
    {
        return 'inspection';
    }

    public function customerReportUrl(int $days): string
    {
        return URL::temporarySignedRoute('inspections.report', now()->addDays($days), ['inspection' => $this->id]);
    }

    public function hasReport(): bool
    {
        return $this->report_path !== null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['mileage_km', 'fuel_level', 'observations', 'report_generated_at'])->logOnlyDirty();
    }
}
