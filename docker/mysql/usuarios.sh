#!/bin/sh
# =============================================================================
#  AdoptaUY · Crea los usuarios de MySQL con privilegio mínimo (16.3.4 del integrador)
#
#  Ejecuta docs/base-de-datos/usuarios.sql como root, reemplazando las claves
#  CAMBIAR_clave_... por DB_CLAVE_LOGIN, DB_CLAVE_PUBLICO, ... (del .env de la raíz;
#  sin .env, claves de desarrollo). Tiene que correr después de las migraciones:
#  los permisos por columna necesitan que las tablas existan.
#
#  En Docker lo corre solo el servicio usuarios-bd al levantar el entorno. Para volver
#  a aplicarlo (por ejemplo, si cambian usuarios.sql o las claves):
#      docker compose run --rm usuarios-bd
#  En GitHub Actions lo corre el workflow de pruebas contra el MySQL del runner.
#
#  Variables: MYSQL_HOST y MYSQL_PWD (clave de root), SQL (ruta de usuarios.sql) y
#  BASES (bases a las que se dan los permisos; la primera además crea los usuarios).
#  Las claves no pueden tener los caracteres / ni & (las de iniciar.sh son letras y números).
# =============================================================================
set -e

SQL=${SQL:-/usuarios.sql}
BASES=${BASES:-"adoptauy adoptauy_test"}

# Claves reales en lugar de los marcadores del archivo
script=$(sed \
  -e "s/CAMBIAR_clave_login/${DB_CLAVE_LOGIN:-login_dev}/" \
  -e "s/CAMBIAR_clave_publico/${DB_CLAVE_PUBLICO:-publico_dev}/" \
  -e "s/CAMBIAR_clave_particular/${DB_CLAVE_PARTICULAR:-particular_dev}/" \
  -e "s/CAMBIAR_clave_refugio/${DB_CLAVE_REFUGIO:-refugio_dev}/" \
  -e "s/CAMBIAR_clave_admin/${DB_CLAVE_ADMIN:-admin_dev}/" \
  -e "s/CAMBIAR_clave_sistema/${DB_CLAVE_SISTEMA:-sistema_dev}/" \
  -e "s/CAMBIAR_clave_migraciones/${DB_CLAVE_MIGRACIONES:-migraciones_dev}/" \
  -e "s/CAMBIAR_clave_respaldo/${DB_CLAVE_RESPALDO:-respaldo_dev}/" \
  "$SQL")

primera=1
for base in $BASES; do
  if [ "$primera" = 1 ]; then
    echo "[usuarios-bd] Creando los usuarios y sus permisos sobre $base..."
    printf '%s\n' "$script" | sed "s/ON adoptauy\./ON $base./g" | mysql -h "$MYSQL_HOST" -uroot
    primera=0
  else
    # Las otras bases (adoptauy_test, la de las pruebas) reciben los mismos permisos,
    # sin volver a crear los usuarios
    echo "[usuarios-bd] Mismos permisos sobre $base..."
    printf '%s\n' "$script" \
      | sed -e '/^DROP USER/,/;$/d' -e '/^CREATE USER/d' -e "s/ON adoptauy\./ON $base./g" \
      | mysql -h "$MYSQL_HOST" -uroot
  fi
done

echo "[usuarios-bd] Listo:"
mysql -h "$MYSQL_HOST" -uroot -N -e "SELECT CONCAT(User, '@', Host) FROM mysql.user WHERE User LIKE 'adoptauy\\_%' ORDER BY User"
