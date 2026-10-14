#!/usr/bin/env bash
# =============================================================================
#  AdoptaUY · setup.sh
#  Deja lista la VM (Ubuntu Server 24.04 en VirtualBox) con todo el entorno:
#  Docker + Nginx (HTTPS) + PHP-FPM 8.4 (Laravel 13) + MySQL 8.4 + cola + scheduler
#  + Mailpit (desarrollo) + Postfix relay (perfil "produccion") + respaldo diario.
#
#  Uso (con el usuario creado en la instalación, NO como root):
#     bash setup.sh                       # crea un proyecto Laravel 13 nuevo
#     bash setup.sh <url-del-repo-git>    # clona el repositorio del equipo
#
#  Variables opcionales:
#     PROJECT_DIR=~/adoptauy   carpeta del proyecto
#     APP_URL=https://localhost:8443  URL con la que se entra desde la PC (NAT de VirtualBox)
#
#  Se puede volver a ejecutar: no pisa el código, ni las claves ya generadas.
# =============================================================================
set -euo pipefail

REPO_URL="${1:-}"
PROJECT_DIR="${PROJECT_DIR:-$HOME/adoptauy}"
APP_URL="${APP_URL:-https://localhost:8443}"
LARAVEL_VERSION="^13.0"

paso() { printf '\n\033[1;35m==> %s\033[0m\n' "$*"; }
ok()   { printf '\033[1;32m    ✔ %s\033[0m\n' "$*"; }
err()  { printf '\033[1;31m    ✘ %s\033[0m\n' "$*" >&2; exit 1; }

[[ $EUID -eq 0 ]] && err "Ejecutalo con tu usuario normal (con sudo), no como root."
grep -q 'VERSION_ID="24.04"' /etc/os-release || echo "    ! Aviso: el script está pensado para Ubuntu 24.04."
sudo -v || err "Tu usuario necesita permisos de sudo."

# Mientras no se vuelva a iniciar sesión, el usuario todavía no está en el grupo docker
DC="sudo docker compose"

# -----------------------------------------------------------------------------
paso "1/7 Actualizando Ubuntu y zona horaria"
export DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a
sudo -E apt-get update -qq
sudo -E apt-get -y -qq upgrade
sudo -E apt-get install -y -qq ca-certificates curl git ufw openssl
sudo timedatectl set-timezone America/Montevideo
ok "Sistema actualizado (America/Montevideo)"

# -----------------------------------------------------------------------------
paso "2/7 Instalando Docker Engine y Compose (repositorio oficial)"
if ! command -v docker >/dev/null 2>&1; then
  sudo install -m 0755 -d /etc/apt/keyrings
  sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
  sudo chmod a+r /etc/apt/keyrings/docker.asc
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}") stable" \
    | sudo tee /etc/apt/sources.list.d/docker.list >/dev/null
  sudo -E apt-get update -qq
  sudo -E apt-get install -y -qq docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
fi
sudo systemctl enable --now docker >/dev/null
sudo usermod -aG docker "$USER"
ok "$(sudo docker --version)"

# -----------------------------------------------------------------------------
paso "3/7 Firewall (SSH, HTTP, HTTPS y bandeja de Mailpit)"
sudo ufw allow OpenSSH >/dev/null
sudo ufw allow 80/tcp >/dev/null
sudo ufw allow 443/tcp >/dev/null
sudo ufw allow 8025/tcp >/dev/null
sudo ufw --force enable >/dev/null
ok "ufw activo: 22, 80, 443, 8025"

# -----------------------------------------------------------------------------
paso "4/7 Carpeta del proyecto: $PROJECT_DIR"
if [[ -n "$REPO_URL" && ! -d "$PROJECT_DIR/.git" ]]; then
  git clone "$REPO_URL" "$PROJECT_DIR"
  ok "Repositorio clonado"
fi
mkdir -p "$PROJECT_DIR"
cd "$PROJECT_DIR"

# -----------------------------------------------------------------------------
paso "5/7 Generando archivos de Docker (solo los que falten)"
mkdir -p docker/php docker/nginx/certs scripts

escribir() {  # escribir <ruta>  (contenido por stdin; no pisa si ya existe)
  if [[ -f "$1" ]]; then cat >/dev/null; echo "    · $1 ya existe, se conserva"
  else cat >"$1"; ok "$1"; fi
}

escribir docker-compose.yml <<'EOF'
# AdoptaUY · entorno local (Sprint 4) · Ubuntu Server 24.04 en VirtualBox
name: adoptauy

services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
      args:
        UID: ${UID:-1000}
        GID: ${GID:-1000}
    image: adoptauy/app:dev
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
    depends_on:
      db:
        condition: service_healthy
    networks: [backend]

  web:
    image: nginx:1.27-alpine
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www/html:ro
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
      - ./docker/nginx/certs:/etc/nginx/certs:ro
    depends_on: [app]
    networks: [backend]

  db:
    image: mysql:8.4
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${DB_DATABASE:-adoptauy}
      MYSQL_USER: ${DB_USERNAME:-adoptauy}
      MYSQL_PASSWORD: ${DB_PASSWORD:?Definí DB_PASSWORD en .env}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:?Definí DB_ROOT_PASSWORD en .env}
      TZ: America/Montevideo
    command: ["--character-set-server=utf8mb4", "--collation-server=utf8mb4_unicode_ci"]
    volumes:
      - db_data:/var/lib/mysql
    ports:
      - "127.0.0.1:3306:3306"
    healthcheck:
      test: ["CMD-SHELL", "mysqladmin ping -h 127.0.0.1 -u root -p$$MYSQL_ROOT_PASSWORD --silent"]
      interval: 10s
      timeout: 5s
      retries: 12
    networks: [backend]

  queue:
    image: adoptauy/app:dev
    restart: unless-stopped
    working_dir: /var/www/html
    command: ["php", "artisan", "queue:work", "--tries=3", "--sleep=3"]
    volumes:
      - ./:/var/www/html
    depends_on:
      db:
        condition: service_healthy
    networks: [backend]

  scheduler:
    image: adoptauy/app:dev
    restart: unless-stopped
    working_dir: /var/www/html
    command: ["php", "artisan", "schedule:work"]
    volumes:
      - ./:/var/www/html
    depends_on:
      db:
        condition: service_healthy
    networks: [backend]

  mailpit:                       # desarrollo: captura los correos (no salen a Internet)
    image: axllent/mailpit:v1.21
    restart: unless-stopped
    ports:
      - "8025:8025"
    networks: [backend]

  mail:                          # produccion: Postfix como relay hacia el SMTP externo (D54, D75)
    image: boky/postfix          # fijar la versión exacta al instalar
    profiles: ["produccion"]
    restart: unless-stopped
    environment:
      RELAYHOST: "[${SMTP_RELAY_HOST:-smtp.ejemplo.com}]:587"
      RELAYHOST_USERNAME: ${SMTP_RELAY_USER:-}
      RELAYHOST_PASSWORD: ${SMTP_RELAY_PASSWORD:-}
      ALLOWED_SENDER_DOMAINS: ${MAIL_SENDER_DOMAIN:-adoptauy.local}
    networks: [backend]

volumes:
  db_data:

networks:
  backend:
EOF

escribir docker/php/Dockerfile <<'EOF'
# PHP-FPM 8.4 para Laravel 13 (requiere PHP 8.3 o superior)
FROM php:8.4-fpm-bookworm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev \
        libfreetype6-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring intl zip gd bcmath exif opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_HOME=/tmp/composer

# Mismo UID/GID que el usuario de la VM, para evitar problemas de permisos en storage/
RUN groupmod -o -g ${GID} www-data && usermod -o -u ${UID} -g ${GID} www-data

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-adoptauy.ini

WORKDIR /var/www/html
USER www-data
EOF

escribir docker/php/php.ini <<'EOF'
; Ajustes de PHP para AdoptaUY
date.timezone = America/Montevideo
memory_limit = 256M
; Fotos de mascotas: hasta 6 por publicación
upload_max_filesize = 8M
post_max_size = 50M
max_file_uploads = 6
expose_php = Off
EOF

escribir docker/nginx/default.conf <<'EOF'
# HTTP redirige siempre a HTTPS (RNF:0013, D73)
server {
    listen 80;
    server_name _;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    http2 on;
    server_name _;
    root /var/www/html/public;
    index index.php;

    ssl_certificate     /etc/nginx/certs/adoptauy.crt;
    ssl_certificate_key /etc/nginx/certs/adoptauy.key;
    ssl_protocols       TLSv1.2 TLSv1.3;

    client_max_body_size 50M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Strict-Transport-Security "max-age=31536000" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param HTTPS on;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Certificado autofirmado para el servidor local (D73); se reemplaza cuando haya dominio
if [[ ! -f docker/nginx/certs/adoptauy.crt ]]; then
  openssl req -x509 -nodes -newkey rsa:2048 -days 825 \
    -keyout docker/nginx/certs/adoptauy.key -out docker/nginx/certs/adoptauy.crt \
    -subj "/C=UY/O=CodeCrafters/CN=localhost" \
    -addext "subjectAltName=DNS:localhost,DNS:srv-web,IP:127.0.0.1" 2>/dev/null
  chmod 600 docker/nginx/certs/adoptauy.key
  ok "Certificado autofirmado generado (válido 825 días)"
fi
grep -qxF 'docker/nginx/certs/' .gitignore 2>/dev/null || echo 'docker/nginx/certs/' >>.gitignore

escribir scripts/respaldo.sh <<'EOF'
#!/usr/bin/env bash
# Respaldo diario de AdoptaUY (RNF:0014, D76): base de datos + fotos, 7 días de retención
set -euo pipefail
cd "$(dirname "$0")/.."
DESTINO="${RESPALDO_DIR:-$HOME/respaldos-adoptauy}"
FECHA="$(date +%F_%H%M)"
mkdir -p "$DESTINO"
docker compose exec -T db sh -c 'mysqldump --single-transaction -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' \
  | gzip > "$DESTINO/bd_$FECHA.sql.gz"
tar -czf "$DESTINO/storage_$FECHA.tar.gz" storage/app
find "$DESTINO" -type f -mtime +7 -delete
echo "Respaldo OK: $DESTINO ($FECHA)"
EOF
chmod +x scripts/respaldo.sh

# -----------------------------------------------------------------------------
paso "6/7 Laravel y archivo .env"

# set_env CLAVE VALOR → reemplaza la línea (aunque esté comentada) o la agrega
set_env() {
  local k="$1" v="$2"
  if grep -qE "^#?[[:space:]]*${k}=" .env; then
    sed -i -E "s|^#?[[:space:]]*${k}=.*|${k}=${v//|/\\|}|" .env
  else
    printf '%s=%s\n' "$k" "$v" >>.env
  fi
}
get_env() { grep -E "^$1=" .env 2>/dev/null | tail -1 | cut -d= -f2- || true; }

# .env mínimo para poder construir la imagen y levantar la base
[[ -f .env ]] || touch .env
set_env UID "$(id -u)"
set_env GID "$(id -g)"
[[ -n "$(get_env DB_PASSWORD)" ]]      || set_env DB_PASSWORD "$(openssl rand -hex 16)"
[[ -n "$(get_env DB_ROOT_PASSWORD)" ]] || set_env DB_ROOT_PASSWORD "$(openssl rand -hex 16)"

echo "    · Construyendo la imagen de PHP (la primera vez tarda unos minutos)"
$DC build app

if [[ ! -f artisan ]]; then
  echo "    · Creando proyecto Laravel $LARAVEL_VERSION"
  rm -rf .laravel-nuevo
  $DC run --rm --no-deps app composer create-project --no-interaction "laravel/laravel:$LARAVEL_VERSION" .laravel-nuevo
  # Conservar las claves ya generadas y pasar el esqueleto a la raíz
  mv .env .env.docker.bak          # se usa el .env de Laravel y se le reponen las claves
  shopt -s dotglob
  mv -n .laravel-nuevo/* .
  shopt -u dotglob
  rm -rf .laravel-nuevo
  [[ -f .env ]] || cp .env.example .env
  while IFS='=' read -r k v; do [[ -n "$k" ]] && set_env "$k" "$v"; done < .env.docker.bak
  rm -f .env.docker.bak
  ok "Proyecto Laravel creado"
else
  [[ -f .env.example ]] && ! grep -q '^APP_KEY=' .env && cat .env.example >>.env
  echo "    · Instalando dependencias (composer install)"
  $DC run --rm --no-deps app composer install --no-interaction
  ok "Dependencias instaladas"
fi

set_env APP_NAME AdoptaUY
set_env APP_ENV local
set_env APP_DEBUG true
set_env APP_URL "$APP_URL"
set_env APP_TIMEZONE America/Montevideo
set_env APP_LOCALE es
set_env DB_CONNECTION mysql
set_env DB_HOST db
set_env DB_PORT 3306
set_env DB_DATABASE adoptauy
set_env DB_USERNAME adoptauy
set_env QUEUE_CONNECTION database
set_env MAIL_MAILER smtp
set_env MAIL_HOST mailpit
set_env MAIL_PORT 1025
# Producción: MAIL_HOST=mail, MAIL_PORT=587 y SMTP_RELAY_HOST/USER/PASSWORD del proveedor
set_env MAIL_USERNAME null
set_env MAIL_PASSWORD null
set_env MAIL_FROM_ADDRESS no-responder@adoptauy.local
set_env MAIL_FROM_NAME '"${APP_NAME}"'
chmod 600 .env
grep -qxF '.env' .gitignore 2>/dev/null || echo '.env' >>.gitignore
ok ".env configurado (claves de MySQL generadas al azar)"

# -----------------------------------------------------------------------------
paso "7/7 Levantando contenedores y preparando la base"
$DC up -d
echo -n "    · Esperando a MySQL"
for _ in $(seq 1 40); do
  [[ "$(sudo docker inspect -f '{{.State.Health.Status}}' adoptauy-db-1 2>/dev/null)" == healthy ]] && break
  echo -n "."; sleep 3
done
echo
[[ -z "$(get_env APP_KEY)" ]] && $DC exec -T app php artisan key:generate --force
$DC exec -T app php artisan config:clear
$DC exec -T app php artisan migrate --force
$DC exec -T app php artisan storage:link 2>/dev/null || true
$DC restart queue scheduler >/dev/null
$DC ps

# Respaldo diario a las 02:00, antes de las tareas programadas de las 03:00 (D71, D76)
LINEA="0 2 * * * cd $PROJECT_DIR && ./scripts/respaldo.sh >> $HOME/respaldo-adoptauy.log 2>&1"
( crontab -l 2>/dev/null | grep -v 'scripts/respaldo.sh' ; echo "$LINEA" ) | crontab -
ok "Respaldo diario programado (02:00, 7 días de retención)"

cat <<FIN

$(printf '\033[1;32m')AdoptaUY listo.$(printf '\033[0m')
  Sitio .............. $APP_URL
  Correos (Mailpit) .. http://localhost:8025
  Certificado ........ autofirmado: el navegador muestra un aviso la primera vez
  Respaldos .......... $HOME/respaldos-adoptauy (02:00 todos los días)
  Proyecto ........... $PROJECT_DIR
  Claves de MySQL .... en $PROJECT_DIR/.env (DB_PASSWORD / DB_ROOT_PASSWORD)

Cerrá la sesión SSH y volvé a entrar para usar "docker" sin sudo.
Comandos útiles (desde $PROJECT_DIR):
  docker compose ps | docker compose logs -f web app
  docker compose exec app php artisan <comando>
  docker compose down        (apaga; los datos quedan en el volumen db_data)
FIN
