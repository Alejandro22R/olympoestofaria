<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Settings\BackupSettings;
use App\Domain\Settings\SettingsTransfer;
use App\Domain\System\BackupStatus;
use App\Domain\System\MaintenanceMode;
use App\Domain\System\SystemHealth;
use App\Filament\Clusters\Settings\SettingsPage;
use App\Jobs\RunSystemTask;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ajustes > Sistema > Respaldos y salud: frecuencia y destino de las copias, copia manual, prueba
 * de restauración, estado del sistema, modo mantenimiento y exportar/importar la configuración.
 */
class ManageBackupSettings extends SettingsPage
{
    protected static string $settings = BackupSettings::class;

    protected static ?int $navigationSort = 53;

    protected static ?string $slug = 'backups';

    public static function getSectionGroup(): string
    {
        return 'system';
    }

    public static function getNavigationLabel(): string
    {
        return __('system.title');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['run_at'] = substr((string) $data['run_at'], 0, 5);
        $data['disks'] = array_values(array_intersect((array) ($data['disks'] ?? []), (array) config('olympo.backup_disks')));
        $data['weekday'] = (int) $data['weekday'];

        return $data;
    }

    protected function getHeaderActions(): array
    {
        $maintenance = app(MaintenanceMode::class);

        return [
            Action::make('backupNow')->label(__('system.actions.backup_now'))->icon(Heroicon::OutlinedCircleStack)
                ->requiresConfirmation()
                ->action(function (): void {
                    RunSystemTask::dispatch('backup', auth()->user());
                    Notification::make()->info()->title(__('system.tasks.started'))->send();
                }),
            Action::make('restoreTest')->label(__('system.actions.restore_test'))->icon(Heroicon::OutlinedArrowPath)->color('gray')
                ->requiresConfirmation()->modalDescription(__('system.actions.restore_test_help'))
                ->action(function (): void {
                    RunSystemTask::dispatch('restore_test', auth()->user());
                    Notification::make()->info()->title(__('system.tasks.started'))->send();
                }),
            Action::make('maintenance')
                ->label(fn () => $maintenance->active() ? __('system.actions.maintenance_off') : __('system.actions.maintenance_on'))
                ->icon(Heroicon::OutlinedWrenchScrewdriver)
                ->color(fn () => $maintenance->active() ? 'success' : 'danger')
                ->requiresConfirmation()
                ->modalDescription(fn () => $maintenance->active() ? null : __('system.actions.maintenance_help'))
                ->action(function () use ($maintenance): void {
                    $maintenance->active() ? $maintenance->disable(auth()->user()) : $maintenance->enable(auth()->user());
                    Notification::make()->success()->title($maintenance->active() ? __('system.health.maintenance_on') : __('system.health.maintenance_off'))->send();
                }),
            ActionGroup::make([
                Action::make('exportSettings')->label(__('system.actions.export_settings'))->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn (): StreamedResponse => response()->streamDownload(
                        fn () => print (json_encode(app(SettingsTransfer::class)->export(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                        'olympo-ajustes-'.now()->format('Ymd-His').'.json',
                        ['Content-Type' => 'application/json'],
                    )),
                Action::make('importSettings')->label(__('system.actions.import_settings'))->icon(Heroicon::OutlinedArrowUpTray)
                    ->modalDescription(__('system.actions.import_help'))
                    ->schema([
                        FileUpload::make('file')->label('JSON')->storeFiles(false)->required()
                            ->acceptedFileTypes(['application/json', 'text/plain'])->maxSize(1024),
                    ])
                    ->action(function (array $data): void {
                        /** @var TemporaryUploadedFile $file */
                        $file = $data['file'];
                        $payload = json_decode((string) file_get_contents($file->getRealPath()), true);
                        try {
                            $groups = app(SettingsTransfer::class)->import(is_array($payload) ? $payload : []);
                        } catch (ValidationException $e) {
                            Notification::make()->danger()->title(collect($e->errors())->flatten()->first())->send();

                            return;
                        }
                        $this->fillForm();
                        Notification::make()->success()->title(__('system.transfer.imported', ['count' => count($groups)]))->send();
                    }),
                Action::make('horizon')->label(__('system.actions.horizon'))->icon(Heroicon::OutlinedQueueList)
                    ->url(url('/horizon'), shouldOpenInNewTab: true),
            ])->label(__('system.actions.more'))->button()->color('gray'),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $disks = collect((array) config('olympo.backup_disks'))->mapWithKeys(fn (string $d) => [$d => __('system.disks.'.$d)])->all();
        $days = collect(range(1, 7))->mapWithKeys(fn (int $d) => [$d => __('settings.weekdays.'.$d)])->all();

        $health = collect(app(SystemHealth::class)->checks())->map(fn (array $check, string $key) => Grid::make(3)->schema([
            TextEntry::make("health_{$key}")->hiddenLabel()->state(__('system.health.checks.'.$key))->weight('bold'),
            TextEntry::make("health_{$key}_state")->hiddenLabel()->badge()
                ->state(match ($check['ok']) {
                    true => __('system.health.ok'), false => __('system.health.attention'), default => __('system.health.info')
                })
                ->color(match ($check['ok']) {
                    true => 'success', false => 'danger', default => 'gray'
                }),
            TextEntry::make("health_{$key}_msg")->hiddenLabel()->state($check['message'])->color('gray'),
        ]))->values()->all();

        $copies = collect(app(BackupStatus::class)->destinations())->map(fn (array $row) => TextEntry::make('copies_'.$row['disk'])
            ->label(__('system.disks.'.$row['disk']))
            ->state($row['error'] ?? __('system.backup.copies', [
                'count' => $row['count'],
                'date' => $row['newest'] ? Carbon::parse($row['newest'])->timezone(FilamentTimezone::get())->format('d/m/Y H:i') : '—',
            ])))->all();

        return $schema->components([
            Section::make(__('system.health.title'))->description(__('system.health.help'))->schema($health),
            Section::make(__('system.backup.title'))->columns(2)->schema([
                Toggle::make('enabled')->label(__('system.backup.enabled'))->live()->columnSpanFull(),
                Radio::make('frequency')->label(__('system.backup.frequency'))
                    ->options(['daily' => __('system.backup.daily'), 'weekly' => __('system.backup.weekly')])->inline()->required()->live(),
                TimePicker::make('run_at')->label(__('system.backup.run_at'))->seconds(false)->timezone('UTC')->required(),
                Select::make('weekday')->label(__('system.backup.weekday'))->options($days)->native(false)
                    ->visible(fn (Get $get) => $get('frequency') === 'weekly')->required(fn (Get $get) => $get('frequency') === 'weekly'),
                TextInput::make('keep_days')->label(__('system.backup.keep_days'))->integer()->required()->minValue(3)->maxValue(365)
                    ->suffix(__('settings.units.days')),
                CheckboxList::make('disks')->label(__('system.backup.disks'))->options($disks)->required()->minItems(1)
                    ->helperText(__('system.backup.disks_help')),
                Toggle::make('include_files')->label(__('system.backup.include_files'))->helperText(__('system.backup.include_files_help')),
                ...$copies,
            ]),
        ]);
    }
}
