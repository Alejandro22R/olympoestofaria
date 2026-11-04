<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Appointments\Services\AgendaService;
use App\Models\Appointment;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentTimezone;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('vehicle_id')->label(__('appointments.fields.vehicle'))
                    ->relationship('vehicle', 'plate', fn ($query) => $query->with('customer'))
                    ->getOptionLabelFromRecordUsing(fn (Vehicle $record) => $record->label().' — '.$record->customer->name)
                    ->searchable(['plate', 'brand', 'model'])
                    ->preload()
                    ->required()
                    ->columnSpanFull(),
                DateTimePicker::make('scheduled_at')->label(__('appointments.fields.scheduled_at'))
                    ->seconds(false)
                    ->minutesStep(15)
                    ->required()
                    ->live(onBlur: true)
                    ->helperText(fn (Get $get, ?Appointment $record) => self::availabilityHint($get('scheduled_at'), $record)),
                DateTimePicker::make('promised_delivery_at')->label(__('appointments.fields.promised_delivery_at'))
                    ->seconds(false)
                    ->afterOrEqual('scheduled_at'),
                Select::make('status')->label(__('appointments.fields.status'))
                    ->options(fn (?Appointment $record) => self::statusOptions($record))
                    ->visibleOn('edit')
                    ->required()
                    ->native(false),
                Textarea::make('notes')->label(__('appointments.fields.notes'))->rows(3)->columnSpanFull(),
            ]);
    }

    /**
     * Estado actual más los permitidos por la máquina de estados.
     *
     * @return array<string, string>
     */
    public static function statusOptions(?Appointment $record): array
    {
        $current = $record->status ?? AppointmentStatus::Scheduled;

        return collect([$current, ...$current->allowedTransitions()])
            ->mapWithKeys(fn (AppointmentStatus $s) => [$s->value => $s->getLabel()])
            ->all();
    }

    public static function availabilityHint(?string $value, ?Appointment $record): ?string
    {
        if (blank($value)) {
            return null;
        }

        $agenda = app(AgendaService::class);
        // El picker entrega la hora local del taller.
        $day = CarbonImmutable::parse($value, FilamentTimezone::get())->toDateString();
        $capacity = $agenda->capacityFor($day);

        if ($capacity === 0) {
            return $agenda->holidayOn($day)->name ?? __('appointments.closed');
        }

        $remaining = $agenda->remainingOn($day, null, $record?->getKey());

        return $remaining === 0
            ? __('appointments.full')
            : __('appointments.availability', ['remaining' => $remaining, 'capacity' => $capacity]);
    }
}
