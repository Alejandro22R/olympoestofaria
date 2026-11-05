<?php

namespace App\Filament\Resources\Activities\Pages;

use App\Domain\Settings\AuditSettings;
use App\Filament\Resources\Activities\ActivityResource;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label(__('system.audit.export'))->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->action(fn (): StreamedResponse => $this->exportCsv()),
            Action::make('retention')->label(__('system.audit.retention'))->icon(Heroicon::OutlinedClock)->color('gray')
                ->fillForm(fn () => ['retention_days' => app(AuditSettings::class)->retention_days])
                ->schema([
                    TextInput::make('retention_days')->label(__('system.audit.retention_days'))
                        ->helperText(__('system.audit.retention_help'))
                        ->integer()->required()->minValue(180)->maxValue(3650)->suffix(__('settings.units.days')),
                ])
                ->action(function (array $data): void {
                    $settings = app(AuditSettings::class);
                    $settings->retention_days = (int) $data['retention_days'];
                    $settings->save();
                    Notification::make()->success()->title(__('settings.saved'))->send();
                }),
        ];
    }

    /**
     * Exporta lo que la tabla muestra con los filtros actuales (máx. 50 000 filas).
     */
    private function exportCsv(): StreamedResponse
    {
        /** @var Builder<Activity> $query */
        $query = $this->getFilteredSortedTableQuery();
        activity('system')->causedBy(auth()->user())->event('exported')->log('audit.exported');

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'created_at', 'causer', 'log', 'event', 'subject_type', 'subject_id', 'description', 'properties']);
            foreach ($query->limit(50000)->cursor() as $a) {
                fputcsv($out, [
                    $a->id, $a->created_at?->toIso8601String(), $a->causer?->getAttribute('name'), $a->log_name, $a->event,
                    ActivityResource::subjectLabel($a->subject_type), $a->subject_id, $a->description, json_encode($a->properties, JSON_UNESCAPED_UNICODE),
                ]);
            }
            fclose($out);
        }, 'olympo-auditoria-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
