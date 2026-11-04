<?php

namespace App\Models\Contracts;

/**
 * Modelos con atributos traducibles (spatie/laravel-translatable, trait HasTranslations).
 */
interface Translatable
{
    /**
     * @return array<int, string>
     */
    public function getTranslatableAttributes(): array;

    /**
     * @param  array<int, string>|null  $allowedLocales
     * @return array<string, mixed>
     */
    public function getTranslations(?string $key = null, ?array $allowedLocales = null): array;
}
