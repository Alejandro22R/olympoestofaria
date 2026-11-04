<?php

namespace App\Filament\Clusters\Settings;

use App\Domain\Auth\Permission;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\Unexposed;
use Illuminate\Contracts\Support\Htmlable;
use Spatie\LaravelSettings\Settings;

/**
 * Base de cada sección de Ajustes: carga la clase tipada, guarda en bloque
 * (Guardar / Descartar) y deja la auditoría al listener AuditSettingsChanges.
 *
 * @property-read Schema $form
 */
abstract class SettingsPage extends Page
{
    protected static ?string $cluster = SettingsCluster::class;

    /** @var class-string<Settings> */
    protected static string $settings;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    abstract public static function getSectionGroup(): string;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::SettingsManage->value);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('settings.groups.'.static::getSectionGroup());
    }

    public function getTitle(): string|Htmlable
    {
        return static::getNavigationLabel();
    }

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        /** @var Settings $settings */
        $settings = app(static::$settings);
        $this->form->fill($this->mutateFormDataBeforeFill($settings->toArray()));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->mutateFormDataBeforeSave($this->form->getState());

        /** @var Settings $settings */
        $settings = app(static::$settings);
        $settings->fill($data)->save();

        $this->fillForm();

        Notification::make()->success()->title(__('settings.saved'))->send();
    }

    public function discard(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->fillForm();

        Notification::make()->title(__('settings.discarded'))->send();
    }

    #[Unexposed]
    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
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
                        Action::make('save')
                            ->label(__('settings.actions.save'))
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                        Action::make('discard')
                            ->label(__('settings.actions.discard'))
                            ->color('gray')
                            ->action('discard'),
                    ])->sticky()->key('form-actions'),
                ]),
        ]);
    }
}
