<?php

namespace App\Filament\Pages\Auth;

use App\Domain\Tours\Actions\CompleteTour;
use App\Notifications\StaffAlert;
use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Mi perfil: datos, contraseña, idioma, avisos por correo y verificación en dos pasos (2FA).
 */
class EditProfile extends BaseEditProfile
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetTours')->label(__('tours.labels.reset'))->icon(Heroicon::OutlinedAcademicCap)->color('gray')
                ->requiresConfirmation()->modalDescription(__('tours.labels.reset_help'))
                ->action(function (): void {
                    app(CompleteTour::class)->reset(auth()->user());
                    Notification::make()->success()->title(__('tours.labels.reset_done'))->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            Select::make('locale')->label(__('users.fields.locale'))
                ->options(['pt_BR' => __('settings.locales.pt_BR'), 'es' => __('settings.locales.es')])
                ->required()->native(false),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getCurrentPasswordFormComponent(),
            Section::make(__('users.profile.notifications'))
                ->description(__('users.profile.notifications_help'))
                ->schema([
                    CheckboxList::make('notification_preferences.mail_categories')
                        ->hiddenLabel()
                        ->options(collect(StaffAlert::CATEGORIES)->mapWithKeys(fn (string $c) => [$c => __('users.alert_categories.'.$c)])->all())
                        ->columns(2),
                ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $mail = (array) ($data['notification_preferences']['mail'] ?? []);
        $data['notification_preferences'] = ['mail_categories' => array_keys(array_filter($mail))];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $selected = (array) ($data['notification_preferences']['mail_categories'] ?? []);
        $data['notification_preferences'] = [
            'mail' => collect(StaffAlert::CATEGORIES)->mapWithKeys(fn (string $c) => [$c => in_array($c, $selected, true)])->all(),
        ];

        return $data;
    }
}
