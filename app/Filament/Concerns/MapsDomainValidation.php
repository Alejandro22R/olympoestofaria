<?php

namespace App\Filament\Concerns;

use Illuminate\Validation\ValidationException;

/**
 * Las Actions de dominio lanzan ValidationException con claves "planas" (scheduled_at);
 * los formularios de Filament esperan "data.scheduled_at". Este helper las traduce.
 */
trait MapsDomainValidation
{
    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    protected function withFormErrors(callable $callback, string $statePath = 'data'): mixed
    {
        try {
            return $callback();
        } catch (ValidationException $e) {
            $messages = [];
            foreach ($e->errors() as $key => $errors) {
                $messages[str_starts_with($key, $statePath.'.') ? $key : $statePath.'.'.$key] = $errors;
            }

            throw ValidationException::withMessages($messages);
        }
    }
}
