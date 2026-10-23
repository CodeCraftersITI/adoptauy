#!/usr/bin/env bash
# AdoptaUY · Prueba básica del script de la base de datos.
# Crea la base desde cero y comprueba tablas, claves y valores por defecto; después carga
# los datos de prueba y los usuarios de MySQL y prueba cada privilegio (permitido y denegado).
# ATENCIÓN: borra y recrea la base adoptauy. No usar contra datos reales.
#
# Uso:  MYSQL_PWD=<clave> MYSQL="mysql -h 127.0.0.1 -P 3307 -uroot" HOST=127.0.0.1 PUERTO=3307 bash probar.sh
set -uo pipefail
cd "$(dirname "$0")"
MYSQL="${MYSQL:-mysql -uroot}"
ok=0; fallas=0

sql()  { $MYSQL --default-character-set=utf8mb4 -N -B adoptauy -e "$1" 2>&1 | grep -v 'Using a password'; }
chk()  { # chk "descripción" "valor esperado" "consulta"
  local r; r="$(sql "$3")"
  if [[ "$r" == "$2" ]]; then ok=$((ok+1)); else fallas=$((fallas+1)); echo "FALLA: $1 (esperado: $2, obtenido: $r)"; fi; }
error() { # error "descripción" "sentencia que debe fallar"
  if [[ "$(sql "$2")" == ERROR* ]]; then ok=$((ok+1)); else fallas=$((fallas+1)); echo "FALLA: $1 (no dio error)"; fi; }

$MYSQL --default-character-set=utf8mb4 < adoptauy.sql 2>&1 | grep -v 'Using a password' && { echo "El script dio error"; exit 1; }

# Estructura
chk "14 tablas" 14 "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='adoptauy'"
chk "dos subtipos de publicación" 2 "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='adoptauy' AND table_name IN ('Publicacion_Adopcion','Publicacion_Perdida')"
chk "sin Temperamento" 0 "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='adoptauy' AND column_name LIKE 'Temperamento%'"
chk "sin triggers" 0 "SELECT COUNT(*) FROM information_schema.triggers WHERE trigger_schema='adoptauy'"
chk "sin procedimientos ni funciones" 0 "SELECT COUNT(*) FROM information_schema.routines WHERE routine_schema='adoptauy'"

# Datos ficticios
sql "INSERT INTO Usuario (Email, Contraseña, Tipo_Usuario) VALUES ('ana@ejemplo.test','hash','Particular'),('refugio@ejemplo.test','hash','Refugio')" >/dev/null
chk "Estado_Usuario por defecto" "Activo" "SELECT Estado_Usuario FROM Usuario WHERE Email='ana@ejemplo.test'"
chk "Email_Verificado vacío al registrarse" "NULL" "SELECT IFNULL(Email_Verificado,'NULL') FROM Usuario WHERE Email='ana@ejemplo.test'"
error "Email repetido" "INSERT INTO Usuario (Email, Contraseña, Tipo_Usuario) VALUES ('ana@ejemplo.test','hash','Particular')"

sql "INSERT INTO Particular VALUES (1,'12345672','Ana','Pérez','099123456')" >/dev/null
error "CI repetida" "INSERT INTO Particular VALUES (2,'12345672','Otro','Nombre','099000000')"
error "Particular sin Usuario" "INSERT INTO Particular VALUES (99,'11111111','X','Y','099000000')"

sql "INSERT INTO Refugio (ID_Usuario, Nombre_Refugio, Certificado_Personeria, Comprobante_Domicilio, Ubicacion) VALUES (2,'Refugio de prueba','cert.pdf','dom.pdf','Montevideo')" >/dev/null
chk "Estado_Validacion por defecto" "Pendiente de validación" "SELECT Estado_Validacion FROM Refugio WHERE ID_Usuario=2"
sql "INSERT INTO Cuenta_Bancaria VALUES (2,1,'Banco de prueba','Caja de ahorro','000123-4','Refugio de prueba')" >/dev/null
error "Cuenta de un usuario que no es refugio" "INSERT INTO Cuenta_Bancaria VALUES (1,1,'Banco','Caja de ahorro','1','Ana')"

# Publicación en adopción: supertipo + subtipo
sql "INSERT INTO Publicacion_Animal (ID_Usuario, Tipo_Publicacion, Especie, Raza, Sexo) VALUES (1,'Adopción','Perro','Mestizo','Macho')" >/dev/null
sql "INSERT INTO Publicacion_Adopcion (ID_Publicacion, Edad_Meses, Tamaño, Estado_Salud, Estado_Esterilizacion, Ubicacion_Aproximada) VALUES (1,8,'Mediano','Sano','Esterilizado','Cordón')" >/dev/null
chk "Estado_Adopcion por defecto" "En adopción" "SELECT Estado_Adopcion FROM Publicacion_Adopcion WHERE ID_Publicacion=1"
chk "Estado_Moderacion por defecto" "Visible" "SELECT Estado_Moderacion FROM Publicacion_Animal WHERE ID_Publicacion=1"
error "Subtipo sin supertipo" "INSERT INTO Publicacion_Adopcion (ID_Publicacion, Edad_Meses, Tamaño, Estado_Salud, Estado_Esterilizacion, Ubicacion_Aproximada) VALUES (50,8,'Mediano','Sano','Esterilizado','Cordón')"
error "Tamaño fuera de la lista" "UPDATE Publicacion_Adopcion SET Tamaño='Gigante' WHERE ID_Publicacion=1"
error "Edad negativa" "UPDATE Publicacion_Adopcion SET Edad_Meses=-1 WHERE ID_Publicacion=1"
sql "INSERT INTO Foto_Animal VALUES (1,1,'fotos/1-1.jpg')" >/dev/null
error "Foto repetida" "INSERT INTO Foto_Animal VALUES (1,1,'fotos/1-1b.jpg')"

# Publicación perdida
sql "INSERT INTO Publicacion_Animal (ID_Usuario, Tipo_Publicacion, Especie, Raza, Sexo, Descripcion) VALUES (1,'Perdida','Gato','Siamés','Hembra','Collar rojo')" >/dev/null
sql "INSERT INTO Publicacion_Perdida (ID_Publicacion, Nombre_Mascota, Fecha_Extravio, Zona_Extravio, Contacto, Fecha_Vencimiento) VALUES (2,'Mishi',CURDATE(),'Pocitos','099123456',CURDATE()+INTERVAL 30 DAY)" >/dev/null
chk "Estado_Perdida por defecto" "Perdida" "SELECT Estado_Perdida FROM Publicacion_Perdida WHERE ID_Publicacion=2"

# Solicitud, adopción y seguimiento
error "Solicitud sobre una publicación perdida" "INSERT INTO Solicitud_Adopcion (ID_Publicacion, ID_Usuario) VALUES (2,1)"
error "Solicitud de un refugio" "INSERT INTO Solicitud_Adopcion (ID_Publicacion, ID_Usuario) VALUES (1,2)"
sql "INSERT INTO Solicitud_Adopcion (ID_Publicacion, ID_Usuario) VALUES (1,1)" >/dev/null
chk "Estado_Solicitud por defecto" "Pendiente" "SELECT Estado_Solicitud FROM Solicitud_Adopcion WHERE ID_Publicacion=1 AND ID_Usuario=1"
sql "INSERT INTO Adopcion (ID_Solicitud, Fecha_Adopcion) SELECT ID_Solicitud, CURDATE() FROM Solicitud_Adopcion WHERE ID_Publicacion=1 AND ID_Usuario=1" >/dev/null
error "Dos adopciones de la misma solicitud" "INSERT INTO Adopcion (ID_Solicitud, Fecha_Adopcion) SELECT ID_Solicitud, CURDATE() FROM Solicitud_Adopcion WHERE ID_Publicacion=1 AND ID_Usuario=1"
sql "INSERT INTO Seguimiento (ID_Adopcion, Fecha_Aviso, Fecha_Limite) SELECT ID_Adopcion, CURDATE()+INTERVAL 15 DAY, CURDATE()+INTERVAL 22 DAY FROM Adopcion" >/dev/null
chk "Estado_Evidencia por defecto" "Pendiente" "SELECT Estado_Evidencia FROM Seguimiento"

# Reporte
sql "INSERT INTO Reporte (ID_Usuario_Reportante, ID_Publicacion_Reportada, Motivo) VALUES (2,1,'Publicación duplicada o spam')" >/dev/null
chk "Estado_Reporte por defecto" "Pendiente" "SELECT Estado_Reporte FROM Reporte"
error "Medida fuera de la lista" "UPDATE Reporte SET Medida='Multa'"

# =============================================================================
# Datos de prueba y usuarios de la base
# =============================================================================
for f in adoptauy.sql datos-prueba.sql usuarios.sql; do
  $MYSQL --default-character-set=utf8mb4 < "$f" 2>&1 | grep -v 'Using a password' && { echo "$f dio error"; exit 1; }
done

# Datos: un registro por estado
chk "8 usuarios (1 admin, 4 particulares, 3 refugios)" "1 4 3" "SELECT CONCAT_WS(' ',SUM(Tipo_Usuario='Administrador'),SUM(Tipo_Usuario='Particular'),SUM(Tipo_Usuario='Refugio')) FROM Usuario"
chk "refugios en 3 estados" 3 "SELECT COUNT(DISTINCT Estado_Validacion) FROM Refugio"
chk "adopción en sus 3 estados" 3 "SELECT COUNT(DISTINCT Estado_Adopcion) FROM Publicacion_Adopcion"
chk "perdidas en sus 3 estados" 3 "SELECT COUNT(DISTINCT Estado_Perdida) FROM Publicacion_Perdida"
chk "solicitudes en sus 3 estados" 3 "SELECT COUNT(DISTINCT Estado_Solicitud) FROM Solicitud_Adopcion"
chk "reportes en sus 3 estados" 3 "SELECT COUNT(DISTINCT Estado_Reporte) FROM Reporte"
chk "cada publicación tiene un solo subtipo" 0 "SELECT COUNT(*) FROM Publicacion_Animal p LEFT JOIN Publicacion_Adopcion a USING(ID_Publicacion) LEFT JOIN Publicacion_Perdida d USING(ID_Publicacion) WHERE (a.ID_Publicacion IS NULL) = (d.ID_Publicacion IS NULL)"
chk "fotos entre 1 y 6 (adopción) y 1 y 3 (perdida)" 0 "SELECT COUNT(*) FROM (SELECT p.Tipo_Publicacion t, COUNT(f.ID_Foto) n FROM Publicacion_Animal p LEFT JOIN Foto_Animal f USING(ID_Publicacion) GROUP BY p.ID_Publicacion, p.Tipo_Publicacion) x WHERE n<1 OR (t='Adopción' AND n>6) OR (t='Perdida' AND n>3)"
chk "un cachorro (menos de 12 meses)" 1 "SELECT COUNT(*) FROM Publicacion_Adopcion WHERE Edad_Meses < 12"
chk "seguimiento: aviso a los 15 días y límite a los 22" 0 "SELECT COUNT(*) FROM Seguimiento s JOIN Adopcion a USING(ID_Adopcion) WHERE s.Fecha_Aviso <> a.Fecha_Adopcion + INTERVAL 15 DAY OR s.Fecha_Limite <> s.Fecha_Aviso + INTERVAL 7 DAY"
chk "una publicación en revisión con 3 reportes" 3 "SELECT COUNT(DISTINCT r.ID_Usuario_Reportante) FROM Reporte r JOIN Publicacion_Animal p ON p.ID_Publicacion=r.ID_Publicacion_Reportada WHERE p.Estado_Moderacion='En revisión'"
chk "contraseñas solo como hash bcrypt" 0 "SELECT COUNT(*) FROM Usuario WHERE \`Contraseña\` NOT LIKE '\$2y\$%'"

# Privilegios: cada usuario prueba algo permitido y algo denegado
HOST="${HOST:-127.0.0.1}"; PUERTO="${PUERTO:-3306}"
como() { # como usuario "sentencia"
  MYSQL_PWD="CAMBIAR_clave_${1#adoptauy_}" mysql -h "$HOST" -P "$PUERTO" -u "$1" --default-character-set=utf8mb4 -N -B adoptauy -e "$2" 2>&1 | grep -v 'Using a password'; }
si() { local r; r="$(como "$1" "$2")"; if [[ "$r" != ERROR* ]]; then ok=$((ok+1)); else fallas=$((fallas+1)); echo "FALLA: $1 debería poder: $2 ($r)"; fi; }
no() { local r; r="$(como "$1" "$2")"; if [[ "$r" == ERROR* ]]; then ok=$((ok+1)); else fallas=$((fallas+1)); echo "FALLA: $1 no debería poder: $2"; fi; }

si adoptauy_login      "SELECT Email, \`Contraseña\`, Estado_Usuario FROM Usuario WHERE Email='ana.suarez@prueba.test'"
no adoptauy_login      "SELECT CI FROM Particular"
no adoptauy_login      "UPDATE Usuario SET Estado_Usuario='Activo' WHERE ID_Usuario=5"
si adoptauy_publico    "SELECT COUNT(*) FROM Publicacion_Animal"
si adoptauy_publico    "SELECT Nombre_Refugio, Ubicacion FROM Refugio"
no adoptauy_publico    "SELECT Certificado_Personeria FROM Refugio"
no adoptauy_publico    "SELECT Telefono FROM Particular"
no adoptauy_publico    "UPDATE Usuario SET Estado_Usuario='Baja' WHERE ID_Usuario=2"
si adoptauy_particular "UPDATE Publicacion_Adopcion SET Estado_Salud='Sano' WHERE ID_Publicacion=1"
si adoptauy_particular "INSERT INTO Reporte (ID_Usuario_Reportante, ID_Publicacion_Reportada, Motivo) VALUES (3, 4, 'Publicación duplicada o spam')"
no adoptauy_particular "DELETE FROM Publicacion_Animal WHERE ID_Publicacion=1"
no adoptauy_particular "SELECT CI FROM Particular"
no adoptauy_particular "UPDATE Refugio SET Estado_Validacion='Validado' WHERE ID_Usuario=7"
si adoptauy_refugio    "UPDATE Refugio SET Nombre_Refugio='Refugio de Prueba Norte' WHERE ID_Usuario=6"
si adoptauy_refugio    "UPDATE Solicitud_Adopcion SET Estado_Solicitud='Rechazada' WHERE ID_Solicitud=3"
no adoptauy_refugio    "UPDATE Refugio SET Estado_Validacion='Validado' WHERE ID_Usuario=6"
no adoptauy_refugio    "INSERT INTO Solicitud_Adopcion (ID_Publicacion, ID_Usuario) VALUES (1, 6)"
si adoptauy_admin      "UPDATE Refugio SET Estado_Validacion='Validado', Fecha_Resolucion=NOW() WHERE ID_Usuario=7"
si adoptauy_admin      "UPDATE Reporte SET Estado_Reporte='Desestimado' WHERE ID_Reporte=1"
no adoptauy_admin      "DELETE FROM Usuario WHERE ID_Usuario=5"
no adoptauy_admin      "DROP TABLE Reporte"
si adoptauy_sistema    "UPDATE Publicacion_Perdida SET Estado_Perdida='Archivada' WHERE Fecha_Vencimiento < CURRENT_DATE"
no adoptauy_sistema    "INSERT INTO Publicacion_Animal (ID_Usuario, Tipo_Publicacion, Especie, Raza, Sexo) VALUES (2,'Adopción','Perro','Mestizo','Macho')"
no adoptauy_sistema    "UPDATE Publicacion_Perdida SET Contacto='099999999' WHERE ID_Publicacion=5"
si adoptauy_migraciones "CREATE TABLE prueba_migracion (id INT PRIMARY KEY); DROP TABLE prueba_migracion"
chk "respaldo: solo lectura y LOCK TABLES" "1" "SELECT COUNT(*) FROM information_schema.schema_privileges WHERE grantee=\"'adoptauy_respaldo'@'localhost'\" AND privilege_type='LOCK TABLES'"
chk "respaldo: sin INSERT, UPDATE ni DELETE" "0" "SELECT COUNT(*) FROM information_schema.schema_privileges WHERE grantee=\"'adoptauy_respaldo'@'localhost'\" AND privilege_type IN ('INSERT','UPDATE','DELETE')"

echo "Pruebas correctas: $ok · fallas: $fallas"
[[ $fallas -eq 0 ]]
