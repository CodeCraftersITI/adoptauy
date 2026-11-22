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
  # Una clave distinta para cada usuario de MySQL del documento (16.3.4)
  for rol in LOGIN PUBLICO PARTICULAR REFUGIO ADMIN SISTEMA MIGRACIONES RESPALDO; do
    sed -i.bak "s/^DB_CLAVE_$rol=.*/DB_CLAVE_$rol=$(azar)/" .env
  done
  [[ "$(uname -s)" == Linux* ]] && sed -i.bak "s/^UID=.*/UID=$(id -u)/; s/^GID=.*/GID=$(id -g)/" .env
  rm -f .env.bak
  echo "✔ .env creado con contraseñas aleatorias"
fi

# Puertos y URL opcionales (infra/setup.sh los pasa para la VM: 80, 443 y su IP, D83)
for clave in HTTP_PORT HTTPS_PORT APP_URL; do
  valor="${!clave:-}"
  [[ -z "$valor" ]] && continue
  if grep -q "^$clave=" .env; then sed -i.bak "s|^$clave=.*|$clave=$valor|" .env; else echo "$clave=$valor" >> .env; fi
  rm -f .env.bak
done

docker compose up -d --build
echo "Preparando Laravel (la primera vez tarda unos minutos)..."
until docker compose exec -T app test -f storage/.preparado 2>/dev/null; do sleep 5; printf '.'; done
echo
# Usuarios de MySQL del documento (16.3.4): el servicio usuarios-bd termina solo
until [ "$(docker compose ps -a --format '{{.State}}' usuarios-bd)" = "exited" ]; do sleep 2; done
[ "$(docker compose ps -a --format '{{.ExitCode}}' usuarios-bd)" = 0 ] || { docker compose logs usuarios-bd; exit 1; }
docker compose ps
PUERTO=$(grep -E '^HTTPS_PORT=' .env 2>/dev/null | cut -d= -f2); PUERTO=${PUERTO:-8443}
[[ "$PUERTO" == 443 ]] && SUFIJO="" || SUFIJO=":$PUERTO"
echo "✔ AdoptaUY listo: https://localhost$SUFIJO  ·  correos: http://localhost:8025"
