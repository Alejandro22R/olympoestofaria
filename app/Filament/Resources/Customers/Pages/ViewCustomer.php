<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Domain\Customers\Actions\CustomerDocumentUrl;
use App\Domain\Customers\Actions\StoreCustomerDocument;
use App\Filament\Concerns\MapsDomainValidation;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Models\CustomerDocument;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
            EditAction::make(),
        ];
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
            ->visible(fn () => (bool) auth()->user()?->can('create', CustomerDocument::class))
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
