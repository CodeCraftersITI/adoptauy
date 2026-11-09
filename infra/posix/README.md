# Seguridad POSIX del servidor

Configuración de seguridad de la VM Ubuntu Server 24.04 donde corre AdoptaUY con Docker.

1. Completar `red.conf` con la red real (RED_ADMIN y la IP de la PC de cada integrante).
2. Copiar la clave pública SSH de cada integrante en `claves/<usuario>.pub`.
3. `sudo bash infra/posix/aplicar.sh --desde ~/adoptauy` (mueve el clon actual a `/srv/adoptauy/app`).
4. `sudo bash /srv/adoptauy/app/infra/posix/verificar.sh` (checklist; no modifica nada).

| Archivo | Qué hace |
|---|---|
| `aplicar.sh` | Usuarios y grupos, directorios, permisos, ACL, SSH, TCP Wrappers, ufw, respaldo y chattr. Se puede volver a ejecutar |
| `verificar.sh` | Comprueba cada punto y muestra ✔ o ✘ |
| `sshd-adoptauy.conf` | Plantilla de `/etc/ssh/sshd_config.d/10-adoptauy.conf` |
| `sudoers-adoptauy` | `/etc/sudoers.d/adoptauy`: privilegio mínimo por rol |
| `sbin/adoptauy-desplegar` | Actualiza el código y reconstruye los contenedores (sudo: dev e infra) |
| `sbin/adoptauy-respaldo` | Respaldo diario con permisos, ACL y xattr (RNF:0014) |
| `sbin/adoptauy-restaurar` | Restauración de prueba o de producción, con control SHA-256 |
| `systemd/` | Timer del respaldo a las 02:00 |
| `logrotate-adoptauy` | Rotación de registros con `chattr +a` |

Reemplaza tres cosas que hacía `infra/setup.sh`: la regla de ufw para 8025 (Mailpit queda solo en
127.0.0.1), la línea de cron del respaldo (pasa a un timer de systemd) y el grupo docker para el
usuario de instalación (docker equivale a root).
