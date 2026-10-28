#!/usr/bin/env bash
# =============================================================================
#  AdoptaUY · scripts/verificar-despliegue.sh
#  Prueba de integración del despliegue: comprueba que la aplicación, la base de
#  datos y los servicios del servidor funcionan juntos (Nginx, PHP-FPM, MySQL,
#  cola, planificador y correo). No modifica nada.
#
#  Uso, desde la carpeta del repositorio, en la VM o en una PC de desarrollo:
#     bash scripts/verificar-despliegue.sh                 # verificación rápida
#     bash scripts/verificar-despliegue.sh --con-pruebas   # además corre PHPUnit en el contenedor
#
#  Cubre: RNF:0012 (despliegue reproducible), RNF:0013 (HTTPS y redirección),
#  RNF:0010 (servicios externos) y la observación 4 de la devolución del Hito 2.
#  Sale con código 0 si todo pasa y 1 si algo falla: sirve también en CI.
# =============================================================================
set -uo pipefail
cd "$(dirname "$0")/.."

ok=0; mal=0
bien()  { printf '  \033[32m✔\033[0m %s\n' "$*"; ok=$((ok + 1)); }
falla() { printf '  \033[31m✘\033[0m %s\n' "$*"; mal=$((mal + 1)); }
seccion() { printf '\n\033[1m%s\033[0m\n' "$*"; }
comprobar() { local texto="$1"; shift; if "$@" >/dev/null 2>&1; then bien "$texto"; else falla "$texto"; fi; }

command -v docker >/dev/null || { echo "Falta Docker"; exit 1; }
leer_env() { grep -E "^$1=" .env 2>/dev/null | tail -1 | cut -d= -f2-; }
HTTP_PORT=$(leer_env HTTP_PORT);   HTTP_PORT=${HTTP_PORT:-8080}
HTTPS_PORT=$(leer_env HTTPS_PORT); HTTPS_PORT=${HTTPS_PORT:-8443}
[[ "$HTTPS_PORT" == 443 ]] && URL_HTTPS="https://localhost/" || URL_HTTPS="https://localhost:$HTTPS_PORT/"
[[ "$HTTP_PORT" == 80 ]] && URL_HTTP="http://localhost/" || URL_HTTP="http://localhost:$HTTP_PORT/"

printf 'AdoptaUY · verificación del despliegue · %s · %s\n' "$(hostname)" "$(date '+%d/%m/%Y %H:%M')"

# -----------------------------------------------------------------------------
seccion "1. Contenedores"
# -----------------------------------------------------------------------------
corriendo=$(docker compose ps --status running --services 2>/dev/null)
for s in app web db queue scheduler; do
  if grep -qx "$s" <<<"$corriendo"; then bien "$s en ejecución"; else falla "$s no está en ejecución"; fi
done
if grep -qx mail <<<"$corriendo"; then bien "mail (Postfix, producción) en ejecución"
elif grep -qx mailpit <<<"$corriendo"; then bien "mailpit (desarrollo) en ejecución"
else falla "No hay servicio de correo (mail o mailpit)"; fi
estado_db=$(docker compose ps db --format '{{.Health}}' 2>/dev/null)
[[ "$estado_db" == healthy ]] && bien "MySQL responde a su prueba de salud" || falla "MySQL no está healthy (estado: ${estado_db:-desconocido})"

# -----------------------------------------------------------------------------
seccion "2. Web: Nginx, HTTPS y redirección (RNF:0013)"
# -----------------------------------------------------------------------------
codigo=$(curl -sk -o /dev/null -w '%{http_code}' "$URL_HTTPS")
[[ "$codigo" == 200 ]] && bien "La página de inicio responde por HTTPS ($URL_HTTPS → 200)" || falla "HTTPS responde $codigo en $URL_HTTPS"
codigo=$(curl -s -o /dev/null -w '%{http_code}' "$URL_HTTP")
destino=$(curl -sI "$URL_HTTP" | tr -d '\r' | awk -F': ' 'tolower($1)=="location"{print $2}')
[[ "$codigo" == 301 && "$destino" == https://* ]] && bien "HTTP redirige a HTTPS ($codigo → $destino)" || falla "HTTP no redirige a HTTPS (código $codigo)"
cabeceras=$(curl -skI "$URL_HTTPS" | tr -d '\r')
grep -qi '^x-powered-by' <<<"$cabeceras" && falla "El servidor expone la cabecera X-Powered-By" || bien "No expone la versión de PHP (X-Powered-By)"
for ruta in ingresar registro; do
  codigo=$(curl -sk -o /dev/null -w '%{http_code}' "${URL_HTTPS}${ruta}")
  [[ "$codigo" == 200 ]] && bien "/$ruta responde 200" || falla "/$ruta responde $codigo"
done
codigo=$(curl -sk -o /dev/null -w '%{http_code}' "${URL_HTTPS}cuenta")
[[ "$codigo" == 302 ]] && bien "/cuenta sin sesión redirige a ingresar (control de acceso)" || falla "/cuenta sin sesión responde $codigo"

# -----------------------------------------------------------------------------
seccion "3. Aplicación ↔ base de datos"
# -----------------------------------------------------------------------------
comprobar "Laravel se conecta a MySQL" docker compose exec -T app php artisan db:show
pendientes=$(docker compose exec -T app php artisan migrate:status 2>/dev/null | grep -c Pending)
[[ "$pendientes" == 0 ]] && bien "Todas las migraciones aplicadas" || falla "$pendientes migraciones pendientes"
for tabla in Usuario Particular Refugio Publicacion_Animal Publicacion_Adopcion Publicacion_Perdida Foto_Animal; do
  comprobar "Tabla $tabla presente" docker compose exec -T app php artisan db:table "$tabla"
done

# -----------------------------------------------------------------------------
seccion "4. Archivos, cola y planificador"
# -----------------------------------------------------------------------------
comprobar "storage/ escribible por PHP (fotos subidas)" docker compose exec -T app sh -c 'test -w storage/app/public'
comprobar "Enlace public/storage creado (las fotos se ven en el sitio)" docker compose exec -T app sh -c 'test -L public/storage'
comprobar "El planificador ve sus tareas" docker compose exec -T scheduler php artisan schedule:list
comprobar "La cola acepta conexiones" docker compose exec -T queue php artisan queue:monitor default

# -----------------------------------------------------------------------------
if [[ "${1:-}" == "--con-pruebas" ]]; then
  seccion "5. Suite de PHPUnit dentro del contenedor"
  if docker compose exec -T app php artisan test; then bien "PHPUnit sin fallas"; else falla "PHPUnit con fallas"; fi
fi

printf '\nResultado: %d correctas · %d fallas\n' "$ok" "$mal"
[[ "$mal" == 0 ]]
