<?php

namespace App\Domain\Tours;

use Illuminate\Support\Facades\Lang;

/**
 * Tutoriales guiados del panel: qué tutorial corresponde a cada pantalla (por nombre de ruta)
 * y sus pasos. Los textos viven en lang/{idioma}/tours.php; aquí solo el orden y a qué
 * elemento apunta cada paso (selector CSS o atributo data-tour). Un paso sin elemento, o cuyo
 * elemento no está en pantalla, se muestra centrado.
 */
final class TourCatalog
{
    /** Pasos comunes de una lista (botón de crear, búsqueda, filtros y tarjetas). */
    private const LIST = [
        ['create', '.fi-header-actions-ctn', 'bottom'],
        ['search', '.fi-ta-search-field', 'bottom'],
        ['filters', '.fi-ta-header-toolbar', 'bottom'],
        ['card', '.fi-ta-record', 'top'],
    ];

    /**
     * Ruta (o prefijo terminado en *) => tutorial.
     *
     * @var array<string, string>
     */
    private const ROUTES = [
        'filament.admin.pages.dashboard' => 'dashboard',
        'filament.admin.pages.calendar' => 'calendar',
        'filament.admin.pages.production' => 'board',
        'filament.admin.pages.reports' => 'reports',
        'filament.admin.resources.appointments.index' => 'appointments',
        'filament.admin.resources.appointments.check-in' => 'checkin',
        'filament.admin.resources.inspections.index' => 'inspections',
        'filament.admin.resources.work-orders.index' => 'work_orders',
        'filament.admin.resources.work-orders.edit' => 'work_order',
        'filament.admin.resources.projects.index' => 'projects',
        'filament.admin.resources.quotes.index' => 'quotes',
        'filament.admin.resources.quotes.edit' => 'quote',
        'filament.admin.resources.payments.index' => 'payments',
        'filament.admin.resources.customers.index' => 'customers',
        'filament.admin.resources.vehicles.index' => 'vehicles',
        'filament.admin.resources.inventory-items.index' => 'inventory',
        'filament.admin.resources.service-types.index' => 'catalog',
        'filament.admin.resources.materials.index' => 'catalog',
        'filament.admin.resources.gallery-items.index' => 'gallery',
        'filament.admin.resources.reviews.index' => 'reviews',
        'filament.admin.settings.*' => 'settings',
    ];

    /**
     * Pasos de cada tutorial: [clave del texto, selector o null, lado del globo].
     *
     * @return array<string, list<array{0: string, 1: ?string, 2: string}>>
     */
    public static function definitions(): array
    {
        return [
            'dashboard' => [
                ['welcome', null, 'bottom'],
                ['menu', '.fi-sidebar-nav', 'right'],
                ['actions', '[data-tour="dash-actions"]', 'bottom'],
                ['today', '[data-tour="dash-today"]', 'top'],
                ['pending', '[data-tour="dash-pending"]', 'left'],
                ['search', '.fi-global-search-field', 'bottom'],
                ['bell', '.fi-topbar-database-notifications-btn', 'bottom'],
                ['profile', '.fi-user-menu', 'bottom'],
                ['help', '[data-olympo-tour-start]', 'bottom'],
            ],
            'calendar' => [
                ['intro', null, 'bottom'],
                ['new', '[data-tour="new-appointment"]', 'bottom'],
                ['day', '.fi-page-content', 'top'],
                ['list', '[data-tour="appointments-list"]', 'bottom'],
            ],
            'appointments' => [['intro', null, 'bottom'], ...self::LIST],
            'checkin' => [
                ['intro', null, 'bottom'],
                ['state', '[data-tour="checkin-state"]', 'top'],
                ['diagram', '[data-tour="checkin-diagram"]', 'top'],
                ['consent', '[data-tour="checkin-consent"]', 'top'],
                ['finish', '.fi-sc-actions', 'top'],
            ],
            'board' => [
                ['intro', null, 'bottom'],
                ['column', '[data-tour="board-column"]', 'right'],
                ['card', '[data-tour="board-card"]', 'right'],
                ['move', '[data-tour="board-card"] select', 'bottom'],
            ],
            'inspections' => [['intro', null, 'bottom'], ...array_slice(self::LIST, 1)],
            'work_orders' => [['intro', null, 'bottom'], ...self::LIST],
            'work_order' => [
                ['intro', null, 'bottom'],
                ['move', '[data-tour="wo-move"]', 'bottom'],
                ['progress', '[data-tour="wo-progress"]', 'bottom'],
                ['deliver', '[data-tour="wo-deliver"]', 'bottom'],
            ],
            'projects' => [['intro', null, 'bottom'], ...array_slice(self::LIST, 1)],
            'quotes' => [['intro', null, 'bottom'], ...self::LIST],
            'quote' => [
                ['intro', null, 'bottom'],
                ['items', '.fi-fo-repeater', 'top'],
                ['send', '[data-tour="quote-send"]', 'bottom'],
                ['payment', '[data-tour="quote-payment"]', 'bottom'],
            ],
            'payments' => [['intro', null, 'bottom'], ...self::LIST],
            'customers' => [['intro', null, 'bottom'], ...self::LIST],
            'vehicles' => [['intro', null, 'bottom'], ...self::LIST],
            'inventory' => [['intro', null, 'bottom'], ...self::LIST],
            'catalog' => [['intro', null, 'bottom'], ...self::LIST],
            'gallery' => [['intro', null, 'bottom'], ...self::LIST],
            'reviews' => [['intro', null, 'bottom'], ...array_slice(self::LIST, 2)],
            'reports' => [
                ['intro', null, 'bottom'],
                ['period', '.fi-page-content [role="group"]', 'bottom'],
            ],
            'settings' => [
                ['intro', null, 'bottom'],
                ['sections', '.fi-page-sub-navigation-sidebar, .fi-page-sub-navigation-dropdown', 'right'],
                ['save', '.fi-sc-actions', 'top'],
            ],
        ];
    }

    public static function keyForRoute(?string $route): ?string
    {
        if ($route === null) {
            return null;
        }

        foreach (self::ROUTES as $pattern => $key) {
            if ($pattern === $route || (str_ends_with($pattern, '*') && str_starts_with($route, rtrim($pattern, '*')))) {
                return $key;
            }
        }

        return null;
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::definitions());
    }

    /**
     * Pasos listos para el navegador, en el idioma actual.
     *
     * @return list<array{element: ?string, title: string, body: string, side: string}>
     */
    public static function steps(string $key): array
    {
        return array_map(function (array $step) use ($key): array {
            // Los pasos comunes de lista usan textos genéricos si el módulo no tiene los suyos.
            $base = Lang::has("tours.{$key}.{$step[0]}") ? "tours.{$key}.{$step[0]}" : "tours.list.{$step[0]}";

            return ['element' => $step[1], 'title' => __("{$base}.title"), 'body' => __("{$base}.body"), 'side' => $step[2]];
        }, self::definitions()[$key] ?? []);
    }
}
