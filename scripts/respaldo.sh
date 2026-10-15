#!/usr/bin/env bash
# Respaldo de AdoptaUY (RNF:0014, D76): base de datos + archivos subidos, 7 días de retención.
# En el servidor local lo ejecuta cron todos los días a las 02:00 (ver infra/setup.sh).
set -euo pipefail
cd "$(dirname "$0")/.."
DESTINO="${RESPALDO_DIR:-$(pwd)/respaldos}"
FECHA="$(date +%F_%H%M)"
mkdir -p "$DESTINO"
docker compose exec -T db sh -c 'mysqldump --single-transaction -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' \
  | gzip > "$DESTINO/bd_$FECHA.sql.gz"
tar -czf "$DESTINO/archivos_$FECHA.tar.gz" -C src/laravel storage/app
find "$DESTINO" -type f -mtime +7 -delete
echo "Respaldo OK: $DESTINO ($FECHA)"
