<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Antirrepetición de alertas programadas: cada clave se avisa una sola vez.
 */
#[Fillable(['key', 'type'])]
class AlertLog extends Model
{
    /**
     * Reserva la clave; devuelve false si la alerta ya se envió.
     */
    public static function claim(string $key, string $type): bool
    {
        return static::query()->insertOrIgnore([
            'key' => $key,
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;
    }
}
