<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Domain\Shared\Rules\BrazilianPlate;
use App\Domain\Shared\Rules\VehicleYear;
use App\Domain\Vehicles\Validation\UniquePlate;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class VehicleForm
{
    public static function configure(Schema $schema, bool $withCustomer = true): Schema
    {
        return $schema
            ->columns(2)
            ->components(self::fields($withCustomer));
    }

    /**
     * @return list<Component|Field>
     */
    public static function fields(bool $withCustomer = true): array
    {
        return array_values(array_filter([
            $withCustomer ? Select::make('customer_id')->label(__('vehicles.fields.customer'))
                ->relationship('customer', 'name')
                ->searchable()->preload()->required()->columnSpanFull() : null,
            TextInput::make('plate')->label(__('vehicles.fields.plate'))
                ->required()->maxLength(8)
                ->helperText(__('vehicles.fields.plate_help'))
                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                ->formatStateUsing(fn (?Vehicle $record, ?string $state) => $record?->formattedPlate() ?? $state)
                ->rule(new BrazilianPlate)
                ->rule(fn (?Vehicle $record) => new UniquePlate($record)),
            Select::make('vehicle_model_id')->label(__('vehicles.fields.vehicle_model'))
                ->options(fn () => VehicleModel::query()->where('is_active', true)->orderBy('brand')->get()
                    ->mapWithKeys(fn (VehicleModel $m) => [$m->id => $m->label()])->all())
                ->searchable()
                ->live()
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    if ($model = VehicleModel::find($state)) {
                        $set('brand', $model->brand);
                        $set('model', $model->model);
                    }
                }),
            TextInput::make('brand')->label(__('vehicles.fields.brand'))->required()->maxLength(60),
            TextInput::make('model')->label(__('vehicles.fields.model'))->required()->maxLength(60),
            TextInput::make('year')->label(__('vehicles.fields.year'))
                ->integer()->rule(new VehicleYear),
            TextInput::make('color')->label(__('vehicles.fields.color'))->maxLength(40),
            TextInput::make('mileage_km')->label(__('vehicles.fields.mileage_km'))
                ->integer()->minValue(0)->maxValue(5_000_000)->suffix('km'),
            Textarea::make('notes')->label(__('vehicles.fields.notes'))->rows(2)->columnSpanFull(),
        ]));
    }
}
