#!/usr/bin/env bash
# =============================================================================
#  AdoptaUY · infra/posix/aplicar-transicion.sh
#  Igual que aplicar.sh, pero SSH queda abierto con contraseña mientras el
#  equipo carga sus claves: nadie queda afuera. Solo hace falta RED_ADMIN en
#  red.conf; las IP de cada PC pueden quedar sin completar.
#
#  Uso:  sudo bash infra/posix/aplicar-transicion.sh [--desde ~/adoptauy]
#  Después, con las claves en claves/<usuario>.pub y las IP en red.conf:
#        sudo bash infra/posix/aplicar.sh
# =============================================================================
exec bash "$(cd "$(dirname "$0")" && pwd)/aplicar.sh" --ssh-con-contrasena "$@"
