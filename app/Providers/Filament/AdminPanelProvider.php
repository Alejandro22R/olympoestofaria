<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Filament\Support\InitialsAvatarProvider;
use App\Http\Middleware\EnforceIdleTimeout;
use App\Http\Middleware\EnsureTwoFactorWhenRequired;
use App\Http\Middleware\SetLocale;
use App\Support\Brand;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->passwordReset()
            ->profile(EditProfile::class, isSimple: false)
            ->multiFactorAuthentication([
                AppAuthentication::make()->recoverable()->brandName('Olympo'),
            ], isRequired: true)
            ->multiFactorAuthenticationRequiredMiddlewareName(EnsureTwoFactorWhenRequired::class)
            ->brandName('Olympo')
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            // Ajustes > Marca: logo claro para el tema claro y logo oscuro (por defecto) para el oscuro.
            // Emblema + nombre (o el logo subido en Ajustes > Marca); se adapta al tema claro/oscuro.
            ->brandLogo(fn () => new HtmlString(view('components.brand-logo')->render()))
            ->brandLogoHeight('2.25rem')
            ->favicon(fn () => Brand::settings()?->faviconUrl() ?? asset('favicon.svg'))
            ->colors([
                'primary' => Brand::primary(),
                'gray' => Brand::gray(),
                'success' => Color::hex('#2FA36B'),
                'warning' => Color::hex('#E0A100'),
                'danger' => Color::hex('#D64545'),
                'info' => Brand::primary(),
            ])
            ->font('Manrope', provider: LocalFontProvider::class)
            ->defaultThemeMode(Brand::settings()?->default_theme === 'light' ? ThemeMode::Light : ThemeMode::Dark)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->unsavedChangesAlerts()
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            // Menú por flujo de trabajo: lo de todos los días primero, la configuración al final.
            ->navigationGroups([
                NavigationGroup::make()->label(fn (): string => __('navigation.groups.daily')),
                NavigationGroup::make()->label(fn (): string => __('navigation.groups.sales')),
                NavigationGroup::make()->label(fn (): string => __('navigation.groups.workshop'))->collapsed(),
                NavigationGroup::make()->label(fn (): string => __('navigation.groups.portal'))->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            // Tutoriales guiados: botón "?" en la barra superior y datos del tutorial de la pantalla.
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn (): string => view('filament.tour-button')->render())
            ->renderHook(PanelsRenderHook::BODY_END, fn (): string => view('filament.tour')->render())
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            // El idioma también debe aplicarse a las peticiones de Livewire del panel.
            ->middleware([SetLocale::class], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
                // Cierra la sesión tras el tiempo de inactividad de Ajustes > Seguridad.
                EnforceIdleTimeout::class,
            ], isPersistent: true);
    }
}
