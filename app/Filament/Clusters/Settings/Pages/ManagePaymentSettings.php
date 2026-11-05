<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Gateways\MercadoPagoGateway;
use App\Domain\Payments\Gateways\PaymentGateway;
use App\Domain\Settings\PaymentSettings;
use App\Filament\Clusters\Settings\SettingsPage;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

/**
 * Ajustes > Ventas > Pagos. Mercado Pago se activa y desactiva aquí; las credenciales son de
 * solo escritura (cifradas, nunca se devuelven al navegador) y se pueden probar.
 */
class ManagePaymentSettings extends SettingsPage
{
    protected static string $settings = PaymentSettings::class;

    protected static ?int $navigationSort = 21;

    protected static ?string $slug = 'payments';

    /** Secretos: el formulario nunca los recibe; vacío al guardar = conservar el actual. */
    private const SECRETS = ['mercadopago_access_token', 'mercadopago_webhook_secret'];

    public static function getSectionGroup(): string
    {
        return 'sales';
    }

    public static function getNavigationLabel(): string
    {
        return __('payments.settings.title');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (self::SECRETS as $key) {
            $data[$key] = null;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach (self::SECRETS as $key) {
            if (blank($data[$key] ?? null)) {
                unset($data[$key]);
            } else {
                $data[$key] = trim((string) $data[$key]);
            }
        }
        $data['manual_methods'] = array_values($data['manual_methods'] ?? []);
        $data['mercadopago_methods'] = array_values($data['mercadopago_methods'] ?? []);

        return $data;
    }

    public function save(): void
    {
        $settings = app(PaymentSettings::class);
        $state = $this->form->getRawState();

        // Pasar a producción es una acción crítica: requiere confirmación explícita.
        if (($state['mercadopago_mode'] ?? null) === 'production' && $settings->mercadopago_mode !== 'production' && ! ($state['confirm_production'] ?? false)) {
            Notification::make()->warning()->title(__('payments.settings.confirm_production_required'))->send();
            $this->addError('data.confirm_production', __('payments.settings.confirm_production_required'));

            return;
        }

        parent::save();
    }

    private static function secretHint(string $key): string
    {
        return filled(app(PaymentSettings::class)->{$key}) ? __('payments.settings.secret_set') : __('payments.settings.secret_missing');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testConnection')
                ->label(__('payments.settings.test_connection'))
                ->icon(Heroicon::OutlinedSignal)
                ->color('gray')
                ->action(function (): void {
                    $result = app(PaymentGateway::class)->testConnection();
                    Notification::make()->title($result->message)->{$result->ok ? 'success' : 'danger'}()->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $methods = fn (array $only) => collect($only)->mapWithKeys(fn (string $m) => [$m => PaymentMethod::from($m)->getLabel()])->all();

        return $schema->components([
            Section::make(__('payments.settings.deposit'))->columns(2)->schema([
                TextInput::make('deposit_percent')->label(__('payments.settings.deposit_percent'))->integer()->required()->minValue(0)->maxValue(100)->suffix('%'),
                Toggle::make('deposit_required_to_confirm')->label(__('payments.settings.deposit_required'))->inline(false),
                Select::make('balance_moment')->label(__('payments.settings.balance_moment'))
                    ->options(['on_delivery' => __('payments.settings.on_delivery'), 'before_delivery' => __('payments.settings.before_delivery')])
                    ->required()->native(false),
                CheckboxList::make('manual_methods')->label(__('payments.settings.manual_methods'))
                    ->options($methods(PaymentSettings::MANUAL_METHODS))->columns(2)->required(),
            ]),
            Section::make('Mercado Pago')
                ->description(__('payments.settings.mp_help'))
                ->columns(2)
                ->schema([
                    Toggle::make('mercadopago_enabled')->label(__('payments.settings.mp_enabled'))->live()->columnSpanFull(),
                    Radio::make('mercadopago_mode')->label(__('payments.settings.mode'))
                        ->options(['sandbox' => __('payments.modes.sandbox'), 'production' => __('payments.modes.production')])
                        ->inline()->required()->live(),
                    Toggle::make('confirm_production')->label(__('payments.settings.confirm_production'))
                        ->dehydrated(false)
                        ->visible(fn (Get $get) => $get('mercadopago_mode') === 'production' && app(PaymentSettings::class)->mercadopago_mode !== 'production'),
                    CheckboxList::make('mercadopago_methods')->label(__('payments.settings.online_methods'))
                        ->options($methods(PaymentSettings::ONLINE_METHODS))
                        ->helperText(__('payments.settings.online_methods_help'))->columnSpanFull(),
                    TextInput::make('mercadopago_public_key')->label('Public Key')->maxLength(200)->columnSpanFull(),
                    TextInput::make('mercadopago_access_token')->label('Access Token')
                        ->password()->revealable(false)->autocomplete('new-password')->maxLength(300)
                        ->placeholder(fn () => self::secretHint('mercadopago_access_token'))
                        ->helperText(__('payments.settings.secret_help'))
                        ->required(fn (Get $get) => $get('mercadopago_enabled') && blank(app(PaymentSettings::class)->mercadopago_access_token)),
                    TextInput::make('mercadopago_webhook_secret')->label(__('payments.settings.webhook_secret'))
                        ->password()->revealable(false)->autocomplete('new-password')->maxLength(300)
                        ->placeholder(fn () => self::secretHint('mercadopago_webhook_secret'))
                        ->helperText(__('payments.settings.secret_help')),
                    Placeholder::make('webhook_url')->label(__('payments.settings.webhook_url'))
                        ->content(fn () => new HtmlString('<code class="select-all break-all">'.e(app(MercadoPagoGateway::class)->notificationUrl()).'</code>'))
                        ->helperText(__('payments.settings.webhook_url_help'))->columnSpanFull(),
                    TextInput::make('pix_expiration_minutes')->label(__('payments.settings.pix_expiration'))->integer()->required()->minValue(5)->maxValue(43200)->suffix('min'),
                    TextInput::make('statement_descriptor')->label(__('payments.settings.descriptor'))->maxLength(22)->alphaDash(),
                ]),
        ]);
    }
}
