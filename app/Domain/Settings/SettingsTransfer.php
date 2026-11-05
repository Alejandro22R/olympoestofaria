<?php

namespace App\Domain\Settings;

use Illuminate\Validation\ValidationException;
use Spatie\LaravelSettings\Settings;
use Throwable;

/**
 * Exportar e importar la configuración en JSON (sin secretos), para pasar de pruebas a producción.
 * Al importar solo se aceptan grupos y claves conocidos; cada sección se guarda con su clase
 * tipada, así que la auditoría y la caché funcionan igual que desde el panel.
 */
class SettingsTransfer
{
    public const FORMAT = 'olympo-settings';

    /**
     * @return list<class-string<Settings>>
     */
    public static function classes(): array
    {
        return array_values((array) config('settings.settings'));
    }

    /**
     * @return array{format: string, version: int, exported_at: string, groups: array<string, array<string, mixed>>}
     */
    public function export(): array
    {
        $groups = [];
        foreach (self::classes() as $class) {
            /** @var Settings $settings */
            $settings = app($class);
            $values = $settings->toArray();
            foreach ($class::encrypted() as $secret) {
                unset($values[$secret]);
            }
            $groups[$class::group()] = $values;
        }

        activity('settings')->causedBy(auth()->user())->event('exported')->log('settings.exported');

        return ['format' => self::FORMAT, 'version' => 1, 'exported_at' => now()->toIso8601String(), 'groups' => $groups];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string> grupos importados
     */
    public function import(array $payload): array
    {
        if (($payload['format'] ?? null) !== self::FORMAT || ! is_array($payload['groups'] ?? null)) {
            throw ValidationException::withMessages(['file' => __('system.transfer.invalid')]);
        }

        $byGroup = collect(self::classes())->mapWithKeys(fn (string $class) => [$class::group() => $class]);
        $imported = [];

        foreach ($payload['groups'] as $group => $values) {
            $class = $byGroup->get($group);
            if ($class === null || ! is_array($values)) {
                continue;
            }

            /** @var Settings $settings */
            $settings = app($class);
            $known = array_diff(array_keys($settings->toArray()), $class::encrypted());
            $values = array_intersect_key($values, array_flip($known));
            if ($values === []) {
                continue;
            }

            try {
                $settings->fill($values)->save();
            } catch (Throwable) {
                throw ValidationException::withMessages(['file' => __('system.transfer.invalid_group', ['group' => $group])]);
            }
            $imported[] = (string) $group;
        }

        return $imported;
    }
}
