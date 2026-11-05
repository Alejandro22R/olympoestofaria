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

## Fase 2: Check-in e inspección ✅

- Acción "Hacer check-in" en las citas agendadas o confirmadas (`/admin/appointments/{id}/check-in`). Recepción y dueño la usan; el tapicero solo consulta.
- Diagrama SVG del vehículo (vista superior en corte, `VehicleSilhouettes`) con 17 zonas exteriores e interiores. Un toque crea la marca con zona y posición; cada marca exige tipo de daño y admite nota y fotos (disco privado). Las zonas que no se ven en el dibujo (techo interior, otro) se eligen en la lista.
- Formulario: kilometraje (obligatorio u opcional según Ajustes), combustible, objetos del vehículo, observaciones y consentimiento LGPD. **Sin firma del cliente.**
- `RegisterCheckIn`: valida todo en el servidor (consentimiento, kilometraje, combustible, objetos existentes, zona y tipo por marca, posición, límite de marcas y fotos, rutas de fotos dentro de la carpeta de inspecciones), crea la inspección, actualiza el kilometraje del vehículo y pasa la cita a "recibida" por la máquina de estados.
- Acta en PDF (dompdf) en el idioma del cliente, con Sora/Manrope (TTF en `resources/fonts/pdf`), diagrama numerado, fotos, objetos, texto legal y consentimiento. Se guarda en el disco privado.
- Envío del acta: por correo (PDF adjunto, en cola) o por WhatsApp (enlace `wa.me` con mensaje traducido y enlace firmado al PDF, válido los días de Ajustes). Cada envío registra quién, cuándo, canal y destinatario (`inspection_report_sends`), visible en la ficha de la inspección.
- El personal abre el PDF con un enlace firmado de 5 minutos, con sesión y permiso. Las inspecciones no se pueden borrar.
- Ajustes > Check-in y checklists: kilometraje obligatorio, fotos por daño, daños por inspección, vigencia del enlace, texto legal y de consentimiento por idioma. Listas editables de tipos de daño y objetos del vehículo (solo dueño).

**Aceptación:** inspección completa registrada, con PDF descargable y enviado al cliente. Cubierto por pruebas (181 en verde), Pint y Larastan sin errores.

## Fase 3: Órdenes de trabajo e inventario ✅

- **Órdenes de trabajo** (`/admin/work-orders`): se abren desde la inspección ("Abrir orden de servicio") o a mano. Heredan vehículo, cita y entrega prometida. Llevan servicios, descripción, horas, precio (centavos) y material del inventario con cantidad. Las órdenes no se borran: se cancelan con motivo.
- **Máquina de estados** `WorkOrderStatus`: recibida → en producción → lista → entregada (con retrabajo lista → en producción y cancelación). No se puede saltar etapas. Cada cambio queda en el historial (`work_order_updates`) y en el activity log.
- **Etapas editables** (Ajustes > Etapas de producción): nombre pt/es, color, orden, estado al que pertenecen, visibles al cliente y "avisa al cliente" (el aviso al cliente llega en la Fase 6). Una etapa con órdenes no se borra, se desactiva.
- **Tablero kanban** (`/admin/production`): arrastrar y soltar (y selector "Mover de etapa…" para móvil/teclado). Se sincroniza al instante entre pantallas por el canal privado `workshop` (evento `work-order.moved`, solo identificadores) con refresco de respaldo cada 30 s. El tapicero mueve tarjetas y registra avances con fotos, pero no edita la orden.
- **Presencia**: en la ficha de la orden se ve quién más la tiene abierta (canal de presencia `work-orders.{id}`).
- **Inventario** (`/admin/inventory-items`): el stock solo cambia con movimientos (entrada, consumo, ajuste) y nunca queda negativo. El material de la orden se descuenta automáticamente y una sola vez al entrar en producción; si falta material, la orden no avanza y se indica qué falta. Al guardar la orden se avisa si el stock no alcanza.
- **Avisos en vivo y campana** (Laravel Notifications `database` + `broadcast` en formato Filament, traducidos al idioma de cada usuario): stock mínimo en el momento del consumo, cita creada o movida (el calendario se refresca solo).
- **Alertas programadas** (`php artisan olympo:alerts`, cada hora): stock mínimo, orden atrasada, a punto de atrasarse, estancada en una etapa y citas de mañana sin confirmar. Sin repeticiones (`alert_logs`). Umbrales en Ajustes > Producción y alertas.
- **Entrega** (desde una orden "lista"): estado final de cada daño del check-in (reparado / sin cambios), kilometraje y notas. Crea la garantía (mayor plazo entre los servicios de la orden, o el plazo por defecto) y genera el **acta de entrega en PDF** con servicios, comparación recepción × entrega y garantía. **Sin firma**: se envía por correo o WhatsApp con registro de envío, igual que el check-in (envíos unificados en `report_sends`).
- **Ajustes nuevos**: Producción y alertas, Etapas de producción, Garantías (plazo por defecto, recordatorio, condiciones pt/es), Plazos por servicio y Siluetas por carrocería (sedán, hatch y picape; el check-in usa la silueta del tipo de carrocería del modelo).
- Datos demo: stock inicial y tres órdenes repartidas en el tablero.

**Aceptación:** una orden consume inventario, avanza por el tablero y cierra con acta. Cubierto por pruebas (241 en verde), Pint y Larastan sin errores.

**Decisión aplicada:** el acta de entrega sale sin firma, con envío y registro (opción prevista en el plan). Si se decide usar firma, se agrega en la página de entrega sin cambiar el resto.

## Fase 4: Portal del cliente y constructor ✅

- **Landing de impacto** (`/`, PLAN.md §2.2): hero con video en bucle (MP4/WebM + póster precargado, sin audio, solo póster con "reducir movimiento" o ahorro de datos), titular palabra por palabra en CSS (no espera al JS para el LCP), cinta de marcas, círculo de marca que crece con el scroll, servicios con inclinación y zoom (enlazan al constructor con la pieza preseleccionada), configurador en vivo, antes y después arrastrable (solo `translateX`, accesible con teclado), galería filtrable con FLIP, reels, materiales con panel lateral y textura con parallax, línea de tiempo del proceso (etapas visibles al cliente), contadores animados y CTA fijo en móvil. Secciones activables desde Ajustes.
- **Movimiento**: GSAP + ScrollTrigger + Lenis solo en la home (`resources/js/landing.js`), animando únicamente `transform` y `opacity`, con limpieza en `livewire:navigating`. Sin JS o con `prefers-reduced-motion` todo el contenido está visible y estático. Transiciones entre páginas con View Transitions API.
- **Catálogo público** (`/catalogo`): materiales con sus colores (filtro por tipo, panel de detalle) y galería de trabajos filtrable por marca, servicio y material, con hover-play de clips.
- **Constructor** (`/projeto`, Livewire): vehículo → piezas → material y color → resumen y contacto. Visualizador SVG del interior (asientos, volante, puertas, techo, piso y costura) con fundido de color por opacidad. Borrador guardado en la sesión. Registro ligero (nombre, WhatsApp, correo), consentimiento LGPD obligatorio y opt-in de contacto. Honeypot y límite de 5 envíos por hora por IP.
- **Rango estimado** (`EstimateCalculator`, cubierto al 100 % por pruebas): mano de obra = precio base × cantidad (o × m² si la unidad es m²), material = m² × precio por m²; rango = subtotal ∓ margen, mínimo redondeado hacia abajo y máximo hacia arriba. Margen, redondeo y "mostrar estimado" en Ajustes > Cotizaciones y precios. El servidor recalcula siempre; nunca se confía en el navegador.
- **Leads** (`/admin/projects`): el proyecto llega como solicitud con aviso en vivo (campana + evento `lead.created`; la lista se refresca sola). Estados nuevo → contactado → cotizado → aprobado / perdido (con motivo). Botón para responder por WhatsApp. Confirmación para el cliente con enlace firmado y botón de WhatsApp al taller con el resumen.
- **Medios**: biblioteca en el panel (`/admin/gallery-items`) con `spatie/laravel-medialibrary`: fotos con conversiones WebP y `srcset`, foto del antes, video convertido en cola por FFmpeg a MP4 H.264 + WebM VP9 sin audio y póster (`ProcessGalleryVideo`), aviso al usuario que lo subió por su canal privado, y selector de "dónde se muestra" (portada, galería, antes/después, tarjeta de servicio, reels). Los videos solo se reproducen visibles y como máximo 2 a la vez en móvil.
- **Ajustes nuevos**: Cotizaciones y precios; Catálogo y portal (titular y subtítulo pt/es, secciones, contadores, Instagram, SEO).
- **SEO**: título, descripción, canonical, `hreflang` pt-BR/es y Open Graph por página; todo el texto en el HTML.
- **Auditoría Lighthouse (móvil, servidor de desarrollo sin compresión)**: inicio 85/100/100/100 (rendimiento/accesibilidad/buenas prácticas/SEO), catálogo 97/100/100/100, constructor 84–98/100/100/100; CLS 0–0,013. El LCP de la home (≈3,5 s) está dominado por el servidor de desarrollo; repetir la medición en producción (gzip/brotli, HTTP/2, CDN) y en un teléfono real.
- Datos demo: 6 trabajos con imágenes generadas y un clip del hero generado con FFmpeg (marcadores propios, sin licencias de terceros).

**Aceptación:** un visitante arma un proyecto, ve el estimado y el taller lo recibe como lead. Cubierto por pruebas (301 en verde), Pint y Larastan sin errores.

**Notas de diseño:** `steel-500` (#737373) sobre negro da 4,42:1 y no llega a AA para texto pequeño; en el portal se usa `steel-300` para texto y `steel-500` solo como acento.

**Pendiente del taller:** video real de costura (horizontal 1920×1080 y vertical 720×1280), fotos originales de trabajos y antes/después, y autorización escrita de uso de imágenes.
