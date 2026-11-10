<?php

namespace App\Domain\Tours\Actions;

use App\Domain\Tours\TourCatalog;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Marca un tutorial como visto (o los reinicia todos) para que no se abra solo otra vez.
 */
class CompleteTour
{
    public function execute(User $user, string $key): void
    {
        if (! TourCatalog::exists($key)) {
            throw ValidationException::withMessages(['tour' => __('tours.errors.unknown')]);
        }

        $done = $user->completed_tours ?? [];
        if (! in_array($key, $done, true)) {
            $done[] = $key;
            $user->forceFill(['completed_tours' => array_values($done)])->saveQuietly();
        }
    }

    public function reset(User $user): void
    {
        $user->forceFill(['completed_tours' => []])->saveQuietly();
    }
}
