# Olympo Estofaria

Sistema web del taller de tapicería automotriz Olympo: portal del cliente (catálogo, constructor, cotización, seguimiento) y panel interno (clientes, vehículos, agenda, check-in, órdenes, inventario, pagos y garantías).

- Plan completo y fases: [`PLAN.md`](PLAN.md)
- Reglas para el agente: [`CLAUDE.md`](CLAUDE.md)
- Estado por fase: [`docs/PROGRESS.md`](docs/PROGRESS.md)

## Stack

| Capa | Versión |
|---|---|
| PHP | 8.3+ |
| Laravel | 13 |
| Filament (panel `/admin`) | 5 |
| Livewire | 4 |
| Tailwind CSS | 4 |
| PostgreSQL / Redis | 16+ / 7+ |
| Reverb + Echo | 1.x |
| Pest / Larastan / Pint | 4 / 3 / 1 |
| spatie: permission, settings, activitylog, translatable | 8 / 3 / 4 / 6 |

Pest 4 y activitylog 4 se eligieron porque sus versiones 5 exigen PHP 8.4. La plataforma de Composer está fijada en PHP 8.3 (`config.platform.php`) para que el lock siga siendo compatible.

## Puesta en marcha (local)

El código está en la rama `claude/brave-curie-uoh85c`.

```bash
git clone https://github.com/Alejandro22R/olympoestofaria.git
```

```bash
cd olympoestofaria && git checkout claude/brave-curie-uoh85c
```

### Opción A: con Docker (recomendada; en Windows, dentro de WSL2)

Solo necesitas Docker Desktop. PHP 8.5, PostgreSQL, Redis, FFmpeg y Node vienen en los contenedores.

```bash
cp .env.example .env
```

En `.env` cambia: `APP_URL=http://localhost`, `DB_HOST=pgsql`, `REDIS_HOST=redis`.

Dependencias de PHP sin instalar PHP en tu PC:

```bash
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs
```

```bash
./vendor/bin/sail up -d
```

```bash
./vendor/bin/sail artisan key:generate
```

```bash
./vendor/bin/sail artisan storage:link
```

```bash
./vendor/bin/sail artisan migrate --seed
```

```bash
./vendor/bin/sail npm install
```

```bash
./vendor/bin/sail npm run build
```

Procesos en segundo plano (cada uno en su terminal):

```bash
./vendor/bin/sail artisan queue:listen --tries=1
```

```bash
./vendor/bin/sail artisan reverb:start --host=0.0.0.0
```

```bash
./vendor/bin/sail artisan schedule:work
```

Abre http://localhost (portal) y http://localhost/admin (panel).

### Opción B: sin Docker

Necesitas PHP 8.3+ (extensiones `pgsql`, `redis` o `predis`, `intl`, `gd`, `zip`, `bcmath`), Composer, Node 22, PostgreSQL y Redis. FFmpeg es opcional (videos de la galería).
Crea el usuario `olympo` (contraseña `secret`) y las bases `olympo` y `olympo_test`, o ajusta `.env` y `phpunit.xml`.

```bash
composer setup
```

```bash
php artisan storage:link
```

```bash
php artisan migrate --seed
```

```bash
composer dev
```

`composer dev` levanta el servidor (http://localhost:8000), la cola, los logs y Vite. Aparte: `php artisan reverb:start` (tiempo real) y `php artisan schedule:work` (alertas y tareas programadas).

## Despliegue

Después de cada despliegue con migraciones:

```bash
php artisan migrate --force
```

```bash
php artisan settings:clear-cache
```

Los ajustes se leen con caché; si se agregan campos nuevos de ajustes, la caché vieja debe limpiarse.

Procesos permanentes (Supervisor): `php artisan horizon` (colas: notificaciones, correos, conversión de videos), `php artisan reverb:start` (WebSockets) y el scheduler (`* * * * * php artisan schedule:run`), que ejecuta las alertas, los avisos programados al cliente (recordatorios, garantía, reseñas), la conciliación de pagos, el vencimiento de cotizaciones y la depuración del registro de envíos.

## Conectar Mercado Pago (Pix)

Mercado Pago viene **desactivado**. Mientras esté así, el taller registra los pagos a mano (efectivo, Pix directo, transferencia) desde la cotización o la orden.

1. En el panel de desarrolladores de Mercado Pago, crea una aplicación ("Suas integrações") y copia las credenciales **de prueba**: Public Key y Access Token.
2. En **Ajustes > Pagos**: activa "pagos online por Mercado Pago", deja el modo en **Teste**, marca Pix, pega Public Key y Access Token y guarda. Usa **Testar conexão** para verificar.
3. Copia la **URL de notificaciones** que muestra esa página (`https://tu-dominio/webhooks/mercadopago`). En Mercado Pago > Webhooks, pégala con el evento **Pagamentos** y copia la **assinatura secreta** al campo "Assinatura secreta do webhook". En producción la URL debe ser HTTPS pública.
4. Prueba un pago con un usuario de prueba de Mercado Pago: aprueba una cotización desde el portal y paga el Pix; el pago se confirma solo por el webhook (y, si un aviso se pierde, la conciliación cada 10 minutos lo recupera).
5. Para cobrar de verdad: reemplaza las credenciales por las de **producción**, cambia el modo a **Produção** (pide confirmación explícita) y actualiza el webhook de producción.

Las credenciales se guardan cifradas en la base de datos, nunca se muestran de vuelta y no quedan en la auditoría. Dejar un campo de secreto vacío al guardar conserva el valor actual.

## Conectar WhatsApp Cloud API

WhatsApp viene **desactivado**. Mientras esté así, los avisos salen por correo y las actas usan el enlace wa.me para que el equipo las envíe a mano.

1. En [Meta for Developers](https://developers.facebook.com/) crea una app de tipo **Business**, agrega el producto **WhatsApp** y registra el número del taller.
2. Crea un **usuario del sistema** en el Business Manager con permiso `whatsapp_business_messaging` y genera un **token permanente**.
3. En el **WhatsApp Manager**, crea las plantillas de mensaje con el nombre `olympo_{evento}` (por ejemplo `olympo_payment_approved`, `olympo_work_order_ready`) en `pt_BR` y `es`. El texto de referencia y el orden de las variables (`{{1}}`, `{{2}}`…) de cada una están en **Ajustes > Modelos de mensagem** (canal WhatsApp).
4. En **Ajustes > WhatsApp**: pega el **ID del número de teléfono**, el ID de la cuenta y el token, activa el canal y usa **Testar conexão**.
5. En **Ajustes > Notificações** elige el canal principal (WhatsApp o correo), el respaldo, los recordatorios y el horario silencioso.

Solo reciben WhatsApp los clientes que aceptaron el contacto (opt-in del constructor o de la ficha). El token se guarda cifrado y nunca se muestra de vuelta.

## Producción, seguridad y LGPD

- Despliegue paso a paso (servidor, HTTPS, Nginx, Supervisor, cron, Sentry, copias, lista de lanzamiento): [docs/DEPLOY.md](docs/DEPLOY.md).
- Auditoría de seguridad y cumplimiento: [docs/SECURITY.md](docs/SECURITY.md).
- Primer dueño en producción: `php artisan olympo:create-owner correo@dominio "Nombre"`.
- Accesibilidad (axe-core): `npm run test:a11y`. Carga: `npm run test:load` (y `tests/load/k6-portal.js`).

## Usuarios de demostración

Creados por `UserSeeder` solo fuera de producción. Contraseña: `password`.

| Correo | Rol |
|---|---|
| owner@olympo.test | Dueño (todo, incluidos Ajustes) |
| reception@olympo.test | Recepción |
| upholsterer@olympo.test | Tapicero (solo lectura) |
| customer@olympo.test | Cliente (sin acceso al panel) |

## Calidad

```bash
php artisan test
```

```bash
vendor/bin/pint --test
```

```bash
vendor/bin/phpstan analyse --memory-limit=1G
```

CI en GitHub Actions (`.github/workflows/ci.yml`) ejecuta las tres sobre PostgreSQL y Redis.
