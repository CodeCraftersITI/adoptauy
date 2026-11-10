#!/usr/bin/env bash
# AdoptaUY · infra/posix/verificar.sh · checklist de la configuración de seguridad.
# Uso: sudo bash infra/posix/verificar.sh   (no modifica nada)
set -uo pipefail
ok=0; mal=0
chk() { # chk "descripción" comando...
  if "${@:2}" >/dev/null 2>&1; then printf '  \033[32m✔\033[0m %s\n' "$1"; ok=$((ok+1))
  else printf '  \033[31m✘\033[0m %s\n' "$1"; mal=$((mal+1)); fi; }
modo() { [[ "$(stat -c '%a %U:%G' "$1")" == "$2" ]]; }
attr() { lsattr -d "$1" 2>/dev/null | cut -d' ' -f1 | grep -q "$2"; }
acl()  { getfacl -cp "$1" 2>/dev/null | grep -qx "$2"; }
APP=/srv/adoptauy/app; ST=$APP/src/laravel/storage

echo "1. Jerarquía de acceso"
chk "/srv/adoptauy 755 root:root"                  modo /srv/adoptauy "755 root:root"
chk "app 755 root:root"                            modo $APP "755 root:root"
chk ".env 640 root:adoptauy-infra"                 modo $APP/.env "640 root:adoptauy-infra"
chk "storage 2771 adoptauy:adoptauy-dev"           modo $ST "2771 adoptauy:adoptauy-dev"
chk "storage/app/private 2770"                     modo $ST/app/private "2770 adoptauy:adoptauy-dev"
chk "compartido 3770 root:codecrafters"            modo /srv/adoptauy/compartido "3770 root:codecrafters"
chk "/var/log/adoptauy 2750 root:adm"              modo /var/log/adoptauy "2750 root:adm"
chk "/var/backups/adoptauy 700 root:root"          modo /var/backups/adoptauy "700 root:root"

echo "2. Usuarios y grupos"
for u in camilo leandro inaki rodrigo; do
  chk "$u en codecrafters"                         bash -c "id -nG $u | grep -qw codecrafters"
  chk "$u fuera de docker y sudo"                  bash -c "! id -nG $u | grep -Eqw 'docker|sudo'"
  chk "$u: contraseña vence a los 90 días"         bash -c "chage -l $u | grep -q 'Maximum.*: 90'"
done
chk "admin con sudo y sin SSH (fuera de codecrafters)" bash -c "id -nG admin | grep -qw sudo && ! id -nG admin | grep -qw codecrafters"
chk "adoptauy es cuenta de servicio sin shell"     bash -c "getent passwd adoptauy | grep -q ':/usr/sbin/nologin$'"
chk "sudoers válido"                               visudo -cf /etc/sudoers.d/adoptauy
chk "sudoers 440 root:root"                        modo /etc/sudoers.d/adoptauy "440 root:root"
chk "pwquality minlen 12"                          grep -q '^minlen = 12' /etc/security/pwquality.conf.d/adoptauy.conf

echo "3. Permisos especiales"
chk "umask 027 en sesiones"                        grep -q '^umask 027' /etc/profile.d/adoptauy-umask.sh
chk "sin setuid nuevos desde la línea base"        bash -c "diff <(find / -xdev -type f \( -perm -4000 -o -perm -2000 \) 2>/dev/null | sort) /var/log/adoptauy/suid-base.txt"

echo "4. Atributos"
for f in /etc/ssh/sshd_config.d/10-adoptauy.conf /etc/sudoers.d/adoptauy $APP/.env /etc/hosts.allow; do
  chk "+i en $f"                                   attr "$f" i
done
for f in /var/log/adoptauy/{sudo,respaldo,despliegue}.log; do chk "+a en $f" attr "$f" a; done

echo "5. ACL"
chk "logs: grupo adoptauy-bd lee"                  acl /var/log/adoptauy "group:adoptauy-bd:r-x"
chk "logs: ACL por defecto para adoptauy-dev"      acl /var/log/adoptauy "default:group:adoptauy-dev:r-x"
chk "storage/logs: monitoreo lee con herencia"     acl $ST/logs "default:group:adoptauy-monitoreo:r-x"

echo "6. Hosts permitidos"
chk "sshd -t sin errores"                          sshd -t
chk "PermitRootLogin no"                           bash -c "sshd -T 2>/dev/null | grep -qx 'permitrootlogin no'"
chk "PasswordAuthentication no"                    bash -c "sshd -T 2>/dev/null | grep -qx 'passwordauthentication no'"
chk "AllowGroups codecrafters"                     bash -c "sshd -T 2>/dev/null | grep -qx 'allowgroups codecrafters'"
chk "sshd enlazado con libwrap"                    bash -c "ldd /usr/sbin/sshd | grep -q libwrap"
chk "hosts.deny rechaza todo lo demás"             grep -qx 'sshd: ALL' /etc/hosts.deny

echo "7. Firewall"
chk "ufw activo"                                   bash -c "ufw status | grep -q 'Status: active'"
chk "entrante denegado por defecto"                bash -c "ufw status verbose | grep -q 'deny (incoming)'"
chk "sin regla abierta para 8025"                  bash -c "! ufw status | grep -q 8025"
chk "Docker publica solo 80/443 hacia afuera"      bash -c "! ss -Htln | awk '{print \$4}' | grep -Ev '^(127\.0\.0\.1|\[::1\]|127\.0\.0\.53[0-9]*)' | grep -Ev ':(22|80|443|10050)$' | grep -q ."

echo "8. Respaldo"
chk "timer habilitado"                             systemctl is-enabled adoptauy-respaldo.timer
chk "hay al menos un respaldo"                     bash -c "ls -d /var/backups/adoptauy/diario/*/ | grep -q ."
chk "último respaldo íntegro (SHA-256)"            bash -c 'd=$(ls -1d /var/backups/adoptauy/diario/*/ | tail -1); cd "$d" && sha256sum --quiet -c SHA256SUMS'

echo; echo "Correctos: $ok · con problemas: $mal"
(( mal == 0 ))
