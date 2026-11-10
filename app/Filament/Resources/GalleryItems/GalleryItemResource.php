<?php

namespace App\Filament\Resources\GalleryItems;

use App\Filament\Resources\GalleryItems\Pages\CreateGalleryItem;
use App\Filament\Resources\GalleryItems\Pages\EditGalleryItem;
use App\Filament\Resources\GalleryItems\Pages\ListGalleryItems;
use App\Models\GalleryItem;
use App\Models\Material;
use App\Models\ServiceType;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Biblioteca de medios del portal: fotos (WebP + srcset), antes/después y videos
 * (FFmpeg a MP4/WebM + póster), con etiquetas y selector de "dónde se muestra".
 */
class GalleryItemResource extends Resource
{
    protected static ?string $model = GalleryItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 30;

    public static function getModelLabel(): string
    {
        return __('gallery.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('gallery.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.portal');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextInput::make('title.pt_BR')->label(__('catalog.fields.name_pt'))->required()->maxLength(120),
                TextInput::make('title.es')->label(__('catalog.fields.name_es'))->maxLength(120),
                Toggle::make('is_published')->label(__('gallery.fields.published'))->default(true)->inline(false),
                Select::make('service_type_id')->label(__('work_orders.fields.service'))
                    ->options(fn () => ServiceType::query()->orderBy('sort')->get()->mapWithKeys(fn (ServiceType $s) => [$s->id => $s->name])->all()),
                Select::make('material_id')->label(__('gallery.fields.material'))
                    ->options(fn () => Material::query()->get()->mapWithKeys(fn (Material $m) => [$m->id => $m->name])->all()),
                TextInput::make('color')->label(__('vehicles.fields.color'))->maxLength(40),
                TextInput::make('brand')->label(__('vehicles.fields.brand'))->maxLength(60),
                TextInput::make('model')->label(__('vehicles.fields.model'))->maxLength(60),
                TextInput::make('sort')->label(__('catalog.fields.sort'))->integer()->default(0),
                CheckboxList::make('placements')->label(__('gallery.fields.placements'))
                    ->options(collect(GalleryItem::PLACEMENTS)->mapWithKeys(fn (string $p) => [$p => __('gallery.placements.'.$p)])->all())
                    ->columns(5)->columnSpanFull()->default(['gallery']),
            ]),
            Section::make(__('gallery.sections.media'))->columns(3)->schema([
                SpatieMediaLibraryFileUpload::make('image')->label(__('gallery.fields.image'))
                    ->collection('image')->image()->maxSize(15 * 1024)->imageEditor(),
                SpatieMediaLibraryFileUpload::make('before')->label(__('gallery.fields.before'))
                    ->collection('before')->image()->maxSize(15 * 1024)
                    ->helperText(__('gallery.fields.before_help')),
                SpatieMediaLibraryFileUpload::make('video')->label(__('gallery.fields.video'))
                    ->collection('video')->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/webm'])->maxSize(200 * 1024)
                    ->helperText(__('gallery.fields.video_help')),
                Placeholder::make('video_state')->label(__('gallery.fields.video_status'))
                    ->content(fn (?GalleryItem $record) => $record?->video_status ? __('gallery.video_status.'.$record->video_status).($record->video_error ? ' — '.$record->video_error : '') : '—')
                    ->visibleOn('edit'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')->collection('image')->conversion('thumb')->label(''),
                TextColumn::make('title')->label(__('catalog.fields.name'))->searchable(),
                TextColumn::make('placements')->label(__('gallery.fields.placements'))->badge()
                    ->formatStateUsing(fn (string $state) => __('gallery.placements.'.$state)),
                TextColumn::make('video_status')->label(__('gallery.fields.video_status'))->badge()
                    ->formatStateUsing(fn (?string $state) => $state ? __('gallery.video_status.'.$state) : '—')
                    ->color(fn (?string $state) => match ($state) {
                        'ready' => 'success', 'failed' => 'danger', 'processing', 'pending' => 'warning', default => 'gray'
                    }),
                IconColumn::make('is_published')->label(__('gallery.fields.published'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('placement')->label(__('gallery.fields.placements'))
                    ->options(collect(GalleryItem::PLACEMENTS)->mapWithKeys(fn (string $p) => [$p => __('gallery.placements.'.$p)])->all())
                    ->query(fn ($query, array $data) => $data['value'] ? $query->whereJsonContains('placements', $data['value']) : $query),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGalleryItems::route('/'),
            'create' => CreateGalleryItem::route('/create'),
            'edit' => EditGalleryItem::route('/{record}/edit'),
        ];
    }
}
