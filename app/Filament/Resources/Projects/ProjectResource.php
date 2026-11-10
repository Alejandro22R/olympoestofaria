<?php

namespace App\Filament\Resources\Projects;

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\ProjectSummary;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Filament\Support\CardList;
use App\Filament\Support\Money;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Solicitudes del portal (leads): seguimiento nuevo → contactado → cotizado → aprobado / perdido.
 */
class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return __('projects.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('projects.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.sales');
    }

    public static function getNavigationBadge(): ?string
    {
        $new = Project::query()->where('status', ProjectStatus::Submitted)->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('customer.name')->label(__('projects.fields.customer')),
                TextEntry::make('customer.phone')->label(__('projects.fields.phone')),
                TextEntry::make('customer.email')->label(__('customers.fields.email'))->placeholder('—'),
                TextEntry::make('vehicle')->label(__('projects.fields.vehicle'))->state(fn (Project $record) => $record->vehicleLabel()),
                TextEntry::make('status')->label(__('projects.fields.status'))->badge(),
                TextEntry::make('estimate')->label(__('projects.fields.estimate'))
                    ->state(fn (Project $record) => Money::format($record->estimate_min_cents).' – '.Money::format($record->estimate_max_cents)),
                TextEntry::make('submitted_at')->label(__('projects.fields.submitted_at'))->dateTime('d/m/Y H:i'),
                TextEntry::make('opt_in')->label(__('projects.fields.opt_in'))->badge()
                    ->state(fn (Project $record) => $record->customer->contact_opt_in_at ? '✓' : '—'),
                TextEntry::make('lost_reason')->label(__('projects.fields.lost_reason'))->visible(fn (Project $record) => $record->lost_reason !== null),
            ]),
            Section::make(__('projects.fields.pieces'))->schema([
                RepeatableEntry::make('lines')->hiddenLabel()
                    ->state(fn (Project $record) => app(ProjectSummary::class)->lines($record))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('service')->label(__('work_orders.fields.service')),
                        TextEntry::make('material')->label(__('work_orders.fields.material'))->placeholder('—'),
                        TextEntry::make('color')->label(__('catalog.fields.color_hex'))->placeholder('—'),
                    ]),
                ColorEntry::make('configuration.stitching')->label(__('projects.fields.stitching'))->placeholder('—'),
                TextEntry::make('configuration.notes')->label(__('projects.fields.notes'))->placeholder('—'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return CardList::grid($table, 'projects')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('customer'))
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                CardList::card(
                    TextColumn::make('customer.name')->label(__('projects.fields.customer'))->searchable(),
                    TextColumn::make('status')->label(__('projects.fields.status')),
                    [
                        CardList::detail(TextColumn::make('vehicle')->label(__('projects.fields.vehicle'))->state(fn (Project $record) => $record->vehicleLabel()), 'heroicon-m-truck'),
                        CardList::detail(TextColumn::make('customer.phone')->label(__('projects.fields.phone'))->searchable(), 'heroicon-m-phone'),
                        CardList::detail(TextColumn::make('estimate_max_cents')->label(__('projects.fields.estimate'))
                            ->formatStateUsing(fn (Project $record) => Money::format($record->estimate_min_cents).' – '.Money::format($record->estimate_max_cents))->sortable(), 'heroicon-m-banknotes'),
                        CardList::detail(TextColumn::make('submitted_at')->label(__('projects.fields.submitted_at'))->sortable()
                            ->formatStateUsing(fn (Project $record) => $record->number().' · '.$record->submitted_at?->diffForHumans()), 'heroicon-m-clock'),
                    ],
                ),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('projects.fields.status'))->options(ProjectStatus::class)->multiple(),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'view' => ViewProject::route('/{record}'),
        ];
    }
}
