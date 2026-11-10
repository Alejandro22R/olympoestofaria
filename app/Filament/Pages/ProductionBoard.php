<?php

namespace App\Filament\Pages;

use App\Domain\WorkOrders\Actions\MoveWorkOrder;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Filament\Concerns\HasModuleIntro;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Models\WorkflowStage;
use App\Models\WorkOrder;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

/**
 * Tablero de producción (kanban) por etapas. Se sincroniza en vivo entre pantallas
 * por el canal privado "workshop" (Reverb) y tiene un refresco de respaldo cada 30 s.
 */
class ProductionBoard extends Page
{
    use HasModuleIntro;

    protected static function moduleKey(): string
    {
        return 'board';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'production';

    protected string $view = 'filament.pages.production-board';

    protected Width|string|null $maxContentWidth = Width::Full;

    public static function getNavigationLabel(): string
    {
        return __('work_orders.board');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.daily');
    }

    public function getTitle(): string|Htmlable
    {
        return __('work_orders.board');
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('viewAny', WorkOrder::class);
    }

    #[On('echo-private:workshop,.work-order.moved')]
    public function refreshBoard(): void
    {
        // Re-render: los datos se leen en getViewData().
    }

    public function moveCard(int $workOrderId, int $stageId): void
    {
        $order = WorkOrder::query()->findOrFail($workOrderId);
        abort_unless((bool) auth()->user()?->can('progress', $order), 403);
        $stage = WorkflowStage::query()->findOrFail($stageId);

        try {
            app(MoveWorkOrder::class)->execute($order, $stage, auth()->user());
        } catch (ValidationException $e) {
            Notification::make()->danger()->title(__('work_orders.messages.cannot_move'))
                ->body(collect($e->errors())->flatten()->implode(' '))->persistent()->send();

            return;
        }

        Notification::make()->success()->title(__('work_orders.messages.moved', ['stage' => $stage->name]))->send();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $stages = WorkflowStage::query()->ordered()->get();
        $tz = FilamentTimezone::get();

        $orders = WorkOrder::query()
            ->with(['vehicle.customer', 'responsible'])
            ->withCount('items')
            ->whereIn('status', WorkOrderStatus::onBoard())
            ->orderByRaw('due_at IS NULL, due_at')
            ->get()
            ->groupBy('workflow_stage_id');

        $user = auth()->user();

        return [
            'columns' => $stages->map(fn (WorkflowStage $stage) => [
                'id' => $stage->id,
                'name' => $stage->name,
                'status' => $stage->status,
                'color' => $stage->color_hex,
                'cards' => ($orders[$stage->id] ?? collect())->map(fn (WorkOrder $o) => [
                    'id' => $o->id,
                    'number' => $o->number(),
                    'plate' => $o->vehicle->formattedPlate(),
                    'vehicle' => $o->vehicle->brand.' '.$o->vehicle->model,
                    'customer' => $o->vehicle->customer->name,
                    'responsible' => $o->responsible?->name,
                    'due' => $o->due_at?->setTimezone($tz)->format('d/m H:i'),
                    'late' => $o->isLate(),
                    'items' => $o->items_count,
                    'url' => WorkOrderResource::getUrl('edit', ['record' => $o]),
                    'can_move' => (bool) $user?->can('progress', $o),
                    'targets' => $stages->filter(fn (WorkflowStage $s) => $s->id !== $o->workflow_stage_id
                        && ($s->status === $o->status || $o->status->canTransitionTo($s->status)))
                        ->mapWithKeys(fn (WorkflowStage $s) => [$s->id => $s->name])->all(),
                ])->values()->all(),
            ])->all(),
        ];
    }
}
