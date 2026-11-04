<?php

namespace App\Filament\Resources\WarrantyTerms;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\WarrantyTerms\Pages\ManageWarrantyTerms;
use App\Models\ServiceType;
use App\Models\WarrantyTerm;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Ajustes > Garantías > Plazo por tipo de servicio.
 */
class WarrantyTermResource extends Resource
{
    protected static ?string $model = WarrantyTerm::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 13;

    public static function getModelLabel(): string
    {
        return __('deliveries.settings.term');
    }

    public static function getPluralModelLabel(): string
    {
        return __('deliveries.settings.terms');
    }

    public static function getNavigationLabel(): string
    {
        return __('deliveries.settings.terms');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('settings.groups.workshop');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('service_type_id')->label(__('work_orders.fields.service'))
                ->options(fn () => ServiceType::query()->orderBy('sort')->get()->mapWithKeys(fn (ServiceType $s) => [$s->id => $s->name])->all())
                ->required()->unique(ignoreRecord: true),
            TextInput::make('months')->label(__('deliveries.settings.months'))->integer()->required()->minValue(0)->maxValue(120)->suffix(__('deliveries.settings.months_suffix')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('serviceType'))
            ->columns([
                TextColumn::make('serviceType.name')->label(__('work_orders.fields.service')),
                TextColumn::make('months')->label(__('deliveries.settings.months'))->suffix(' '.__('deliveries.settings.months_suffix')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageWarrantyTerms::route('/')];
    }
}
