# Progreso por fase

## Fase 0: Cimientos ✅

- Laravel 13 + PHP 8.3, PostgreSQL, Redis, Sail (`compose.yaml`), Pest 4, Pint, Larastan (nivel 6) y CI.
- Filament 5 en `/admin` con tema oscuro, paleta de marca (`App\Support\Brand`), Sora/Manrope autoalojadas y logo provisional.
- Roles `owner`, `reception`, `upholsterer`, `customer` y matriz de permisos en `App\Domain\Auth\Permission` (sincronizada por `RolesAndPermissionsSeeder`). Solo el personal activo entra al panel.
- i18n `pt_BR` (predeterminado) y `es`: idioma por usuario en el panel y `?lang=` en el portal (`SetLocale`). Contenido traducible del catálogo con `spatie/laravel-translatable`.
- Reverb + Echo: canal privado `workshop` (solo personal) y `App.Models.User.{id}`; Echo del panel configurado en `config/filament.php`; en el portal se carga aparte (`resources/js/echo.js`).
- Base de Ajustes: clúster "Ajustes" con navegación lateral por grupos, `SettingsPage` con guardar/descartar, aviso de cambios sin guardar, caché de `spatie/laravel-settings`, permiso `settings.manage` solo para el dueño, auditoría de cada cambio (valor anterior/nuevo, secretos enmascarados) y evento `SettingsUpdated` por el canal `workshop`.
- Sección General (nombre, ciudad, WhatsApp, correo, Maps, idiomas, zona horaria, moneda, país).
- Identidad visual: tokens de la §2.1 en `resources/css/brand.css` (Tailwind 4 usa `@theme` en CSS en lugar de `tailwind.config`).
- Seeders: roles, sucursal, catálogo demo (7 servicios, 4 materiales con variantes, 3 modelos de auto) y usuarios demo.
- Esquema preparado para varias sucursales (`branches`, `branch_id`).

**Pendiente del taller:** logo original (PNG/SVG) en versión oscura y clara; los SVG de `public/images/brand` son marcadores provisionales y los hex de la paleta deben verificarse con cuentagotas.
