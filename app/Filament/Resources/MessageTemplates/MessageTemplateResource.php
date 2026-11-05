<?php

namespace App\Filament\Resources\MessageTemplates;

use App\Domain\Auth\Permission;
use App\Domain\Notifications\Actions\SaveMessageTemplate;
use App\Domain\Notifications\Customer\MessageEvent;
use App\Domain\Notifications\Customer\TemplateRenderer;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\MessageTemplates\Pages\ManageMessageTemplates;
use App\Filament\Support\ActionErrors;
use App\Models\MessageTemplate;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Ajustes > Comunicación > Plantillas (por evento, canal e idioma). No se crean ni se borran:
 * el catálogo de eventos es fijo; se editan el texto y la conexión con la plantilla de Meta.
 */
class MessageTemplateResource extends Resource
{
    protected static ?string $model = MessageTemplate::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 32;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::SettingsManage->value);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getModelLabel(): string
    {
        return __('communication.templates.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('communication.templates.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('communication.templates.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('settings.groups.communication');
    }

    public static function form(Schema $schema): Schema
    {
        $event = fn (?MessageTemplate $record) => $record ? MessageEvent::from($record->event) : null;
        $vars = fn (?MessageTemplate $record) => implode(', ', array_map(fn (string $v) => '{{'.$v.'}}', $event($record)?->variables() ?? []));

        return $schema->columns(2)->components([
            TextEntry::make('event_label')->label(__('communication.templates.fields.event'))
                ->state(fn (?MessageTemplate $record) => $event($record)?->getLabel()),
            TextEntry::make('channel_label')->label(__('communication.templates.fields.channel'))
                ->state(fn (?MessageTemplate $record) => $record ? __('communication.channels.'.$record->channel).' · '.__('settings.locales.'.$record->locale) : null),
            TextInput::make('subject')->label(__('communication.templates.fields.subject'))->maxLength(200)->columnSpanFull()
                ->visible(fn (?MessageTemplate $record) => $record?->channel === 'mail')->required(fn (?MessageTemplate $record) => $record?->channel === 'mail'),
            Textarea::make('body')->label(__('communication.templates.fields.body'))->rows(6)->required()->maxLength(4000)->columnSpanFull()
                ->live(debounce: 500)
                ->helperText(fn (?MessageTemplate $record) => __('communication.templates.help.body', ['vars' => $vars($record)])),
            TextEntry::make('preview')->label(__('communication.templates.preview'))->columnSpanFull()
                ->state(fn (Get $get, ?MessageTemplate $record) => $record ? TemplateRenderer::render((string) $get('body'), self::sample($event($record))) : null),
            TextInput::make('whatsapp_template')->label(__('communication.templates.fields.whatsapp_template'))->maxLength(120)->regex('/^[a-z0-9_]+$/')
                ->visible(fn (?MessageTemplate $record) => $record?->channel === 'whatsapp'),
            TextInput::make('whatsapp_language')->label(__('communication.templates.fields.whatsapp_language'))->maxLength(10)
                ->visible(fn (?MessageTemplate $record) => $record?->channel === 'whatsapp'),
            TagsInput::make('whatsapp_params')->label(__('communication.templates.fields.whatsapp_params'))->columnSpanFull()
                ->helperText(__('communication.templates.help.whatsapp'))
                ->visible(fn (?MessageTemplate $record) => $record?->channel === 'whatsapp'),
            Toggle::make('is_active')->label(__('communication.templates.fields.is_active')),
        ]);
    }

    /**
     * Valores de ejemplo para la vista previa.
     *
     * @return array<string, string>
     */
    public static function sample(?MessageEvent $event): array
    {
        $examples = [
            'nome' => 'Ana', 'oficina' => 'Olympo', 'numero' => 'ORC-2026-0042', 'veiculo' => 'Toyota Corolla 2020',
            'total' => 'R$ 2.450,00', 'validade' => '30/11/2026', 'link' => 'https://…', 'sinal' => 'R$ 735,00',
            'valor' => 'R$ 735,00', 'tipo' => 'Sinal', 'placa' => 'ABC1D23', 'data' => '12/11/2026', 'hora' => '09:00',
            'endereco' => 'São Paulo', 'etapa' => 'Costura', 'saldo' => 'R$ 1.715,00', 'vencimento' => '12/11/2027',
        ];

        return array_intersect_key($examples, array_flip($event?->variables() ?? []));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('event')
            ->columns([
                TextColumn::make('event')->label(__('communication.templates.fields.event'))
                    ->formatStateUsing(fn (string $state) => MessageEvent::tryFrom($state)?->getLabel() ?? $state)->searchable(),
                TextColumn::make('category')->label(__('communication.templates.fields.category'))->badge()
                    ->state(fn (MessageTemplate $record) => __('communication.categories.'.(MessageEvent::tryFrom($record->event)?->category() ?? 'transactional'))),
                TextColumn::make('channel')->label(__('communication.templates.fields.channel'))
                    ->formatStateUsing(fn (string $state) => __('communication.channels.'.$state)),
                TextColumn::make('locale')->label(__('communication.templates.fields.locale'))
                    ->formatStateUsing(fn (string $state) => __('settings.locales.'.$state)),
                TextColumn::make('whatsapp_template')->label(__('communication.templates.fields.whatsapp_template'))->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')->label(__('communication.templates.fields.is_active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('channel')->label(__('communication.templates.fields.channel'))
                    ->options(['mail' => __('communication.channels.mail'), 'whatsapp' => __('communication.channels.whatsapp')]),
                SelectFilter::make('locale')->label(__('communication.templates.fields.locale'))
                    ->options(['pt_BR' => __('settings.locales.pt_BR'), 'es' => __('settings.locales.es')]),
            ])
            ->recordActions([
                EditAction::make()->using(fn (MessageTemplate $record, array $data) => ActionErrors::map(fn () => app(SaveMessageTemplate::class)->execute($record, $data))),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMessageTemplates::route('/'),
        ];
    }
}
