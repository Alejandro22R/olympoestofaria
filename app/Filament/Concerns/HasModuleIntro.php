<?php

namespace App\Filament\Concerns;

use Illuminate\Contracts\Support\Htmlable;

/**
 * Frase corta bajo el título de cada módulo que explica para qué sirve (lang/{idioma}/help.php).
 */
trait HasModuleIntro
{
    abstract protected static function moduleKey(): string;

    public function getSubheading(): string|Htmlable|null
    {
        return __('help.'.static::moduleKey().'.intro');
    }
}
