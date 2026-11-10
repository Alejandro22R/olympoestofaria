<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Domain\Appointments\Actions\ScheduleAppointment;
use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Inspections\Actions\InspectionReportLink;
use App\Domain\Inspections\Enums\ReportChannel;
use App\Domain\Payments\Actions\RecordManualPayment;
use App\Domain\Payments\Actions\RequestPixPayment;
use App\Domain\Payments\BillingSummary;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentType;
use App\Domain\Quotes\Actions\DecideQuote;
use App\Domain\Quotes\Actions\ReviseQuote;
use App\Domain\Quotes\Actions\SaveQuote;
use App\Domain\Quotes\Actions\SendQuote;
use App\Domain\Quotes\Enums\QuoteStatus;
use App\Domain\Settings\PaymentSettings;
use App\Domain\Shared\Rules\BrazilianPlate;
use App\Domain\WorkOrders\Actions\CreateWorkOrder;
use App\Filament\Concerns\MapsDomainValidation;
use App\Filament\Resources\Inspections\InspectionResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Filament\Support\Money;
use App\Filament\Support\MoneyInput;
use App\Models\Branch;
use App\Models\Quote;
use App\Models\ReportSend;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class EditQuote extends EditRecord
{
    use MapsDomainValidation;

    protected static string $resource = QuoteResource::class;

    protected function quote(): Quote
    {
        $record = $this->getRecord();
        abort_unless($record instanceof Quote, 404);

        return $record;
    }

    public function getTitle(): string|Htmlable
    {
        return $this->quote()->number().' · '.$this->quote()->customer->name;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->quote()->items->map(fn ($i) => [
            'service_type_id' => $i->service_type_id,
            'material_variant_id' => $i->material_variant_id,
            'description' => $i->description,
            'quantity' => $i->quantity,
            'unit_price_cents' => $i->unit_price_cents,
        ])->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->withFormErrors(fn () => app(SaveQuote::class)->execute($this->quote(), $data, auth()->user()));
    }

    protected function getFormActions(): array
    {
        return $this->quote()->status->isEditable() ? parent::getFormActions() : [];
    }

    protected function getHeaderActions(): array
    {
        $can = fn () => (bool) auth()->user()?->can('update', $this->quote());

        return [
            Action::make('send')->extraAttributes(['data-tour' => 'quote-send'])
                ->label(fn () => $this->quote()->status === QuoteStatus::Draft ? __('quotes.actions.send') : __('quotes.actions.resend'))
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->visible(fn () => $can() && in_array($this->quote()->status, [QuoteStatus::Draft, QuoteStatus::Sent], true))
                ->schema([
                    Radio::make('channel')->label(__('inspections.fields.channel'))
                        ->options(InspectionResource::channelOptions())->default(ReportChannel::WhatsApp->value)->required(),
                ])
                ->action(function (array $data): void {
                    if ($this->quote()->status === QuoteStatus::Draft) {
                        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    }
                    $result = $this->withFormErrors(fn () => app(SendQuote::class)->execute($this->quote()->refresh(), ReportChannel::from($data['channel']), auth()->user()), 'mountedActions.0.data');
                    $this->notifySent($result);
                    $this->refreshFormData(['status_info', 'totals']);
                }),
            Action::make('pix')
                ->label(__('payments.actions.generate_pix'))
                ->icon(Heroicon::OutlinedQrCode)
                ->color('success')
                ->visible(fn () => $can() && $this->quote()->status === QuoteStatus::Approved
                    && app(PaymentSettings::class)->onlinePixAvailable()
                    && BillingSummary::forQuote($this->quote())->depositOutstandingCents() > 0)
                ->schema([TextInput::make('email')->label(__('customers.fields.email'))->email()->required()->default(fn () => $this->quote()->customer->email)])
                ->action(function (array $data): void {
                    $payment = $this->withFormErrors(fn () => app(RequestPixPayment::class)->deposit($this->quote(), $data['email']), 'mountedActions.0.data');
                    Notification::make()->success()->title(__('payments.messages.pix_created', ['amount' => Money::format($payment->amount_cents)]))
                        ->body(__('payments.messages.pix_share'))->persistent()->send();
                }),
            Action::make('manualPayment')->extraAttributes(['data-tour' => 'quote-payment'])
                ->label(__('payments.actions.record_manual'))
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('gray')
                ->visible(fn () => $can() && $this->quote()->status === QuoteStatus::Approved && BillingSummary::forQuote($this->quote())->outstandingCents() > 0)
                ->schema([
                    Select::make('type')->label(__('payments.fields.type'))->options(PaymentType::class)->default(PaymentType::Deposit->value)->required(),
                    Select::make('method')->label(__('payments.fields.method'))
                        ->options(fn () => collect(app(PaymentSettings::class)->manual_methods)->mapWithKeys(fn (string $m) => [$m => PaymentMethod::from($m)->getLabel()])->all())->required(),
                    MoneyInput::make('amount_cents')->label(__('payments.fields.amount'))->required()
                        ->default(fn () => number_format(BillingSummary::forQuote($this->quote())->depositOutstandingCents() / 100, 2, '.', '')),
                    TextInput::make('note')->label(__('inventory.fields.note'))->maxLength(200),
                ])
                ->action(function (array $data): void {
                    $this->withFormErrors(fn () => app(RecordManualPayment::class)->execute(
                        $this->quote(), $data['type'] instanceof PaymentType ? $data['type'] : PaymentType::from($data['type']), $data['method'] instanceof PaymentMethod ? $data['method'] : PaymentMethod::from($data['method']), (int) $data['amount_cents'], auth()->user(), $data['note'] ?? null,
                    ), 'mountedActions.0.data');
                    Notification::make()->success()->title(__('payments.messages.recorded'))->send();
                    $this->refreshFormData(['totals']);
                }),
            ActionGroup::make([
                Action::make('preview')
                    ->label(__('quotes.actions.customer_page'))
                    ->icon(Heroicon::OutlinedEye)
                    ->visible(fn () => $this->quote()->status !== QuoteStatus::Draft)
                    ->url(fn () => route('portal.quote', $this->quote()), shouldOpenInNewTab: true),
                Action::make('pdf')
                    ->label(__('quotes.actions.pdf'))
                    ->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->visible(fn () => $this->quote()->report_path !== null)
                    ->url(fn () => URL::temporarySignedRoute('admin.quotes.report', now()->addMinutes(InspectionReportLink::STAFF_TTL_MINUTES), ['quote' => $this->quote()]), shouldOpenInNewTab: true),
                Action::make('approve')
                    ->label(__('quotes.actions.approve_for_customer'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->visible(fn () => $can() && $this->quote()->status === QuoteStatus::Sent)
                    ->requiresConfirmation()
                    ->action(function (): void {
                        $this->withFormErrors(fn () => app(DecideQuote::class)->approve($this->quote(), 'staff'));
                        Notification::make()->success()->title(QuoteStatus::Approved->getLabel())->send();
                    }),
                Action::make('reject')
                    ->label(__('quotes.actions.reject'))
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn () => $can() && $this->quote()->status === QuoteStatus::Sent)
                    ->schema([Textarea::make('reason')->label(__('quotes.fields.rejection_reason'))->required()->maxLength(255)])
                    ->action(function (array $data): void {
                        app(DecideQuote::class)->reject($this->quote(), $data['reason']);
                        Notification::make()->title(QuoteStatus::Rejected->getLabel())->send();
                    }),
                Action::make('revise')
                    ->label(__('quotes.actions.revise'))
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->visible(fn () => $can() && in_array($this->quote()->status, [QuoteStatus::Sent, QuoteStatus::Expired, QuoteStatus::Rejected], true))
                    ->requiresConfirmation()
                    ->modalDescription(__('quotes.messages.revise_help'))
                    ->action(function (): void {
                        $new = app(ReviseQuote::class)->execute($this->quote(), auth()->user());
                        $this->redirect(QuoteResource::getUrl('edit', ['record' => $new]));
                    }),
                Action::make('schedule')
                    ->label(__('quotes.actions.schedule'))
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->visible(fn () => $can() && $this->quote()->status === QuoteStatus::Approved && $this->quote()->appointment_id === null)
                    ->schema([
                        TextInput::make('plate')->label(__('vehicles.fields.plate'))
                            ->visible(fn () => $this->quote()->vehicle_id === null)->required(fn () => $this->quote()->vehicle_id === null)
                            ->rule(new BrazilianPlate),
                        DateTimePicker::make('scheduled_at')->label(__('appointments.fields.scheduled_at'))->seconds(false)->minutesStep(15)->required(),
                        DateTimePicker::make('promised_delivery_at')->label(__('appointments.fields.promised_delivery_at'))->seconds(false),
                    ])
                    ->action(function (array $data): void {
                        $quote = $this->quote();
                        $this->withFormErrors(function () use ($quote, $data) {
                            $vehicle = $quote->vehicle ?? $this->vehicleFromLead($quote, (string) $data['plate']);
                            $paidOrNotRequired = $quote->deposit_paid_at !== null || ! app(PaymentSettings::class)->deposit_required_to_confirm || $quote->deposit_cents === 0;
                            $appointment = app(ScheduleAppointment::class)->execute([
                                'vehicle_id' => $vehicle->id,
                                'branch_id' => Branch::default()?->id,
                                'scheduled_at' => $data['scheduled_at'],
                                'promised_delivery_at' => $data['promised_delivery_at'] ?? null,
                                'status' => $paidOrNotRequired ? AppointmentStatus::Confirmed : AppointmentStatus::Scheduled,
                                'created_by' => auth()->id(),
                            ]);
                            $quote->forceFill(['appointment_id' => $appointment->id, 'vehicle_id' => $vehicle->id])->save();
                        }, 'mountedActions.0.data');
                        Notification::make()->success()->title(__('quotes.messages.scheduled'))->send();
                    }),
                Action::make('workOrder')
                    ->label(__('quotes.actions.work_order'))
                    ->icon(Heroicon::OutlinedWrench)
                    ->visible(fn () => $can() && $this->quote()->status === QuoteStatus::Approved && $this->quote()->vehicle_id !== null
                        && ! WorkOrder::query()->where('quote_id', $this->quote()->id)->exists())
                    ->requiresConfirmation()
                    ->action(function (): void {
                        $order = app(CreateWorkOrder::class)->fromQuote($this->quote(), auth()->user());
                        $this->redirect(WorkOrderResource::getUrl('edit', ['record' => $order]));
                    }),
            ]),
        ];
    }

    private function vehicleFromLead(Quote $quote, string $plate): Vehicle
    {
        $vehicle = $quote->project?->configuration['vehicle'] ?? [];

        return Vehicle::query()->firstOrCreate(['plate' => BrazilianPlate::normalize($plate)], [
            'customer_id' => $quote->customer_id,
            'vehicle_model_id' => $vehicle['vehicle_model_id'] ?? null,
            'brand' => $vehicle['brand'] ?? '—',
            'model' => $vehicle['model'] ?? '—',
            'year' => $vehicle['year'] ?? null,
        ]);
    }

    /**
     * @param  array{send: ReportSend, url: ?string}  $result
     */
    private function notifySent(array $result): void
    {
        if ($result['url'] !== null) {
            Notification::make()->success()->title(__('inspections.messages.sent_whatsapp', ['recipient' => $result['send']->recipient]))->send();
            $this->js('window.open('.json_encode($result['url']).', "_blank")');
        } else {
            Notification::make()->success()->title(__('inspections.messages.sent_email', ['recipient' => $result['send']->recipient]))->send();
        }
    }
}
