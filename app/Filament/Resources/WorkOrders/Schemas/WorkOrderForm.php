<?php

namespace App\Filament\Resources\WorkOrders\Schemas;

use App\Domain\Auth\Role;
use App\Filament\Support\MoneyInput;
use App\Models\InventoryItem;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class WorkOrderForm
{
    /**
     * El material ya se descontó del inventario: las cantidades quedan bloqueadas.
     */
    public static function materialsConsumed(mixed $livewire): bool
    {
        $record = is_object($livewire) && method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;

        return $record instanceof WorkOrder && $record->materials_consumed_at !== null;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(['default' => 1, 'md' => 3])
                ->schema([
                    Select::make('vehicle_id')->label(__('work_orders.fields.vehicle'))
                        ->relationship('vehicle', 'plate', fn ($query) => $query->with('customer'))
                        ->getOptionLabelFromRecordUsing(fn (Vehicle $record) => $record->label().' — '.$record->customer->name)
                        ->searchable(['plate', 'brand', 'model'])
                        ->preload()
                        ->required()
                        ->disabledOn('edit')
                        ->columnSpan(['md' => 2]),
                    Placeholder::make('stage_info')->label(__('work_orders.fields.stage'))
                        ->content(fn (?WorkOrder $record) => $record ? $record->stage?->name.' · '.$record->status->getLabel() : '—')
                        ->visibleOn('edit'),
                    Select::make('responsible_id')->label(__('work_orders.fields.responsible'))
                        ->options(fn () => User::role([Role::Upholsterer->value, Role::Owner->value, Role::Reception->value])
                            ->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable(),
                    DateTimePicker::make('due_at')->label(__('work_orders.fields.due_at'))->seconds(false),
                    Textarea::make('notes')->label(__('work_orders.fields.notes'))->rows(2)->columnSpanFull(),
                ]),
            Section::make(__('work_orders.sections.items'))
                ->description(fn (?WorkOrder $record) => $record?->materials_consumed_at ? __('work_orders.messages.materials_locked') : __('work_orders.messages.materials_hint'))
                ->schema([
                    Repeater::make('items')->hiddenLabel()
                        ->relationship('items')
                        ->addActionLabel(__('work_orders.actions.add_item'))
                        ->defaultItems(0)
                        ->columns(['default' => 1, 'md' => 6])
                        ->schema([
                            Select::make('service_type_id')->label(__('work_orders.fields.service'))
                                ->options(fn () => ServiceType::query()->where('is_active', true)->orderBy('sort')->get()
                                    ->mapWithKeys(fn (ServiceType $s) => [$s->id => $s->name])->all())
                                ->live()
                                ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                    $service = ServiceType::find($state);
                                    if ($service && blank($get('price_cents'))) {
                                        $set('price_cents', number_format($service->base_price_cents / 100, 2, '.', ''));
                                    }
                                    if ($service && blank($get('hours'))) {
                                        $set('hours', $service->estimated_hours);
                                    }
                                })
                                ->columnSpan(['md' => 2]),
                            TextInput::make('description')->label(__('work_orders.fields.description'))->maxLength(200)->columnSpan(['md' => 2]),
                            MoneyInput::make('price_cents')->label(__('work_orders.fields.price'))->columnSpan(['md' => 2]),
                            TextInput::make('hours')->label(__('work_orders.fields.hours'))->numeric()->minValue(0)->maxValue(999)
                                ->default(0)->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? 0 : $state)
                                ->columnSpan(['md' => 1]),
                            Select::make('inventory_item_id')->label(__('work_orders.fields.material'))
                                ->options(fn () => InventoryItem::query()->where('is_active', true)->orderBy('name')->get()
                                    ->mapWithKeys(fn (InventoryItem $i) => [$i->id => $i->name.' ('.$i->stock.' '.$i->unit->getLabel().')'])->all())
                                ->searchable()
                                ->disabled(fn ($livewire) => self::materialsConsumed($livewire))
                                ->columnSpan(['md' => 3]),
                            TextInput::make('quantity')->label(__('work_orders.fields.quantity'))
                                ->numeric()->minValue(0)->maxValue(9999)->step('0.1')->default(0)
                                ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? 0 : $state)
                                ->requiredWith('inventory_item_id')
                                ->disabled(fn ($livewire) => self::materialsConsumed($livewire))
                                ->columnSpan(['md' => 2]),
                        ]),
                ]),
        ]);
    }
}
