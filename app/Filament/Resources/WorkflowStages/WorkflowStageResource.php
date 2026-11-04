<?php

namespace App\Filament\Resources\WorkflowStages;

use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\WorkflowStages\Pages\ManageWorkflowStages;
use App\Models\WorkflowStage;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Ajustes > Etapas de producción: columnas del tablero (nombre, color, orden, visibilidad).
 */
class WorkflowStageResource extends Resource
{
    protected static ?string $model = WorkflowStage::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return __('work_orders.settings.stage');
    }

    public static function getPluralModelLabel(): string
    {
        return __('work_orders.settings.stages');
    }

    public static function getNavigationLabel(): string
    {
        return __('work_orders.settings.stages');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('settings.groups.workshop');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name.pt_BR')->label(__('catalog.fields.name_pt'))->required()->maxLength(60),
            TextInput::make('name.es')->label(__('catalog.fields.name_es'))->maxLength(60),
            Select::make('status')->label(__('work_orders.settings.status'))
                ->helperText(__('work_orders.settings.status_help'))
                ->options(collect(WorkOrderStatus::onBoard())->mapWithKeys(fn (WorkOrderStatus $s) => [$s->value => $s->getLabel()])->all())
                ->required()->native(false),
            ColorPicker::make('color_hex')->label(__('catalog.fields.color_hex'))->hex()->required()->regex('/^#[0-9A-Fa-f]{6}$/'),
            TextInput::make('sort')->label(__('catalog.fields.sort'))->integer()->default(0),
            Toggle::make('visible_to_customer')->label(__('work_orders.settings.visible_to_customer'))->default(true),
            Toggle::make('notifies_customer')->label(__('work_orders.settings.notifies_customer'))
                ->helperText(__('work_orders.settings.notifies_customer_help')),
            Toggle::make('is_active')->label(__('catalog.fields.is_active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('workOrders'))
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                ColorColumn::make('color_hex')->label(__('catalog.fields.color_hex')),
                TextColumn::make('name')->label(__('catalog.fields.name')),
                TextColumn::make('status')->label(__('work_orders.settings.status'))->badge(),
                IconColumn::make('visible_to_customer')->label(__('work_orders.settings.visible_to_customer'))->boolean(),
                IconColumn::make('notifies_customer')->label(__('work_orders.settings.notifies_customer'))->boolean(),
                IconColumn::make('is_active')->label(__('catalog.fields.is_active'))->boolean(),
            ])
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing(fn (array $data, WorkflowStage $record) => [...$data, 'name' => $record->getTranslations('name'), 'status' => $record->status->value]),
                // Una etapa con órdenes no se borra: se desactiva.
                DeleteAction::make()->visible(fn (WorkflowStage $record) => $record->work_orders_count === 0),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageWorkflowStages::route('/')];
    }
}
