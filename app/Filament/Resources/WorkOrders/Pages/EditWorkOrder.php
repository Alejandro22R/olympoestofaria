<?php

namespace App\Filament\Resources\WorkOrders\Pages;

use App\Domain\Customers\Access\TrackingLink;
use App\Domain\Inspections\Actions\InspectionReportLink;
use App\Domain\Inspections\Enums\ReportChannel;
use App\Domain\Inventory\Actions\ConsumeWorkOrderMaterials;
use App\Domain\Payments\Actions\RecordManualPayment;
use App\Domain\Payments\Actions\RequestPixPayment;
use App\Domain\Payments\BillingSummary;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentType;
use App\Domain\Reports\SendReport;
use App\Domain\Settings\PaymentSettings;
use App\Domain\Settings\ProductionSettings;
use App\Domain\WorkOrders\Actions\CancelWorkOrder;
use App\Domain\WorkOrders\Actions\MoveWorkOrder;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Filament\Concerns\MapsDomainValidation;
use App\Filament\Resources\Inspections\InspectionResource;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Filament\Support\Money;
use App\Filament\Support\MoneyInput;
use App\Models\WorkflowStage;
use App\Models\WorkOrder;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

class EditWorkOrder extends EditRecord
{
    use MapsDomainValidation;

    protected static string $resource = WorkOrderResource::class;

    protected function order(): WorkOrder
    {
        $record = $this->getRecord();
        abort_unless($record instanceof WorkOrder, 404);

        return $record;
    }

    public function getTitle(): string|Htmlable
    {
        return $this->order()->number().' · '.$this->order()->vehicle->formattedPlate();
    }

    /**
     * Presencia: quién más tiene abierta esta orden (canal de presencia work-orders.{id}).
     */
    public function getSubheading(): string|Htmlable|null
    {
        $channel = 'work-orders.'.$this->order()->id;

        return new HtmlString(view('filament.components.presence', ['channel' => $channel, 'me' => auth()->id()])->render());
    }

    protected function afterSave(): void
    {
        $shortages = app(ConsumeWorkOrderMaterials::class)->shortages($this->order());
        if ($shortages !== []) {
            Notification::make()->warning()->title(__('inventory.errors.missing_for_order'))
                ->body(implode("\n", $shortages))->persistent()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        $order = $this->order();

        return [
            Action::make('move')->extraAttributes(['data-tour' => 'wo-move'])
                ->label(__('work_orders.actions.move'))
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->visible(fn () => (bool) auth()->user()?->can('progress', $this->order()))
                ->schema([
                    Select::make('stage_id')->label(__('work_orders.fields.stage'))
                        ->options(fn () => WorkflowStage::query()->ordered()->get()
                            ->filter(fn (WorkflowStage $s) => $s->status === $this->order()->status || $this->order()->status->canTransitionTo($s->status))
                            ->mapWithKeys(fn (WorkflowStage $s) => [$s->id => $s->name.' ('.$s->status->getLabel().')'])->all())
                        ->default($order->workflow_stage_id)
                        ->required(),
                    Textarea::make('note')->label(__('work_orders.fields.note'))->rows(2),
                ])
                ->action(function (array $data): void {
                    $stage = WorkflowStage::findOrFail($data['stage_id']);
                    $this->withFormErrors(
                        fn () => app(MoveWorkOrder::class)->execute($this->order(), $stage, auth()->user(), $data['note'] ?? null),
                        'mountedActions.0.data',
                    );
                    Notification::make()->success()->title(__('work_orders.messages.moved', ['stage' => $stage->name]))->send();
                    $this->refreshFormData(['stage_info']);
                }),
            Action::make('progress')->extraAttributes(['data-tour' => 'wo-progress'])
                ->label(__('work_orders.actions.progress'))
                ->icon(Heroicon::OutlinedCamera)
                ->color('gray')
                ->visible(fn () => (bool) auth()->user()?->can('progress', $this->order()))
                ->schema([
                    Textarea::make('note')->label(__('work_orders.fields.note'))->rows(3)->required(),
                    FileUpload::make('photos')->label(__('work_orders.fields.photos'))
                        ->disk((string) config('olympo.private_disk'))->directory('work-orders/progress')->visibility('private')
                        ->image()->multiple()->maxFiles(app(ProductionSettings::class)->progress_photos_max)->maxSize(8 * 1024),
                ])
                ->action(function (array $data): void {
                    $stage = $this->order()->stage ?? WorkflowStage::firstFor($this->order()->status);
                    abort_if($stage === null, 422);
                    app(MoveWorkOrder::class)->execute($this->order(), $stage, auth()->user(), $data['note'], array_values($data['photos'] ?? []));
                    Notification::make()->success()->title(__('work_orders.messages.progress_saved'))->send();
                }),
            Action::make('deliver')->extraAttributes(['data-tour' => 'wo-deliver'])
                ->label(__('deliveries.actions.deliver'))
                ->icon(Heroicon::OutlinedKey)
                ->color('success')
                ->visible(fn () => $this->order()->status === WorkOrderStatus::Ready
                    && (bool) auth()->user()?->can('deliver', $this->order()))
                ->url(fn () => WorkOrderResource::getUrl('deliver', ['record' => $this->order()])),
            Action::make('balance')
                ->label(__('payments.actions.charge_balance'))
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('gray')
                ->visible(fn () => (bool) auth()->user()?->can('deliver', $this->order())
                    && BillingSummary::forWorkOrder($this->order())->outstandingCents() > 0
                    && $this->order()->status !== WorkOrderStatus::Cancelled)
                ->modalDescription(fn () => __('payments.messages.outstanding', ['amount' => Money::format(BillingSummary::forWorkOrder($this->order())->outstandingCents())]))
                ->schema([
                    Radio::make('how')->label(__('payments.fields.method'))
                        ->options(fn () => array_filter([
                            'pix_online' => app(PaymentSettings::class)->onlinePixAvailable() ? __('payments.actions.generate_pix') : null,
                            'manual' => __('payments.actions.record_manual'),
                        ]))->default('manual')->required()->live(),
                    Select::make('method')->label(__('payments.fields.method'))
                        ->options(fn () => collect(app(PaymentSettings::class)->manual_methods)->mapWithKeys(fn (string $m) => [$m => PaymentMethod::from($m)->getLabel()])->all())
                        ->visible(fn ($get) => $get('how') === 'manual')->required(fn ($get) => $get('how') === 'manual'),
                    MoneyInput::make('amount_cents')->label(__('payments.fields.amount'))
                        ->default(fn () => number_format(BillingSummary::forWorkOrder($this->order())->outstandingCents() / 100, 2, '.', ''))
                        ->visible(fn ($get) => $get('how') === 'manual'),
                    TextInput::make('email')->label(__('customers.fields.email'))->email()
                        ->default(fn () => $this->order()->vehicle->customer->email)
                        ->visible(fn ($get) => $get('how') === 'pix_online')->required(fn ($get) => $get('how') === 'pix_online'),
                ])
                ->action(function (array $data): void {
                    if ($data['how'] === 'pix_online') {
                        $payment = $this->withFormErrors(fn () => app(RequestPixPayment::class)->balance($this->order(), $data['email']), 'mountedActions.0.data');
                        Notification::make()->success()->title(__('payments.messages.pix_created', ['amount' => Money::format($payment->amount_cents)]))
                            ->body(__('payments.messages.pix_share'))->persistent()->send();

                        return;
                    }
                    $this->withFormErrors(fn () => app(RecordManualPayment::class)->execute(
                        $this->order(), PaymentType::Balance, PaymentMethod::from($data['method']), (int) $data['amount_cents'], auth()->user(),
                    ), 'mountedActions.0.data');
                    Notification::make()->success()->title(__('payments.messages.recorded'))->send();
                }),
            ActionGroup::make([
                Action::make('tracking')
                    ->label(__('tracking.send_link'))
                    ->icon(Heroicon::OutlinedLink)
                    ->visible(fn () => $this->order()->status !== WorkOrderStatus::Cancelled)
                    ->url(fn () => app(TrackingLink::class)->whatsappUrl($this->order()), shouldOpenInNewTab: true),
                Action::make('inspection')
                    ->label(__('inspections.model'))
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->visible(fn () => $this->order()->inspection_id !== null)
                    ->url(fn () => InspectionResource::getUrl('view', ['record' => $this->order()->inspection_id])),
                Action::make('deliveryPdf')
                    ->label(__('deliveries.actions.download'))
                    ->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->visible(fn () => $this->order()->delivery?->report_path !== null)
                    ->url(fn () => URL::temporarySignedRoute('admin.deliveries.report', now()->addMinutes(InspectionReportLink::STAFF_TTL_MINUTES), ['delivery' => $this->order()->delivery]), shouldOpenInNewTab: true),
                Action::make('sendDelivery')
                    ->label(__('deliveries.actions.send'))
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->visible(fn () => $this->order()->delivery !== null && (bool) auth()->user()?->can('deliver', $this->order()))
                    ->schema([
                        Radio::make('channel')->label(__('inspections.fields.channel'))
                            ->options(InspectionResource::channelOptions())->default(ReportChannel::WhatsApp->value)->required(),
                    ])
                    ->action(function (array $data): void {
                        $delivery = $this->order()->delivery;
                        abort_if($delivery === null, 404);
                        $result = $this->withFormErrors(
                            fn () => app(SendReport::class)->execute($delivery, ReportChannel::from($data['channel']), auth()->user()),
                            'mountedActions.0.data',
                        );
                        if ($result['url'] !== null) {
                            Notification::make()->success()->title(__('inspections.messages.sent_whatsapp', ['recipient' => $result['send']->recipient]))->send();
                            $this->js('window.open('.json_encode($result['url']).', "_blank")');
                        } else {
                            Notification::make()->success()->title(__('inspections.messages.sent_email', ['recipient' => $result['send']->recipient]))->send();
                        }
                    }),
                Action::make('cancel')
                    ->label(__('work_orders.actions.cancel'))
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn () => $this->order()->status->canTransitionTo(WorkOrderStatus::Cancelled)
                        && (bool) auth()->user()?->can('update', $this->order()))
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('reason')->label(__('work_orders.fields.cancel_reason'))->required()->rows(2),
                        Toggle::make('confirm')->label(__('work_orders.messages.cancel_confirm'))->accepted(),
                    ])
                    ->action(function (array $data): void {
                        app(CancelWorkOrder::class)->execute($this->order(), $data['reason'], auth()->user());
                        Notification::make()->success()->title(__('work_orders.messages.cancelled'))->send();
                    }),
            ]),
        ];
    }
}
