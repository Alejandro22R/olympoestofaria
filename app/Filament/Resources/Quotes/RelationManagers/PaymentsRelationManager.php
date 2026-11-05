<?php

namespace App\Filament\Resources\Quotes\RelationManagers;

use App\Filament\Support\Money;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('payments.plural');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('inspections.fields.created_at'))->dateTime('d/m/Y H:i'),
                TextColumn::make('type')->label(__('payments.fields.type'))->badge(),
                TextColumn::make('method')->label(__('payments.fields.method')),
                TextColumn::make('provider')->label(__('payments.fields.provider')),
                TextColumn::make('amount_cents')->label(__('payments.fields.amount'))->formatStateUsing(fn (int $state) => Money::format($state)),
                TextColumn::make('status')->label(__('payments.fields.status'))->badge(),
                TextColumn::make('paid_at')->label(__('payments.fields.paid_at'))->dateTime('d/m/Y H:i')->placeholder('—'),
            ]);
    }
}
