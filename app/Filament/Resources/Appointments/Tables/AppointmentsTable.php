<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Models\Appointment;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('vehicle.customer'))
            ->defaultSort('scheduled_at')
            ->columns([
                TextColumn::make('scheduled_at')->label(__('appointments.fields.scheduled_at'))
                    ->dateTime('D d/m/Y H:i')->sortable(),
                TextColumn::make('vehicle.plate')->label(__('appointments.fields.vehicle'))
                    ->formatStateUsing(fn (Appointment $record) => $record->vehicle->label())
                    ->searchable(),
                TextColumn::make('vehicle.customer.name')->label(__('appointments.fields.customer'))->searchable(),
                TextColumn::make('status')->label(__('appointments.fields.status'))->badge(),
                TextColumn::make('promised_delivery_at')->label(__('appointments.fields.promised_delivery_at'))
                    ->dateTime('d/m/Y H:i')->placeholder('—')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('appointments.fields.status'))
                    ->options(AppointmentStatus::class)->multiple(),
                Filter::make('upcoming')
                    ->label(__('appointments.upcoming'))
                    ->query(fn (Builder $query) => $query->where('scheduled_at', '>=', now()->startOfDay()))
                    ->default(),
                Filter::make('period')
                    ->schema([
                        DatePicker::make('from')->label(__('appointments.from')),
                        DatePicker::make('until')->label(__('appointments.until')),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('scheduled_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('scheduled_at', '<=', $d))),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
