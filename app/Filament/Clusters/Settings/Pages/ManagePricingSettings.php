<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Auth\Role;
use App\Domain\Settings\PricingSettings;
use App\Filament\Clusters\Settings\SettingsPage;
use App\Filament\Support\MoneyInput;
use Filament\Forms\Components\CheckboxList;
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['final_price_roles'] = array_values($data['final_price_roles'] ?? []);

        return $data;
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
            Section::make(__('portal_settings.pricing.quotes'))
                ->columns(2)
                ->schema([
                    TextInput::make('quote_validity_days')->label(__('portal_settings.pricing.validity'))->integer()->required()->minValue(1)->maxValue(90)->suffix('d'),
                    TextInput::make('max_discount_percent')->label(__('portal_settings.pricing.max_discount'))->integer()->required()->minValue(0)->maxValue(100)->suffix('%'),
                    CheckboxList::make('final_price_roles')->label(__('portal_settings.pricing.price_roles'))
                        ->options(collect([Role::Owner, Role::Reception, Role::Upholsterer])->mapWithKeys(fn (Role $r) => [$r->value => $r->label()])->all())
                        ->required()->columns(3),
                    Toggle::make('require_contact_before_quote')->label(__('portal_settings.pricing.contact_first')),
                ]),
        ]);
    }
}
