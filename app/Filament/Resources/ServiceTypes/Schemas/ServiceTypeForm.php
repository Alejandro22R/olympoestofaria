<?php

namespace App\Filament\Resources\ServiceTypes\Schemas;

use App\Domain\Catalog\Enums\PricingUnit;
use App\Filament\Support\MoneyInput;
use App\Filament\Support\TranslatableFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(3)
                    ->schema([
                        TranslatableFields::tabs([
                            'name' => ['label' => __('catalog.fields.name'), 'required' => true],
                            'description' => ['label' => __('catalog.fields.description'), 'textarea' => true],
                        ]),
                        TextInput::make('slug')->label(__('catalog.fields.slug'))
                            ->required()->maxLength(60)->alphaDash()->unique(ignoreRecord: true),
                        MoneyInput::make('base_price_cents')->label(__('catalog.fields.base_price'))->required(),
                        Select::make('pricing_unit')->label(__('catalog.fields.pricing_unit'))
                            ->options(PricingUnit::class)->required()->native(false),
                        TextInput::make('estimated_hours')->label(__('catalog.fields.estimated_hours'))
                            ->numeric()->minValue(0)->maxValue(999),
                        TextInput::make('material_m2')->label(__('catalog.fields.material_m2'))
                            ->numeric()->minValue(0)->maxValue(999),
                        TextInput::make('sort')->label(__('catalog.fields.sort'))->integer()->default(0),
                        Toggle::make('is_active')->label(__('catalog.fields.is_active'))->default(true),
                    ]),
            ]);
    }
}
