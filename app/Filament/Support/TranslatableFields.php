<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * Campos traducibles (spatie/laravel-translatable) editados en pestañas pt_BR / es.
 */
final class TranslatableFields
{
    /**
     * @param  array<string, array{label: string, required?: bool, textarea?: bool}>  $fields
     */
    public static function tabs(array $fields): Tabs
    {
        $tabs = [];
        foreach (['pt_BR' => 'Português', 'es' => 'Español'] as $locale => $label) {
            $components = [];
            foreach ($fields as $name => $config) {
                $component = ($config['textarea'] ?? false)
                    ? Textarea::make("{$name}.{$locale}")->rows(3)
                    : TextInput::make("{$name}.{$locale}")->maxLength(160);

                $components[] = $component
                    ->label($config['label'])
                    ->required(($config['required'] ?? false) && $locale === 'pt_BR');
            }
            $tabs[] = Tab::make($label)->schema($components);
        }

        return Tabs::make('translations')->tabs($tabs)->columnSpanFull();
    }
}
