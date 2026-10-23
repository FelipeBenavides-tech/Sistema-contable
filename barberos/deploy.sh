#!/usr/bin/env bash
# Actualiza Kaixa en el servidor con los últimos cambios de main. Ver DESPLIEGUE.md.
set -euo pipefail

cd "$(dirname "$0")"

php artisan down --retry=15 || true
trap 'php artisan up' EXIT

git pull --ff-only origin main
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force

[ -s "$HOME/.nvm/nvm.sh" ] && . "$HOME/.nvm/nvm.sh"
npm ci
npm run build

php artisan optimize:clear
php artisan optimize

echo "Kaixa actualizado."
