<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Último resultado de un chequeo del sistema (Ajustes > Respaldos y salud).
 *
 * @property Carbon $checked_at
 * @property array<string, mixed>|null $details
 */
#[Fillable(['key', 'ok', 'message', 'details', 'checked_at'])]
class HealthCheck extends Model
{
    protected function casts(): array
    {
        return ['ok' => 'boolean', 'details' => 'array', 'checked_at' => 'datetime'];
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function record(string $key, bool $ok, ?string $message = null, array $details = []): self
    {
        return static::query()->updateOrCreate(['key' => $key], [
            'ok' => $ok,
            'message' => $message !== null ? mb_substr($message, 0, 1000) : null,
            'details' => $details,
            'checked_at' => now(),
        ]);
    }

    public static function latestFor(string $key): ?self
    {
        return static::query()->where('key', $key)->first();
    }
}
