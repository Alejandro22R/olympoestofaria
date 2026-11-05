<?php

namespace App\Filament\Resources\ConsentEvents;

use App\Domain\Auth\Permission;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\ConsentEvents\Pages\ListConsentEvents;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\ConsentEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Ajustes > Sistema > Registro de consentimientos (solo lectura): prueba del consentimiento LGPD.
 */
class ConsentEventResource extends Resource
{
    protected static ?string $model = ConsentEvent::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHandRaised;

    protected static ?int $navigationSort = 54;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::PrivacyManage->value);
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
        return __('privacy.consents.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('privacy.consents.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('privacy.consents.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('settings.groups.system');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['customer', 'user']))
            ->columns([
                TextColumn::make('created_at')->label(__('privacy.consents.at'))->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('customer.name')->label(__('customers.fields.name'))->searchable()
                    ->url(fn (ConsentEvent $record) => CustomerResource::getUrl('view', ['record' => $record->customer_id])),
                TextColumn::make('type')->label(__('privacy.consents.type'))->formatStateUsing(fn (string $state) => __('privacy.consents.types.'.$state)),
                IconColumn::make('granted')->label(__('privacy.consents.granted'))->boolean(),
                TextColumn::make('source')->label(__('privacy.consents.source'))->formatStateUsing(fn (string $state) => __('privacy.consents.sources.'.$state)),
                TextColumn::make('user.name')->label(__('privacy.consents.recorded_by'))->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('type')->label(__('privacy.consents.type'))
                    ->options(collect(['privacy', 'contact', 'document'])->mapWithKeys(fn (string $t) => [$t => __('privacy.consents.types.'.$t)])->all()),
                TernaryFilter::make('granted')->label(__('privacy.consents.granted')),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListConsentEvents::route('/')];
    }
}
