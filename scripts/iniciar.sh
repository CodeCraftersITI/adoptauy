#!/usr/bin/env bash
# AdoptaUY · primera puesta en marcha en cualquier máquina con Docker
# (Linux, macOS, o Windows con WSL / Git Bash y Docker Desktop).
#
#   git clone https://github.com/CodeCraftersITI/adoptauy.git
#   cd adoptauy && bash scripts/iniciar.sh
#
# Se puede volver a ejecutar: no pisa los .env ni el certificado existentes.
set -euo pipefail
cd "$(dirname "$0")/.."

paso() { printf '\n\033[1;35m==> %s\033[0m\n' "$*"; }
ok()   { printf '\033[1;32m    ✔ %s\033[0m\n' "$*"; }
err()  { printf '\033[1;31m    ✘ %s\033[0m\n' "$*" >&2; exit 1; }

command -v docker >/dev/null || err "Falta Docker. Instalalo (en la VM: bash infra/setup.sh)."
docker compose version >/dev/null 2>&1 || err "Falta el plugin docker compose."
DC="docker compose"

azar() { LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 24; }

paso "1/5 Archivos .env"
if [[ ! -f .env ]]; then
  cp .env.example .env
  sed -i.bak "s/^DB_PASSWORD=.*/DB_PASSWORD=$(azar)/; s/^DB_ROOT_PASSWORD=.*/DB_ROOT_PASSWORD=$(azar)/" .env
  if [[ "$(uname -s)" == Linux* ]]; then
    sed -i.bak "s/^UID=.*/UID=$(id -u)/; s/^GID=.*/GID=$(id -g)/" .env
  fi
  rm -f .env.bak
  ok ".env creado con contraseñas aleatorias"
else
  ok ".env ya existe, se conserva"
fi
[[ -f src/laravel/.env ]] || { cp src/laravel/.env.example src/laravel/.env; ok "src/laravel/.env creado"; }

paso "2/5 Certificado HTTPS autofirmado (D73)"
if [[ ! -f docker/nginx/certs/adoptauy.crt ]]; then
  if command -v openssl >/dev/null; then
    MSYS_NO_PATHCONV=1 openssl req -x509 -nodes -newkey rsa:2048 -days 825 \
      -keyout docker/nginx/certs/adoptauy.key -out docker/nginx/certs/adoptauy.crt \
      -subj "/C=UY/O=CodeCrafters/CN=localhost" \
      -addext "subjectAltName=DNS:localhost,DNS:srv-web,IP:127.0.0.1" 2>/dev/null
  else
    docker run --rm -v "$(pwd)/docker/nginx/certs:/certs" alpine:3.20 sh -c \
      'apk add -q --no-cache openssl && openssl req -x509 -nodes -newkey rsa:2048 -days 825 \
       -keyout /certs/adoptauy.key -out /certs/adoptauy.crt -subj "/C=UY/O=CodeCrafters/CN=localhost" \
       -addext "subjectAltName=DNS:localhost,DNS:srv-web,IP:127.0.0.1"'
  fi
  ok "Certificado generado (el navegador muestra un aviso la primera vez)"
else
  ok "Certificado existente, se conserva"
fi

paso "3/5 Construyendo la imagen de PHP (la primera vez tarda unos minutos)"
$DC build app

paso "4/5 Dependencias de Laravel"
$DC up -d db mailpit
$DC run --rm --no-deps app composer install --no-interaction
grep -q '^APP_KEY=base64' src/laravel/.env || $DC run --rm --no-deps app php artisan key:generate
ok "Composer y APP_KEY listos"

paso "5/5 Levantando servicios y base de datos"
$DC up -d
$DC exec -T app php artisan migrate --force
$DC exec -T app php artisan storage:link >/dev/null 2>&1 || true
$DC ps

HTTPS_PORT=$(grep -E '^HTTPS_PORT=' .env | cut -d= -f2); HTTPS_PORT=${HTTPS_PORT:-443}
MAILPIT_PORT=$(grep -E '^MAILPIT_PORT=' .env | cut -d= -f2); MAILPIT_PORT=${MAILPIT_PORT:-8025}
SUF=$([[ "$HTTPS_PORT" == 443 ]] && echo "" || echo ":$HTTPS_PORT")
cat <<FIN

AdoptaUY listo.
  Sitio .............. https://localhost$SUF
  Correos (Mailpit) .. http://localhost:$MAILPIT_PORT
  MySQL (DBeaver) .... 127.0.0.1:$(grep -E '^DB_PORT_LOCAL=' .env | cut -d= -f2) · usuario y clave en .env

Día a día:  docker compose up -d  ·  docker compose exec app php artisan test
FIN
