<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use Carbon\CarbonImmutable;
use Database\Factories\WorkOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property WorkOrderStatus $status
 * @property-read CarbonImmutable|null $due_at
 * @property-read CarbonImmutable|null $started_at
 * @property-read CarbonImmutable|null $finished_at
 * @property-read CarbonImmutable|null $delivered_at
 * @property-read CarbonImmutable|null $stage_changed_at
 * @property-read CarbonImmutable|null $materials_consumed_at
 * @property-write \DateTimeInterface|string|null $due_at
 * @property-write \DateTimeInterface|string|null $started_at
 * @property-write \DateTimeInterface|string|null $finished_at
 * @property-write \DateTimeInterface|string|null $delivered_at
 * @property-write \DateTimeInterface|string|null $stage_changed_at
 * @property-write \DateTimeInterface|string|null $materials_consumed_at
 */
#[Fillable(['branch_id', 'vehicle_id', 'appointment_id', 'inspection_id', 'quote_id', 'status', 'workflow_stage_id', 'responsible_id', 'due_at', 'notes'])]
class WorkOrder extends Model
{
    /** @use HasFactory<WorkOrderFactory> */
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'status' => WorkOrderStatus::class,
            'due_at' => UtcDateTime::class,
            'started_at' => UtcDateTime::class,
            'finished_at' => UtcDateTime::class,
            'delivered_at' => UtcDateTime::class,
            'stage_changed_at' => UtcDateTime::class,
            'materials_consumed_at' => UtcDateTime::class,
        ];
    }

    public function number(): string
    {
        return 'OS-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function isLate(): bool
    {
        return $this->status->isOpen() && $this->due_at !== null && $this->due_at->isPast();
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * @return BelongsTo<Inspection, $this>
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * @return BelongsTo<WorkflowStage, $this>
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(WorkflowStage::class, 'workflow_stage_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    /**
     * @return HasMany<WorkOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class);
    }

    /**
     * @return HasMany<WorkOrderUpdate, $this>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(WorkOrderUpdate::class)->latest('id');
    }

    /**
     * @return HasOne<Delivery, $this>
     */
    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    /**
     * @return HasOne<Warranty, $this>
     */
    public function warranty(): HasOne
    {
        return $this->hasOne(Warranty::class);
    }

    public function totalCents(): int
    {
        return (int) $this->items->sum('price_cents');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['status', 'workflow_stage_id', 'responsible_id', 'due_at'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
