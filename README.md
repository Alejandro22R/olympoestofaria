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

## Puesta en marcha

### Con Docker (Laravel Sail)

```bash
cp .env.example .env
```

En `.env`, cambia `DB_HOST=pgsql` y `REDIS_HOST=redis`. Luego:

```bash
composer install
```

```bash
./vendor/bin/sail up -d
```

```bash
./vendor/bin/sail artisan key:generate
```

```bash
./vendor/bin/sail artisan migrate --seed
```

```bash
./vendor/bin/sail npm install && ./vendor/bin/sail npm run build
```

### Sin Docker

Necesitas PHP 8.3+, PostgreSQL y Redis locales. Crea las bases `olympo` y `olympo_test` (usuario `olympo` / `secret`, o ajusta `.env` y `phpunit.xml`).

```bash
composer setup
```

```bash
php artisan migrate --seed
```

```bash
composer dev
```

Procesos aparte: `php artisan reverb:start` (WebSockets) y `php artisan horizon` (colas).

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
