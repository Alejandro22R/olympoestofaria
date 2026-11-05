#!/usr/bin/env bash
# Despliegue sin caída en /var/www/olympo (releases/ + shared/ + current -> release activa).
# Uso (en el servidor, como www-data): bash deploy/deploy.sh [rama]
set -euo pipefail

BRANCH="${1:-main}"
APP=/var/www/olympo
REPO="${REPO:-git@github.com:alejandro22r/olympoestofaria.git}"
RELEASE="$APP/releases/$(date +%Y%m%d%H%M%S)"

echo "→ Clonando $BRANCH en $RELEASE"
git clone --depth 1 --branch "$BRANCH" "$REPO" "$RELEASE"

echo "→ Enlazando .env y storage compartidos"
ln -s "$APP/shared/.env" "$RELEASE/.env"
rm -rf "$RELEASE/storage"
ln -s "$APP/shared/storage" "$RELEASE/storage"

cd "$RELEASE"
echo "→ Dependencias"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci --no-audit --no-fund
npm run build
rm -rf node_modules

echo "→ Base de datos y cachés"
php artisan migrate --force
php artisan settings:clear-cache
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan storage:link
php artisan optimize
php artisan filament:optimize

echo "→ Activando la nueva versión"
ln -sfn "$RELEASE" "$APP/current"
sudo systemctl reload php8.3-fpm
php artisan horizon:terminate   # Supervisor lo reinicia con el código nuevo
php artisan reverb:restart

echo "→ Limpiando versiones antiguas (se conservan 5)"
ls -1dt "$APP"/releases/* | tail -n +6 | xargs -r rm -rf

echo "✓ Desplegado: $(git rev-parse --short HEAD)"
curl -fsS "$(grep ^APP_URL= "$APP/shared/.env" | cut -d= -f2)/up" > /dev/null && echo "✓ /up responde"
