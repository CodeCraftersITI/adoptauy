#!/usr/bin/env bash
# =============================================================================
#  AdoptaUY · infra/posix/aplicar.sh
#  Configuración de seguridad POSIX de la VM del servidor (Ubuntu Server 24.04):
#  jerarquía de directorios, usuarios y grupos, permisos, atributos, ACL, hosts
#  permitidos, firewall y respaldo. Se puede volver a ejecutar sin romper nada.
#
#  Uso (como root, desde el repositorio):
#     sudo bash infra/posix/aplicar.sh                      # instala en /srv/adoptauy/app
#     sudo bash infra/posix/aplicar.sh --desde ~/adoptauy   # mueve un clon existente
#
#  Antes: completar infra/posix/red.conf y dejar las claves públicas en
#  infra/posix/claves/<usuario>.pub (camilo, leandro, inaki, rodrigo).
# =============================================================================
set -euo pipefail
umask 0022
DIR="$(cd "$(dirname "$0")" && pwd)"
paso() { printf '\n\033[1;35m==> %s\033[0m\n' "$*"; }
ok()   { printf '\033[1;32m    ✔ %s\033[0m\n' "$*"; }
av()   { printf '\033[1;33m    ! %s\033[0m\n' "$*"; }
err()  { printf '\033[1;31m    ✘ %s\033[0m\n' "$*" >&2; exit 1; }

[[ $EUID -eq 0 ]] || err "Ejecutalo como root: sudo bash $0"
ORIGEN=""; [[ "${1:-}" == "--desde" ]] && ORIGEN="${2:?Falta la ruta del clon actual}"

# --- Datos de red ------------------------------------------------------------
CONF="${RED_CONF:-$DIR/red.conf}"
# shellcheck source=red.conf
source "$CONF"
grep -Eq '^[A-Z_]+="__COMPLETAR__"' "$CONF" && err "Completá $CONF con la red real antes de aplicar."
ip_ok()   { [[ "$1" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}$ ]]; }
cidr_ok() { [[ "$1" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}/([0-9]|[12][0-9]|3[0-2])$ ]]; }
cidr_ok "$RED_ADMIN" || err "RED_ADMIN no es una red CIDR válida: $RED_ADMIN"
for v in IP_CAMILO IP_LEANDRO IP_INAKI IP_RODRIGO; do ip_ok "${!v}" || err "$v no es una IP válida"; done

BASE=/srv/adoptauy
APP=$BASE/app
STORAGE=$APP/src/laravel/storage
LOGS=/var/log/adoptauy
RESP=/var/backups/adoptauy

# =============================================================================
paso "1/8 Usuarios, grupos y políticas de contraseña"
# =============================================================================
apt-get install -y -qq acl attr libpam-pwquality rsync >/dev/null

# Grupos por rol
for g in codecrafters adoptauy-infra adoptauy-bd adoptauy-dev adoptauy-monitoreo; do
  getent group "$g" >/dev/null || groupadd "$g"
done

# Cuenta de servicio sin shell ni home: dueña de los archivos que escribe PHP
getent passwd adoptauy >/dev/null || \
  useradd --system --user-group --no-create-home --home-dir /nonexistent \
          --shell /usr/sbin/nologin --comment "AdoptaUY (servicio)" adoptauy

# Integrantes: usuario:nombre completo:grupos
INTEGRANTES=(
  "camilo:Camilo Blanco:codecrafters,adoptauy-bd"
  "leandro:Leandro Estévez:codecrafters,adoptauy-monitoreo,adm"
  "inaki:Iñaki Pérez:codecrafters,adoptauy-dev"
  "rodrigo:Rodrigo González:codecrafters,adoptauy-infra,adm"
)
# Política de contraseñas: largo y complejidad (pam_pwquality) y vencimiento (login.defs)
install -d -m 0755 /etc/security/pwquality.conf.d
cat > /etc/security/pwquality.conf.d/adoptauy.conf <<'EOF'
# AdoptaUY: contraseñas de al menos 12 caracteres con 3 tipos distintos
minlen = 12
minclass = 3
maxrepeat = 3
usercheck = 1
dictcheck = 1
enforce_for_root
EOF
sed -i -E 's/^PASS_MAX_DAYS.*/PASS_MAX_DAYS\t90/; s/^PASS_MIN_DAYS.*/PASS_MIN_DAYS\t1/; s/^PASS_WARN_AGE.*/PASS_WARN_AGE\t7/' /etc/login.defs

NUEVAS=()
for linea in "${INTEGRANTES[@]}"; do
  IFS=: read -r u nombre grupos <<<"$linea"
  if ! id "$u" >/dev/null 2>&1; then
    useradd --create-home --shell /bin/bash --comment "$nombre" --groups "$grupos" "$u"
    clave="$(openssl rand -base64 12 | tr -d '/+=\n')-7"   # temporaria, cumple pwquality
    echo "$u:$clave" | chpasswd
    chage -d 0 "$u"                       # obliga a cambiarla en el primer ingreso
    NUEVAS+=("$u  $clave")
  else
    usermod --groups "$grupos" "$u"       # deja exactamente los grupos del rol
  fi
  chage -M 90 -m 1 -W 7 -I 14 "$u"        # vence a los 90 días, aviso 7, bloqueo 14 días después
  gpasswd -d "$u" docker >/dev/null 2>&1 || true   # docker = root: nadie del equipo en ese grupo
  gpasswd -d "$u" sudo   >/dev/null 2>&1 || true   # sin sudo completo
  chmod 0750 "/home/$u"
  if [[ -f "$DIR/claves/$u.pub" ]]; then
    install -d -m 0700 -o "$u" -g "$u" "/home/$u/.ssh"
    install -m 0600 -o "$u" -g "$u" "$DIR/claves/$u.pub" "/home/$u/.ssh/authorized_keys"
  else
    av "Sin clave pública para $u (infra/posix/claves/$u.pub): no va a poder entrar por SSH"
  fi
done

# umask 027 para las sesiones: lo nuevo no queda legible para "otros"
cat > /etc/profile.d/adoptauy-umask.sh <<'EOF'
# AdoptaUY: archivos nuevos 640 y directorios 750 por defecto
umask 027
EOF
ok "Grupos, cuenta de servicio adoptauy (nologin) e integrantes con vencimiento de contraseña"

# =============================================================================
paso "2/8 Jerarquía de directorios (FHS)"
# =============================================================================
install -d -m 0755 -o root -g root "$BASE"
if [[ ! -d "$APP/.git" ]]; then
  if [[ -n "$ORIGEN" ]]; then
    (cd "$ORIGEN" && docker compose down) >/dev/null 2>&1 || true       # el volumen de la base se conserva
    mv "$ORIGEN" "$APP"
    [[ -d "$DIR" ]] || DIR="$APP/infra/posix"            # el script estaba dentro del clon movido
  else
    git clone -q https://github.com/CodeCraftersITI/adoptauy.git "$APP"
  fi
fi
[[ -f "$APP/.env" ]] || cp "$APP/.env.example" "$APP/.env"
chattr -i "$APP/.env" 2>/dev/null || true

# Código: lo modifica solo root (vía adoptauy-desplegar); "otros" leen porque
# el Nginx del contenedor corre con un UID que no pertenece a ningún grupo del host
chown -R root:root "$APP"
chmod 0755 "$APP"

# Secretos y certificados
chown root:adoptauy-infra "$APP/.env";  chmod 0640 "$APP/.env"
install -d -m 0700 -o root -g root "$APP/docker/nginx/certs"

# Archivos que escribe Laravel: dueño adoptauy, grupo adoptauy-dev, setgid
SVC_UID=$(id -u adoptauy); SVC_GID=$(id -g adoptauy)
install -d "$STORAGE"/{app/public,app/private,framework/{cache/data,sessions,views},logs}
chown -R adoptauy:adoptauy-dev "$STORAGE"
find "$STORAGE" -type d -exec chmod 2770 {} +
find "$STORAGE" -type f -exec chmod 0660 {} +
chmod 2771 "$STORAGE" "$STORAGE/app"          # "otros" solo atraviesan
chmod 2775 "$STORAGE/app/public"              # fotos públicas: las sirve Nginx
find "$STORAGE/app/public" -type f -exec chmod 0664 {} +

# Carpeta compartida del equipo: setgid (grupo heredado) + sticky (cada uno borra lo suyo)
install -d -m 3770 -o root -g codecrafters "$BASE/compartido"

# Registros (FHS: /var/log) y respaldos (FHS: /var/backups)
install -d -m 2750 -o root -g adm "$LOGS"
chattr -a "$LOGS"/*.log 2>/dev/null || true          # se vuelve a poner en el paso 8
touch "$LOGS"/{sudo,respaldo,despliegue}.log
chown root:adm "$LOGS"/*.log; chmod 0640 "$LOGS"/*.log
install -d -m 0700 -o root -g root "$RESP" "$RESP/diario" "$RESP/semanal" "$RESP/espejo"

# Configuración local del servidor
install -d -m 0750 -o root -g adoptauy-infra /etc/adoptauy
chattr -i /etc/adoptauy/red.conf 2>/dev/null || true
install -m 0640 -o root -g adoptauy-infra "$CONF" /etc/adoptauy/red.conf

# El contenedor PHP corre con el UID/GID de la cuenta de servicio; Mailpit solo en localhost
for par in "UID=$SVC_UID" "GID=$SVC_GID" "MAILPIT_PORT=127.0.0.1:8025" "DB_PORT_LOCAL=127.0.0.1:3307"; do
  clave="${par%%=*}"
  if grep -q "^$clave=" "$APP/.env"; then sed -i "s|^$clave=.*|$par|" "$APP/.env"; else echo "$par" >> "$APP/.env"; fi
done
ok "Árbol en $BASE, $LOGS y $RESP"

# =============================================================================
paso "3/8 Scripts administrados y sudo con privilegio mínimo"
# =============================================================================
for s in adoptauy-desplegar adoptauy-respaldo adoptauy-restaurar; do
  chattr -i "/usr/local/sbin/$s" 2>/dev/null || true
  install -m 0755 -o root -g root "$DIR/sbin/$s" "/usr/local/sbin/$s"
done
chattr -i /etc/sudoers.d/adoptauy 2>/dev/null || true
install -m 0440 -o root -g root "$DIR/sudoers-adoptauy" /etc/sudoers.d/adoptauy.nuevo
visudo -cf /etc/sudoers.d/adoptauy.nuevo >/dev/null || { rm -f /etc/sudoers.d/adoptauy.nuevo; err "sudoers inválido"; }
mv /etc/sudoers.d/adoptauy.nuevo /etc/sudoers.d/adoptauy
# Línea base de binarios setuid/setgid para detectar cambios (no se agrega ninguno)
find / -xdev -type f \( -perm -4000 -o -perm -2000 \) 2>/dev/null | sort > "$LOGS/suid-base.txt"; chmod 0640 "$LOGS/suid-base.txt"
ok "Wrappers en /usr/local/sbin y /etc/sudoers.d/adoptauy validado"

# =============================================================================
paso "4/8 ACL"
# =============================================================================
# Registros: además del grupo dueño (adm), leen BD y desarrollo. Con permisos clásicos
# habría un solo grupo; la ACL suma dos más, y la ACL por defecto la heredan los
# archivos nuevos (por ejemplo, los que crea logrotate al rotar)
setfacl -m g:adoptauy-bd:rX,g:adoptauy-dev:rX "$LOGS"
setfacl -d -m g:adm:rX,g:adoptauy-bd:rX,g:adoptauy-dev:rX "$LOGS"
setfacl -m g:adoptauy-bd:r,g:adoptauy-dev:r "$LOGS"/{sudo,respaldo,despliegue}.log
# Log de Laravel: lo lee monitoreo (Leandro) sin entrar al grupo adoptauy-dev; con herencia
setfacl -m g:adoptauy-monitoreo:x "$STORAGE"
setfacl -R -m g:adoptauy-monitoreo:rX "$STORAGE/logs"
setfacl -d -m g:adoptauy-monitoreo:rX "$STORAGE/logs"
ok "ACL en $LOGS y $STORAGE/logs"

# =============================================================================
paso "5/8 Hosts permitidos (SSH y TCP Wrappers)"
# =============================================================================
chattr -i /etc/ssh/sshd_config.d/10-adoptauy.conf 2>/dev/null || true
sed -e "s|{{RED_ADMIN}}|$RED_ADMIN|g" -e "s|{{IP_CAMILO}}|$IP_CAMILO|g" -e "s|{{IP_LEANDRO}}|$IP_LEANDRO|g" \
    -e "s|{{IP_INAKI}}|$IP_INAKI|g" -e "s|{{IP_RODRIGO}}|$IP_RODRIGO|g" \
    "$DIR/sshd-adoptauy.conf" > /etc/ssh/sshd_config.d/10-adoptauy.conf
chmod 0644 /etc/ssh/sshd_config.d/10-adoptauy.conf
sshd -t || err "sshd_config inválido: no se reinicia SSH"
# Segunda capa: sshd de Ubuntu 24.04 (OpenSSH 9.6) todavía usa libwrap
chattr -i /etc/hosts.allow /etc/hosts.deny 2>/dev/null || true
printf '# AdoptaUY: SSH solo desde la red de administración\nsshd: %s\n' "$RED_ADMIN" > /etc/hosts.allow
printf '# AdoptaUY: todo lo no permitido en hosts.allow se rechaza\nsshd: ALL\n' > /etc/hosts.deny
systemctl restart ssh 2>/dev/null || av "No se pudo reiniciar ssh (¿sin systemd?)"
ok "sshd: AllowGroups, AllowUsers por IP y Match Address; TCP Wrappers como segunda capa"

# =============================================================================
paso "6/8 Firewall (ufw)"
# =============================================================================
apt-get install -y -qq ufw >/dev/null
ufw default deny incoming  >/dev/null
ufw default allow outgoing >/dev/null
ufw delete allow OpenSSH   >/dev/null 2>&1 || true   # reglas abiertas de infra/setup.sh
ufw delete allow 22/tcp    >/dev/null 2>&1 || true
ufw delete allow 8025/tcp  >/dev/null 2>&1 || true
ufw allow 80/tcp  comment 'AdoptaUY HTTP (redirige a HTTPS)' >/dev/null
ufw allow 443/tcp comment 'AdoptaUY HTTPS' >/dev/null
ufw limit from "$RED_ADMIN" to any port 22 proto tcp comment 'SSH red de administracion' >/dev/null
[[ -n "${IP_ZABBIX:-}" ]] && ufw allow from "$IP_ZABBIX" to any port 10050 proto tcp comment 'Zabbix agente' >/dev/null
ufw logging low >/dev/null
ufw --force enable >/dev/null || av "ufw no se pudo activar (¿contenedor sin netfilter?)"
ok "ufw: entrante denegado; 80 y 443 abiertos; 22 solo desde $RED_ADMIN"

# =============================================================================
paso "7/8 Respaldo diario con systemd (reemplaza la línea de cron)"
# =============================================================================
install -m 0644 "$DIR/systemd/adoptauy-respaldo.service" /etc/systemd/system/
install -m 0644 "$DIR/systemd/adoptauy-respaldo.timer"   /etc/systemd/system/
install -m 0644 "$DIR/logrotate-adoptauy" /etc/logrotate.d/adoptauy
for u in root ${SUDO_USER:-}; do
  crontab -u "$u" -l 2>/dev/null | grep -v 'scripts/respaldo.sh' | crontab -u "$u" - 2>/dev/null || true
done
if systemctl daemon-reload 2>/dev/null; then
  systemctl enable --now adoptauy-respaldo.timer >/dev/null
  ok "adoptauy-respaldo.timer activo (02:00, Persistent=true)"
else
  av "Sin systemd: activar el timer en la VM con systemctl enable --now adoptauy-respaldo.timer"
fi

# =============================================================================
paso "8/8 Atributos (chattr)"
# =============================================================================
chattr +i /etc/ssh/sshd_config.d/10-adoptauy.conf /etc/sudoers.d/adoptauy /etc/adoptauy/red.conf \
          /etc/hosts.allow /etc/hosts.deny "$APP/.env" /usr/local/sbin/adoptauy-*
chattr +a "$LOGS"/{sudo,respaldo,despliegue}.log
ok "+i en configuración crítica y +a en los registros de $LOGS"

if command -v docker >/dev/null && [[ -z "${SIN_DOCKER:-}" ]]; then
  paso "Levantando AdoptaUY con el UID de la cuenta de servicio"
  (cd "$APP" && docker compose up -d --build) && ok "Contenedores actualizados"
fi

if ((${#NUEVAS[@]})); then
  printf '\n\033[1mContraseñas temporales (se cambian en el primer ingreso; entregarlas en persona):\033[0m\n'
  printf '  %s\n' "${NUEVAS[@]}"
fi
echo; echo "Verificar con: sudo bash $DIR/verificar.sh"
