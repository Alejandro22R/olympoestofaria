<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use App\Domain\Appointments\Enums\AppointmentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property CarbonImmutable $scheduled_at
 * @property CarbonImmutable|null $promised_delivery_at
 * @property AppointmentStatus $status
 */
#[Fillable(['branch_id', 'vehicle_id', 'scheduled_at', 'promised_delivery_at', 'status', 'notes', 'created_by'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'scheduled_at' => UtcDateTime::class,
            'promised_delivery_at' => UtcDateTime::class,
            'status' => AppointmentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasOne<Inspection, $this>
     */
    public function inspection(): HasOne
    {
        return $this->hasOne(Inspection::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
