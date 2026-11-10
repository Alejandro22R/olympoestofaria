<?php

namespace App\Filament\Resources\Payments;

use App\Domain\Payments\Actions\ApplyPaymentStatus;
use App\Domain\Payments\Enums\PaymentProvider;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Gateways\PaymentGateway;
use App\Domain\Payments\Gateways\PaymentGatewayException;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Support\CardList;
use App\Filament\Support\Money;
use App\Models\Payment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Pagos (solo lectura): se crean desde la cotización u orden. Permite consultar un Pix en Mercado Pago.
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 12;

    public static function getModelLabel(): string
    {
        return __('payments.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payments.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.sales');
    }

    public static function table(Table $table): Table
    {
        return CardList::grid($table, 'payments')
            ->modifyQueryUsing(fn ($query) => $query->with(['customer', 'quote', 'workOrder', 'recorder']))
            ->defaultSort('id', 'desc')
            ->columns([
                CardList::card(
                    TextColumn::make('amount_cents')->label(__('payments.fields.amount'))->formatStateUsing(fn (int $state) => Money::format($state))->sortable()
                        ->extraAttributes(['class' => 'font-display']),
                    TextColumn::make('status')->label(__('payments.fields.status')),
                    [
                        CardList::detail(TextColumn::make('customer.name')->label(__('quotes.fields.customer'))->searchable(), 'heroicon-m-user'),
                        CardList::detail(TextColumn::make('type')->label(__('payments.fields.type'))
                            ->formatStateUsing(fn (Payment $record) => $record->type->getLabel().' · '.$record->method->getLabel().' · '.($record->quote?->number() ?? $record->workOrder?->number() ?? '—')), 'heroicon-m-credit-card'),
                        CardList::detail(TextColumn::make('provider')->label(__('payments.fields.provider'))
                            ->formatStateUsing(fn (Payment $record) => $record->provider->getLabel().($record->mode === 'sandbox' ? ' ('.__('payments.modes.sandbox').')' : '')), 'heroicon-m-building-library'),
                        CardList::detail(TextColumn::make('created_at')->label(__('inspections.fields.created_at'))->sortable()
                            ->formatStateUsing(fn (Payment $record) => $record->paid_at ? __('payments.cards.paid_at', ['date' => $record->paid_at->setTimezone(FilamentTimezone::get())->format('d/m H:i')]) : __('payments.cards.created_at', ['date' => $record->created_at?->setTimezone(FilamentTimezone::get())->format('d/m H:i')])), 'heroicon-m-clock'),
                    ],
                ),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('payments.fields.status'))->options(PaymentStatus::class)->multiple(),
                SelectFilter::make('provider')->label(__('payments.fields.provider'))->options(PaymentProvider::class),
            ])
            ->recordActions([
                Action::make('check')
                    ->label(__('payments.actions.check'))
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (Payment $record) => $record->provider === PaymentProvider::MercadoPago && $record->status === PaymentStatus::Pending && $record->gateway_id !== null)
                    ->action(function (Payment $record): void {
                        try {
                            $remote = app(PaymentGateway::class)->fetchPayment((string) $record->gateway_id);
                            app(ApplyPaymentStatus::class)->execute($record, PaymentStatus::fromMercadoPago($remote->status), $remote->statusDetail);
                            Notification::make()->success()->title(__('payments.messages.checked', ['status' => $record->fresh()?->status->getLabel()]))->send();
                        } catch (PaymentGatewayException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPayments::route('/')];
    }
}
