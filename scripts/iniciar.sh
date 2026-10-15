#!/usr/bin/env bash
# AdoptaUY · puesta en marcha con contraseñas propias (servidor local o si preferís no usar las de desarrollo).
# Para desarrollar alcanza con:  docker compose up -d --build
# Este script además crea un .env con contraseñas aleatorias y espera a que todo esté listo.
set -euo pipefail
cd "$(dirname "$0")/.."

command -v docker >/dev/null || { echo "Falta Docker (en la VM: bash infra/setup.sh)"; exit 1; }

if [[ ! -f .env ]]; then
  azar() { LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 24; }
  cp .env.example .env
  sed -i.bak "s/^DB_PASSWORD=.*/DB_PASSWORD=$(azar)/; s/^DB_ROOT_PASSWORD=.*/DB_ROOT_PASSWORD=$(azar)/" .env
  [[ "$(uname -s)" == Linux* ]] && sed -i.bak "s/^UID=.*/UID=$(id -u)/; s/^GID=.*/GID=$(id -g)/" .env
  rm -f .env.bak
  echo "✔ .env creado con contraseñas aleatorias"
fi

docker compose up -d --build
echo "Preparando Laravel (la primera vez tarda unos minutos)..."
until docker compose exec -T app test -f storage/.preparado 2>/dev/null; do sleep 5; printf '.'; done
echo
docker compose ps
echo "✔ AdoptaUY listo: https://localhost  ·  correos: http://localhost:8025"
