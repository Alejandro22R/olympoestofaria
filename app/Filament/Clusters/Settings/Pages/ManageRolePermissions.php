<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Auth\Permission;
use App\Domain\Auth\Role;
use App\Domain\Users\Actions\UpdateRolePermissions;
use App\Filament\Clusters\Settings\SettingsCluster;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\Unexposed;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * Ajustes > Equipo > Roles y permisos: matriz editable de recepción y tapicero.
 *
 * @property-read Schema $form
 */
class ManageRolePermissions extends Page
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 41;

    protected static ?string $slug = 'roles';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::UsersManage->value);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('settings.groups.team');
    }

    public static function getNavigationLabel(): string
    {
        return __('users.roles.title');
    }

    public function getTitle(): string|Htmlable
    {
        return static::getNavigationLabel();
    }

    public function mount(): void
    {
        $this->fillForm();
    }

    private function fillForm(): void
    {
        $data = [];
        foreach (UpdateRolePermissions::editableRoles() as $role) {
            $data[$role->value] = RoleModel::findByName($role->value, 'web')->permissions->pluck('name')->all();
        }
        $this->form->fill($data);
    }

    /**
     * @return array<string, string>
     */
    private static function permissionOptions(): array
    {
        $locked = [Permission::SettingsManage, Permission::UsersManage];

        return collect(Permission::cases())
            ->reject(fn (Permission $p) => in_array($p, $locked, true))
            ->mapWithKeys(fn (Permission $p) => [$p->value => __('users.permissions.'.str_replace('.', '_', $p->value))])
            ->all();
    }

    public function save(UpdateRolePermissions $update): void
    {
        abort_unless(static::canAccess(), 403);
        $state = $this->form->getState();

        foreach (UpdateRolePermissions::editableRoles() as $role) {
            $update->execute($role, array_values((array) ($state[$role->value] ?? [])));
        }

        $this->fillForm();
        Notification::make()->success()->title(__('settings.saved'))->send();
    }

    #[Unexposed]
    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(Role::Owner->label())->description(__('users.roles.owner_help'))->compact(),
            ...collect(UpdateRolePermissions::editableRoles())->map(fn (Role $role) => Section::make($role->label())
                ->collapsible()
                ->schema([
                    CheckboxList::make($role->value)->hiddenLabel()
                        ->options(self::permissionOptions())
                        ->columns(['sm' => 2, 'lg' => 3])
                        ->bulkToggleable(),
                ]))->all(),
        ]);
    }

    #[Unexposed]
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label(__('settings.actions.save'))->submit('save')->keyBindings(['mod+s']),
                    ])->sticky()->key('form-actions'),
                ]),
        ]);
    }
}
