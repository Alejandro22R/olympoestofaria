<?php

namespace App\Models;

use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Quotes\Enums\QuoteStatus;
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
 * Cotización final. Una cotización enviada no se edita: se revisa creando una versión nueva.
 *
 * @property QuoteStatus $status
 * @property Carbon|null $valid_until
 * @property Carbon|null $sent_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $deposit_paid_at
 */
#[Fillable(['branch_id', 'customer_id', 'project_id', 'vehicle_id', 'appointment_id', 'previous_quote_id', 'version', 'status', 'vehicle_label', 'subtotal_cents', 'discount_cents', 'total_cents', 'deposit_cents', 'valid_until', 'notes', 'created_by'])]
class Quote extends Model implements SendableReport
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'version' => 'integer',
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'total_cents' => 'integer',
            'deposit_cents' => 'integer',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'deposit_paid_at' => 'datetime',
        ];
    }

    public function number(): string
    {
        return 'ORC-'.str_pad((string) ($this->rootId()), 5, '0', STR_PAD_LEFT).($this->version > 1 ? ' v'.$this->version : '');
    }

    /** Id de la primera versión (todas las versiones comparten número). */
    public function rootId(): int
    {
        $quote = $this;
        for ($i = 0; $i < 50 && $quote->previousVersion !== null; $i++) {
            $quote = $quote->previousVersion;
        }

        return (int) $quote->id;
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->endOfDay()->isPast();
    }

    public function paidCents(): int
    {
        return (int) $this->payments()->where('status', PaymentStatus::Approved)->sum('amount_cents');
    }

    public function outstandingCents(): int
    {
        return max(0, $this->total_cents - $this->paidCents());
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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
     * @return BelongsTo<Quote, $this>
     */
    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'previous_quote_id');
    }

    /**
     * @return HasMany<QuoteItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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
        return $this->customer;
    }

    public function reportVehicle(): Vehicle
    {
        // Para el texto del mensaje: si el lead aún no tiene vehículo registrado, se usa uno en memoria.
        return $this->vehicle ?? new Vehicle(['plate' => '', 'brand' => (string) $this->vehicle_label, 'model' => '']);
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
        return 'quote';
    }

    public function customerReportUrl(int $days): string
    {
        return URL::temporarySignedRoute('portal.quote', now()->addDays(max($days, 1)), ['quote' => $this->id]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['status', 'total_cents', 'discount_cents', 'deposit_cents', 'valid_until'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
