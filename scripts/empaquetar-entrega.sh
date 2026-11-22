#!/usr/bin/env bash
# AdoptaUY · Arma el zip de la entrega de Programación a partir de una versión marcada (tag).
#   bash scripts/empaquetar-entrega.sh v1.0-entrega-programacion
#   bash scripts/empaquetar-entrega.sh v1.0-entrega-programacion --con-google
# Solo lleva la aplicación, Docker y la base de datos (sin docs, diagramas ni CI).
# Con --con-google agrega un .env con las claves de Google del .env de la raíz, para que
# el docente pueda probar Continuar con Google. Ese .env va solo en el zip, nunca en GitHub.
set -euo pipefail
cd "$(dirname "$0")/.."

VERSION=${1:-v1.0-entrega-programacion}
ZIP="AdoptaUY-$VERSION.zip"
EXTRA=()

if [ "${2:-}" = "--con-google" ]; then
  grep -q '^GOOGLE_CLIENT_ID=.\+' .env || { echo "Falta GOOGLE_CLIENT_ID en el .env de la raíz"; exit 1; }
  TEMP=$(mktemp -d)
  {
    echo "# Claves de Google para probar Continuar con Google (solo para la corrección)"
    grep -E '^GOOGLE_(CLIENT_ID|CLIENT_SECRET|REDIRECT_URI)=' .env
  } > "$TEMP/.env"
  EXTRA=(--add-file="$TEMP/.env")
  ZIP="AdoptaUY-$VERSION-con-google.zip"
fi

git archive --format=zip --prefix=adoptauy/ -o "$ZIP" "${EXTRA[@]}" "$VERSION" \
  LEEME.md docker-compose.yml .env.example .gitattributes docker \
  docs/base-de-datos/adoptauy.sql docs/base-de-datos/usuarios.sql src/laravel

[ -n "${TEMP:-}" ] && rm -rf "$TEMP"
echo "Listo: $ZIP"
