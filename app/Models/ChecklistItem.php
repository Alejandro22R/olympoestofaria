<?php

namespace App\Models;

use App\Models\Contracts\Translatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Objeto que puede quedar dentro del vehículo (estéreo, gato, llave de rueda…).
 */
#[Fillable(['slug', 'name', 'sort', 'is_active'])]
class ChecklistItem extends Model implements Translatable
{
    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort' => 'integer'];
    }
}
