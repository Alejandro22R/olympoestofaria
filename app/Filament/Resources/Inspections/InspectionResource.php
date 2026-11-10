<?php

namespace App\Filament\Resources\Inspections;

use App\Domain\Inspections\Enums\ReportChannel;
use App\Filament\Resources\Inspections\Pages\ListInspections;
use App\Filament\Resources\Inspections\Pages\ViewInspection;
use App\Filament\Support\CardList;
use App\Models\ChecklistItem;
use App\Models\Inspection;
use App\Models\InspectionMark;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Inspecciones (check-in): solo lectura; el acta es un registro de prueba.
 */
class InspectionResource extends Resource
{
    protected static ?string $model = Inspection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('inspections.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inspections.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.daily');
    }

    public static function canCreate(): bool
    {
        // Se crean desde la cita (acción "Hacer check-in").
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('inspections.sections.vehicle_state'))
                ->columns(3)
                ->schema([
                    TextEntry::make('vehicle.plate')->label(__('inspections.fields.vehicle'))
                        ->formatStateUsing(fn (Inspection $record) => $record->vehicle->label()),
                    TextEntry::make('vehicle.customer.name')->label(__('inspections.fields.customer')),
                    TextEntry::make('created_at')->label(__('inspections.fields.created_at'))->dateTime('d/m/Y H:i'),
                    TextEntry::make('mileage_km')->label(__('inspections.fields.mileage_km'))
                        ->numeric(thousandsSeparator: '.')->suffix(' km')->placeholder('—'),
                    TextEntry::make('fuel_level')->label(__('inspections.fields.fuel_level'))->badge(),
                    TextEntry::make('inspector.name')->label(__('inspections.fields.inspector'))->placeholder('—'),
                    TextEntry::make('items')->label(__('inspections.fields.items_present'))
                        ->state(fn (Inspection $record) => ChecklistItem::query()
                            ->whereIn('id', $record->items_present)->orderBy('sort')->get()
                            ->map(fn (ChecklistItem $item) => $item->name)->all())
                        ->badge()->placeholder('—')->columnSpanFull(),
                    TextEntry::make('observations')->label(__('inspections.fields.observations'))->placeholder('—')->columnSpanFull(),
                    TextEntry::make('lgpd_consent_at')->label(__('inspections.fields.lgpd_consent'))->dateTime('d/m/Y H:i'),
                ]),
            Section::make(__('inspections.fields.marks'))
                ->schema([
                    RepeatableEntry::make('marks')->hiddenLabel()
                        ->columns(3)
                        ->schema([
                            TextEntry::make('zone')->label(__('inspections.fields.zone'))
                                ->formatStateUsing(fn (string $state) => __('inspections.zones.'.$state)),
                            TextEntry::make('damageType.name')->label(__('inspections.fields.damage_type'))->badge(),
                            TextEntry::make('note')->label(__('inspections.fields.note'))->placeholder('—'),
                            TextEntry::make('photos_count')->label(__('inspections.fields.photos'))
                                ->state(fn (InspectionMark $record) => count($record->photos)),
                        ])
                        ->placeholder(__('inspections.report.no_marks')),
                ]),
            Section::make(__('inspections.sections.sends'))
                ->schema([
                    RepeatableEntry::make('sends')->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            TextEntry::make('channel')->label(__('inspections.fields.channel'))->badge(),
                            TextEntry::make('recipient')->label(__('inspections.fields.recipient')),
                            TextEntry::make('sender.name')->label(__('inspections.fields.sent_by'))->placeholder('—'),
                            TextEntry::make('sent_at')->label(__('inspections.fields.sent_at'))->dateTime('d/m/Y H:i'),
                        ])
                        ->placeholder('—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return CardList::grid($table, 'inspections')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['vehicle.customer', 'sends'])->withCount('marks'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                CardList::card(
                    TextColumn::make('vehicle.plate')->label(__('inspections.fields.vehicle'))
                        ->formatStateUsing(fn (Inspection $record) => $record->vehicle->label())->searchable(),
                    TextColumn::make('marks_count')->label(__('inspections.fields.marks_count'))
                        ->formatStateUsing(fn (int $state) => trans_choice('inspections.cards.marks', $state, ['count' => $state]))
                        ->color(fn (int $state) => $state > 0 ? 'warning' : 'success'),
                    [
                        CardList::detail(TextColumn::make('created_at')->label(__('inspections.fields.created_at'))->dateTime('d/m/Y H:i')->sortable(), 'heroicon-m-calendar'),
                        CardList::detail(TextColumn::make('vehicle.customer.name')->label(__('inspections.fields.customer'))->searchable(), 'heroicon-m-user'),
                        CardList::detail(TextColumn::make('last_send')->label(__('inspections.fields.last_send'))
                            ->state(function (Inspection $record): ?string {
                                $send = $record->sends->sortByDesc('sent_at')->first();

                                return $send ? $send->channel->getLabel().' · '.$send->sent_at->format('d/m H:i') : null;
                            })
                            ->placeholder(__('inspections.cards.not_sent')), 'heroicon-m-paper-airplane'),
                    ],
                ),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInspections::route('/'),
            'view' => ViewInspection::route('/{record}'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function channelOptions(): array
    {
        return collect(ReportChannel::cases())->mapWithKeys(fn (ReportChannel $c) => [$c->value => $c->getLabel()])->all();
    }
}
