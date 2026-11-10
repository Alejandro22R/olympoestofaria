<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Inspections\Actions\RegisterCheckIn;
use App\Domain\Inspections\Enums\FuelLevel;
use App\Domain\Inspections\Support\VehicleSilhouettes;
use App\Domain\Settings\CheckinSettings;
use App\Filament\Concerns\MapsDomainValidation;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Inspections\InspectionResource;
use App\Models\Appointment;
use App\Models\ChecklistItem;
use App\Models\DamageType;
use App\Models\Inspection;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\Unexposed;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Check-in de una cita: estado del vehículo, diagrama de daños y consentimiento LGPD (sin firma).
 *
 * @property-read Schema $form
 */
class CheckInAppointment extends Page
{
    use InteractsWithRecord;
    use MapsDomainValidation;

    protected static string $resource = AppointmentResource::class;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();

        $this->form->fill([
            'mileage_km' => $this->appointment()->vehicle->mileage_km,
            'items_present' => [],
            'marks' => [],
        ]);
    }

    protected function appointment(): Appointment
    {
        $record = $this->getRecord();
        abort_unless($record instanceof Appointment, 404);

        return $record;
    }

    public function hydrate(): void
    {
        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::canCheckIn($this->appointment()), 403);
    }

    public static function canCheckIn(Appointment $appointment): bool
    {
        return (bool) auth()->user()?->can('create', Inspection::class)
            && in_array($appointment->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true)
            && ! $appointment->inspection()->exists();
    }

    public function getTitle(): string|Htmlable
    {
        return __('inspections.check_in_title', ['plate' => $this->appointment()->vehicle->formattedPlate()]);
    }

    public function getBreadcrumb(): string
    {
        return __('inspections.check_in');
    }

    #[Unexposed]
    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $settings = app(CheckinSettings::class);
        $silhouette = VehicleSilhouettes::forVehicle($this->appointment()->vehicle);

        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Section::make(__('inspections.sections.vehicle_state'))->extraAttributes(['data-tour' => 'checkin-state'])
                    ->schema([
                        TextInput::make('mileage_km')->label(__('inspections.fields.mileage_km'))
                            ->integer()->minValue(0)->maxValue(5_000_000)->suffix('km')
                            ->required($settings->mileage_required),
                        ToggleButtons::make('fuel_level')->label(__('inspections.fields.fuel_level'))
                            ->options(FuelLevel::class)->inline()->required(),
                        CheckboxList::make('items_present')->label(__('inspections.fields.items_present'))
                            ->options(fn () => ChecklistItem::query()->where('is_active', true)->orderBy('sort')->get()
                                ->mapWithKeys(fn (ChecklistItem $i) => [$i->id => $i->name])->all())
                            ->columns(2),
                        Textarea::make('observations')->label(__('inspections.fields.observations'))->rows(3),
                    ]),
                Section::make(__('inspections.sections.diagram'))->extraAttributes(['data-tour' => 'checkin-diagram'])
                    ->description(__('inspections.sections.diagram_help'))
                    ->schema([
                        ViewField::make('diagram')
                            ->view('filament.forms.damage-diagram', [
                                'outline' => VehicleSilhouettes::outline($silhouette),
                                'zones' => VehicleSilhouettes::zones($silhouette),
                                'zoneLabels' => VehicleSilhouettes::options($silhouette),
                                'viewBox' => VehicleSilhouettes::viewBox($silhouette),
                                'colors' => DamageType::query()->pluck('color_hex', 'id')->all(),
                            ])
                            ->hiddenLabel()
                            ->dehydrated(false),
                    ]),
            ]),
            Section::make(__('inspections.fields.marks'))
                ->schema([
                    Repeater::make('marks')->hiddenLabel()
                        ->addActionLabel(__('inspections.actions.add_mark'))
                        ->maxItems($settings->max_marks)
                        ->defaultItems(0)
                        ->itemLabel(fn (array $state): ?string => VehicleSilhouettes::options($silhouette)[$state['zone'] ?? ''] ?? null)
                        ->columns(['default' => 1, 'md' => 3])
                        ->schema([
                            Select::make('zone')->label(__('inspections.fields.zone'))
                                ->options(VehicleSilhouettes::options($silhouette))->required()->native(false),
                            Select::make('damage_type_id')->label(__('inspections.fields.damage_type'))
                                ->options(fn () => DamageType::query()->where('is_active', true)->orderBy('sort')->get()
                                    ->mapWithKeys(fn (DamageType $t) => [$t->id => $t->name])->all())
                                ->required()->native(false)->live(),
                            TextInput::make('note')->label(__('inspections.fields.note'))->maxLength(500),
                            FileUpload::make('photos')->label(__('inspections.fields.photos'))
                                ->disk((string) config('olympo.private_disk'))
                                ->directory(RegisterCheckIn::PHOTO_DIRECTORY)
                                ->visibility('private')
                                ->image()
                                ->multiple()
                                ->maxFiles($settings->max_photos_per_mark)
                                ->maxSize(8 * 1024)
                                ->columnSpanFull(),
                            Hidden::make('x'),
                            Hidden::make('y'),
                        ]),
                ]),
            Section::make(__('inspections.sections.consent'))->extraAttributes(['data-tour' => 'checkin-consent'])
                ->schema([
                    Checkbox::make('lgpd_consent')->label($settings->consentText())->accepted()
                        ->validationMessages(['accepted' => __('inspections.errors.consent_required')]),
                ]),
        ]);
    }

    #[Unexposed]
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('register')
                ->footer([
                    Actions::make([
                        Action::make('register')
                            ->label(__('inspections.actions.register'))
                            ->submit('register'),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function register(): void
    {
        $this->authorizeAccess();

        $data = $this->form->getState();

        $inspection = $this->withFormErrors(
            fn () => app(RegisterCheckIn::class)->execute($this->appointment(), $data, auth()->user()),
        );

        Notification::make()->success()->title(__('inspections.messages.registered'))->send();

        $this->redirect(InspectionResource::getUrl('view', ['record' => $inspection]));
    }
}
