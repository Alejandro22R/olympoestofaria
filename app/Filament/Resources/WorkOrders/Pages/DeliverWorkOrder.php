<?php

namespace App\Filament\Resources\WorkOrders\Pages;

use App\Domain\Deliveries\Actions\RegisterDelivery;
use App\Domain\Deliveries\Enums\MarkOutcome;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Filament\Concerns\MapsDomainValidation;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Models\InspectionMark;
use App\Models\WorkOrder;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\Unexposed;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Entrega: estado final de cada daño del check-in, kilometraje y notas. Sin firma.
 *
 * @property-read Schema $form
 */
class DeliverWorkOrder extends Page
{
    use InteractsWithRecord;
    use MapsDomainValidation;

    protected static string $resource = WorkOrderResource::class;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();

        $this->form->fill([
            'mileage_km' => $this->order()->vehicle->mileage_km,
            'marks_status' => [],
        ]);
    }

    public function hydrate(): void
    {
        $this->authorizeAccess();
    }

    protected function order(): WorkOrder
    {
        $record = $this->getRecord();
        abort_unless($record instanceof WorkOrder, 404);

        return $record;
    }

    protected function authorizeAccess(): void
    {
        abort_unless(
            $this->order()->status === WorkOrderStatus::Ready && (bool) auth()->user()?->can('deliver', $this->order()),
            403,
        );
    }

    public function getTitle(): string|Htmlable
    {
        return __('deliveries.title', ['number' => $this->order()->number(), 'plate' => $this->order()->vehicle->formattedPlate()]);
    }

    public function getBreadcrumb(): string
    {
        return __('deliveries.actions.deliver');
    }

    #[Unexposed]
    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $marks = $this->order()->inspection?->marks()->with('damageType')->get() ?? collect();

        $markFields = $marks->values()->map(fn (InspectionMark $mark, int $i) => ToggleButtons::make('marks_status.'.$mark->id)
            ->label(($i + 1).'. '.__('inspections.zones.'.$mark->zone).' — '.$mark->damageType->name.($mark->note ? ' ('.$mark->note.')' : ''))
            ->options(MarkOutcome::class)
            ->inline()
            ->required())->all();

        return $schema->components([
            Section::make(__('deliveries.sections.comparison'))
                ->description(__('deliveries.sections.comparison_help'))
                ->schema($markFields !== [] ? $markFields : [
                    Placeholder::make('no_marks')->hiddenLabel()->content(__('deliveries.report.no_marks')),
                ]),
            Section::make(__('deliveries.sections.final_state'))
                ->columns(2)
                ->schema([
                    TextInput::make('mileage_km')->label(__('inspections.fields.mileage_km'))->integer()->minValue(0)->suffix('km'),
                    Textarea::make('notes')->label(__('deliveries.fields.notes'))->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    #[Unexposed]
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('deliver')
                ->footer([
                    Actions::make([
                        Action::make('deliver')->label(__('deliveries.actions.confirm'))->submit('deliver')->color('success'),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function deliver(): void
    {
        $this->authorizeAccess();
        $data = $this->form->getState();

        $this->withFormErrors(fn () => app(RegisterDelivery::class)->execute($this->order(), $data, auth()->user()));

        Notification::make()->success()->title(__('deliveries.messages.delivered'))->send();
        $this->redirect(WorkOrderResource::getUrl('edit', ['record' => $this->order()]));
    }
}
