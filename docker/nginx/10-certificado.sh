#!/bin/sh
# Genera un certificado autofirmado si no hay uno (desarrollo y servidor local, D73).
# Para usar uno real, copiá adoptauy.crt y adoptauy.key en docker/nginx/certs/.
set -e
DIR=/etc/nginx/certs
if [ ! -f "$DIR/adoptauy.crt" ] || [ ! -f "$DIR/adoptauy.key" ]; then
  echo "[adoptauy] Generando certificado autofirmado..."
  openssl req -x509 -nodes -newkey rsa:2048 -days 825 \
    -keyout "$DIR/adoptauy.key" -out "$DIR/adoptauy.crt" \
    -subj "/C=UY/O=CodeCrafters/CN=localhost" \
    -addext "subjectAltName=DNS:localhost,DNS:srv-web,IP:127.0.0.1" 2>/dev/null
  chmod 644 "$DIR/adoptauy.crt"; chmod 640 "$DIR/adoptauy.key"
fi
