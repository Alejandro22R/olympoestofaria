# Despliegue en producción

Guía para publicar Olympo en un servidor propio (VPS) con HTTPS. Probado en la estructura del repositorio; los valores de dominio y rutas son ejemplos.

## 1. Servidor

- Ubuntu 24.04 LTS, 2 vCPU y 4 GB de RAM como mínimo (la conversión de videos con FFmpeg usa CPU).
- PHP 8.3 con FPM y extensiones: `pgsql`, `redis`, `intl`, `mbstring`, `bcmath`, `gd` o `imagick`, `zip`, `exif`, `pcntl`, `curl`, `xml`. Activa OPcache.
- PostgreSQL 16 (y su cliente `pg_dump`, que usan las copias), Redis 7, Nginx, Supervisor, Node 22 (solo para compilar), FFmpeg, Certbot.

```bash
sudo apt install nginx postgresql redis-server supervisor ffmpeg certbot python3-certbot-nginx php8.3-fpm php8.3-{pgsql,redis,intl,mbstring,bcmath,gd,zip,curl,xml}
```

## 2. Base de datos y carpetas

```bash
sudo -u postgres createuser --pwprompt olympo
```

```bash
sudo -u postgres createdb -O olympo olympo
```

```bash
sudo -u postgres createdb -O olympo olympo_restore_test
```

La segunda base queda vacía: la prueba mensual de restauración restaura allí la última copia (nunca toca la base real).

```bash
sudo mkdir -p /var/www/olympo/{releases,shared/storage}
```

```bash
sudo chown -R www-data:www-data /var/www/olympo
```

Copia `.env.production.example` a `/var/www/olympo/shared/.env`, completa los valores y genera la clave con `php artisan key:generate --show`. Crea dentro de `shared/storage` las carpetas `app/private`, `app/public`, `framework/{cache,sessions,views}` y `logs`.

## 3. Primer despliegue

```bash
sudo -u www-data bash deploy/deploy.sh main
```

El script clona la rama en una carpeta nueva, instala dependencias, compila el front, migra, limpia la caché de ajustes, sincroniza permisos, optimiza y cambia el enlace `current` sin cortar el servicio. Conserva las 5 últimas versiones.

Crea el usuario dueño (no hay usuarios demo en producción):

```bash
cd /var/www/olympo/current && sudo -u www-data php artisan olympo:create-owner dono@olympoestofaria.com.br "Nome do dono"
```

Al entrar, activa el 2FA en **Mi perfil** y, si quieres, hazlo obligatorio en **Ajustes > Seguridad**.

## 4. HTTPS, Nginx, colas y planificador

- Nginx: `deploy/nginx.conf` → `/etc/nginx/sites-available/olympo`, luego `certbot --nginx -d olympoestofaria.com.br`. Incluye HTTP/2, gzip, caché de assets y el proxy de WebSockets para Reverb en `/app`.
- Supervisor: `deploy/supervisor/olympo.conf` (Horizon y Reverb).
- Cron: `crontab -u www-data deploy/cron` (planificador cada minuto).
- Detrás de Cloudflare u otro proxy: `TRUSTED_PROXIES=*` (o las IPs del proxy).

## 5. Monitoreo

- **Sentry**: crea un proyecto Laravel y pon el DSN en `SENTRY_LARAVEL_DSN`. Nunca se envían datos personales (`send_default_pii` desactivado). El estado aparece en Ajustes > Integraciones.
- **Disponibilidad**: un monitor externo (UptimeRobot, Better Stack…) contra `https://tu-dominio/up` cada minuto. `/up` sigue respondiendo en modo mantenimiento.
- **Ajustes > Respaldos y salud**: base de datos, colas (Horizon), planificador, tiempo real, disco, última copia y última prueba de restauración. Los fallos de copia y las alertas técnicas llegan al dueño (campana; correo cuando se configure).
- **Horizon** (`/horizon`): solo el dueño.

## 6. Copias de seguridad

- Se configuran en **Ajustes > Respaldos y salud**: frecuencia (diaria o semanal), hora, días de retención, destinos y si incluyen archivos.
- Guarda al menos una copia **fuera del servidor**: crea un bucket privado (Cloudflare R2 o S3) distinto del de la app y completa `BACKUP_S3_*`. Aparecerá el destino "Nube".
- Define `BACKUP_ARCHIVE_PASSWORD` para cifrar el ZIP y guarda esa contraseña fuera del servidor.
- El día 1 de cada mes se prueba la restauración de la última copia (también con el botón "Testar restauração").

### Restaurar de verdad

1. Activa el modo mantenimiento desde el panel.
2. Descarga el ZIP más reciente del destino y descomprímelo.
3. Restaura el volcado: `psql -U olympo -d olympo -f db-dumps/postgresql-olympo.sql` (sobre una base vacía).
4. Copia `private/` y `public/` a `shared/storage/app/`.
5. `php artisan settings:clear-cache && php artisan optimize` y desactiva el mantenimiento.

## 7. Lista de verificación de lanzamiento

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` con https.
- [ ] `FORCE_HTTPS`, `SECURITY_CSP`, `SECURITY_HSTS` y `SESSION_SECURE_COOKIE` en `true`.
- [ ] Certificado válido y redirección de http a https.
- [ ] Horizon, Reverb y el cron corriendo (Ajustes > Respaldos y salud todo en verde).
- [ ] Primera copia hecha y prueba de restauración aprobada.
- [ ] Sentry recibe un error de prueba (`php artisan sentry:test`).
- [ ] Textos legales revisados por el abogado y fecha de versión actualizada (Ajustes > Privacidad).
- [ ] Dueño con 2FA; usuarios del equipo invitados desde Ajustes > Usuarios.
- [ ] Mercado Pago en modo producción solo después de un pago de prueba (README).
- [ ] Medición de Lighthouse y prueba de carga contra el dominio real (abajo).

## 8. Pruebas de carga y accesibilidad

- Carga rápida, sin dependencias: `BASE_URL=https://tu-dominio CONCURRENCY=20 DURATION=30 npm run test:load`. El portal limita 120 peticiones por minuto e IP: haz la prueba en staging con `PORTAL_RATE_LIMIT` alto.
- Carga sostenida con k6: `k6 run -e BASE_URL=https://staging tests/load/k6-portal.js` (umbral: p95 < 800 ms y < 1 % de errores).
- Accesibilidad (axe-core, WCAG 2.1 AA, móvil y escritorio, portal y panel): `BASE_URL=https://tu-dominio npm run test:a11y`.
