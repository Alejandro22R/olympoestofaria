<?php

namespace App\Filament\Support;

use Illuminate\Validation\ValidationException;

/**
 * Versión estática de MapsDomainValidation para acciones de tabla en modal:
 * los errores del dominio ("role") se muestran en el campo del modal ("mountedActions.0.data.role").
 */
final class ActionErrors
{
    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function map(callable $callback, string $statePath = 'mountedActions.0.data'): mixed
    {
        try {
            return $callback();
        } catch (ValidationException $e) {
            $messages = [];
            foreach ($e->errors() as $key => $errors) {
                $messages[$statePath.'.'.$key] = $errors;
            }

            throw ValidationException::withMessages($messages);
        }
    }
}
