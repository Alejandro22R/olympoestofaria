<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Settings\PortalSettings;
use App\Filament\Clusters\Settings\SettingsPage;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManagePortalSettings extends SettingsPage
{
    protected static string $settings = PortalSettings::class;

    protected static ?int $navigationSort = 22;

    protected static ?string $slug = 'portal';

    public static function getSectionGroup(): string
    {
        return 'sales';
    }

    public static function getNavigationLabel(): string
    {
        return __('portal_settings.portal.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('portal_settings.portal.landing'))->columns(2)->schema([
                TextInput::make('headline.pt_BR')->label(__('portal_settings.portal.headline').' (PT)')->required()->maxLength(120),
                TextInput::make('headline.es')->label(__('portal_settings.portal.headline').' (ES)')->required()->maxLength(120),
                Textarea::make('subheadline.pt_BR')->label(__('portal_settings.portal.subheadline').' (PT)')->required()->rows(2)->maxLength(300),
                Textarea::make('subheadline.es')->label(__('portal_settings.portal.subheadline').' (ES)')->required()->rows(2)->maxLength(300),
                CheckboxList::make('sections')->label(__('portal_settings.portal.sections'))
                    ->options(collect(PortalSettings::SECTIONS)->mapWithKeys(fn (string $s) => [$s => __('portal_settings.sections.'.$s)])->all())
                    ->columns(4)->columnSpanFull(),
                Toggle::make('show_prices')->label(__('portal_settings.portal.show_prices')),
                Toggle::make('show_out_of_stock')->label(__('portal_settings.portal.show_out_of_stock')),
            ]),
            Section::make(__('portal_settings.portal.social'))->columns(3)->schema([
                TextInput::make('counter_cars')->label(__('portal.home.counters.cars'))->integer()->required()->minValue(0),
                TextInput::make('counter_years')->label(__('portal.home.counters.years'))->integer()->required()->minValue(0),
                TextInput::make('counter_followers')->label(__('portal.home.counters.followers'))->integer()->required()->minValue(0),
                TextInput::make('instagram_url')->label('Instagram')->url()->maxLength(255)->columnSpanFull(),
            ]),
            Section::make('SEO')->columns(2)->schema([
                TextInput::make('seo_title.pt_BR')->label(__('portal_settings.portal.seo_title').' (PT)')->required()->maxLength(70),
                TextInput::make('seo_title.es')->label(__('portal_settings.portal.seo_title').' (ES)')->required()->maxLength(70),
                Textarea::make('seo_description.pt_BR')->label(__('portal_settings.portal.seo_description').' (PT)')->required()->rows(2)->maxLength(160),
                Textarea::make('seo_description.es')->label(__('portal_settings.portal.seo_description').' (ES)')->required()->rows(2)->maxLength(160),
            ]),
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['sections'] = array_values($data['sections'] ?? []);

        return $data;
    }
}
