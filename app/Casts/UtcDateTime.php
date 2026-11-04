<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Instantes guardados siempre en UTC. Eloquent por defecto formatea un Carbon
 * sin convertir su zona horaria, así que una hora de São Paulo se guardaría como si fuera UTC.
 *
 * @implements CastsAttributes<CarbonImmutable, DateTimeInterface|string>
 */
class UtcDateTime implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse((string) $value, 'UTC');
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $moment = $value instanceof DateTimeInterface
            ? CarbonImmutable::instance($value)
            : CarbonImmutable::parse((string) $value, (string) config('app.timezone'));

        return $moment->utc()->format('Y-m-d H:i:s');
    }
}
