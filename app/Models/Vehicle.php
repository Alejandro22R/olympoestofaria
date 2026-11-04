<?php

namespace App\Models;

use App\Domain\Shared\Rules\BrazilianPlate;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['customer_id', 'vehicle_model_id', 'plate', 'brand', 'model', 'year', 'color', 'mileage_km', 'notes'])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'mileage_km' => 'integer',
        ];
    }

    /**
     * La placa se guarda normalizada (sin guion, en mayúsculas).
     *
     * @return Attribute<string, string>
     */
    protected function plate(): Attribute
    {
        return Attribute::make(set: fn (string $value) => BrazilianPlate::normalize($value));
    }

    public function formattedPlate(): string
    {
        // Formato antiguo con guion (ABC-1234); Mercosul sin guion (ABC1D23).
        return preg_match('/^[A-Z]{3}\d{4}$/', $this->plate)
            ? substr($this->plate, 0, 3).'-'.substr($this->plate, 3)
            : $this->plate;
    }

    public function label(): string
    {
        return trim("{$this->formattedPlate()} · {$this->brand} {$this->model}".($this->year ? " {$this->year}" : ''));
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

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
