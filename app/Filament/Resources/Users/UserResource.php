<?php

namespace App\Filament\Resources\Users;

use App\Domain\Auth\Role;
use App\Domain\Users\Actions\SaveStaffUser;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Support\ActionErrors;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Password;

/**
 * Ajustes > Equipo > Usuarios: invitar, cambiar rol e idioma, desactivar y reiniciar el 2FA.
 * Solo personal (los clientes tienen su cuenta ligera del portal).
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 40;

    public static function getModelLabel(): string
    {
        return __('users.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('users.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('users.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('settings.groups.team');
    }

    /**
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        return User::query()->role(array_map(fn (Role $r) => $r->value, Role::staff()))->with('roles');
    }

    /**
     * @return array<string, string>
     */
    public static function roleOptions(): array
    {
        return collect(Role::staff())->mapWithKeys(fn (Role $r) => [$r->value => $r->label()])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label(__('users.fields.name'))->required()->maxLength(120),
            TextInput::make('email')->label(__('users.fields.email'))->email()->required()->maxLength(160),
            Select::make('role')->label(__('users.fields.role'))->options(self::roleOptions())->required()->native(false),
            Select::make('locale')->label(__('users.fields.locale'))
                ->options(['pt_BR' => __('settings.locales.pt_BR'), 'es' => __('settings.locales.es')])
                ->default('pt_BR')->required()->native(false),
            Toggle::make('is_active')->label(__('users.fields.is_active'))->default(true)
                ->helperText(__('users.fields.is_active_help'))->hiddenOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label(__('users.fields.name'))->searchable()->sortable(),
                TextColumn::make('email')->label(__('users.fields.email'))->searchable(),
                TextColumn::make('roles.name')->label(__('users.fields.role'))->badge()
                    ->formatStateUsing(fn (string $state) => Role::tryFrom($state)?->label() ?? $state),
                IconColumn::make('two_factor')->label(__('users.fields.two_factor'))->boolean()
                    ->state(fn (User $record) => $record->hasTwoFactor()),
                TextColumn::make('last_login_at')->label(__('users.fields.last_login_at'))->since()->placeholder('—')->sortable(),
                IconColumn::make('is_active')->label(__('users.fields.is_active'))->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label(__('users.fields.is_active')),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, User $record) => [...$data, 'role' => $record->getRoleNames()->first()])
                    ->using(fn (User $record, array $data) => ActionErrors::map(fn () => app(SaveStaffUser::class)->execute($data, $record, auth()->user()))),
                Action::make('resendInvite')->label(__('users.actions.send_reset'))
                    ->icon(Heroicon::OutlinedEnvelope)->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->is_active)
                    ->action(function (User $record): void {
                        Password::broker()->sendResetLink(['email' => $record->email]);
                        Notification::make()->success()->title(__('users.notifications.reset_sent'))->send();
                    }),
                Action::make('resetTwoFactor')->label(__('users.actions.reset_2fa'))
                    ->icon(Heroicon::OutlinedShieldExclamation)->color('danger')
                    ->requiresConfirmation()->modalDescription(__('users.actions.reset_2fa_help'))
                    ->visible(fn (User $record) => $record->hasTwoFactor() && ! $record->is(auth()->user()))
                    ->action(function (User $record): void {
                        $record->saveAppAuthenticationSecret(null);
                        $record->saveAppAuthenticationRecoveryCodes(null);
                        Notification::make()->success()->title(__('users.notifications.2fa_reset'))->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
