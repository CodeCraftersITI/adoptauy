#!/usr/bin/env bash
# AdoptaUY · Arma el zip de la entrega de Programación a partir de una versión marcada (tag).
#   bash scripts/empaquetar-entrega.sh v1.0-entrega-programacion
# Solo lleva la aplicación, Docker y la base de datos (sin docs, diagramas ni CI).
set -euo pipefail
cd "$(dirname "$0")/.."

VERSION=${1:-v1.0-entrega-programacion}
ZIP="AdoptaUY-$VERSION.zip"

git archive --format=zip --prefix=adoptauy/ -o "$ZIP" "$VERSION" \
  LEEME.md docker-compose.yml .env.example .gitattributes docker \
  docs/base-de-datos/adoptauy.sql docs/base-de-datos/usuarios.sql src/laravel

echo "Listo: $ZIP"
