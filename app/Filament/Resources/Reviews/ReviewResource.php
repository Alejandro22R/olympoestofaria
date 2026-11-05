<?php

namespace App\Filament\Resources\Reviews;

use App\Domain\Reviews\Actions\PublishReview;
use App\Filament\Resources\Reviews\Pages\EditReview;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

/**
 * Reseñas de clientes: moderar, elegir fotos de antes/después y publicar en la landing.
 */
class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?int $navigationSort = 65;

    public static function getModelLabel(): string
    {
        return __('reviews.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('reviews.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.portal');
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Review::query()->where('consent_publish', true)->where('is_published', false)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('customer.name')->label(__('reviews.fields.customer')),
                TextEntry::make('workOrder.number')->label(__('reviews.fields.work_order'))
                    ->state(fn (Review $record) => $record->workOrder->number())
                    ->url(fn (Review $record) => WorkOrderResource::getUrl('edit', ['record' => $record->work_order_id])),
                TextEntry::make('rating')->label(__('reviews.fields.rating'))->state(fn (Review $record) => str_repeat('★', $record->rating).str_repeat('☆', 5 - $record->rating)),
                TextEntry::make('comment')->label(__('reviews.fields.comment'))->placeholder('—')->columnSpanFull(),
                TextEntry::make('consent_publish')->label(__('reviews.fields.consent_publish'))
                    ->state(fn (Review $record) => $record->consent_publish ? __('filament-forms::components.toggle_buttons.boolean.true') : __('filament-forms::components.toggle_buttons.boolean.false'))
                    ->badge()->color(fn (Review $record) => $record->consent_publish ? 'success' : 'gray'),
            ]),
            Section::make(__('gallery.sections.media'))->columns(2)->schema([
                SpatieMediaLibraryFileUpload::make('before')->label(__('reviews.fields.before'))
                    ->collection('before')->image()->maxSize(15 * 1024)->imageEditor(),
                SpatieMediaLibraryFileUpload::make('after')->label(__('reviews.fields.after'))
                    ->collection('after')->image()->maxSize(15 * 1024)->imageEditor(),
            ]),
        ]);
    }

    public static function publishAction(): Action
    {
        return Action::make('togglePublish')
            ->label(fn (Review $record) => $record->is_published ? __('reviews.actions.unpublish') : __('reviews.actions.publish'))
            ->icon(fn (Review $record) => $record->is_published ? Heroicon::OutlinedEyeSlash : Heroicon::OutlinedEye)
            ->color(fn (Review $record) => $record->is_published ? 'gray' : 'success')
            ->visible(fn (Review $record) => $record->is_published || $record->consent_publish)
            ->authorize(fn (Review $record) => auth()->user()?->can('update', $record))
            ->action(function (Review $record): void {
                try {
                    app(PublishReview::class)->execute($record, ! $record->is_published);
                } catch (ValidationException $e) {
                    Notification::make()->danger()->title(collect($e->errors())->flatten()->first())->send();
                }
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['customer', 'workOrder']))
            ->columns([
                TextColumn::make('created_at')->label(__('reviews.fields.created_at'))->date('d/m/Y')->sortable(),
                TextColumn::make('customer.name')->label(__('reviews.fields.customer'))->searchable(),
                TextColumn::make('rating')->label(__('reviews.fields.rating'))->sortable()
                    ->formatStateUsing(fn (int $state) => str_repeat('★', $state)),
                TextColumn::make('comment')->label(__('reviews.fields.comment'))->limit(60)->wrap(),
                IconColumn::make('consent_publish')->label(__('reviews.fields.consent_publish'))->boolean(),
                IconColumn::make('is_published')->label(__('reviews.fields.is_published'))->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label(__('reviews.fields.is_published')),
            ])
            ->recordActions([
                self::publishAction(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
            'edit' => EditReview::route('/{record}/edit'),
        ];
    }
}
