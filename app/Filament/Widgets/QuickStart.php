<?php

namespace App\Filament\Widgets;

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Quotes\Enums\QuoteStatus;
use App\Domain\Settings\GeneralSettings;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Filament\Pages\AppointmentCalendar;
use App\Filament\Pages\ProductionBoard;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Inspection;
use App\Models\Project;
use App\Models\Quote;
use App\Models\WorkOrder;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;
use Illuminate\Support\Str;

/**
 * Inicio del panel: saludo, accesos rápidos a lo que cada rol hace a diario y "Hoy"
 * (recepciones del día, autos listos, solicitudes nuevas y cotizaciones esperando respuesta).
 * Solo muestra lo que el usuario puede usar.
 */
class QuickStart extends Widget
{
    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.quick-start';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        $tz = app(GeneralSettings::class)->timezone;
        $now = CarbonImmutable::now($tz);
        $can = fn (string $ability, string $model) => (bool) $user?->can($ability, $model);

        $actions = array_values(array_filter([
            $can('create', Appointment::class) ? ['label' => __('dashboard.actions.appointment'), 'hint' => __('dashboard.actions.appointment_hint'), 'icon' => 'heroicon-o-calendar-days', 'url' => AppointmentResource::getUrl('create'), 'tour' => 'qa-appointment', 'tone' => 'primary'] : null,
            $can('viewAny', WorkOrder::class) ? ['label' => __('dashboard.actions.board'), 'hint' => __('dashboard.actions.board_hint'), 'icon' => 'heroicon-o-view-columns', 'url' => ProductionBoard::getUrl(), 'tour' => 'qa-board', 'tone' => 'navy'] : null,
            $can('create', Quote::class) ? ['label' => __('dashboard.actions.quote'), 'hint' => __('dashboard.actions.quote_hint'), 'icon' => 'heroicon-o-document-currency-dollar', 'url' => QuoteResource::getUrl('create'), 'tour' => 'qa-quote', 'tone' => 'success'] : null,
            $can('create', Customer::class) ? ['label' => __('dashboard.actions.customer'), 'hint' => __('dashboard.actions.customer_hint'), 'icon' => 'heroicon-o-user-plus', 'url' => CustomerResource::getUrl('create'), 'tour' => 'qa-customer', 'tone' => 'warning'] : null,
        ]));

        $start = $now->startOfDay()->utc();
        $end = $now->endOfDay()->utc();

        $today = $can('viewAny', Appointment::class)
            ? Appointment::query()->with('vehicle.customer')->whereBetween('scheduled_at', [$start, $end])
                ->whereIn('status', [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed, AppointmentStatus::CheckedIn])
                ->orderBy('scheduled_at')->limit(6)->get()
            : collect();

        $cards = array_values(array_filter([
            $can('viewAny', WorkOrder::class) ? [
                'label' => __('dashboard.today.ready'), 'icon' => 'heroicon-o-check-badge', 'color' => 'success',
                'count' => WorkOrder::query()->where('status', WorkOrderStatus::Ready)->count(),
                'url' => WorkOrderResource::getUrl('index', ['filters' => ['status' => ['values' => [WorkOrderStatus::Ready->value]]]]),
            ] : null,
            $can('viewAny', WorkOrder::class) ? [
                'label' => __('dashboard.today.late'), 'icon' => 'heroicon-o-clock', 'color' => 'danger',
                'count' => WorkOrder::query()->whereIn('status', WorkOrderStatus::onBoard())->where('due_at', '<', now())->count(),
                'url' => WorkOrderResource::getUrl('index', ['filters' => ['late' => ['isActive' => true]]]),
            ] : null,
            $can('viewAny', Project::class) ? [
                'label' => __('dashboard.today.leads'), 'icon' => 'heroicon-o-sparkles', 'color' => 'warning',
                'count' => Project::query()->where('status', ProjectStatus::Submitted)->count(),
                'url' => ProjectResource::getUrl('index'),
            ] : null,
            $can('viewAny', Quote::class) ? [
                'label' => __('dashboard.today.quotes'), 'icon' => 'heroicon-o-document-text', 'color' => 'info',
                'count' => Quote::query()->where('status', QuoteStatus::Sent)->count(),
                'url' => QuoteResource::getUrl('index'),
            ] : null,
        ]));

        $hour = (int) $now->format('H');

        return [
            'greeting' => __($hour < 12 ? 'dashboard.greeting.morning' : ($hour < 18 ? 'dashboard.greeting.afternoon' : 'dashboard.greeting.evening'), ['name' => strtok((string) $user?->name, ' ')]),
            'date' => Str::ucfirst($now->locale(app()->getLocale())->isoFormat('dddd, D [de] MMMM')),
            'actions' => $actions,
            'appointments' => $today,
            'calendarUrl' => $can('viewAny', Appointment::class) ? AppointmentCalendar::getUrl() : null,
            'canCheckIn' => (bool) $user?->can('create', Inspection::class),
            'cards' => $cards,
            'tz' => $tz,
        ];
    }
}
