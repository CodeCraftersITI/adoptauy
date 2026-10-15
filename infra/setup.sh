#!/usr/bin/env bash
# =============================================================================
#  AdoptaUY · infra/setup.sh
#  Prepara la VM del servidor local (Ubuntu Server 24.04 en VirtualBox) y deja
#  AdoptaUY corriendo con Docker. En una máquina de desarrollo que ya tiene
#  Docker no hace falta: alcanza con  bash scripts/iniciar.sh
#
#  Uso, con el usuario creado en la instalación (NO como root):
#     git clone https://github.com/CodeCraftersITI/adoptauy.git
#     cd adoptauy && bash infra/setup.sh
#
#  Hace: actualiza Ubuntu, instala Docker Engine y Compose (repositorio oficial),
#  configura el firewall, ejecuta scripts/iniciar.sh y programa el respaldo
#  diario (D76). Se puede volver a ejecutar sin perder datos.
# =============================================================================
set -euo pipefail
cd "$(dirname "$0")/.."
REPO_DIR="$(pwd)"

paso() { printf '\n\033[1;35m==> %s\033[0m\n' "$*"; }
ok()   { printf '\033[1;32m    ✔ %s\033[0m\n' "$*"; }
err()  { printf '\033[1;31m    ✘ %s\033[0m\n' "$*" >&2; exit 1; }

[[ $EUID -eq 0 ]] && err "Ejecutalo con tu usuario normal (con sudo), no como root."
grep -q 'VERSION_ID="24.04"' /etc/os-release || echo "    ! Aviso: pensado para Ubuntu 24.04."
sudo -v || err "Tu usuario necesita permisos de sudo."

paso "1/4 Actualizando Ubuntu y zona horaria"
export DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a
sudo -E apt-get update -qq
sudo -E apt-get -y -qq upgrade
sudo -E apt-get install -y -qq ca-certificates curl git ufw openssl
sudo timedatectl set-timezone America/Montevideo
ok "Sistema actualizado (America/Montevideo)"

paso "2/4 Docker Engine y Compose (repositorio oficial)"
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

paso "3/4 Firewall: SSH, HTTP (8080), HTTPS (8443) y Mailpit"
sudo ufw allow OpenSSH >/dev/null
sudo ufw allow 8080/tcp >/dev/null
sudo ufw allow 8443/tcp >/dev/null
sudo ufw allow 8025/tcp >/dev/null
sudo ufw --force enable >/dev/null
ok "ufw activo: 22, 8080, 8443, 8025"

paso "4/4 AdoptaUY y respaldo diario"
# sg aplica el grupo docker sin tener que cerrar la sesión
sg docker -c "bash '$REPO_DIR/scripts/iniciar.sh'"
LINEA="0 2 * * * cd $REPO_DIR && ./scripts/respaldo.sh >> $REPO_DIR/respaldos/respaldo.log 2>&1"
mkdir -p "$REPO_DIR/respaldos"
( crontab -l 2>/dev/null | grep -v 'scripts/respaldo.sh' ; echo "$LINEA" ) | crontab -
ok "Respaldo diario a las 02:00 en $REPO_DIR/respaldos (7 días)"

cat <<FIN

Servidor listo. Desde la PC anfitriona (NAT de VirtualBox, D77):
  Sitio ........ https://localhost:8443
  Correos ...... http://localhost:8025
  SSH .......... ssh -p 2222 $USER@localhost
Cerrá la sesión SSH y volvé a entrar para usar "docker" sin sudo.
FIN
