# Plan de desarrollo: Sistema web para taller de tapicería automotriz

> Documento pensado para usarse en Claude Code. Cópialo a la raíz del proyecto como `PLAN.md` y pega la sección 12 como `CLAUDE.md`.

## 1. Contexto y objetivo

Taller de tapicería y personalización de interiores de autos (asientos, volantes, puertas, techo, pisos, tintas/Colortek, restauración de autos antiguos). Mercado principal: Brasil (portugués), con posibilidad de español.

El sistema tiene dos caras:

- **Portal del cliente:** explorar catálogo, armar un proyecto visual, pedir cotización, agendar y seguir el trabajo.
- **Panel interno del taller:** clientes, vehículos, citas, check-in con diagrama de daños, órdenes de trabajo, inventario, finanzas y garantías.

Principio rector: el cliente descubre y decide en el portal; el taller opera todo desde el panel; ambos comparten una sola base de datos y un solo flujo (lead → cotización → cita → check-in → trabajo → entrega → garantía).

## 2. Decisiones de producto (ya tomadas)

1. **El catálogo y el constructor son públicos.** Registro y pago solo al confirmar. El pago inicial es un **anticipo/seña**, no un peaje para entrar.
2. **Cotización en dos pasos:** el sistema calcula un rango estimado y el taller confirma el precio final.
3. **Documento de identidad:** guardar el número y, solo si es indispensable, la foto, en almacenamiento privado cifrado, con consentimiento explícito (LGPD).
4. **Multi-idioma desde el día 1:** `pt-BR` (principal) y `es`.
5. **Un solo taller al inicio**, pero con el esquema preparado para varias sucursales (`branch_id` donde aplique).
6. **El check-in no lleva firma del cliente.** Se registra el consentimiento LGPD y el acta en PDF se envía al cliente por WhatsApp o correo, con registro de quién la envió, cuándo y por qué canal. (La firma del acta de entrega queda pendiente de confirmar.)
7. **Todo lo configurable vive en un panel de Ajustes** (sección 2.6), editable solo por el dueño y con auditoría; nada de valores de negocio fijos en el código.

## 2.1 Identidad visual y paleta (a partir del logo)

El logo es un busto griego (estilo Zeus/Poseidón) en blanco y gris sobre un círculo azul, con olas grises y la palabra OLYMPO en una tipografía de grecas. Va sobre fondo negro. Por eso el sistema usa un **tema oscuro como base**: premium, automotriz y coherente con el Instagram del taller.

> Los hex siguientes están estimados a partir de la imagen del logo. Antes de fijarlos, verificarlos con un cuentagotas sobre el archivo original (PNG/SVG).

| Token | Hex (estimado) | Uso |
|---|---|---|
| `brand-500` Azul Olympo | `#0B8FD9` | Color de marca, acentos, enlaces sobre fondo oscuro, íconos activos |
| `brand-700` Azul profundo | `#0877B5` | Botones primarios con texto blanco (cumple contraste AA) |
| `brand-800` Azul hover | `#066A9F` | Estado hover/pressed de botones |
| `ink-950` Negro | `#000000` | Fondo principal del portal |
| `ink-900` Negro suave | `#0D0D0D` | Tarjetas y paneles sobre el fondo |
| `ink-700` Gris oscuro | `#3A3A3A` | Bordes, divisores, fondos secundarios |
| `steel-500` Gris acero | `#737373` | Texto secundario, íconos inactivos (las olas del logo) |
| `steel-300` Gris claro | `#C9C9C9` | Sombras del busto, bordes suaves, texto de apoyo |
| `paper` Blanco | `#FFFFFF` | Texto principal sobre oscuro, logo |
| Estados | éxito `#2FA36B`, alerta `#E0A100`, error `#D64545` | Estados de orden, pagos y validaciones (no vienen del logo) |

**Reglas de contraste (calculadas):**
- Blanco sobre `#0B8FD9`: 3,5:1. Solo sirve para texto grande o en negrita. Para botones y texto pequeño, usar `brand-700`.
- Negro sobre `#0B8FD9`: 5,9:1, cumple AA.
- `#0B8FD9` sobre negro: 5,9:1, sirve para enlaces y acentos en tema oscuro.

**Tipografía (en toda la aplicación, portal y panel):**
- **Títulos y cifras destacadas: Sora** (pesos 500–700). Geométrica, ancha y moderna, con aire premium y automotriz; funciona muy bien con el logo sobre fondo negro y con números grandes (KPIs, importes).
- **Cuerpo, tablas y formularios: Manrope** (pesos 400–700). Muy legible en tamaños pequeños y en tablas densas.
- Ambas son de Google Fonts. Cargar solo los pesos usados, con `font-display: swap`, y en producción **alojarlas en el propio servidor** (self-hosting) para ganar velocidad y no depender de un tercero.
- Pila de respaldo: `system-ui, sans-serif`. Sin otras fuentes en la interfaz; los PDFs (cotizaciones y actas) usan la misma pareja.
- Ya no se usan Cinzel ni Inter. El logo es un wordmark propio, no se reemplaza con texto.

**Uso del logo:**
- Versión principal sobre negro. Para fondos claros (PDFs, cotizaciones, actas), pedir al taller una versión con trazos oscuros o usar el círculo azul con fondo blanco.
- Respetar un margen mínimo igual a la altura de la letra "O" alrededor del logo.
- Favicon y avatar: solo el círculo con el busto, sin texto.

**Panel interno (Filament):** configurar el color primario en `brand-500`/`brand-700`, modo oscuro por defecto y el logo en la cabecera y la pantalla de login.

## 2.2 Landing y catálogo: dirección de movimiento

Objetivo: que quien llega desde Instagram sienta en los primeros 3 segundos que es un taller premium, y que el catálogo se disfrute como un configurador de autos de gama alta. El movimiento debe servir para vender (guiar la mirada, mostrar el antes y después, hacer tangible el material), no solo decorar.

### Estructura de la landing (scroll narrativo)

1. **Hero de impacto:** video corto en bucle (interior de cuero, costuras en macro) a pantalla completa, con el logo que aparece con un trazo/máscara y el titular entrando palabra por palabra. Botón principal "Arma tu proyecto" y secundario de WhatsApp.
2. **Transición al logo:** el círculo del logo crece y se convierte en la sección siguiente (efecto de máscara circular al hacer scroll).
3. **Servicios (asientos, volantes, puertas, techo, piso, tintas):** tarjetas que entran escalonadas, con inclinación sutil (tilt) y zoom de imagen al pasar el cursor.
4. **Configurador en vivo (momento estrella):** un interior de auto en SVG donde el usuario cambia color de cuero, costuras y detalles y la transición de color es fluida y en tiempo real. Es el mismo componente del constructor, en versión demo.
5. **Antes y después:** control deslizante arrastrable sobre fotos reales de trabajos.
6. **Galería filtrable:** al filtrar por marca, modelo o material, las tarjetas se reordenan con animación (técnica FLIP), sin saltos.
7. **Material en detalle:** al elegir un cuero o tela, la imagen se amplía en macro con textura y el nombre, origen y características aparecen en un panel lateral.
8. **Prueba social:** contadores animados (autos tapizados, años, seguidores) y reseñas con fotos.
9. **CTA final:** barra fija inferior en móvil con "Armar proyecto" y WhatsApp.

### Lenguaje de movimiento (reglas)

- **Duración:** 200–300 ms en interacciones, 600–900 ms en entradas de sección. Easing suave (`power3.out` o `cubic-bezier(0.22, 1, 0.36, 1)`).
- **Solo `transform` y `opacity`** para animar. Nada de animar `width`, `height`, `top` o `box-shadow` pesado.
- **Carácter:** elegante y preciso, sin rebotes juguetones. Referencia mental: configurador de un fabricante premium.
- **Acento de marca:** el azul `brand-500` para destellos, líneas que se dibujan y estados activos. Fondo negro con profundidad (gradientes sutiles, grano ligero).
- **Cursor y hover:** estados de hover claros, cursor personalizado opcional solo en escritorio.
- **Transiciones entre páginas:** View Transitions API con respaldo sin animación en navegadores que no la soporten.

### Accesibilidad y rendimiento (no negociables)

- Respetar `prefers-reduced-motion`: sin parallax, sin autoplay y con transiciones mínimas.
- Diseño **móvil primero**: la mayoría llegará desde Instagram. Probar en un teléfono de gama media.
- Objetivos: LCP bajo 2,5 s, CLS bajo 0,1 y 60 fps en scroll. Hero con imagen de póster mientras carga el video; video en MP4/WebM comprimido y sin audio.
- Imágenes en AVIF/WebP con `srcset`, carga diferida fuera de la primera pantalla.
- Cargar las librerías de animación solo en las páginas que las usan.
- Contenido legible sin JavaScript (SEO): los textos y enlaces existen en el HTML, la animación los mejora.

## 2.3 Medios y contenido en movimiento

### Hero: máquina de coser cosiendo cuero

Un video de fondo a pantalla completa en bucle. Es la pieza que define la primera impresión.

**Toma ideal (guion para grabarlo en el taller):**
1. Plano macro de la aguja entrando en el cuero, con la costura avanzando (contrastada, hilo visible).
2. Plano lateral de las manos guiando el cuero.
3. Detalle de la costura terminada sobre un asiento (cuero naranja o caramelo, como en sus trabajos).
4. Cierre con un paneo corto sobre un interior terminado.
- Luz lateral o rasante para resaltar la textura del cuero; fondo oscuro para que combine con el tema negro del sitio.
- Cámara fija o con movimiento lento. Grabar en vertical y en horizontal (el sitio usará ambas versiones).
- Sin audio. Duración final del bucle: 8–12 segundos, con el último cuadro enlazando con el primero.

**Especificaciones técnicas:**
- Escritorio: 1920×1080, MP4 (H.264) más WebM, entre 2 y 4 MB. Móvil: 720×1280 vertical, menos de 2 MB.
- Atributos: `autoplay muted loop playsinline preload="metadata"` y una imagen `poster` para que haya contenido antes de que cargue.
- Capa oscura con degradado (negro 40–60 %) para que el titular blanco se lea siempre.
- Con `prefers-reduced-motion` o conexión lenta: mostrar solo el póster.
- Si aún no existe la toma: usar un video de stock con licencia comercial como marcador temporal (por ejemplo, de Pexels o Pixabay, verificando la licencia de cada clip) y reemplazarlo cuando el taller grabe el suyo.

### Inventario de contenido del Instagram del taller

Fuente: el contenido propio del taller (reels, fotos, destacados). Puntos importantes:
- **Pedir los archivos originales al taller.** Tienen mejor calidad que las versiones descargadas de Instagram, que vienen recomprimidas y a veces con marca de agua.
- **Autorización escrita del dueño** para usar fotos y videos en el sitio, y permiso de las personas que aparezcan (clientes, empleados).
- **Música de los reels:** no reutilizarla; en el sitio van sin audio o con música con licencia.
- Organizar el material en carpetas por servicio: asientos, volantes, puertas, techo, piso, tintas/Colortek, restauración, fachada y equipo.
- Cada pieza se guarda con: servicio, marca/modelo del auto, material, color y si sirve para hero, galería o antes/después.

### Dónde usar más movimiento

| Zona | Efecto con video o animación |
|---|---|
| Hero | Video de costura en bucle con el logo apareciendo encima |
| Franja de servicios | Mosaico de clips cortos en bucle (2–4 s) que se reproducen al entrar en pantalla |
| Catálogo | Tarjetas con foto estática que reproducen un clip corto al pasar el cursor (y al tocar en móvil) |
| Proceso del taller | Línea de tiempo con scroll: recepción, desmontaje, corte, costura, montaje, entrega, con un video por paso |
| Antes y después | Control deslizante sobre fotos o clips alineados |
| Reels destacados | Carrusel horizontal tipo "stories" con los mejores clips |
| Marcas atendidas | Cinta horizontal infinita (marquee) con logos de marcas de autos |
| Materiales | Textura en macro que se mueve con el cursor (parallax suave) |

### Gestión técnica de los medios

- Pasar todo por una cola de procesamiento (FFmpeg) que genere versiones MP4/WebM, miniaturas y póster automáticamente al subir desde el panel.
- Panel Filament con una biblioteca de medios: etiquetas, orden, y selector de "dónde se muestra".
- Videos servidos desde almacenamiento S3/R2 con CDN. Cargar solo los que estén visibles (`IntersectionObserver`) y pausar los que salgan de pantalla.
- Límite de reproducciones simultáneas en móvil (máximo 2–3) para cuidar batería y datos.

## 2.4 Tiempo real (WebSockets)

**Tecnología:** **Laravel Reverb** (servidor WebSocket oficial de Laravel) con **Laravel Echo** en el cliente, y Redis para escalar. Livewire y Filament se integran con Echo, así que los componentes se actualizan solos cuando llega un evento.

### Dónde aporta valor

| Funcionalidad | Canal | Qué ocurre en vivo |
|---|---|---|
| Tablero de producción (kanban) | Privado del taller | Mover una orden de etapa lo refleja al instante en todas las pantallas |
| Nuevas solicitudes (leads) | Privado del taller | Aviso inmediato en el panel cuando un cliente envía un proyecto |
| Pagos | Privado del taller y del cliente | El pago confirmado por el webhook actualiza la cita y la pantalla del cliente sin recargar |
| Seguimiento del cliente | Privado por orden | El cliente ve el cambio de estado y las fotos de avance mientras ocurren |
| Calendario de citas | Privado del taller | Una cita creada o movida aparece al instante y evita dobles reservas |
| Presencia en órdenes | De presencia | Se ve quién está abriendo o editando la misma orden, para evitar pisar cambios |
| Inventario | Privado del taller | Alerta de stock mínimo en el momento en que se consume el material |
| Procesamiento de medios | Privado del usuario | Barra de progreso mientras FFmpeg convierte el video subido |
| Chat taller-cliente (opcional) | Privado por orden | Mensajes y archivos asociados a la orden, como alternativa a WhatsApp |

### Reglas de implementación

- Eventos con `ShouldBroadcast`, enviados por cola y con `afterCommit`, para no emitir algo que luego se revierte en la base de datos.
- **Canales siempre privados o de presencia**, autorizados con Policies en `routes/channels.php`. Un cliente solo puede escuchar sus propias órdenes.
- **No enviar datos sensibles por el socket** (documentos, datos personales): se emiten solo identificadores y estados, y el cliente consulta el detalle con su sesión.
- Plan B si el socket cae: reconexión automática de Echo y sondeo ligero (`wire:poll`) como respaldo.
- En producción: Reverb detrás de Nginx/Caddy con `wss://`, administrado con Supervisor, y límites de conexiones por usuario.
- Pruebas: cada evento con su prueba de que se emite en el canal correcto y de que un usuario ajeno no puede suscribirse.

## 2.5 Notificaciones, alertas y validaciones

### Notificaciones (sistema multicanal)

Basado en **Laravel Notifications**, con un solo punto de envío y varios canales:

| Canal | Uso |
|---|---|
| In-app (`database`) | Campana de notificaciones en el panel Filament y en el portal del cliente, con contador y estado leído/no leído |
| Tiempo real (`broadcast`) | Aviso instantáneo por Reverb (sección 2.4) |
| Correo | Cotización, comprobantes, actas en PDF, recordatorios |
| WhatsApp | Recordatorio de cita, "tu auto está listo", pago recibido. Con **WhatsApp Cloud API**, plantillas aprobadas por Meta y consentimiento previo (opt-in) |
| Push web (opcional) | Avisos al teléfono del cliente o del equipo sin abrir el sitio |

**Catálogo de eventos (quién recibe qué):**

| Evento | Cliente | Taller |
|---|---|---|
| Proyecto enviado | Confirmación de recepción | Aviso de nuevo lead |
| Cotización lista o actualizada | Correo y WhatsApp con PDF | — |
| Cotización aprobada | Confirmación | Aviso a recepción |
| Pago de anticipo recibido o fallido | Comprobante o aviso de reintento | Aviso a recepción y al dueño |
| Cita creada, movida o cancelada | Confirmación | Aviso al calendario del equipo |
| Recordatorio de cita | 24 h y 2 h antes | — |
| Check-in registrado | Acta en PDF por WhatsApp o correo, con registro de envío | — |
| Cambio de etapa del trabajo | Estado con foto de avance | Actualización del tablero |
| Trabajo listo para entrega | Aviso con saldo pendiente | — |
| Entrega realizada | Acta firmada y garantía | Cierre de orden |
| Garantía por vencer | Recordatorio de revisión | — |

**Reglas del sistema de notificaciones:**
- **Preferencias por usuario:** qué canales quiere y para qué tipo de aviso; obligatorios los transaccionales (pago, cita).
- **Plantillas traducidas** (`pt-BR` y `es`) y editables desde el panel, con variables (nombre, placa, fecha, enlace).
- **Horario silencioso** para avisos no urgentes y para WhatsApp comercial.
- **Antirrepetición:** un mismo aviso no se envía dos veces (clave de idempotencia) y se agrupan los avisos de ráfagas.
- **Todo por cola** con reintentos y retroceso exponencial; si un canal falla, se usa el siguiente (por ejemplo WhatsApp falla, entonces correo).
- **Registro de envíos** (`notification_logs`): canal, estado, intento, error y fecha, visible en el panel para soporte.
- **Cumplimiento:** consentimiento explícito para mensajes por WhatsApp y correo, con opción de darse de baja.

### Alertas internas del taller (reglas automáticas)

Tareas programadas (scheduler) que revisan condiciones y avisan al responsable; si no se atienden, escalan al dueño:

- **Lead sin respuesta** pasadas X horas.
- **Cotización sin respuesta** del cliente tras X días (recordatorio al cliente y al taller).
- **Cita sin confirmar** el día anterior.
- **Orden atrasada** respecto a la fecha de entrega prometida, o a punto de atrasarse.
- **Anticipo o saldo vencido.**
- **Stock mínimo** de materiales y consumo anormal frente a lo cotizado.
- **Garantías próximas a vencer.**
- **Alertas técnicas** (solo para el administrador): webhook de pago fallido, cola atascada, copia de seguridad que no se realizó, disco casi lleno, errores recurrentes en Sentry.

### Validaciones (en tres capas)

**1. Cliente (ayuda inmediata):** validación en vivo con Livewire/Alpine, máscaras de entrada (teléfono, placa, documento) y mensajes claros. Nunca es la única defensa.

**2. Servidor (obligatoria):** `Form Requests` y reglas personalizadas, siempre. Los mensajes de error van traducidos a `pt-BR` y `es`.

**3. Reglas de negocio (en las Actions/Services):** lo que depende del estado del sistema.

Reglas principales:

| Área | Validación |
|---|---|
| Vehículo | Placa con formato brasileño (antiguo `ABC-1234` y Mercosul `ABC1D23`), configurable por país; año entre 1950 y el año actual + 1; kilometraje positivo |
| Cliente | Teléfono en formato internacional (E.164); correo válido; CPF/CNPJ con dígito verificador (con paquete de validación brasileña vigente o reglas propias); consentimiento LGPD obligatorio para guardar documento |
| Fechas y citas | Recepción no en el pasado; entrega igual o posterior a recepción; respetar capacidad máxima del día, días no laborables y feriados; sin dobles reservas |
| Check-in | No se puede guardar sin kilometraje, combustible y consentimiento LGPD (el check-in no lleva firma); cada marca de daño requiere zona y tipo; fotos con límite de cantidad |
| Archivos | Tipo MIME real (no solo la extensión), tamaño máximo, dimensiones mínimas para fotos, un único documento de identidad por cliente; análisis antivirus opcional (ClamAV) |
| Dinero | Valores en centavos (enteros), mayores que cero; anticipo menor o igual al total; el pago no puede superar el saldo pendiente |
| Estados | **Máquina de estados** con transiciones permitidas (por ejemplo, una orden no pasa de "recibida" a "entregada" sin pasar por "en producción" y "lista"); cada cambio queda auditado |
| Cotizaciones | Precio final solo editable por roles autorizados; versión anterior se conserva; vigencia obligatoria |
| Inventario | Consumo no puede dejar stock negativo; aviso si falta material para la orden |
| Formularios públicos | Protección contra spam (honeypot o Turnstile), límite de tasa y saneamiento de textos |

**Errores y mensajes:** respuestas coherentes, sin exponer detalles internos; el usuario ve qué corregir y en qué campo.

### Pruebas de esta sección

- Cada regla de validación con casos válidos e inválidos (Pest con datasets).
- `Notification::fake()` y `Event::fake()` para comprobar quién recibe cada aviso y por qué canal.
- Pruebas de la máquina de estados: transiciones permitidas y prohibidas.
- Prueba de que las alertas programadas se disparan solo cuando corresponde y no se duplican.

## 2.6 Panel de control (Ajustes del sistema)

**Objetivo:** que el dueño cambie cómo funciona el sistema sin tocar código ni pedir un despliegue: horarios, capacidad, precios, plazos, textos, avisos, permisos y conexiones. La pantalla de referencia es "Ajustes" en el lienzo de diseño: navegación lateral por secciones, filas con interruptores, campos y botones, y un aviso de cambios sin guardar.

### Reglas generales

- **Acceso:** solo el rol `owner` edita (permiso `settings.manage`); los demás roles no ven la sección.
- **Tipos y validación:** cada ajuste tiene tipo (texto, número, booleano, lista, hora, color o secreto), valor por defecto y rango válido. No se guarda nada fuera de rango (por ejemplo, un anticipo entre 0 y 100 %).
- **Guardado en bloque:** botones "Guardar cambios" y "Descartar", con aviso de cambios sin guardar al salir.
- **Caché y tiempo real:** lectura con caché; al guardar se invalida y se emite un evento para que las demás pantallas abiertas usen el valor nuevo.
- **Auditoría:** cada cambio registra quién, cuándo, valor anterior y valor nuevo (los secretos, enmascarados).
- **Secretos** (credenciales de pago, WhatsApp, correo): campos de solo escritura, cifrados, que nunca se muestran de vuelta, con botón "Probar conexión". Los secretos de infraestructura (base de datos, clave de la aplicación) siguen solo en variables de entorno.
- **Acciones críticas con confirmación:** activar el modo producción de pagos, anonimizar a un cliente, activar el modo mantenimiento.
- **Exportar e importar configuración** (JSON sin secretos) para pasar de pruebas a producción.
- **Nada de negocio queda fijo en el código:** capacidad por día, anticipo, vigencias, plazos de garantía, umbrales de alerta, etapas del tablero y plantillas se leen de los ajustes.

### Secciones

| Grupo | Sección | Qué se configura | Fase |
|---|---|---|---|
| Taller | General | Nombre comercial, ciudad, WhatsApp y correo del taller, enlace de Maps, idioma por defecto e idiomas del portal, zona horaria, moneda, país para validaciones (placa, CPF/CNPJ), sucursales | 1 |
| Taller | Agenda y capacidad | Días y horario, vehículos por día (y en sábado), anticipación mínima, duración de recepción, cierre al llenarse, feriados y cierres especiales | 1 |
| Taller | Etapas de producción | Nombre, color y orden de las etapas, etapas visibles al cliente, etapa que avisa, alerta por estancamiento, fotos de avance | 3 |
| Taller | Check-in y checklists | Tipos de daño, objetos del vehículo, niveles de combustible, fotos por daño, kilometraje obligatorio, siluetas por carrocería, envío y texto legal del acta, consentimiento LGPD | 3 |
| Taller | Garantías | Plazo por tipo de servicio, inicio del plazo, recordatorio de revisión, condiciones, pedido de reseña | 3 |
| Ventas | Cotizaciones y precios | Margen del rango estimado, redondeo, mostrar estimado, contacto previo, vigencia, mano de obra, margen sobre materiales, descuento máximo, roles que editan el precio final | 5 |
| Ventas | Pagos | Anticipo, momento del saldo, métodos, cuotas, conexión con Mercado Pago (prueba o producción, webhook) | 5 |
| Ventas | Catálogo y portal | Precios visibles, materiales sin stock, secciones de la landing, titular y botones, video del hero, SEO y redes | 5 |
| Comunicación | Notificaciones | Canales activos, canal de respaldo, recordatorios de cita, horario silencioso, plantillas por idioma, registro de envíos | 6 |
| Comunicación | Alertas automáticas | Umbrales (solicitud, cotización, cita, orden, anticipo), escalamiento al dueño, alertas técnicas | 6 |
| Equipo | Usuarios y roles | Invitar y desactivar usuarios, verificación en dos pasos, cierre por inactividad, bloqueo por intentos, matriz de permisos por rol | 6 |
| Sistema | Marca y apariencia | Logos (oscuro y claro), favicon, colores, tipografías, tema por defecto, animaciones reducidas | 6 |
| Sistema | Integraciones | Estado y prueba de Mercado Pago, WhatsApp Cloud API, correo, almacenamiento, tiempo real, Mercado Livre, Sentry, Turnstile | 6 |
| Sistema | Privacidad y LGPD | Guardar o no la foto del documento, retención de datos, textos legales, exportar y anonimizar clientes, registro de consentimientos | 7 |
| Sistema | Respaldos y salud | Frecuencia y destino de copias, prueba de restauración, estado de colas, tiempo real, tareas y disco, modo mantenimiento | 7 |
| Sistema | Auditoría | Eventos recientes, retención del registro, exportación | 7 |

> Los números que aparecen en el diseño (capacidad de 4 vehículos, anticipo del 30 %, vigencia de 7 días, plazos de garantía, etc.) son **valores de ejemplo**: se definen con el taller antes del lanzamiento y se cargan como valores iniciales editables.

### Implementación

- **Base:** `spatie/laravel-settings` (verificar vigencia y compatibilidad con la versión de Laravel y Filament) con clases tipadas por grupo (`GeneralSettings`, `AgendaSettings`, `PricingSettings`, `PaymentSettings`…) y una página de Filament por sección con el mismo diseño. Alternativa: tabla propia `settings` con casts y caché.
- **Listas editables** (etapas, feriados, plantillas de mensajes, plazos de garantía, siluetas de vehículos) como recursos o tablas propias, no como JSON dentro de ajustes.
- **Lectura** siempre por un servicio de ajustes con caché; prohibido leer `config()` o constantes para valores de negocio.
- **Seeders** con valores iniciales editables y ajustes de ejemplo para el entorno de desarrollo.
- **Pruebas:** permisos por rol, validación de rangos, auditoría de cada cambio, invalidación de caché y que el calendario, las cotizaciones y las alertas usen el valor nuevo.

## 3. Stack recomendado

| Capa | Tecnología | Por qué |
|---|---|---|
| Backend | **Laravel** (última versión estable) + PHP 8.3+ | Productivo, maduro y con todo lo que se necesita |
| Panel interno | **Filament** (última mayor estable) | Panel completo con tablas, formularios, calendario y roles en poco tiempo |
| Portal cliente | **Livewire + Alpine.js + Tailwind CSS** | Mismo ecosistema que Filament, sin duplicar frontend |
| Visualizadores | **SVG interactivo** + Alpine.js | Visualizador de colores y diagrama de daños sin librerías pesadas |
| Animación | **GSAP + ScrollTrigger** (scroll narrativo, máscaras, textos) y **Lenis** (scroll suave) | Estándar de la industria para landings premium; muy buen rendimiento |
| Microinteracciones | CSS (transiciones, `@starting-style`) + View Transitions API + Alpine.js | Lo simple sin JavaScript extra |
| Medios | Video MP4/WebM comprimido, imágenes AVIF/WebP, `medialibrary` para conversiones | Impacto visual sin sacrificar velocidad |
| Tipografía | **Sora** (títulos) y **Manrope** (texto), autoalojadas, solo los pesos usados | Aspecto premium y legibilidad en tablas, sin depender de un tercero |
| Ajustes | `spatie/laravel-settings` + una página de Filament por sección (verificar vigencia y compatibilidad) | Configuración tipada, con caché, permisos y auditoría |
| 3D opcional (fase posterior) | `<model-viewer>` o Three.js | Solo si el taller aporta modelos 3D de asientos o volantes; no es necesario para el MVP |
| Base de datos | **PostgreSQL** | Integridad, JSON y buen rendimiento (MySQL también sirve) |
| Colas y caché | **Redis** + Laravel Horizon | Notificaciones, PDFs y webhooks fuera de la petición |
| Tiempo real | **Laravel Reverb** (WebSockets) + **Laravel Echo** | Kanban, avisos, pagos y seguimiento en vivo, integrado con Livewire y Filament |
| Notificaciones | **Laravel Notifications** (database, broadcast, mail) + WhatsApp Cloud API | Un solo sistema multicanal con preferencias, plantillas y registro de envíos |
| Validación | Form Requests + reglas personalizadas + máquina de estados (Enums y Actions) | Integridad de datos y de procesos en el servidor |
| Antivirus (opcional) | ClamAV | Revisar archivos subidos por clientes |
| Archivos | **S3 compatible** (Cloudflare R2 o S3), bucket **privado** con URLs firmadas | Fotos de autos y documentos sensibles |
| Pagos | **Mercado Pago** (Pix, tarjeta, parcelado) tras una interfaz `PaymentGateway` | Estándar en Brasil y permite cambiar de pasarela |
| WhatsApp | MVP: enlaces `wa.me` con el resumen. Fase posterior: **WhatsApp Cloud API** | Evita depender de aprobación de plantillas al inicio |
| PDFs | Dompdf o Browsershot | Cotizaciones y actas de entrega |
| Firma digital (solo entrega, pendiente de confirmar) | `signature_pad` (JS) | El check-in ya no lleva firma; solo se usaría en el acta de entrega si se decide mantenerla |
| Calendario | Plugin de calendario de Filament o FullCalendar | Citas con capacidad por día |
| Paquetes Spatie | `laravel-permission`, `laravel-medialibrary`, `laravel-activitylog`, `laravel-translatable`, `laravel-settings` | Roles, archivos, auditoría, i18n de contenido y configuración tipada |
| Calidad | **Pest**, Larastan (PHPStan), Laravel Pint | Pruebas, análisis estático y estilo |
| Infra | **Docker** (Laravel Sail en dev), CI en GitHub Actions, despliegue en Laravel Forge/Cloud o VPS | Entornos reproducibles |
| Observabilidad | Sentry + Laravel Telescope (solo dev) | Errores y depuración |

> Claude Code debe verificar las versiones vigentes y la compatibilidad Laravel/Filament/Livewire antes de instalar.

## 4. Arquitectura

- Monolito modular en Laravel. Carpetas por dominio en `app/Domain/*` (Catalog, Customers, Vehicles, Quotes, Appointments, Inspections, WorkOrders, Inventory, Payments, Warranties).
- Lógica de negocio en **Actions/Services**, no en controladores ni en recursos de Filament.
- **Enums** para estados (`QuoteStatus`, `WorkOrderStatus`, `PaymentStatus`, `PartCondition`).
- **Policies** por modelo y roles: `owner`, `reception`, `upholsterer`, `customer`.
- **Eventos y listeners** para notificaciones (cita confirmada, trabajo listo, pago recibido).
- **Webhooks de pago** idempotentes, con firma verificada.
- Todo texto visible en archivos de traducción (`lang/pt_BR`, `lang/es`).

## 5. Modelo de datos (núcleo)

- `users` (rol, idioma) · `customers` (nombre, teléfono, correo, documento_num, consentimiento_lgpd_at)
- `customer_documents` (archivo privado, tipo)
- `vehicles` (customer_id, placa, marca, modelo, año, color, km)
- `materials` (tipo: cuero/tela/vinilo/tinta, nombre, proveedor, costo_m2, precio_m2, activo)
- `material_variants` (material_id, color_hex, imagen, stock_vinculado)
- `service_types` (asientos, volante, puertas, techo, piso, tinta, reforma…) con precio base y unidad
- `vehicle_models` (catálogo marca/modelo/año con plantilla SVG de interior y zonas tapizables)
- `projects` (customer_id, vehicle_id, estado, configuración JSON de colores/piezas/materiales, total_estimado_min, total_estimado_max)
- `quotes` (project_id, versión, total_final, vigencia, estado, PDF)
- `appointments` (vehicle_id, fecha_recepcion, fecha_entrega_acordada, estado)
- `inspections` (appointment_id, km, combustible, objetos_en_auto, observaciones, consentimiento_lgpd_at, acta_pdf, acta_enviada_at, acta_enviada_canal, enviada_por)
- `inspection_marks` (inspection_id, zona, x, y, tipo_daño, nota, foto)
- `work_orders` (quote_id, estado, responsable_id, inicio, fin_previsto, fin_real)
- `work_order_items` (servicio, material_variant_id, cantidad, horas, costo)
- `work_order_updates` (estado, nota, fotos, visible_al_cliente)
- `inventory_items` / `inventory_movements` (entrada, consumo por orden, ajuste)
- `payments` (quote_id, tipo: anticipo/saldo, monto, método, estado, gateway_ref)
- `deliveries` (work_order_id, acta PDF, firma_cliente, fecha)
- `warranties` (work_order_id, vence_at, condiciones) · `expenses` · `reviews`
- Configuración (sección 2.6): `settings` (grupo, clave, valor JSON, tipo, es_secreto, actualizado_por) · `business_hours` · `holidays` · `workflow_stages` (nombre, color, orden, visible_cliente) · `message_templates` (evento, canal, idioma, cuerpo) · `warranty_terms` (tipo_servicio, meses) · `vehicle_silhouettes` (carrocería, zonas SVG).
- Auditoría con `activity_log` en modelos sensibles.

## 6. Fases de desarrollo

Cada fase termina con pruebas en verde, Pint y Larastan sin errores, y una demo funcional.

### Fase 0: Cimientos (≈ 1 semana)
- Proyecto Laravel con Docker/Sail, PostgreSQL, Redis, Pest, Pint, Larastan y CI.
- Autenticación (Fortify o Breeze), roles y permisos, i18n `pt-BR`/`es`.
- Instalar Filament con panel `/admin` y layout base del portal público.
- Instalar y configurar **Reverb + Echo** (sección 2.4), con un canal privado de prueba y su autorización.
- Seeders con datos demo (materiales, servicios, 3 modelos de auto).
- **Base de ajustes** (sección 2.6): clases de configuración tipadas con caché, permiso `settings.manage` solo para el dueño, página "Ajustes" con navegación lateral por secciones, guardado en bloque (guardar/descartar) y auditoría de cada cambio.
- **Identidad visual:** tokens de color en `tailwind.config` según la sección 2.1, fuentes (Sora para títulos y Manrope para texto, autoalojadas), tema oscuro por defecto, logo y favicon, y color primario de Filament alineado con la marca.
- **Aceptación:** login por rol, panel vacío accesible solo al personal, CI pasando y portal/panel usando la paleta de marca.

### Fase 1: Núcleo operativo del taller (≈ 2 semanas)
- Recursos Filament: clientes, vehículos, servicios, materiales, citas.
- Calendario de citas con capacidad máxima por día.
- **Ajustes de General y Agenda** (sección 2.6): datos del taller, idioma, zona horaria, horarios, capacidad por día, feriados y cierres; el calendario lee estos valores en lugar de números fijos.
- Documento del cliente subido a bucket privado con consentimiento LGPD.
- **Validaciones base** (sección 2.5): placa, teléfono, documento, fechas y reglas de capacidad de la agenda, con mensajes traducidos y pruebas por regla.
- **Aceptación:** crear cliente → vehículo → cita visible en calendario; el documento solo se abre con URL firmada y permiso.

### Fase 2: Check-in e inspección (≈ 1,5 semanas)
- Diagrama SVG del vehículo por zonas; clic para marcar daños (tipo, nota y foto).
- Formulario de observaciones, km, combustible y objetos dentro del auto.
- PDF del acta de recepción, enviado al cliente por WhatsApp o correo, con registro de envío y consentimiento LGPD. **Sin firma digital en el check-in.**
- **Aceptación:** inspección completa registrada, con PDF descargable y enviado al cliente.

### Fase 3: Órdenes de trabajo e inventario (≈ 2 semanas)
- Orden de trabajo con servicios, materiales, horas y responsable.
- Tablero kanban de producción por estado, **sincronizado en tiempo real** entre pantallas y con presencia de quién edita cada orden.
- Avisos en vivo en el panel: nueva solicitud, stock mínimo y cita creada o movida.
- **Centro de notificaciones in-app** (campana con leído/no leído) y **máquina de estados** de la orden de trabajo con transiciones validadas y auditadas.
- Primeras alertas internas programadas: stock mínimo, orden atrasada y cita sin confirmar.
- **Ajustes de etapas del tablero, check-in y garantías** (sección 2.6): etapas editables, tipos de daño, objetos del vehículo, siluetas, texto del acta y plazos de garantía.
- Inventario con consumo automático por orden y alertas de stock mínimo.
- Acta de entrega con comparación inspección inicial vs. estado final, y firma (pendiente de confirmar: si la entrega también sale sin firma, se reemplaza por el envío del acta con registro, igual que en el check-in).
- **Aceptación:** una orden consume inventario, avanza por el tablero y cierra con acta.

### Fase 4: Portal del cliente y constructor (≈ 3 semanas)
- Catálogo público filtrable (materiales, colores, galería de trabajos por marca/modelo).
- Constructor paso a paso: vehículo → piezas → material y color (visualizador SVG) → resumen.
- Cálculo de **rango estimado** con servicios `service_types` y `materials`.
- Guardar proyecto, registro ligero y envío al taller; botón de WhatsApp con el resumen.
- **Landing de impacto** según la sección 2.2: hero con video, scroll narrativo con GSAP/Lenis, configurador en vivo, antes y después, galería filtrable con animación FLIP.
- Compatibilidad Livewire + animaciones: inicializar GSAP en `x-init`/`wire:ignore`, destruir los `ScrollTrigger` al navegar (`wire:navigate`) y refrescarlos tras actualizaciones del DOM.
- Auditoría de rendimiento y movimiento: Lighthouse móvil, `prefers-reduced-motion` y prueba en teléfono real.
- **Medios en movimiento** según la sección 2.3: hero con video de costura, clips en bucle por servicio, hover-play en el catálogo, línea de tiempo del proceso, y pipeline FFmpeg con biblioteca de medios en el panel.
- **Aceptación:** un visitante arma un proyecto, ve el estimado y el taller lo recibe como lead.

### Fase 5: Cotización, pagos y seguimiento (≈ 2 semanas)
- Flujo lead → cotización final (PDF) → aprobación del cliente.
- Pagos con Mercado Pago: anticipo al confirmar y saldo al entregar; Pix y tarjeta.
- Webhooks idempotentes y conciliación.
- **Ajustes de cotizaciones, pagos y catálogo/portal** (sección 2.6): margen del rango estimado, vigencia, descuentos, anticipo, métodos, conexión con Mercado Pago (modo prueba y producción), textos de la landing y SEO.
- Seguimiento del trabajo para el cliente con fotos (`work_order_updates` visibles), **actualizado en vivo** por WebSocket, y confirmación de pago en tiempo real en la pantalla del cliente.
- **Aceptación:** un pago de prueba confirma la cita y el cliente ve el avance de su orden.

### Fase 6: Comunicación y crecimiento (≈ 2 semanas)
- **Sistema completo de notificaciones multicanal** (sección 2.5): correo, WhatsApp Cloud API, in-app y push web opcional, con preferencias por usuario, plantillas editables `pt-BR`/`es`, horario silencioso, reintentos con respaldo de canal y registro de envíos.
- Catálogo de eventos completo (recordatorios de cita, auto listo, pago, garantía) y **alertas internas con escalamiento** al dueño (lead sin respuesta, anticipo vencido, orden atrasada, fallos técnicos).
- Garantías con recordatorios de revisión; reseñas con fotos antes/después.
- **Ajustes de notificaciones y alertas, usuarios y roles, marca e integraciones** (sección 2.6): plantillas editables, umbrales y escalamiento, matriz de permisos, logos y colores, y estado de cada servicio conectado.
- Seguimiento de leads (nuevo, cotizado, aprobado, perdido) y código de referidos.
- Reportes: servicios más vendidos, marcas frecuentes, tiempo por trabajo, rentabilidad.
- **Aceptación:** el dueño ve un dashboard con ingresos, conversión y stock crítico.

### Fase 7: Endurecimiento y lanzamiento (≈ 1 semana)
- Auditoría de seguridad, límites de tasa, copias de seguridad automáticas, Sentry.
- **Ajustes de privacidad/LGPD, respaldos y salud del sistema, y auditoría** (sección 2.6): retención de datos, exportar y anonimizar clientes, prueba de restauración, modo mantenimiento y registro de cambios.
- Pruebas de carga básicas y de accesibilidad; textos legales (privacidad, términos).
- Despliegue en producción con HTTPS y monitoreo.

## 7. Seguridad y cumplimiento

- Datos personales y documentos: cifrado en reposo, bucket privado, URLs firmadas y de corta duración.
- Consentimiento LGPD registrado con fecha; opción de exportar y eliminar datos del cliente.
- Autorización por Policies en cada recurso; pruebas que verifican que un cliente nunca ve datos de otro.
- Webhooks con verificación de firma y protección contra repetición.
- Rate limiting en login, formularios públicos y constructor; protección contra spam (honeypot o Turnstile).
- Registro de auditoría de quién cambió estados, precios y documentos.
- Secretos solo en variables de entorno; nunca en el repositorio.

## 8. Pruebas

- **Pest:** pruebas de feature para cada flujo y de unidad para el cálculo de cotizaciones.
- Pruebas de autorización por rol en cada fase.
- Pruebas de webhooks de pago con payloads simulados.
- Pruebas de navegador (Pest Browser o Dusk) para el constructor y el diagrama de daños.
- Meta mínima: todo el cálculo de dinero cubierto al 100 %.
- Ajustes: pruebas de permisos por rol, validación de rangos, auditoría de cada cambio e invalidación de caché (el calendario, las cotizaciones y las alertas usan el valor nuevo).

## 9. Rendimiento y UX

- Imágenes optimizadas (WebP, miniaturas con medialibrary) y carga diferida.
- Portal pensado primero para móvil: la mayoría llegará desde Instagram.
- Constructor guarda borrador automáticamente para no perder el avance.
- Paginación y caché en catálogo y galería.

## 10. Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Visualizador de colores complejo | Empezar con SVG por zonas y plantillas para pocos modelos; ampliar según demanda |
| Precio estimado que no coincide con el final | Mostrar siempre como rango y requerir confirmación del taller |
| Datos sensibles filtrados | Bucket privado, cifrado, permisos y auditoría |
| Dependencia de una pasarela | Interfaz `PaymentGateway` desacoplada |
| Alcance que crece | Entregar por fases con criterios de aceptación; congelar el MVP (Fases 0–3 y 4 básica) |
| Demasiados ajustes confunden | Agrupar por secciones, valores por defecto sensatos, solo el dueño edita, guardado en bloque con opción de descartar y auditoría de cada cambio |
| Check-in sin firma pierde valor como prueba | Enviar el acta con fotos al cliente, guardar el registro de envío y el consentimiento LGPD con fecha |

## 11. Orden recomendado si hay que priorizar

1. Fases 0, 1, 2 y 3 (operación del taller: es lo que genera valor inmediato).
2. Constructor con cotización estimada y botón de WhatsApp (Fase 4 simplificada).
3. Pagos y seguimiento (Fase 5).
4. Todo lo demás.

## 12. Contenido sugerido para `CLAUDE.md`

```
# Proyecto: Sistema para taller de tapicería automotriz

## Stack
Laravel (última estable), PHP 8.3+, Filament para el panel /admin, Livewire + Alpine + Tailwind para el portal, PostgreSQL, Redis + Horizon, Pest, Pint, Larastan.

## Reglas
- Responde y comenta en español; el código y los nombres de clases en inglés.
- Lógica de negocio en Actions/Services en app/Domain/*, nunca en controladores ni recursos Filament.
- Estados con Enums. Autorización con Policies y spatie/laravel-permission.
- Todo texto visible con traducciones (lang/pt_BR y lang/es).
- Dinero en centavos (enteros) y moneda BRL.
- Archivos sensibles siempre en disco privado con URLs firmadas.
- Cada tarea termina con: tests Pest en verde, `pint` y `phpstan` sin errores.
- Antes de instalar paquetes, verifica compatibilidad de versiones.
- Notificaciones y validaciones según la sección 2.5: un solo sistema multicanal con Laravel Notifications, validación en tres capas (cliente, Form Requests y reglas de negocio en Actions), máquina de estados para transiciones, mensajes traducidos y una prueba por cada regla.
- Tiempo real: Reverb + Echo según la sección 2.4. Canales privados o de presencia con autorización por Policy, eventos por cola con afterCommit y sin datos sensibles en el socket.
- Landing y catálogo: sigue la sección 2.2 (movimiento). Anima solo transform y opacity, respeta prefers-reduced-motion y carga GSAP/Lenis solo donde se usen.
- Diseño: usa solo los tokens de color de la sección 2.1 de PLAN.md (nada de colores sueltos en las vistas), tema oscuro por defecto y botones primarios en brand-700 con texto blanco.
- Tipografía: Sora para títulos y cifras, Manrope para texto, tablas y formularios, autoalojadas y solo con los pesos usados. No uses otras fuentes.
- Check-in: no lleva firma del cliente. Guarda consentimiento LGPD y envía el acta en PDF por WhatsApp o correo, registrando quién, cuándo y por qué canal.
- Ajustes (sección 2.6): todo valor de negocio configurable (capacidad por día, anticipo, vigencias, plazos, umbrales de alerta, etapas, plantillas) se lee del servicio de ajustes con caché; nada fijo en el código. Solo el dueño edita, cada cambio se audita y los secretos se guardan cifrados y nunca se muestran.
- Trabaja por fases según PLAN.md; no avances a la siguiente sin cumplir la aceptación.
```

## 13. Primer prompt sugerido para Claude Code

```
Lee PLAN.md y CLAUDE.md. Empieza por la Fase 0: crea el proyecto Laravel con Sail, PostgreSQL y Redis, instala Pest, Pint y Larastan, configura Filament en /admin, roles y permisos, i18n pt-BR/es y los seeders demo. Antes de escribir código, muéstrame un plan corto de los pasos y las versiones que vas a instalar.
```
