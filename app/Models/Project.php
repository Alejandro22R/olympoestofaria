<?php

namespace App\Models;

use App\Domain\Projects\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Proyecto del constructor (lead). La configuración guarda vehículo, piezas, materiales y colores.
 *
 * @property ProjectStatus $status
 * @property array{vehicle: array<string, mixed>, pieces: list<array<string, mixed>>, stitching?: ?string, notes?: ?string} $configuration
 */
#[Fillable(['branch_id', 'customer_id', 'referred_by_customer_id', 'vehicle_id', 'vehicle_model_id', 'status', 'configuration', 'estimate_min_cents', 'estimate_max_cents', 'source', 'notes', 'lost_reason', 'submitted_at'])]
class Project extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'configuration' => 'array',
            'estimate_min_cents' => 'integer',
            'estimate_max_cents' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    public function number(): string
    {
        return 'PRJ-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<VehicleModel, $this>
     */
    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class);
    }

    public function vehicleLabel(): string
    {
        $v = $this->configuration['vehicle'] ?? [];

        return trim(($v['brand'] ?? '').' '.($v['model'] ?? '').' '.($v['year'] ?? ''));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['status', 'estimate_min_cents', 'estimate_max_cents', 'lost_reason'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
