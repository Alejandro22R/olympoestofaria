<?php

namespace App\Filament\Resources\VehicleSilhouettes;

use App\Domain\Inspections\Support\VehicleSilhouettes;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\VehicleSilhouettes\Pages\ManageVehicleSilhouettes;
use App\Models\VehicleSilhouette;
use BackedEnum;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Ajustes > Check-in > Siluetas por carrocería. Las zonas se editan como JSON
 * {"zona": {"x":..,"y":..,"w":..,"h":..,"rx":..}} en coordenadas del viewBox.
 */
class VehicleSilhouetteResource extends Resource
{
    protected static ?string $model = VehicleSilhouette::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 7;

    public static function getModelLabel(): string
    {
        return __('inspections.settings.silhouette');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inspections.settings.silhouettes');
    }

    public static function getNavigationLabel(): string
    {
        return __('inspections.settings.silhouettes');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('settings.groups.workshop');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('body_type')->label(__('inspections.settings.body_type'))
                ->helperText(__('inspections.settings.body_type_help'))
                ->required()->alphaDash()->maxLength(32)->unique(ignoreRecord: true),
            TextInput::make('name.pt_BR')->label(__('catalog.fields.name_pt'))->required()->maxLength(60),
            TextInput::make('name.es')->label(__('catalog.fields.name_es'))->maxLength(60),
            TextInput::make('view_box')->label('viewBox')->required()->default(VehicleSilhouettes::VIEWBOX)
                ->regex('/^0 0 \d{2,4} \d{2,4}$/'),
            Textarea::make('outline')->label(__('inspections.settings.outline'))->required()->rows(3)
                ->regex('/^[MLHVCSQTAZmlhvcsqtaz0-9\s.,-]+$/')->columnSpanFull(),
            Textarea::make('zones')->label(__('inspections.settings.zones'))->required()->rows(12)
                ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                ->dehydrateStateUsing(fn (?string $state) => json_decode((string) $state, true))
                ->rule(fn () => function (string $attribute, mixed $value, Closure $fail): void {
                    $zones = json_decode((string) $value, true);
                    if (! is_array($zones) || $zones === []) {
                        $fail(__('inspections.settings.zones_invalid'));

                        return;
                    }
                    foreach ($zones as $key => $zone) {
                        if (! is_string($key) || ! preg_match('/^[a-z_]+$/', $key) || ! is_array($zone)) {
                            $fail(__('inspections.settings.zones_invalid'));

                            return;
                        }
                        foreach (['x', 'y', 'w', 'h'] as $k) {
                            if (! isset($zone[$k]) || ! is_numeric($zone[$k]) || $zone[$k] < 0) {
                                $fail(__('inspections.settings.zones_invalid'));

                                return;
                            }
                        }
                    }
                })
                ->columnSpanFull(),
            Toggle::make('is_active')->label(__('catalog.fields.is_active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('body_type')->label(__('inspections.settings.body_type')),
                TextColumn::make('name')->label(__('catalog.fields.name')),
                TextColumn::make('zones')->label(__('inspections.settings.zones'))->state(fn (VehicleSilhouette $record) => count($record->zones)),
                IconColumn::make('is_active')->label(__('catalog.fields.is_active'))->boolean(),
            ])
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing(fn (array $data, VehicleSilhouette $record) => [...$data, 'name' => $record->getTranslations('name')])
                    ->after(fn () => VehicleSilhouettes::flush()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageVehicleSilhouettes::route('/')];
    }
}
