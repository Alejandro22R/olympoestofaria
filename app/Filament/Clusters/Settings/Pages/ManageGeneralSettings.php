<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Settings\GeneralSettings;
use App\Domain\Shared\Rules\E164Phone;
use App\Filament\Clusters\Settings\SettingsPage;
use App\Http\Middleware\SetLocale;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageGeneralSettings extends SettingsPage
{
    protected static string $settings = GeneralSettings::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'general';

    public static function getSectionGroup(): string
    {
        return 'workshop';
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.sections.general');
    }

    public function form(Schema $schema): Schema
    {
        $locales = collect(SetLocale::SUPPORTED)->mapWithKeys(fn (string $l) => [$l => __('settings.locales.'.$l)])->all();

        return $schema->components([
            Section::make(__('settings.general.identity'))
                ->columns(2)
                ->schema([
                    TextInput::make('business_name')->label(__('settings.general.business_name'))->required()->maxLength(120),
                    TextInput::make('city')->label(__('settings.general.city'))->required()->maxLength(120),
                    TextInput::make('whatsapp')->label(__('settings.general.whatsapp'))
                        ->tel()->placeholder('+5511987654321')
                        ->rule('nullable')->rule(new E164Phone),
                    TextInput::make('email')->label(__('settings.general.email'))->email()->maxLength(160),
                    TextInput::make('maps_url')->label(__('settings.general.maps_url'))->url()->maxLength(500)->columnSpanFull(),
                ]),
            Section::make(__('settings.general.regional'))
                ->columns(2)
                ->schema([
                    Select::make('default_locale')->label(__('settings.general.default_locale'))
                        ->options($locales)->required()->native(false),
                    CheckboxList::make('portal_locales')->label(__('settings.general.portal_locales'))
                        ->options($locales)->required()->minItems(1),
                    Select::make('timezone')->label(__('settings.general.timezone'))
                        ->options(fn () => collect(\DateTimeZone::listIdentifiers(\DateTimeZone::AMERICA))->mapWithKeys(fn ($tz) => [$tz => $tz])->all())
                        ->searchable()->required(),
                    Select::make('currency')->label(__('settings.general.currency'))
                        ->options(['BRL' => 'BRL — Real'])->required()->native(false),
                    Select::make('country')->label(__('settings.general.country'))
                        ->helperText(__('settings.general.country_help'))
                        ->options(['BR' => __('settings.countries.BR')])->required()->native(false),
                ]),
        ]);
    }
}
