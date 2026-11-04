<?php

namespace App\Filament\Resources\Materials\RelationManagers;

use App\Models\Contracts\Translatable;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('catalog.variant.plural');
    }

    public static function getModelLabel(): string
    {
        return __('catalog.variant.model');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name.pt_BR')->label(__('catalog.fields.name_pt'))->required()->maxLength(80),
                TextInput::make('name.es')->label(__('catalog.fields.name_es'))->maxLength(80),
                ColorPicker::make('color_hex')->label(__('catalog.fields.color_hex'))->hex()->required()
                    ->regex('/^#[0-9A-Fa-f]{6}$/'),
                TextInput::make('sku')->label(__('catalog.fields.sku'))->maxLength(60)->unique(ignoreRecord: true),
                Toggle::make('is_active')->label(__('catalog.fields.is_active'))->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('color_hex')
            ->columns([
                ColorColumn::make('color_hex')->label(__('catalog.fields.color_hex')),
                TextColumn::make('name')->label(__('catalog.fields.name')),
                TextColumn::make('sku')->label(__('catalog.fields.sku'))->placeholder('—'),
                IconColumn::make('is_active')->label(__('catalog.fields.is_active'))->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing(fn (array $data, Model $record) => [
                    ...$data,
                    'name' => $record instanceof Translatable ? $record->getTranslations('name') : $data['name'],
                ]),
                DeleteAction::make(),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
