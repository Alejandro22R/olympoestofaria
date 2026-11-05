<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Settings\PricingSettings;
use App\Filament\Clusters\Settings\SettingsPage;
use App\Filament\Support\MoneyInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManagePricingSettings extends SettingsPage
{
    protected static string $settings = PricingSettings::class;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'pricing';

    public static function getSectionGroup(): string
    {
        return 'sales';
    }

    public static function getNavigationLabel(): string
    {
        return __('portal_settings.pricing.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('portal_settings.pricing.estimate'))
                ->description(__('portal_settings.pricing.estimate_help'))
                ->columns(2)
                ->schema([
                    Toggle::make('show_estimate')->label(__('portal_settings.pricing.show_estimate'))->columnSpanFull(),
                    TextInput::make('estimate_margin_percent')->label(__('portal_settings.pricing.margin'))
                        ->integer()->required()->minValue(0)->maxValue(100)->suffix('%'),
                    MoneyInput::make('rounding_cents')->label(__('portal_settings.pricing.rounding'))->required()->minValue(0.01),
                ]),
        ]);
    }
}
