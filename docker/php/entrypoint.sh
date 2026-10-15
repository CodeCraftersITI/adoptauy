#!/bin/sh
# AdoptaUY · arranque del contenedor PHP.
# La primera vez prepara Laravel solo: .env, dependencias, APP_KEY, migraciones.
# Solo el servicio "app" prepara (PREPARAR=1); queue y scheduler esperan a que termine.
set -e
cd /var/www/laravel

if [ "${PREPARAR:-0}" = "1" ]; then
  if [ ! -f .env ]; then
    cp .env.example .env
    echo "[adoptauy] .env creado a partir de .env.example"
  fi

  if [ ! -f vendor/autoload.php ]; then
    echo "[adoptauy] Instalando dependencias con Composer (la primera vez tarda unos minutos)..."
    composer install --no-interaction --prefer-dist --no-progress
  fi

  if ! grep -q '^APP_KEY=base64' .env; then
    php artisan key:generate --force
  fi

  echo "[adoptauy] Esperando a MySQL..."
  i=0
  until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Exception $e) { exit(1); }' 2>/dev/null; do
    i=$((i+1)); [ "$i" -gt 60 ] && { echo "[adoptauy] MySQL no respondió"; exit 1; }
    sleep 2
  done

  php artisan migrate --force
  [ -L public/storage ] || php artisan storage:link >/dev/null 2>&1 || true
  touch storage/.preparado
  echo "[adoptauy] Laravel listo"
else
  echo "[adoptauy] Esperando a que el servicio app termine de preparar Laravel..."
  until [ -f vendor/autoload.php ] && [ -f storage/.preparado ]; do sleep 3; done
fi

exec "$@"
