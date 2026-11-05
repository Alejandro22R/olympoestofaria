<?php

namespace App\Filament\Resources\Activities;

use App\Domain\Auth\Permission;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * Ajustes > Sistema > Auditoría: quién cambió qué y cuándo (estados, precios, documentos, ajustes,
 * permisos, privacidad). Solo lectura, con filtros y exportación CSV; la retención se configura aquí.
 */
class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 55;

    protected static ?string $slug = 'audit';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::SettingsManage->value);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        return static::canAccess();
    }

    public static function getModelLabel(): string
    {
        return __('system.audit.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('system.audit.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('system.audit.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('settings.groups.system');
    }

    public static function subjectLabel(?string $type): string
    {
        return $type ? (string) Str::of(class_basename($type))->snake()->replace('_', ' ')->ucfirst() : '—';
    }

    /**
     * @param  mixed  $value
     */
    public static function flatten($value): string
    {
        return is_scalar($value) || $value === null ? (string) ($value ?? '—') : (string) json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('created_at')->label(__('system.audit.when'))->dateTime('d/m/Y H:i:s'),
            TextEntry::make('causer.name')->label(__('system.audit.who'))->placeholder(__('system.audit.system')),
            TextEntry::make('description')->label(__('system.audit.what')),
            TextEntry::make('subject')->label(__('system.audit.subject'))
                ->state(fn (Activity $record) => self::subjectLabel($record->subject_type).($record->subject_id ? ' #'.$record->subject_id : '')),
            KeyValueEntry::make('old')->label(__('system.audit.old'))->columnSpanFull()
                ->state(fn (Activity $record) => collect((array) ($record->properties['old'] ?? []))->map(fn ($v) => self::flatten($v))->all()),
            KeyValueEntry::make('attributes')->label(__('system.audit.new'))->columnSpanFull()
                ->state(fn (Activity $record) => collect((array) ($record->properties['attributes'] ?? []))->map(fn ($v) => self::flatten($v))->all()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('causer'))
            ->columns([
                TextColumn::make('created_at')->label(__('system.audit.when'))->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('causer.name')->label(__('system.audit.who'))->placeholder(__('system.audit.system')),
                TextColumn::make('log_name')->label(__('system.audit.area'))->badge(),
                TextColumn::make('event')->label(__('system.audit.event'))->placeholder('—'),
                TextColumn::make('subject_type')->label(__('system.audit.subject'))
                    ->formatStateUsing(fn (Activity $record) => self::subjectLabel($record->subject_type).($record->subject_id ? ' #'.$record->subject_id : '')),
                TextColumn::make('description')->label(__('system.audit.what'))->searchable()->limit(50),
            ])
            ->filters([
                SelectFilter::make('log_name')->label(__('system.audit.area'))
                    ->options(fn () => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name', 'log_name')->filter()->all()),
                SelectFilter::make('causer_id')->label(__('system.audit.who'))
                    ->options(fn () => User::query()->whereIn('id', Activity::query()->whereNotNull('causer_id')->select('causer_id'))->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null) ? $query->where('causer_type', (new User)->getMorphClass())->where('causer_id', $data['value']) : $query),
                Filter::make('period')->schema([
                    DatePicker::make('from')->label(__('system.audit.from')),
                    DatePicker::make('until')->label(__('system.audit.until')),
                ])->query(fn (Builder $query, array $data) => $query
                    ->when($data['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                    ->when($data['until'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListActivities::route('/')];
    }
}
