#!/usr/bin/env bash
# Pruebas de las restricciones de adoptauy.sql sobre una base vacía.
# Uso: bash probar.sh
# Por defecto usa "mysql -uroot" local. Para otro servidor, definir MYSQL, por ejemplo:
#   MYSQL_PWD=root_dev MYSQL="mysql -h 127.0.0.1 -P 3307 -uroot" bash probar.sh
# ATENCIÓN: borra y vuelve a crear la base adoptauy. No usar contra datos reales.
cd "$(dirname "$0")"
MYSQL="${MYSQL:-mysql -uroot}"
M="$MYSQL --default-character-set=utf8mb4 adoptauy -N -e"
H='$2y$12$abcdefghijklmnopqrstuvabcdefghijklmnopqrstuvwxyz01234'
pass=0; fail=0
ok()  { if out=$($M "$2" 2>&1); then echo "PASA  $1"; pass=$((pass+1)); else echo "FALLA $1 -> $out"; fail=$((fail+1)); fi; }
err() { if out=$($M "$2" 2>&1); then echo "FALLA $1 (debía rechazarse)"; fail=$((fail+1)); else echo "PASA  $1 -> ${out#*: }"; pass=$((pass+1)); fi; }
chk() { out=$($M "SELECT ($2)" 2>&1 | grep -v "Using a password"); if [ "$out" = "1" ]; then echo "PASA  $1"; pass=$((pass+1)); else echo "FALLA $1 -> $out"; fail=$((fail+1)); fi; }
usr() { $M "SELECT ID_Usuario FROM Usuario WHERE Email='$1'"; }
nuevo() { $M "INSERT INTO Usuario(Email,\`Contraseña\`,Tipo_Usuario) VALUES('$1','$H','$2')"; usr "$1"; }

$MYSQL < adoptauy.sql

# --- Usuarios -----------------------------------------------------------------
ANA=$(nuevo ana@prueba.uy Particular)
ok  "Alta de Particular válido (CP-01)" "INSERT INTO Particular(ID_Usuario,CI,Nombre,Apellido,Telefono) VALUES($ANA,'12345672','Ana','Suárez','099123456')"
BRU=$(nuevo bruno@prueba.uy Particular)
ok  "Cédula de 7 dígitos y teléfono fijo" "INSERT INTO Particular(ID_Usuario,CI,Nombre,Apellido,Telefono) VALUES($BRU,'1234561','Bruno','Pereira','22001234')"
err "Correo repetido" "INSERT INTO Usuario(Email,\`Contraseña\`,Tipo_Usuario) VALUES('ana@prueba.uy','$H','Particular')"
err "Contraseña sin hash (RNE13)" "INSERT INTO Usuario(Email,\`Contraseña\`,Tipo_Usuario) VALUES('x@prueba.uy','Clave2026','Particular')"
CAR=$(nuevo carla@prueba.uy Particular)
err "Dígito verificador incorrecto (RNE23)" "INSERT INTO Particular(ID_Usuario,CI,Nombre,Apellido,Telefono) VALUES($CAR,'12345673','Carla','Díaz','099000111')"
err "Cédula repetida (RNE23)" "INSERT INTO Particular(ID_Usuario,CI,Nombre,Apellido,Telefono) VALUES($CAR,'12345672','Carla','Díaz','099000111')"
err "Cédula con puntos y guion (D79)" "INSERT INTO Particular(ID_Usuario,CI,Nombre,Apellido,Telefono) VALUES($CAR,'1234567-2','Carla','Díaz','099000111')"
err "Teléfono de 7 dígitos (D81)" "INSERT INTO Particular(ID_Usuario,CI,Nombre,Apellido,Telefono) VALUES($CAR,'45678905','Carla','Díaz','0981112')"
err "Nombre de 1 carácter (D80)" "INSERT INTO Particular(ID_Usuario,CI,Nombre,Apellido,Telefono) VALUES($CAR,'45678905','C','Díaz','099000111')"
ok  "Particular con cédula 45678905" "INSERT INTO Particular(ID_Usuario,CI,Nombre,Apellido,Telefono) VALUES($CAR,'45678905','Carla','Díaz','099000111')"
ADM=$(nuevo admin@prueba.uy Administrador); $M "INSERT INTO Administrador(ID_Usuario) VALUES($ADM)"
err "Un Administrador no puede ser también Particular (RNE10)" "INSERT INTO Particular(ID_Usuario,CI,Nombre,Apellido,Telefono) VALUES($ADM,'31415921','Otro','Admin','099000222')"

# --- Publicaciones -------------------------------------------------------------
ADOP="INSERT INTO Publicacion_Animal(ID_Usuario,Tipo_Publicacion,Especie,Raza,Sexo,Edad_Meses,\`Tamaño\`,Estado_Salud,Temperamento,Estado_Esterilizacion,Ubicacion_Aproximada,Estado_Publicacion) VALUES"
PERD="INSERT INTO Publicacion_Animal(ID_Usuario,Tipo_Publicacion,Especie,Raza,Sexo,Descripcion,Nombre_Mascota,Fecha_Extravio,Zona_Extravio,Contacto,Estado_Publicacion) VALUES"
ok  "Publicación en adopción válida (CP-06)" "$ADOP($ANA,'Adopción','Perro','Mestizo','Macho',8,'Mediano','Sano','Tranquilo','Esterilizado','Pocitos','Archivado')"
P1=$($M "SELECT MAX(ID_Publicacion) FROM Publicacion_Animal")
chk "  nace En adopción aunque se envíe otro estado (RNE02)" "SELECT Estado_Publicacion='En adopción' FROM Publicacion_Animal WHERE ID_Publicacion=$P1"
err "Adopción sin tamaño" "INSERT INTO Publicacion_Animal(ID_Usuario,Tipo_Publicacion,Especie,Raza,Sexo,Edad_Meses,Estado_Salud,Temperamento,Estado_Esterilizacion,Ubicacion_Aproximada,Estado_Publicacion) VALUES($ANA,'Adopción','Perro','Mestizo','Macho',8,'Sano','Tranquilo','Esterilizado','Pocitos','En adopción')"
err "Edad negativa (RNE12)" "$ADOP($ANA,'Adopción','Perro','Mestizo','Macho',-1,'Mediano','Sano','Tranquilo','Esterilizado','Pocitos','En adopción')"
err "Especie fuera de la lista" "$ADOP($ANA,'Adopción','Conejo','Mestizo','Macho',8,'Mediano','Sano','Tranquilo','Esterilizado','Pocitos','En adopción')"
err "Raza de 51 caracteres (D80)" "$ADOP($ANA,'Adopción','Perro',REPEAT('a',51),'Macho',8,'Mediano','Sano','Tranquilo','Esterilizado','Pocitos','En adopción')"
ok  "Mascota perdida válida (CP-09)" "$PERD($ANA,'Perdida','Gato','Siamés','Hembra','Collar rojo','Luna',CURRENT_DATE - INTERVAL 6 DAY,'Malvín','098765432','Perdida')"
P2=$($M "SELECT MAX(ID_Publicacion) FROM Publicacion_Animal")
chk "  vence 30 días después de creada (RNE17)" "SELECT Fecha_Vencimiento = CURRENT_DATE + INTERVAL 30 DAY FROM Publicacion_Animal WHERE ID_Publicacion=$P2"
err "Fecha de extravío futura" "$PERD($ANA,'Perdida','Gato','Siamés','Hembra',NULL,'Luna',CURRENT_DATE + INTERVAL 1 DAY,'Malvín','098765432','Perdida')"
err "Zona de 2 caracteres (D80)" "$PERD($ANA,'Perdida','Gato','Siamés','Hembra',NULL,'Luna',CURRENT_DATE,'Ab','098765432','Perdida')"
err "El Administrador no publica" "$PERD($ADM,'Perdida','Gato','Siamés','Hembra',NULL,'Luna',CURRENT_DATE,'Malvín','098765432','Perdida')"

ok  "6 fotos en adopción (RNE01)" "INSERT INTO Foto_Animal(ID_Publicacion,URL_Foto) VALUES($P1,'a.jpg'),($P1,'b.png'),($P1,'c.webp'),($P1,'d.JPG'),($P1,'e.jpeg'),($P1,'f.jpg')"
err "7.ª foto en adopción (RNE01)" "INSERT INTO Foto_Animal(ID_Publicacion,URL_Foto) VALUES($P1,'g.jpg')"
err "Foto GIF (RNE01)" "INSERT INTO Foto_Animal(ID_Publicacion,URL_Foto) VALUES($P2,'a.gif')"
ok  "3 fotos en perdida" "INSERT INTO Foto_Animal(ID_Publicacion,URL_Foto) VALUES($P2,'a.jpg'),($P2,'b.jpg'),($P2,'c.jpg')"
err "4.ª foto en perdida (RNE01)" "INSERT INTO Foto_Animal(ID_Publicacion,URL_Foto) VALUES($P2,'d.jpg')"

err "Transición Perdida → En adopción (RNE02)" "UPDATE Publicacion_Animal SET Estado_Publicacion='En adopción' WHERE ID_Publicacion=$P2"
ok  "Archivar y renovar una mascota perdida (D70)" "UPDATE Publicacion_Animal SET Estado_Publicacion='Archivada' WHERE ID_Publicacion=$P2; CALL sp_renovar_perdida($P2,$ANA)"
chk "  vuelve a Perdida con 30 días desde hoy" "SELECT Estado_Publicacion='Perdida' AND Fecha_Vencimiento=CURRENT_DATE + INTERVAL 30 DAY FROM Publicacion_Animal WHERE ID_Publicacion=$P2"
$M "UPDATE Publicacion_Animal SET Fecha_Vencimiento=CURRENT_DATE WHERE ID_Publicacion=$P2; CALL sp_archivar_perdidas_vencidas()" >/dev/null
chk "Tarea diaria archiva la vencida" "SELECT Estado_Publicacion='Archivada' FROM Publicacion_Animal WHERE ID_Publicacion=$P2"
chk "Vistas públicas: 1 en adopción y 0 perdidas" "SELECT (SELECT COUNT(*) FROM v_publicaciones_en_adopcion)=1 AND (SELECT COUNT(*) FROM v_mascotas_perdidas)=0"

# --- Adopción y seguimiento ---------------------------------------------------
err "Solicitar la propia publicación (RNE18)" "INSERT INTO Solicitud_Adopcion(ID_Publicacion,ID_Usuario) VALUES($P1,$ANA)"
err "Solicitar una mascota perdida (RNE18)" "INSERT INTO Solicitud_Adopcion(ID_Publicacion,ID_Usuario) VALUES($P2,$BRU)"
err "El Administrador no solicita" "INSERT INTO Solicitud_Adopcion(ID_Publicacion,ID_Usuario) VALUES($P1,$ADM)"
ok  "Dos solicitudes de usuarios distintos" "INSERT INTO Solicitud_Adopcion(ID_Publicacion,ID_Usuario) VALUES($P1,$BRU),($P1,$CAR)"
err "Segunda solicitud pendiente del mismo usuario" "INSERT INTO Solicitud_Adopcion(ID_Publicacion,ID_Usuario) VALUES($P1,$BRU)"
SB=$($M "SELECT ID_Solicitud FROM Solicitud_Adopcion WHERE ID_Usuario=$BRU")
SC=$($M "SELECT ID_Solicitud FROM Solicitud_Adopcion WHERE ID_Usuario=$CAR")
err "Concretar quien no es el publicante (RNE03)" "CALL sp_concretar_adopcion($SB,$BRU)"
ok  "Concretar la adopción eligiendo a Bruno (RNE19)" "CALL sp_concretar_adopcion($SB,$ANA)"
chk "  Bruno Adoptada, Carla Rechazada, publicación Adoptado" "SELECT (SELECT Estado_Solicitud FROM Solicitud_Adopcion WHERE ID_Solicitud=$SB)='Adoptada' AND (SELECT Estado_Solicitud FROM Solicitud_Adopcion WHERE ID_Solicitud=$SC)='Rechazada' AND (SELECT Estado_Publicacion FROM Publicacion_Animal WHERE ID_Publicacion=$P1)='Adoptado'"
chk "  seguimiento: aviso día 15 y límite día 22 (RNE20)" "SELECT Fecha_Aviso=CURRENT_DATE + INTERVAL 15 DAY AND Fecha_Limite=CURRENT_DATE + INTERVAL 22 DAY FROM Seguimiento"
err "Adoptado no vuelve a En adopción (RNE02)" "UPDATE Publicacion_Animal SET Estado_Publicacion='En adopción' WHERE ID_Publicacion=$P1"
err "Una solicitud Rechazada no cambia" "UPDATE Solicitud_Adopcion SET Estado_Solicitud='Pendiente' WHERE ID_Solicitud=$SC"
ok  "Bruno publica antes del día 22" "$PERD($BRU,'Perdida','Perro','Mestizo','Macho',NULL,'Toby',CURRENT_DATE,'Centro','099123456','Perdida')"
$M "UPDATE Seguimiento SET Fecha_Aviso=CURRENT_DATE - INTERVAL 8 DAY, Fecha_Limite=CURRENT_DATE - INTERVAL 1 DAY"
chk "Pasado el día 22 sin foto, Bruno queda restringido" "SELECT fn_usuario_restringido($BRU)"
err "  y no puede publicar (RNE21)" "$PERD($BRU,'Perdida','Gato','Siamés','Hembra',NULL,'Mia',CURRENT_DATE,'Centro','099123456','Perdida')"
ok  "Al enviar la foto se levanta la restricción" "UPDATE Seguimiento SET URL_Foto='seguimiento.jpg', Fecha_Envio=NOW(), Estado_Evidencia='Enviada'; $PERD($BRU,'Perdida','Gato','Siamés','Hembra',NULL,'Mia',CURRENT_DATE,'Centro','099123456','Perdida')"

# --- Reportes -----------------------------------------------------------------
R=(); for i in $(seq 1 11); do u=$(nuevo "rep$i@prueba.uy" Particular); R+=("$u"); done
ok  "2 reportes a una publicación" "INSERT INTO Reporte(ID_Usuario_Reportante,ID_Publicacion_Reportada,Motivo) VALUES(${R[0]},$P2,'Publicación duplicada o spam'),(${R[1]},$P2,'Publicación duplicada o spam')"
chk "  sigue Visible" "SELECT Estado_Moderacion='Visible' FROM Publicacion_Animal WHERE ID_Publicacion=$P2"
ok  "3.er reporte" "INSERT INTO Reporte(ID_Usuario_Reportante,ID_Publicacion_Reportada,Motivo) VALUES(${R[2]},$P2,'Publicación duplicada o spam')"
chk "  pasa a En revisión (RNE15)" "SELECT Estado_Moderacion='En revisión' FROM Publicacion_Animal WHERE ID_Publicacion=$P2"
err "Reportar dos veces lo mismo (RNE22)" "INSERT INTO Reporte(ID_Usuario_Reportante,ID_Publicacion_Reportada,Motivo) VALUES(${R[0]},$P2,'Otro')"
err "Reportarse a sí mismo (RNE22)" "INSERT INTO Reporte(ID_Usuario_Reportante,ID_Usuario_Reportado,Motivo) VALUES($ANA,$ANA,'Otro')"
err "Reporte sin objeto (RNE22)" "INSERT INTO Reporte(ID_Usuario_Reportante,Motivo) VALUES($ANA,'Otro')"
ok  "4 reportes a Carla" "INSERT INTO Reporte(ID_Usuario_Reportante,ID_Usuario_Reportado,Motivo) VALUES(${R[0]},$CAR,'Otro'),(${R[1]},$CAR,'Otro'),(${R[2]},$CAR,'Otro'),(${R[3]},$CAR,'Otro')"
chk "  sigue Activa" "SELECT Estado_Usuario='Activo' FROM Usuario WHERE ID_Usuario=$CAR"
ok  "5.º reporte" "INSERT INTO Reporte(ID_Usuario_Reportante,ID_Usuario_Reportado,Motivo) VALUES(${R[4]},$CAR,'Otro')"
chk "  En revisión (RNE15)" "SELECT Estado_Usuario='En revisión' FROM Usuario WHERE ID_Usuario=$CAR"
ok  "Reportes 6 a 10" "INSERT INTO Reporte(ID_Usuario_Reportante,ID_Usuario_Reportado,Motivo) VALUES(${R[5]},$CAR,'Otro'),(${R[6]},$CAR,'Otro'),(${R[7]},$CAR,'Otro'),(${R[8]},$CAR,'Otro'),(${R[9]},$CAR,'Otro')"
chk "  Inhabilitada (RNE15)" "SELECT Estado_Usuario='Inhabilitado' FROM Usuario WHERE ID_Usuario=$CAR"

# --- Refugios -----------------------------------------------------------------
RF=$(nuevo refugio@prueba.uy Refugio)
$M "INSERT INTO Refugio(ID_Usuario,Nombre_Refugio,Certificado_Personeria,Comprobante_Domicilio,Ubicacion) VALUES($RF,'Refugio de prueba','cert.pdf','dom.pdf','Montevideo')"
err "Refugio pendiente no publica cuenta (RNE04)" "INSERT INTO Cuenta_Bancaria(ID_Usuario,Banco,Tipo_Cuenta,Numero_Cuenta,Titular) VALUES($RF,'Banco X','Caja de ahorro','001234','Refugio de prueba')"
err "Refugio pendiente no publica mascotas (RNE04)" "$ADOP($RF,'Adopción','Gato','Mestizo','Hembra',11,'Pequeño','Sana','Juguetona','No esterilizado','Cordón','En adopción')"
err "Rechazo sin motivo (RNE05)" "UPDATE Refugio SET Estado_Validacion='Rechazado' WHERE ID_Usuario=$RF"
err "Pendiente → Suspendido (RNE24)" "UPDATE Refugio SET Estado_Validacion='Suspendido' WHERE ID_Usuario=$RF"
ok  "Validar el refugio" "UPDATE Refugio SET Estado_Validacion='Validado' WHERE ID_Usuario=$RF"
chk "  registra la fecha de resolución" "SELECT Fecha_Resolucion IS NOT NULL FROM Refugio WHERE ID_Usuario=$RF"
ok  "Refugio validado publica cuenta y mascota" "INSERT INTO Cuenta_Bancaria(ID_Usuario,Banco,Tipo_Cuenta,Numero_Cuenta,Titular) VALUES($RF,'Banco X','Caja de ahorro','001234','Refugio de prueba'); $ADOP($RF,'Adopción','Gato','Mestizo','Hembra',11,'Pequeño','Sana','Juguetona','No esterilizado','Cordón','En adopción')"
chk "Vista pública: el gato de 11 meses es cachorro (D63)" "SELECT Es_Cachorro=1 FROM v_publicaciones_en_adopcion WHERE ID_Usuario=$RF"
ok  "3 reportes al refugio" "INSERT INTO Reporte(ID_Usuario_Reportante,ID_Usuario_Reportado,Motivo) VALUES(${R[0]},$RF,'Otro'),(${R[1]},$RF,'Otro'),(${R[2]},$RF,'Otro')"
chk "  Suspendido (RNE15)" "SELECT Estado_Validacion='Suspendido' FROM Refugio WHERE ID_Usuario=$RF"
ok  "Baja del refugio" "UPDATE Refugio SET Estado_Validacion='Dado de baja' WHERE ID_Usuario=$RF"
chk "  sus publicaciones pasan a Archivado (RNE24)" "SELECT COUNT(*)=1 AND MIN(Estado_Publicacion)='Archivado' FROM Publicacion_Animal WHERE ID_Usuario=$RF"
err "Dado de baja es definitivo (RNE24)" "UPDATE Refugio SET Estado_Validacion='Validado' WHERE ID_Usuario=$RF"

echo; echo "Resultado: $pass pasaron, $fail fallaron"
[ "$fail" -eq 0 ]
