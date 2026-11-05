<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Settings\PrivacySettings;
use App\Filament\Clusters\Settings\SettingsPage;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Ajustes > Sistema > Privacidad y LGPD: foto del documento, retención de datos, contacto del
 * encargado y textos legales (Markdown pt/es). Exportar y anonimizar se hace desde la ficha del cliente.
 */
class ManagePrivacySettings extends SettingsPage
{
    protected static string $settings = PrivacySettings::class;

    protected static ?int $navigationSort = 52;

    protected static ?string $slug = 'privacy';

    public static function getSectionGroup(): string
    {
        return 'system';
    }

    public static function getNavigationLabel(): string
    {
        return __('privacy.settings.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewPrivacy')->label(__('privacy.legal.privacy_title'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')->url(route('portal.privacy'), shouldOpenInNewTab: true),
            Action::make('viewTerms')->label(__('privacy.legal.terms_title'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')->url(route('portal.terms'), shouldOpenInNewTab: true),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['legal_updated_at'] = filled($data['legal_updated_at'] ?? null) ? substr((string) $data['legal_updated_at'], 0, 10) : null;
        $data['contact_email'] = filled($data['contact_email'] ?? null) ? trim((string) $data['contact_email']) : null;

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        $editor = fn (string $name) => MarkdownEditor::make($name)->hiddenLabel()->required()->maxLength(30000)
            ->toolbarButtons([['heading', 'bold', 'italic', 'link'], ['bulletList', 'orderedList'], ['undo', 'redo']]);

        return $schema->components([
            Section::make(__('privacy.settings.data'))->columns(2)->schema([
                Toggle::make('store_document_photo')->label(__('privacy.settings.store_document_photo'))
                    ->helperText(__('privacy.settings.store_document_photo_help'))->columnSpanFull(),
                TextInput::make('inactive_customer_months')->label(__('privacy.settings.inactive_customer_months'))
                    ->helperText(__('privacy.settings.inactive_customer_months_help'))
                    ->integer()->required()->minValue(0)->maxValue(240)->suffix(__('privacy.settings.months')),
                TextInput::make('lost_lead_months')->label(__('privacy.settings.lost_lead_months'))
                    ->helperText(__('settings.notifications.zero_off'))
                    ->integer()->required()->minValue(0)->maxValue(240)->suffix(__('privacy.settings.months')),
                TextInput::make('contact_email')->label(__('privacy.settings.contact_email'))
                    ->helperText(__('privacy.settings.contact_email_help'))->email()->maxLength(160),
                DatePicker::make('legal_updated_at')->label(__('privacy.settings.legal_updated_at'))->native(false),
            ]),
            Section::make(__('privacy.settings.legal'))->description(__('privacy.settings.legal_help'))->schema([
                Tabs::make('legal')->tabs([
                    Tab::make(__('privacy.legal.privacy_title').' · PT')->schema([$editor('privacy_policy.pt_BR')]),
                    Tab::make(__('privacy.legal.privacy_title').' · ES')->schema([$editor('privacy_policy.es')]),
                    Tab::make(__('privacy.legal.terms_title').' · PT')->schema([$editor('terms.pt_BR')]),
                    Tab::make(__('privacy.legal.terms_title').' · ES')->schema([$editor('terms.es')]),
                ]),
            ]),
        ]);
    }
}
