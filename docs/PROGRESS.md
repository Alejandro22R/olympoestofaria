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

## Fase 1: Núcleo operativo del taller ✅

- Recursos Filament: Clientes (con vehículos como relación), Vehículos, Citas, Servicios, Materiales (con variantes de color) y Feriados (dentro de Ajustes, solo dueño). Campos de catálogo editables en `pt_BR`/`es`; dinero en centavos con `MoneyInput`.
- Calendario mensual (`/admin/calendar`) con ocupación frente a capacidad, feriados, enlace "agendar en este día" y refresco de respaldo cada 60 s.
- Ajustes de Agenda y capacidad: días laborables, horario, vehículos por día y en sábado, anticipación mínima, duración de recepción y bloqueo al llenarse. El calendario y las reglas leen estos valores (`AgendaService`), nunca números fijos.
- Reglas de agenda en `ScheduleAppointment`: no en el pasado ni antes de la anticipación mínima, día laborable sin feriado, dentro del horario, capacidad del día (con bloqueo por fecha en PostgreSQL para evitar dobles reservas) y entrega posterior a la recepción. Editar una cita sin moverla no reevalúa la agenda.
- Máquina de estados de la cita (`AppointmentStatus`, `ChangeAppointmentStatus`) con transiciones auditadas.
- Fechas guardadas siempre en UTC (`App\Casts\UtcDateTime`) y mostradas en la zona horaria del taller.
- Documento del cliente: número cifrado en la base (`encrypted`) con hash HMAC para unicidad, nunca rellenado en el formulario ni en la auditoría; archivo cifrado en disco privado, uno por cliente, MIME real, solo con consentimiento LGPD, servido por URL firmada de 5 minutos + sesión + permiso, con registro de cada visualización.
- Validaciones con mensajes traducidos y pruebas por regla: placa (antigua y Mercosul, única al normalizar), teléfono E.164, CPF/CNPJ con dígito verificador, año 1950…actual+1, kilometraje, fechas y capacidad.
- Policies por modelo sobre la matriz de permisos; el tapicero solo lee.
- Datos demo operativos (`DemoOperationsSeeder`): 4 clientes, vehículos, un cierre y 8 citas.

**Aceptación:** crear cliente → vehículo → cita visible en el calendario; el documento solo se abre con URL firmada y permiso. Cubierto por pruebas (143 en verde), Pint y Larastan sin errores.
