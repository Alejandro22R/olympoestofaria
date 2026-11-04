<?php

namespace App\Models;

use App\Domain\Catalog\Enums\PricingUnit;
use App\Models\Contracts\Translatable;
use Database\Factories\ServiceTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

#[Fillable(['slug', 'name', 'description', 'base_price_cents', 'pricing_unit', 'estimated_hours', 'material_m2', 'sort', 'is_active'])]
class ServiceType extends Model implements Translatable
{
    /** @use HasFactory<ServiceTypeFactory> */
    use HasFactory, HasTranslations, LogsActivity;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'base_price_cents' => 'integer',
            'pricing_unit' => PricingUnit::class,
            'estimated_hours' => 'decimal:2',
            'material_m2' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
