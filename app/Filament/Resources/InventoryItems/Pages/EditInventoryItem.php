<?php

namespace App\Filament\Resources\InventoryItems\Pages;

use App\Domain\Inventory\Actions\RecordInventoryMovement;
use App\Domain\Inventory\Enums\MovementType;
use App\Filament\Concerns\MapsDomainValidation;
use App\Filament\Resources\InventoryItems\InventoryItemResource;
use App\Models\InventoryItem;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditInventoryItem extends EditRecord
{
    use MapsDomainValidation;

    protected static string $resource = InventoryItemResource::class;

    protected function item(): InventoryItem
    {
        $record = $this->getRecord();
        abort_unless($record instanceof InventoryItem, 404);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->movementAction('stockIn', MovementType::In, Heroicon::OutlinedArrowDownTray, 'success'),
            $this->movementAction('adjust', MovementType::Adjustment, Heroicon::OutlinedAdjustmentsHorizontal, 'gray'),
        ];
    }

    private function movementAction(string $name, MovementType $type, Heroicon $icon, string $color): Action
    {
        return Action::make($name)
            ->label(__('inventory.actions.'.$name))
            ->icon($icon)
            ->color($color)
            ->visible(fn () => (bool) auth()->user()?->can('update', $this->item()))
            ->schema([
                TextInput::make('quantity')->label($type === MovementType::Adjustment ? __('inventory.fields.adjust_quantity') : __('inventory.fields.quantity'))
                    ->helperText($type === MovementType::Adjustment ? __('inventory.messages.adjust_help') : null)
                    ->numeric()->required()->step('0.001'),
                TextInput::make('note')->label(__('inventory.fields.note'))->maxLength(200)->required($type === MovementType::Adjustment),
            ])
            ->action(function (array $data) use ($type): void {
                $this->withFormErrors(
                    fn () => app(RecordInventoryMovement::class)->execute($this->item(), $type, (float) $data['quantity'], auth()->user(), null, $data['note'] ?? null),
                    'mountedActions.0.data',
                );
                Notification::make()->success()->title(__('inventory.messages.recorded'))->send();
                $this->refreshFormData(['stock_info']);
            });
    }
}
