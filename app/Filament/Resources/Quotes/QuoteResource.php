<?php

namespace App\Filament\Resources\Quotes;

use App\Domain\Quotes\Enums\QuoteStatus;
use App\Filament\Resources\Quotes\Pages\CreateQuote;
use App\Filament\Resources\Quotes\Pages\EditQuote;
use App\Filament\Resources\Quotes\Pages\ListQuotes;
use App\Filament\Resources\Quotes\RelationManagers\PaymentsRelationManager;
use App\Filament\Support\CardList;
use App\Filament\Support\Money;
use App\Filament\Support\MoneyInput;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\ServiceType;
use App\Models\Vehicle;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static ?int $navigationSort = 11;

    public static function getModelLabel(): string
    {
        return __('quotes.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('quotes.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.sales');
    }

    public static function form(Schema $schema): Schema
    {
        $locked = fn (?Quote $record) => $record !== null && ! $record->status->isEditable();

        return $schema->components([
            Section::make()->columns(3)->schema([
                Select::make('customer_id')->label(__('quotes.fields.customer'))
                    ->relationship('customer', 'name')->searchable()->preload()->required()
                    ->disabledOn('edit')->live(),
                Select::make('vehicle_id')->label(__('quotes.fields.vehicle'))
                    ->options(fn (Get $get) => Vehicle::query()->where('customer_id', $get('customer_id'))->get()
                        ->mapWithKeys(fn (Vehicle $v) => [$v->id => $v->label()])->all())
                    ->disabled($locked),
                Placeholder::make('status_info')->label(__('quotes.fields.status'))
                    ->content(fn (?Quote $record) => $record ? $record->status->getLabel().' · '.$record->number() : '—')->visibleOn('edit'),
                DatePicker::make('valid_until')->label(__('quotes.fields.valid_until'))->required()->disabled($locked),
                MoneyInput::make('discount_cents')->label(__('quotes.fields.discount'))->disabled($locked),
                Placeholder::make('totals')->label(__('quotes.fields.total'))->visibleOn('edit')
                    ->content(fn (?Quote $record) => $record ? Money::format($record->total_cents).' ('.__('quotes.fields.deposit').': '.Money::format($record->deposit_cents).')' : '—'),
                Textarea::make('notes')->label(__('quotes.fields.notes'))->rows(2)->columnSpanFull()->disabled($locked),
            ]),
            Section::make(__('quotes.fields.items'))->schema([
                Repeater::make('items')->hiddenLabel()
                    ->addActionLabel(__('quotes.actions.add_item'))
                    ->defaultItems(1)
                    ->disabled($locked)
                    ->columns(['default' => 1, 'md' => 6])
                    ->schema([
                        Select::make('service_type_id')->label(__('work_orders.fields.service'))
                            ->options(fn () => ServiceType::query()->where('is_active', true)->orderBy('sort')->get()->mapWithKeys(fn (ServiceType $s) => [$s->id => $s->name])->all())
                            ->columnSpan(['md' => 2]),
                        TextInput::make('description')->label(__('quotes.fields.description'))->required()->maxLength(255)->columnSpan(['md' => 2]),
                        TextInput::make('quantity')->label(__('work_orders.fields.quantity'))->numeric()->minValue(0.01)->default(1)->required(),
                        MoneyInput::make('unit_price_cents')->label(__('quotes.fields.unit_price'))->required(),
                        Hidden::make('material_variant_id'),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return CardList::grid($table, 'quotes')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'previousVersion']))
            ->defaultSort('id', 'desc')
            ->columns([
                CardList::card(
                    TextColumn::make('customer.name')->label(__('quotes.fields.customer'))->searchable(),
                    TextColumn::make('status')->label(__('quotes.fields.status')),
                    [
                        TextColumn::make('total_cents')->label(__('quotes.fields.total'))->formatStateUsing(fn (int $state) => Money::format($state))->sortable()
                            ->weight(FontWeight::Bold)->extraAttributes(['class' => 'font-display']),
                        CardList::detail(TextColumn::make('vehicle_label')->label(__('quotes.fields.vehicle'))->placeholder('—'), 'heroicon-m-truck'),
                        CardList::detail(TextColumn::make('id')->label(__('quotes.fields.number'))
                            ->formatStateUsing(fn (Quote $record) => $record->number().' · '.__('quotes.cards.valid_until', ['date' => $record->valid_until?->format('d/m/Y') ?? '—'])), 'heroicon-m-document-text'),
                        CardList::detail(TextColumn::make('deposit_paid_at')->label(__('quotes.fields.deposit_paid'))
                            ->formatStateUsing(fn ($state) => __('quotes.cards.deposit_paid', ['date' => $state?->format('d/m')]))->placeholder(__('quotes.cards.deposit_pending')), 'heroicon-m-banknotes'),
                    ],
                ),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('quotes.fields.status'))->options(QuoteStatus::class)->multiple()
                    ->default([QuoteStatus::Draft->value, QuoteStatus::Sent->value, QuoteStatus::Approved->value]),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [PaymentsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotes::route('/'),
            'create' => CreateQuote::route('/create'),
            'edit' => EditQuote::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function customerOptions(): array
    {
        return Customer::query()->orderBy('name')->pluck('name', 'id')->all();
    }
}
