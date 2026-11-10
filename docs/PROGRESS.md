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

## Fase 5: Cotización, pagos y seguimiento ✅

- **Cotización final** (`/admin/quotes`): se crea desde la solicitud (ítems y precios iniciales del estimado) o desde cero. Totales siempre recalculados en el servidor (`QuoteTotals`, centavos enteros), descuento limitado por Ajustes, precio final editable solo por los roles configurados, vigencia obligatoria. Una cotización enviada no se edita: se crea una **versión nueva** y la anterior queda como "reemplazada". PDF en el idioma del cliente.
- **Envío** por WhatsApp (enlace firmado a la página de aprobación) o correo (PDF adjunto), con registro de envío. La solicitud pasa a "cotizada".
- **Portal del cliente** (`/orcamento/{id}`): el enlace firmado abre su sesión sin contraseña (cuenta ligera con rol `customer`, nunca reemplaza una sesión del personal). Revisa, acepta las condiciones y aprueba, o pide cambios. La cotización vencida no se puede aprobar.
- **Pagos** (`PaymentGateway` desacoplado):
  - **Mercado Pago activable/desactivable** en Ajustes > Pagos, hoy **solo Pix** (estructura lista para tarjeta). Modo prueba/producción (producción con confirmación explícita), Public Key, Access Token y secreto del webhook **cifrados y de solo escritura**, botón "Testar conexão" y URL del webhook para copiar.
  - Pix del **anticipo** al aprobar (QR, "copia e cola" y cuenta regresiva) y del **saldo** al estar lista la orden (o antes, según Ajustes). Clave de idempotencia y `external_reference` = uuid del pago; un Pix pendiente vigente se reutiliza.
  - **Webhook** `/webhooks/mercadopago`: firma `x-signature` verificada (HMAC-SHA256, tolerancia de tiempo contra repetición), idempotente (`webhook_events`), estado consultado a la API (nunca se confía en el cuerpo), control del monto y estados finales que no se revierten.
  - **Conciliación** cada 10 min (`olympo:payments:reconcile`) y vencimiento de Pix sin pagar.
  - **Pagos manuales** (efectivo, Pix directo, transferencia, tarjeta según Ajustes) desde la cotización o la orden; nunca superan el saldo.
  - Al pagarse el anticipo: cotización con anticipo pagado y **cita confirmada** automáticamente (si así está configurado). Aviso en vivo al panel y a la campana.
- **Seguimiento del cliente** (`/acompanhar/{orden}`, enlace firmado enviado por WhatsApp desde la orden): etapas visibles, avances con fotos (servidas solo al dueño), total/pagado/saldo y pago del saldo con Pix. Se actualiza **en vivo** por el canal privado `customers.{id}` (eventos `work-order.moved` y `payment.updated`, solo identificadores) con sondeo de respaldo.
- Desde la cotización aprobada: **agendar la recepción** (crea el vehículo si el lead aún no lo tenía) y **abrir la orden de trabajo** con los ítems; el check-in de una cita con cotización aprobada también la vincula.
- **Ajustes nuevos**: Pagos; y en Cotizaciones y precios: vigencia, descuento máximo, roles del precio final y exigir contacto previo.

**Aceptación:** un pago de prueba confirma la cita y el cliente ve el avance de su orden. Cubierto por pruebas (361 en verde) con Mercado Pago simulado (`Http::fake`) y webhooks firmados, Pint y Larastan sin errores.

**Para conectar en real:** ver "Conectar Mercado Pago (Pix)" en el README. Desde este entorno de desarrollo no hay salida de red a `api.mercadopago.com`, así que la prueba con credenciales reales debe hacerse en el servidor.

**Despliegue:** ejecutar `php artisan settings:clear-cache` después de migrar.

## Fase 6: Comunicación y crecimiento ✅

- **Avisos al cliente, un solo sistema multicanal** (`CustomerNotifier` → notificación en cola `CustomerMessage` → `CustomerChannel`):
  - Canales WhatsApp Cloud API (Meta) y correo, en el orden de Ajustes. Si uno falla y el respaldo está activo, prueba el siguiente. Si todos fallan, la cola reintenta (1, 5 y 15 min).
  - Cada intento queda en el **registro de envíos** (`notification_logs`) con canal, estado, error e id del proveedor. El registro se depura según los días de retención.
  - **Idempotencia**: un aviso ya entregado no se repite (clave por hecho: `payment:12:approved`, `appointment:5:…`).
  - **Consentimiento**: los transaccionales (pagos, citas, actas) siempre salen. Los avances respetan la baja. El marketing (pedido de reseña, revisión de garantía) exige opt-in. Los correos opcionales llevan enlace de baja firmado; el GET solo muestra la confirmación.
  - **Horario silencioso**: avances y marketing esperan a que termine. Los transaccionales salen al momento.
- **Plantillas editables** (Ajustes > Modelos de mensaje):
  - Por evento, canal e idioma (pt-BR/es), con vista previa y validación de variables (`{{nome}}`, `{{placa}}`…).
  - En WhatsApp se indica el nombre de la plantilla aprobada en Meta y el orden de sus parámetros.
- **Catálogo de eventos** conectado al dominio: proyecto recibido, cotización enviada, aprobada y recordatorio, pago aprobado o rechazado, cita confirmada, movida, cancelada y recordatorio (p. ej. 24 h y 2 h; solo el más cercano), actas de recepción y entrega, avance de etapa (solo etapas con "avisa al cliente"), auto listo con saldo, revisión de garantía y pedido de reseña.
  - Las actas se envían **automáticamente** al terminar el check-in o la entrega (activable en Ajustes).
  - Con WhatsApp conectado y opt-in salen solas por la API; si no, se mantiene el enlace wa.me.
- **Alertas internas con escalamiento** (`olympo:alerts`, cada hora; umbrales en Ajustes > Alertas automáticas):
  - Comerciales: solicitud sin respuesta, cotización sin respuesta (también recuerda al cliente), anticipo vencido, saldo vencido tras la entrega.
  - Técnicas, solo al dueño y por correo: tareas fallidas, webhooks con error, disco casi lleno.
  - Si nadie abre una alerta en N horas, **se escala al dueño** (y por correo si así se configura).
  - Cada persona elige en **Mi perfil** qué tipos de aviso recibe también por correo.
- **Avisos programados** (`olympo:customer-messages`, cada 15 min): recordatorios de cita, revisión de garantía y pedido de reseña N días después de la entrega.
- **Reseñas con fotos antes/después**:
  - El cliente califica de 1 a 5 desde el enlace firmado (`/avaliar/{orden}`) y decide si permite publicarla.
  - El taller la modera en `/admin/reviews`, elige las fotos y la publica.
  - La landing muestra las publicadas en "Prueba social", con nombre e inicial del apellido.
- **Referidos y leads**:
  - Cada cliente tiene un código, visible en su página de seguimiento como enlace `/projeto?ref=CODIGO`.
  - El proyecto y el cliente nuevo quedan vinculados a quien lo recomendó.
  - El embudo (nuevo → contactado → cotizado → aprobado / perdido) aparece en Reportes.
- **Usuarios y roles** (Ajustes > Equipo):
  - Invitar con enlace para definir la contraseña, cambiar rol e idioma, desactivar (no se borra) y reiniciar el 2FA.
  - Siempre queda al menos un dueño activo y nadie se quita a sí mismo el rol de dueño.
  - **Matriz de permisos editable** para recepción y tapicero, auditada. Ajustes y Usuarios son exclusivos del dueño. El seeder ya no pisa la matriz editada.
- **Seguridad**:
  - Verificación en dos pasos con app autenticadora y códigos de recuperación en Mi perfil; puede hacerse **obligatoria para todo el equipo**.
  - Cierre por inactividad, intentos de login por minuto configurables, recuperación de contraseña y registro del último acceso.
- **Marca y apariencia**:
  - Logo oscuro y claro, favicon, color principal (los tonos 700/800 se derivan y se exige contraste AA con texto blanco) y tema por defecto del panel.
  - "Reducir animaciones" para todo el portal. Panel y portal usan el color y el logo configurados.
- **Integraciones**:
  - Estado y botón de prueba de Mercado Pago, WhatsApp, correo (envía un correo de prueba), almacenamiento privado, Reverb y Turnstile.
  - **Cloudflare Turnstile** opcional en el constructor; el honeypot y el límite por IP siguen activos.
- **Dashboard del dueño**: ingresos del mes contra el mes anterior, conversión de cotizaciones, solicitudes del mes con referidos, órdenes abiertas, stock crítico y gráfico de ingresos de 12 meses. Recepción y tapicero no ven cifras.
- **Reportes** (`/admin/reports`, permiso `reports.view`), por periodo: servicios más vendidos, marcas frecuentes, tiempo promedio por trabajo, rentabilidad (servicios menos material consumido a costo de inventario) y embudo de leads.
- Permisos nuevos: `reviews.manage` (dueño y recepción), `reports.view` y `users.manage` (dueño).

**Aceptación:** el dueño ve un dashboard con ingresos, conversión y stock crítico. Cubierto por pruebas (444 en verde), con WhatsApp y Turnstile simulados (`Http::fake`). Pint y Larastan sin errores.

**Para conectar en real:** ver "Conectar WhatsApp Cloud API" en el README. Las plantillas de Meta deben crearse y aprobarse antes de activar el canal. Mientras tanto los avisos salen por correo (con SMTP configurado en `.env`).

**Fuera de esta fase:**
- Sentry: el estado se muestra, pero se configura en la Fase 7.
- Mercado Livre: queda para cuando se defina el uso.
- Push web: opcional en el plan; hoy no está.

## Fase 7: Endurecimiento y lanzamiento ✅

- **Seguridad**:
  - Cabeceras en todas las respuestas: CSP con lista cerrada de orígenes, `X-Frame-Options`, `nosniff`, `Referrer-Policy`, `Permissions-Policy` y HSTS por https.
  - Proxies de confianza y https forzado en producción.
  - Límites de tasa: portal 120/min por IP, webhooks 240/min, más los que ya había en login, constructor y enlaces firmados.
  - Avatares generados en el servidor: Filament mandaba el nombre a ui-avatars.com.
  - Horizon solo para el dueño. Prueba que recorre todos los recursos del panel y confirma que un cliente no entra a ninguno.
  - `composer audit` y `npm audit` sin vulnerabilidades. Detalle en `docs/SECURITY.md`.
- **Sentry** (`sentry/sentry-laravel` 4.29) integrado en el manejador de excepciones. Se activa con `SENTRY_LARAVEL_DSN` y nunca envía datos personales.
- **Privacidad y LGPD** (Ajustes > Privacidade e LGPD):
  - Ajustes: guardar o no la foto del documento, anonimizar clientes inactivos tras N meses, borrar solicitudes perdidas tras N meses, contacto del encargado de datos y textos legales editables en Markdown pt/es.
  - Páginas públicas `/privacidade` y `/termos`, enlazadas en el pie y en el constructor. Los textos base se deben revisar con un abogado.
  - **Registro de consentimientos**: quién, qué, cuándo y desde dónde (sitio, check-in, panel, enlace de baja), con hash de IP. Visible en la ficha del cliente y en una lista propia.
  - **Exportar** los datos del cliente en JSON (portabilidad) y **anonimizar** (confirmación escrita, solo el dueño). La anonimización borra contactos, documento, fotos y actas, cambia las placas por un código, limpia la auditoría y la cuenta del portal, y conserva montos y servicios por obligación fiscal. Se rechaza si hay algo abierto.
  - Retención automática diaria (`olympo:privacy:retention`).
- **Respaldos y salud** (Ajustes > Backups e saúde):
  - Copias con `spatie/laravel-backup` 10: base de datos y, opcionalmente, archivos privados y públicos.
  - Configurable: frecuencia, hora, retención, destinos (servidor y nube S3/R2), ZIP con contraseña.
  - Un fallo avisa al dueño. Botón "Fazer cópia agora".
  - **Prueba de restauración**: mensual y con botón. Abre la última copia, verifica el volcado y las tablas clave y, si hay base de prueba, la restaura y cuenta registros.
  - **Salud**: base de datos, colas, planificador (latido cada minuto), Reverb, disco, última copia y última prueba.
  - **Modo mantenimiento** con confirmación: cierra solo el portal; el panel, los webhooks de pago y `/up` siguen. Página 503 con la marca.
  - **Exportar e importar la configuración** en JSON sin secretos, para pasar de pruebas a producción.
- **Auditoría** (Ajustes > Auditoria): visor del registro con filtros por área, persona y fechas, detalle del valor anterior y nuevo, exportación CSV y retención configurable (mínimo 180 días, limpieza diaria).
- **Accesibilidad**: auditoría con axe-core (WCAG 2.1 AA) en móvil y escritorio, sobre 20 páginas del portal y del panel. Se corrigieron marcadores de imagen sin texto, contrastes de textos secundarios y el botón rojo de Filament (4,33:1 → AA). Resultado: **0 violaciones**. Script: `npm run test:a11y`.
- **Carga**: script sin dependencias (`npm run test:load`) y escenario k6. En este contenedor (4 núcleos, servidor de desarrollo de PHP, no PHP-FPM) cada página tarda 50–85 ms sola; con 8 usuarios concurrentes da ≈21 req/s, p95 ≈420 ms y 0 errores. Repetir contra el servidor real.
- **Lanzamiento**:
  - Guía completa en `docs/DEPLOY.md`.
  - Plantillas de servidor: Nginx con HTTPS, HTTP/2, gzip y proxy de Reverb; Supervisor para Horizon y Reverb; cron; script de despliegue sin caída; `.env.production.example`.
  - `olympo:create-owner` para el primer acceso.

**Aceptación de la fase:** cubierta por pruebas (478 en verde), Pint y Larastan sin errores. El despliegue real queda pendiente: requiere el servidor, el dominio y las credenciales del taller. La guía y las plantillas están listas.

**Pospuesto a pedido del taller:** WhatsApp Cloud API y correo SMTP se conectan al final (ver README). Mientras tanto, los avisos quedan en el registro de envíos y los fallos técnicos llegan a la campana del dueño.

## Ajustes tras la primera prueba local

- **Logo oficial** (emblema de Zeus con el rayo azul):
  - Va en el portal, el panel (emblema + "OLYMPO / ESTOFARIA" en Sora, se adapta al tema claro u oscuro), la página de mantenimiento, el encabezado de los PDF (actas y cotización), el favicon, el ícono de iPhone/Android y la imagen al compartir en redes.
  - Se recortó al círculo (esquinas transparentes) y se optimizó de 202 KB a 36 KB.
  - Si el dueño sube un logo propio en Ajustes > Marca, se usa ese.
- **Panel más simple**:
  - **Menú por flujo de trabajo**: Inicio y Relatórios arriba; *Dia a dia* (agenda, quadro, vistorias, ordens); *Clientes e vendas*; *Oficina* y *Site* plegados. La lista de agendamentos se abre desde la agenda ("Ver em lista").
  - **Inicio nuevo**: saludo, accesos rápidos según el rol (agendar, quadro, novo orçamento, novo cliente), recepciones de hoy con el botón "Receber veículo" y pendientes (prontos para entregar, atrasadas, solicitações novas, orçamentos aguardando). Las cifras del negocio siguen solo para el dueño.
  - **Listas en tarjetas** en lugar de tablas: clientes, veículos, solicitações, orçamentos, pagamentos, ordens, agendamentos, vistorias y estoque. Cada tarjeta muestra el título, un estado y 2–3 datos con íconos. La búsqueda, los filtros y el orden siguen igual.
  - Una frase bajo el título de cada módulo explica para qué sirve, y las listas vacías dicen qué hacer.
- **Tutoriales guiados** (driver.js 1.4.0, autoalojado):
  - Hay un tutorial para cada pantalla principal: inicio, agenda, check-in, quadro, ordens, orçamentos, pagamentos, clientes, veículos, estoque, catálogo, galeria, avaliações, relatórios y ajustes.
  - Se abre solo la primera vez. Después se vuelve a ver con el botón "?" de la barra superior, o se reinician todos en Mi perfil > "Rever tutoriais".
  - El progreso se guarda por usuario (`users.completed_tours`). Los textos están en pt-BR y es.
  - Respeta "reducir movimiento" y usa los colores y fuentes de la marca.
- Accesibilidad: sin violaciones WCAG 2.1 AA (axe-core, 20 páginas). Pruebas: 491 en verde.
