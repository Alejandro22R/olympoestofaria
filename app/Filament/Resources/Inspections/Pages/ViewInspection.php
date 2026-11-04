<?php

namespace App\Filament\Resources\Inspections\Pages;

use App\Domain\Inspections\Actions\GenerateInspectionReport;
use App\Domain\Inspections\Actions\InspectionReportLink;
use App\Domain\Inspections\Actions\SendInspectionReport;
use App\Domain\Inspections\Enums\ReportChannel;
use App\Filament\Concerns\MapsDomainValidation;
use App\Filament\Resources\Inspections\InspectionResource;
use App\Models\Inspection;
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
