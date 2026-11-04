<?php

namespace App\Filament\Resources\Inspections\Pages;

use App\Domain\Inspections\Actions\GenerateInspectionReport;
use App\Domain\Inspections\Actions\InspectionReportLink;
use App\Domain\Inspections\Actions\SendInspectionReport;
use App\Domain\Inspections\Enums\ReportChannel;
use App\Domain\WorkOrders\Actions\CreateWorkOrder;
use App\Filament\Concerns\MapsDomainValidation;
use App\Filament\Resources\Inspections\InspectionResource;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Models\Inspection;
use App\Models\WorkOrder;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property Inspection $record
 */
class ViewInspection extends ViewRecord
{
    use MapsDomainValidation;

    protected static string $resource = InspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createWorkOrder')
                ->label(__('work_orders.actions.create_from_inspection'))
                ->icon(Heroicon::OutlinedWrench)
                ->visible(fn () => ! WorkOrder::query()->where('inspection_id', $this->record->id)->exists()
                    && (bool) auth()->user()?->can('create', WorkOrder::class))
                ->action(function (): void {
                    $order = app(CreateWorkOrder::class)->execute([], $this->record, auth()->user());
                    $this->redirect(WorkOrderResource::getUrl('edit', ['record' => $order]));
                }),
            Action::make('workOrder')
                ->label(__('work_orders.model'))
                ->icon(Heroicon::OutlinedWrench)
                ->color('gray')
                ->visible(fn () => WorkOrder::query()->where('inspection_id', $this->record->id)->exists())
                ->url(fn () => WorkOrderResource::getUrl('edit', ['record' => WorkOrder::query()->where('inspection_id', $this->record->id)->value('id')])),
            Action::make('download')
                ->label(__('inspections.actions.download'))
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->visible(fn () => $this->record->hasReport())
                ->url(fn () => app(InspectionReportLink::class)->forStaff($this->record), shouldOpenInNewTab: true),
            Action::make('regenerate')
                ->label(__('inspections.actions.regenerate'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->visible(fn () => (bool) auth()->user()?->can('update', $this->record))
                ->requiresConfirmation()
                ->action(function (): void {
                    app(GenerateInspectionReport::class)->execute($this->record);
                    Notification::make()->success()->title(__('inspections.messages.regenerated'))->send();
                }),
            Action::make('send')
                ->label(__('inspections.actions.send'))
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->visible(fn () => (bool) auth()->user()?->can('update', $this->record))
                ->schema([
                    Radio::make('channel')->label(__('inspections.fields.channel'))
                        ->options(InspectionResource::channelOptions())
                        ->default(ReportChannel::WhatsApp->value)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $channel = ReportChannel::from($data['channel']);
                    $result = $this->withFormErrors(
                        fn () => app(SendInspectionReport::class)->execute($this->record, $channel, auth()->user()),
                        'mountedActions.0.data',
                    );

                    $recipient = $result['send']->recipient;
                    if ($result['url'] !== null) {
                        Notification::make()->success()
                            ->title(__('inspections.messages.sent_whatsapp', ['recipient' => $recipient]))
                            ->actions([
                                Action::make('open')->label(__('inspections.messages.open_whatsapp'))
                                    ->url($result['url'], shouldOpenInNewTab: true),
                            ])
                            ->persistent()
                            ->send();
                        $this->js('window.open('.json_encode($result['url']).', "_blank")');
                    } else {
                        Notification::make()->success()->title(__('inspections.messages.sent_email', ['recipient' => $recipient]))->send();
                    }

                    $this->record->load('sends.sender');
                }),
        ];
    }
}
