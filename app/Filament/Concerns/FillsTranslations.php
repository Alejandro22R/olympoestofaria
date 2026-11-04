<?php

namespace App\Filament\Concerns;

use App\Models\Contracts\Translatable;

/**
 * En páginas de edición, carga todas las traducciones (pt_BR/es) de los atributos traducibles
 * en lugar del valor del idioma activo.
 */
trait FillsTranslations
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if ($record instanceof Translatable) {
            foreach ($record->getTranslatableAttributes() as $attribute) {
                $data[$attribute] = $record->getTranslations($attribute);
            }
        }

        return $data;
    }
}
