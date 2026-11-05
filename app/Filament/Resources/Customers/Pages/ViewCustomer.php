<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Domain\Auth\Permission;
use App\Domain\Customers\Actions\CustomerDocumentUrl;
use App\Domain\Customers\Actions\StoreCustomerDocument;
use App\Domain\Privacy\Actions\AnonymizeCustomer;
use App\Domain\Privacy\Actions\ExportCustomerData;
use App\Domain\Settings\PrivacySettings;
use App\Filament\Concerns\MapsDomainValidation;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Models\CustomerDocument;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property Customer $record
 */
class ViewCustomer extends ViewRecord
{
    use MapsDomainValidation;

    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->viewDocumentAction(),
            $this->uploadDocumentAction(),
            EditAction::make()->hidden(fn () => $this->record->isAnonymized()),
            ActionGroup::make([
                $this->exportDataAction(),
                $this->anonymizeAction(),
            ])->label(__('privacy.actions.group'))->icon(Heroicon::OutlinedShieldCheck)->button()->color('gray')
                ->visible(fn () => (bool) auth()->user()?->can(Permission::PrivacyManage->value)),
        ];
    }

    protected function exportDataAction(): Action
    {
        return Action::make('exportData')
            ->label(__('privacy.actions.export'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->authorize(fn () => auth()->user()?->can(Permission::PrivacyManage->value))
            ->action(function (): StreamedResponse {
                $data = app(ExportCustomerData::class)->execute($this->record, auth()->user());

                return response()->streamDownload(
                    fn () => print (json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                    'olympo-cliente-'.$this->record->id.'.json',
                    ['Content-Type' => 'application/json'],
                );
            });
    }

    protected function anonymizeAction(): Action
    {
        return Action::make('anonymize')
            ->label(__('privacy.actions.anonymize'))
            ->icon(Heroicon::OutlinedUserMinus)
            ->color('danger')
            ->hidden(fn () => $this->record->isAnonymized())
            ->authorize(fn () => auth()->user()?->can(Permission::PrivacyManage->value))
            ->requiresConfirmation()
            ->modalHeading(__('privacy.actions.anonymize_heading', ['name' => $this->record->name]))
            ->modalDescription(__('privacy.actions.anonymize_help'))
            ->schema([
                TextInput::make('confirm')->label(__('privacy.actions.anonymize_confirm'))
                    ->required()->in([__('privacy.actions.anonymize_word')])
                    ->validationMessages(['in' => __('privacy.actions.anonymize_mismatch')]),
            ])
            ->action(function (): void {
                $this->withFormErrors(fn () => app(AnonymizeCustomer::class)->execute($this->record, auth()->user()), 'mountedActions.0.data');
                $this->record->refresh();
                Notification::make()->success()->title(__('privacy.messages.anonymized'))->send();
            });
    }

    protected function viewDocumentAction(): Action
    {
        return Action::make('viewDocument')
            ->label(__('customers.actions.view_document'))
            ->icon(Heroicon::OutlinedIdentification)
            ->color('gray')
            ->visible(fn () => $this->record->document()->exists()
                && auth()->user()?->can('view', $this->record->document()->first()))
            ->url(fn () => app(CustomerDocumentUrl::class)->for($this->record->document()->firstOrFail()), shouldOpenInNewTab: true);
    }

    protected function uploadDocumentAction(): Action
    {
        return Action::make('uploadDocument')
            ->label(__('customers.actions.upload_document'))
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->visible(fn () => (bool) auth()->user()?->can('create', CustomerDocument::class)
                && app(PrivacySettings::class)->store_document_photo && ! $this->record->isAnonymized())
            ->disabled(fn () => ! $this->record->hasLgpdConsent())
            ->tooltip(fn () => $this->record->hasLgpdConsent() ? null : __('customers.errors.consent_required'))
            ->schema([
                FileUpload::make('file')
                    ->label(__('customers.fields.file'))
                    ->helperText(__('customers.fields.file_help'))
                    ->storeFiles(false)
                    ->acceptedFileTypes(StoreCustomerDocument::ALLOWED_MIME)
                    ->maxSize(StoreCustomerDocument::MAX_BYTES / 1024)
                    ->required(),
            ])
            ->action(function (array $data): void {
                /** @var TemporaryUploadedFile $file */
                $file = $data['file'];

                $this->withFormErrors(
                    fn () => app(StoreCustomerDocument::class)->execute($this->record, $file, auth()->user()),
                    'mountedActions.0.data',
                );

                Notification::make()->success()->title(__('customers.messages.document_uploaded'))->send();
            });
    }
}
