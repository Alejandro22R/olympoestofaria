<?php

namespace App\Models;

use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentProvider;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Enums\PaymentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Pago (anticipo o saldo). Dinero en centavos. uuid = external_reference e idempotencia.
 *
 * @property PaymentStatus $status
 * @property PaymentType $type
 * @property PaymentMethod $method
 * @property PaymentProvider $provider
 * @property Carbon|null $expires_at
 * @property Carbon|null $paid_at
 */
#[Fillable(['customer_id', 'quote_id', 'work_order_id', 'type', 'method', 'provider', 'mode', 'status', 'amount_cents', 'gateway_id', 'pix_qr_code', 'pix_qr_base64', 'pix_ticket_url', 'expires_at', 'paid_at', 'status_detail', 'recorded_by', 'note'])]
#[Hidden(['pix_qr_base64'])]
class Payment extends Model
{
    use LogsActivity;

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            $payment->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'type' => PaymentType::class,
            'method' => PaymentMethod::class,
            'provider' => PaymentProvider::class,
            'amount_cents' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function isPayableOnline(): bool
    {
        return $this->status === PaymentStatus::Pending
            && $this->provider === PaymentProvider::MercadoPago
            && $this->pix_qr_code !== null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Quote, $this>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
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
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['status', 'amount_cents', 'method', 'provider', 'gateway_id', 'paid_at'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
