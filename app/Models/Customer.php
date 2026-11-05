<?php

namespace App\Models;

use App\Domain\Customers\Enums\DocumentType;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['branch_id', 'user_id', 'name', 'email', 'phone', 'document_type', 'document_number', 'lgpd_consent_at', 'contact_opt_in_at', 'notification_preferences', 'unsubscribed_at', 'referral_code', 'referred_by_customer_id', 'locale', 'notes'])]
#[Hidden(['document_number', 'document_hash'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, LogsActivity, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'document_number' => 'encrypted',
            'lgpd_consent_at' => 'datetime',
            'contact_opt_in_at' => 'datetime',
            'notification_preferences' => 'array',
            'unsubscribed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Customer $customer): void {
            $customer->referral_code ??= static::newReferralCode();
        });

        static::saving(function (Customer $customer): void {
            if ($customer->isDirty('document_number')) {
                $customer->document_hash = static::hashDocument($customer->document_number);
            }
        });
    }

    public static function newReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (static::query()->where('referral_code', $code)->exists());

        return $code;
    }

    public static function hashDocument(?string $number): ?string
    {
        $digits = preg_replace('/[^0-9A-Za-z]/', '', (string) $number);

        return $digits === '' || $digits === null ? null : hash_hmac('sha256', strtoupper($digits), (string) config('app.key'));
    }

    /**
     * Documento enmascarado para mostrar en pantalla (***.***.247-25).
     */
    public function maskedDocument(): ?string
    {
        $number = $this->document_number;
        if (blank($number)) {
            return null;
        }

        $digits = (string) preg_replace('/\D/', '', $number);

        return str_repeat('•', max(0, strlen($digits) - 4)).substr($digits, -4);
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * @return HasOne<CustomerDocument, $this>
     */
    public function document(): HasOne
    {
        return $this->hasOne(CustomerDocument::class);
    }

    /**
     * @return HasManyThrough<Appointment, Vehicle, $this>
     */
    public function appointments(): HasManyThrough
    {
        return $this->hasManyThrough(Appointment::class, Vehicle::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function hasLgpdConsent(): bool
    {
        return $this->lgpd_consent_at !== null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        // El número de documento nunca va al registro de auditoría; solo el hecho de que cambió.
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'document_type', 'document_hash', 'lgpd_consent_at', 'locale'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
