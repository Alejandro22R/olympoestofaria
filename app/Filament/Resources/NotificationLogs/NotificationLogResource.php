<?php

namespace App\Filament\Resources\NotificationLogs;

use App\Domain\Auth\Permission;
use App\Domain\Notifications\Customer\MessageEvent;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\NotificationLogs\Pages\ListNotificationLogs;
use App\Models\Customer;
use App\Models\NotificationLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Ajustes > Comunicación > Registro de envíos (solo lectura, para soporte).
 */
class NotificationLogResource extends Resource
{
    protected static ?string $model = NotificationLog::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?int $navigationSort = 33;

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

    public static function getModelLabel(): string
    {
        return __('communication.logs.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('communication.logs.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('communication.logs.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('settings.groups.communication');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('notifiable'))
            ->columns([
                TextColumn::make('created_at')->label(__('communication.logs.fields.created_at'))->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('customer')->label(__('communication.logs.fields.customer'))
                    ->state(fn (NotificationLog $record) => $record->notifiable instanceof Customer ? $record->notifiable->name : '—'),
                TextColumn::make('event')->label(__('communication.logs.fields.event'))
                    ->formatStateUsing(fn (string $state) => MessageEvent::tryFrom($state)?->getLabel() ?? $state),
                TextColumn::make('channel')->label(__('communication.logs.fields.channel'))
                    ->formatStateUsing(fn (string $state) => __('communication.channels.'.$state)),
                TextColumn::make('recipient')->label(__('communication.logs.fields.recipient'))->searchable()->toggleable(),
                TextColumn::make('status')->label(__('communication.logs.fields.status'))->badge()
                    ->formatStateUsing(fn (string $state) => __('communication.statuses.'.$state))
                    ->color(fn (string $state) => match ($state) {
                        'sent' => 'success', 'failed' => 'danger', default => 'gray'
                    }),
                TextColumn::make('attempt')->label(__('communication.logs.fields.attempt'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('error')->label(__('communication.logs.fields.error'))->limit(80)->wrap()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('communication.logs.fields.status'))
                    ->options(collect(['sent', 'failed', 'skipped'])->mapWithKeys(fn (string $s) => [$s => __('communication.statuses.'.$s)])->all()),
                SelectFilter::make('channel')->label(__('communication.logs.fields.channel'))
                    ->options(['mail' => __('communication.channels.mail'), 'whatsapp' => __('communication.channels.whatsapp')]),
                SelectFilter::make('event')->label(__('communication.logs.fields.event'))
                    ->options(collect(MessageEvent::cases())->mapWithKeys(fn (MessageEvent $e) => [$e->value => $e->getLabel()])->all()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationLogs::route('/'),
        ];
    }
}
