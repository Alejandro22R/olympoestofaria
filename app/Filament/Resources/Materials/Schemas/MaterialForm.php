<?php

namespace App\Filament\Resources\Materials\Schemas;

use App\Domain\Catalog\Enums\MaterialType;
use App\Filament\Support\MoneyInput;
use App\Filament\Support\TranslatableFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaterialForm
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
                        Select::make('type')->label(__('catalog.fields.type'))
                            ->options(MaterialType::class)->required()->native(false),
                        TextInput::make('supplier')->label(__('catalog.fields.supplier'))->maxLength(120),
                        TextInput::make('origin')->label(__('catalog.fields.origin'))->maxLength(60),
                        MoneyInput::make('cost_per_m2_cents')->label(__('catalog.fields.cost_per_m2'))->required(),
                        MoneyInput::make('price_per_m2_cents')->label(__('catalog.fields.price_per_m2'))->required()
                            ->gte('cost_per_m2_cents'),
                        Toggle::make('is_active')->label(__('catalog.fields.is_active'))->default(true),
                    ]),
            ]);
    }
}
