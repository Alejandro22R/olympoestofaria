<?php

namespace App\Models;

use App\Models\Contracts\SendableReport;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Acta de entrega (sin firma: se envía al cliente con registro, igual que el check-in).
 *
 * @property array<string, string> $marks_status
 * @property Carbon $delivered_at
 */
#[Fillable(['work_order_id', 'mileage_km', 'marks_status', 'notes', 'delivered_by', 'delivered_at', 'report_disk', 'report_path', 'report_generated_at'])]
class Delivery extends Model implements SendableReport
{
    protected function casts(): array
    {
        return [
            'marks_status' => 'array',
            'delivered_at' => 'datetime',
            'report_generated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WorkOrder, $this>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function deliverer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
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
        return $this->workOrder->vehicle->customer;
    }

    public function reportVehicle(): Vehicle
    {
        return $this->workOrder->vehicle;
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
        return 'delivery';
    }

    public function customerReportUrl(int $days): string
    {
        return URL::temporarySignedRoute('deliveries.report', now()->addDays($days), ['delivery' => $this->id]);
    }
}
