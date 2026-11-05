<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Domain\Projects\Actions\ChangeProjectStatus;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Filament\Concerns\MapsDomainValidation;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewProject extends ViewRecord
{
    use MapsDomainValidation;

    protected static string $resource = ProjectResource::class;

    protected function project(): Project
    {
        $record = $this->getRecord();
        abort_unless($record instanceof Project, 404);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('whatsapp')
                ->label(__('projects.actions.whatsapp'))
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('success')
                ->url(fn () => 'https://wa.me/'.ltrim($this->project()->customer->phone, '+').'?text='.rawurlencode(__('projects.whatsapp.reply', [
                    'name' => $this->project()->customer->name,
                    'number' => $this->project()->number(),
                    'vehicle' => $this->project()->vehicleLabel(),
                ])), shouldOpenInNewTab: true),
            Action::make('contacted')
                ->label(__('projects.actions.contacted'))
                ->icon(Heroicon::OutlinedCheck)
                ->visible(fn () => $this->project()->status->canTransitionTo(ProjectStatus::Contacted) && (bool) auth()->user()?->can('update', $this->project()))
                ->action(function (): void {
                    app(ChangeProjectStatus::class)->execute($this->project(), ProjectStatus::Contacted);
                    Notification::make()->success()->title(ProjectStatus::Contacted->getLabel())->send();
                }),
            Action::make('lost')
                ->label(__('projects.actions.lost'))
                ->icon(Heroicon::OutlinedXMark)
                ->color('gray')
                ->visible(fn () => $this->project()->status->canTransitionTo(ProjectStatus::Lost) && (bool) auth()->user()?->can('update', $this->project()))
                ->schema([Textarea::make('reason')->label(__('projects.fields.lost_reason'))->required()->maxLength(255)])
                ->action(function (array $data): void {
                    $this->withFormErrors(fn () => app(ChangeProjectStatus::class)->execute($this->project(), ProjectStatus::Lost, $data['reason']), 'mountedActions.0.data');
                    Notification::make()->success()->title(ProjectStatus::Lost->getLabel())->send();
                }),
        ];
    }
}
