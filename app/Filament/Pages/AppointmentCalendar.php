<?php

namespace App\Filament\Pages;

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Appointments\Services\AgendaService;
use App\Filament\Concerns\HasModuleIntro;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

/**
 * Calendario mensual de recepciones con la ocupación de cada día frente a la capacidad
 * definida en Ajustes > Agenda y los feriados.
 */
class AppointmentCalendar extends Page
{
    use HasModuleIntro;

    protected static function moduleKey(): string
    {
        return 'calendar';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'calendar';

    protected string $view = 'filament.pages.appointment-calendar';

    #[Url]
    public ?string $month = null;

    public static function getNavigationLabel(): string
    {
        return __('appointments.calendar');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.daily');
    }

    public function getTitle(): string|Htmlable
    {
        return __('appointments.calendar');
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('viewAny', Appointment::class);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newAppointment')->label(__('appointments.actions.new'))->icon(Heroicon::OutlinedPlus)
                ->url(fn () => AppointmentResource::getUrl('create'))
                ->visible(fn () => (bool) auth()->user()?->can('create', Appointment::class))
                ->extraAttributes(['data-tour' => 'new-appointment']),
            Action::make('list')->label(__('appointments.actions.list'))->icon(Heroicon::OutlinedListBullet)->color('gray')
                ->url(fn () => AppointmentResource::getUrl('index'))
                ->extraAttributes(['data-tour' => 'appointments-list']),
        ];
    }

    public function mount(): void
    {
        $this->month = $this->currentMonth()->format('Y-m');
    }

    #[On('echo-private:workshop,.appointment.changed')]
    public function refreshCalendar(): void
    {
        // Re-render en vivo cuando se crea, mueve o cambia de estado una cita.
    }

    public function previousMonth(): void
    {
        $this->month = $this->currentMonth()->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = $this->currentMonth()->addMonth()->format('Y-m');
    }

    public function goToToday(): void
    {
        $this->month = CarbonImmutable::now(app(AgendaService::class)->timezone())->format('Y-m');
    }

    private function currentMonth(): CarbonImmutable
    {
        $tz = app(AgendaService::class)->timezone();

        if (is_string($this->month) && preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            return CarbonImmutable::createFromFormat('Y-m-d', $this->month.'-01', $tz)->startOfDay();
        }

        return CarbonImmutable::now($tz)->startOfMonth();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $agenda = app(AgendaService::class);
        $tz = $agenda->timezone();
        $month = $this->currentMonth();
        $gridStart = $month->startOfWeek(CarbonImmutable::MONDAY);
        $gridEnd = $month->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY)->startOfDay();

        $occupancy = $agenda->occupancyForRange($gridStart->toDateString(), $gridEnd->toDateString());

        $appointments = Appointment::query()
            ->with('vehicle')
            ->whereIn('status', AppointmentStatus::occupying())
            ->where('scheduled_at', '>=', $gridStart->utc())
            ->where('scheduled_at', '<', $gridEnd->addDay()->utc())
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn (Appointment $a) => $a->scheduled_at->setTimezone($tz)->toDateString());

        $today = CarbonImmutable::now($tz)->toDateString();
        $canCreate = (bool) auth()->user()?->can('create', Appointment::class);

        $weeks = [];
        for ($day = $gridStart; $day->lte($gridEnd); $day = $day->addDay()) {
            $key = $day->toDateString();
            $info = $occupancy[$key];
            $weeks[(int) floor($gridStart->diffInDays($day) / 7)][] = [
                'date' => $key,
                'day' => $day->day,
                'in_month' => $day->month === $month->month,
                'is_today' => $key === $today,
                'is_past' => $key < $today,
                'capacity' => $info['capacity'],
                'booked' => $info['booked'],
                'holiday' => $info['holiday'],
                'appointments' => ($appointments[$key] ?? collect())->map(fn (Appointment $a) => [
                    'time' => $a->scheduled_at->setTimezone($tz)->format('H:i'),
                    'label' => $a->vehicle->formattedPlate(),
                    'status' => $a->status,
                    'url' => AppointmentResource::getUrl('edit', ['record' => $a]),
                ])->all(),
                'create_url' => $canCreate && $key >= $today && $info['capacity'] > $info['booked']
                    ? AppointmentResource::getUrl('create', ['date' => $key])
                    : null,
            ];
        }

        return [
            'monthLabel' => $month->locale(app()->getLocale())->isoFormat('MMMM YYYY'),
            'weekdays' => collect(range(1, 7))->map(fn (int $d) => mb_substr(__('settings.weekdays.'.$d), 0, 3))->all(),
            'weeks' => $weeks,
        ];
    }
}
