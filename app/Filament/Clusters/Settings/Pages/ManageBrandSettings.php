<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Settings\BrandSettings;
use App\Filament\Clusters\Settings\SettingsPage;
use App\Support\Brand;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Ajustes > Sistema > Marca y apariencia: logos, favicon, color principal (con control de
 * contraste AA), tema por defecto del panel y animaciones reducidas en el portal.
 * Las tipografías son fijas (Sora y Manrope, autoalojadas).
 */
class ManageBrandSettings extends SettingsPage
{
    protected static string $settings = BrandSettings::class;

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'brand';

    public static function getSectionGroup(): string
    {
        return 'system';
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.brand.title');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['primary_color'] = strtoupper((string) $data['primary_color']);

        return $data;
    }

    public function save(): void
    {
        parent::save();
        // El color y el logo del panel se leen al iniciar la petición: recargar para verlos.
        $this->redirect(static::getUrl());
    }

    public function form(Schema $schema): Schema
    {
        $upload = fn (string $name, string $label) => FileUpload::make($name)->label($label)
            ->disk('public')->directory('brand')->visibility('public')
            ->image()->acceptedFileTypes(['image/svg+xml', 'image/png', 'image/webp'])->maxSize(1024);

        return $schema->components([
            Section::make(__('settings.brand.logos'))->columns(3)->schema([
                $upload('logo_dark_path', __('settings.brand.logo_dark'))->helperText(__('settings.brand.logo_dark_help')),
                $upload('logo_light_path', __('settings.brand.logo_light'))->helperText(__('settings.brand.logo_light_help')),
                $upload('favicon_path', __('settings.brand.favicon'))->acceptedFileTypes(['image/svg+xml', 'image/png'])->maxSize(256),
            ]),
            Section::make(__('settings.brand.appearance'))->columns(2)->schema([
                ColorPicker::make('primary_color')->label(__('settings.brand.primary_color'))
                    ->hex()->required()->regex('/^#[0-9A-Fa-f]{6}$/')
                    ->helperText(__('settings.brand.primary_color_help', ['default' => Brand::BRAND_500]))
                    ->rule(fn () => function (string $attribute, mixed $value, \Closure $fail): void {
                        if (is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value)
                            && Brand::contrastWithWhite(Brand::shades($value)[700]) < 4.5) {
                            $fail(__('settings.brand.low_contrast'));
                        }
                    }),
                Radio::make('default_theme')->label(__('settings.brand.default_theme'))
                    ->options(['dark' => __('settings.brand.themes.dark'), 'light' => __('settings.brand.themes.light')])
                    ->inline()->required(),
                Toggle::make('reduce_motion')->label(__('settings.brand.reduce_motion'))
                    ->helperText(__('settings.brand.reduce_motion_help'))->columnSpanFull(),
            ]),
        ]);
    }
}
