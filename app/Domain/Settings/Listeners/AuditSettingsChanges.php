<?php

namespace App\Domain\Settings\Listeners;

use App\Domain\Settings\Events\SettingsUpdated;
use Spatie\LaravelSettings\Events\SavingSettings;

/**
 * Registra en el activity log cada cambio de ajustes: quién, cuándo,
 * valor anterior y valor nuevo. Los secretos (propiedades cifradas) se enmascaran.
 */
class AuditSettingsChanges
{
    public const MASK = '••••••';

    public function handle(SavingSettings $event): void
    {
        $settings = $event->settings;
        $encrypted = $settings::encrypted();
        $original = $event->originalValues?->all() ?? [];

        $old = [];
        $new = [];

        foreach ($event->properties->all() as $key => $value) {
            $before = $original[$key] ?? null;

            if ($this->normalize($before) === $this->normalize($value)) {
                continue;
            }

            $isSecret = in_array($key, $encrypted, true);
            $old[$key] = $isSecret ? self::MASK : $before;
            $new[$key] = $isSecret ? self::MASK : $value;
        }

        if ($new === []) {
            return;
        }

        activity('settings')
            ->causedBy(auth()->user())
            ->event('updated')
            ->withProperties([
                'group' => $settings::group(),
                'old' => $old,
                'attributes' => $new,
            ])
            ->log('settings.updated');

        SettingsUpdated::dispatch($settings::group(), array_keys($new));
    }

    private function normalize(mixed $value): string
    {
        return (string) json_encode($value);
    }
}
