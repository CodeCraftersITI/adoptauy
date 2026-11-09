# Seguridad POSIX del servidor

Configuración de seguridad de la VM Ubuntu Server 24.04 donde corre AdoptaUY con Docker.

1. Completar `red.conf` con la red real (RED_ADMIN y la IP de la PC de cada integrante).
2. Copiar la clave pública SSH de cada integrante en `claves/<usuario>.pub`.
3. `sudo bash infra/posix/aplicar.sh --desde ~/adoptauy` (mueve el clon actual a `/srv/adoptauy/app`).
4. `sudo bash /srv/adoptauy/app/infra/posix/verificar.sh` (checklist; no modifica nada).

**Si todavía faltan claves o IP fijas**, primero la etapa de transición, para que nadie quede afuera:

1. Completar solo `RED_ADMIN` en `red.conf`.
2. `sudo bash infra/posix/aplicar-transicion.sh --desde ~/adoptauy`: aplica todo igual, pero SSH acepta contraseña o clave, no filtra por la IP de cada PC y también deja entrar a `admin`. Sigue limitado a `RED_ADMIN`.
3. Cada integrante entra con la contraseña temporal, la cambia y prueba su clave con `ssh -i ~/.ssh/<usuario> <usuario>@<IP>`.
4. Con todas las claves en `/srv/adoptauy/app/infra/posix/claves/` y las IP en `red.conf`: `sudo bash /srv/adoptauy/app/infra/posix/aplicar.sh`. Desde ahí, solo clave pública y cada uno desde su PC.

Mientras dure la transición, `verificar.sh` marca como falla «PasswordAuthentication no».

| Archivo | Qué hace |
|---|---|
| `aplicar.sh` | Usuarios y grupos, directorios, permisos, ACL, SSH, TCP Wrappers, ufw, respaldo y chattr. Se puede volver a ejecutar |
| `verificar.sh` | Comprueba cada punto y muestra ✔ o ✘ |
| `aplicar-transicion.sh` | Lo mismo que `aplicar.sh --ssh-con-contrasena`: SSH con contraseña mientras se cargan las claves |
| `sshd-adoptauy.conf` | Plantilla de `/etc/ssh/sshd_config.d/10-adoptauy.conf` |
| `sshd-adoptauy-transicion.conf` | Plantilla de la etapa de transición: contraseña o clave, grupos codecrafters y sudo, solo desde `RED_ADMIN` |
| `sudoers-adoptauy` | `/etc/sudoers.d/adoptauy`: privilegio mínimo por rol |
| `sbin/adoptauy-desplegar` | Actualiza el código y reconstruye los contenedores (sudo: dev e infra) |
| `sbin/adoptauy-respaldo` | Respaldo diario con permisos, ACL y xattr (RNF:0014) |
| `sbin/adoptauy-restaurar` | Restauración de prueba o de producción, con control SHA-256 |
| `systemd/` | Timer del respaldo a las 02:00 |
| `logrotate-adoptauy` | Rotación de registros con `chattr +a` |

Reemplaza tres cosas que hacía `infra/setup.sh`: la regla de ufw para 8025 (Mailpit queda solo en
127.0.0.1), la línea de cron del respaldo (pasa a un timer de systemd) y el grupo docker para el
usuario de instalación (docker equivale a root).
