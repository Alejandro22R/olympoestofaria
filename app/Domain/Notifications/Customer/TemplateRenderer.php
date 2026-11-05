<?php

namespace App\Domain\Notifications\Customer;

use App\Models\MessageTemplate;

/**
 * Busca la plantilla (evento + canal + idioma, con respaldo a pt_BR) y reemplaza {{variables}}.
 */
class TemplateRenderer
{
    public function find(MessageEvent $event, string $channel, string $locale): ?MessageTemplate
    {
        return MessageTemplate::query()
            ->where('event', $event->value)->where('channel', $channel)->where('is_active', true)
            ->whereIn('locale', array_unique([$locale, 'pt_BR']))
            ->orderByRaw('CASE WHEN locale = ? THEN 0 ELSE 1 END', [$locale])
            ->first();
    }

    /**
     * @param  array<string, string|int|float|null>  $vars
     */
    public static function render(string $text, array $vars): string
    {
        return (string) preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/', fn (array $m) => (string) ($vars[$m[1]] ?? ''), $text);
    }

    /**
     * Variables usadas en un texto que el evento no ofrece (para validar al editar).
     *
     * @return list<string>
     */
    public static function unknownVariables(string $text, MessageEvent $event): array
    {
        preg_match_all('/\{\{\s*([a-z_]+)\s*\}\}/', $text, $m);

        return array_values(array_diff(array_unique($m[1]), $event->variables()));
    }
}
