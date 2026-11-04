<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Sucursal. Hoy hay un solo taller, pero el esquema está preparado para varias.
 */
#[Fillable(['name', 'city', 'address', 'is_active'])]
class Branch extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function default(): ?self
    {
        return static::query()->where('is_active', true)->orderBy('id')->first();
    }
}
