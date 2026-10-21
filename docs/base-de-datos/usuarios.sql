-- =============================================================================
--  AdoptaUY · Usuarios de la base de datos y privilegios
--  Equipo CodeCrafters · TC4, ITI CETP-UTU
--
--  Privilegio mínimo: cada usuario de MySQL puede hacer solo lo que necesita su
--  parte de la aplicación. Ninguno, salvo adoptauy_migraciones, puede crear,
--  modificar ni borrar tablas. Ninguno tiene DELETE sobre usuarios,
--  publicaciones, solicitudes ni reportes: las bajas son lógicas (cambio de estado).
--
--  Las contraseñas de este archivo son marcadores: reemplazarlas antes de
--  ejecutarlo y guardarlas solo en el .env del servidor (nunca en GitHub).
--
--  Uso, como root, después de adoptauy.sql:  mysql -u root -p < usuarios.sql
-- =============================================================================

SET NAMES utf8mb4;

-- '%' = desde cualquier contenedor de la red interna de Docker (MySQL no publica
-- el puerto hacia afuera). adoptauy_respaldo solo entra desde el propio servidor.
DROP USER IF EXISTS 'adoptauy_login'@'%', 'adoptauy_publico'@'%', 'adoptauy_particular'@'%',
                   'adoptauy_refugio'@'%', 'adoptauy_admin'@'%', 'adoptauy_sistema'@'%',
                   'adoptauy_migraciones'@'%', 'adoptauy_respaldo'@'localhost';

CREATE USER 'adoptauy_login'@'%'          IDENTIFIED BY 'CAMBIAR_clave_login';
CREATE USER 'adoptauy_publico'@'%'        IDENTIFIED BY 'CAMBIAR_clave_publico';
CREATE USER 'adoptauy_particular'@'%'     IDENTIFIED BY 'CAMBIAR_clave_particular';
CREATE USER 'adoptauy_refugio'@'%'        IDENTIFIED BY 'CAMBIAR_clave_refugio';
CREATE USER 'adoptauy_admin'@'%'          IDENTIFIED BY 'CAMBIAR_clave_admin';
CREATE USER 'adoptauy_sistema'@'%'        IDENTIFIED BY 'CAMBIAR_clave_sistema';
CREATE USER 'adoptauy_migraciones'@'%'    IDENTIFIED BY 'CAMBIAR_clave_migraciones';
CREATE USER 'adoptauy_respaldo'@'localhost' IDENTIFIED BY 'CAMBIAR_clave_respaldo';

-- -----------------------------------------------------------------------------
-- 1. adoptauy_login · inicio de sesión (RF:0022)
--    Solo lee lo necesario para validar correo, contraseña y estado de la cuenta.
--    No ve la cédula, el teléfono ni ningún otro dato personal.
-- -----------------------------------------------------------------------------
GRANT SELECT (ID_Usuario, Email, `Contraseña`, Tipo_Usuario, Estado_Usuario, Email_Verificado)
      ON adoptauy.Usuario TO 'adoptauy_login'@'%';
GRANT SELECT (ID_Usuario, Estado_Validacion) ON adoptauy.Refugio TO 'adoptauy_login'@'%';

-- -----------------------------------------------------------------------------
-- 2. adoptauy_publico · Usuario Invitado (RF:0038 a RF:0042, RF:0048, RF:0062)
--    Consulta lo público y registra Particulares (RF:0039) y solicitudes de refugio (RF:0034).
-- -----------------------------------------------------------------------------
GRANT SELECT ON adoptauy.Publicacion_Animal   TO 'adoptauy_publico'@'%';
GRANT SELECT ON adoptauy.Publicacion_Adopcion TO 'adoptauy_publico'@'%';
GRANT SELECT ON adoptauy.Publicacion_Perdida  TO 'adoptauy_publico'@'%';
GRANT SELECT ON adoptauy.Foto_Animal          TO 'adoptauy_publico'@'%';
GRANT SELECT ON adoptauy.Cuenta_Bancaria      TO 'adoptauy_publico'@'%';
GRANT SELECT, INSERT ON adoptauy.Refugio_Datos_Contacto TO 'adoptauy_publico'@'%';
-- Perfil público del refugio, sin los documentos ni el motivo de rechazo
GRANT SELECT (ID_Usuario, Nombre_Refugio, Ubicacion, Latitud, Longitud, Estado_Validacion, URL_Foto)
      ON adoptauy.Refugio TO 'adoptauy_publico'@'%';
-- Registro: alta y control de correo y cédula repetidos
GRANT SELECT (ID_Usuario, Email), INSERT ON adoptauy.Usuario    TO 'adoptauy_publico'@'%';
GRANT SELECT (ID_Usuario, CI),    INSERT ON adoptauy.Particular TO 'adoptauy_publico'@'%';
GRANT INSERT ON adoptauy.Refugio TO 'adoptauy_publico'@'%';

-- -----------------------------------------------------------------------------
-- 3. adoptauy_particular · Particular con sesión iniciada
--    Publica, solicita adopciones, gestiona sus solicitudes, envía y revisa el
--    seguimiento y reporta (RF:0016 a RF:0021, RF:0046, RF:0049 a RF:0059).
-- -----------------------------------------------------------------------------
GRANT SELECT, INSERT, UPDATE ON adoptauy.Publicacion_Animal   TO 'adoptauy_particular'@'%';
GRANT SELECT, INSERT, UPDATE ON adoptauy.Publicacion_Adopcion TO 'adoptauy_particular'@'%';
GRANT SELECT, INSERT, UPDATE ON adoptauy.Publicacion_Perdida  TO 'adoptauy_particular'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON adoptauy.Foto_Animal  TO 'adoptauy_particular'@'%';   -- reemplazar fotos
GRANT SELECT, INSERT, UPDATE ON adoptauy.Solicitud_Adopcion   TO 'adoptauy_particular'@'%';
GRANT SELECT, INSERT         ON adoptauy.Adopcion             TO 'adoptauy_particular'@'%';
GRANT SELECT, INSERT, UPDATE ON adoptauy.Seguimiento          TO 'adoptauy_particular'@'%';
GRANT SELECT, INSERT         ON adoptauy.Reporte              TO 'adoptauy_particular'@'%';
GRANT SELECT ON adoptauy.Cuenta_Bancaria        TO 'adoptauy_particular'@'%';
GRANT SELECT ON adoptauy.Refugio_Datos_Contacto TO 'adoptauy_particular'@'%';
GRANT SELECT (ID_Usuario, Nombre_Refugio, Ubicacion, Latitud, Longitud, Estado_Validacion, URL_Foto)
      ON adoptauy.Refugio TO 'adoptauy_particular'@'%';
-- Sus datos y los de sus solicitantes (RF:0050): la aplicación filtra qué filas ve
GRANT SELECT (ID_Usuario, Email, Tipo_Usuario, Estado_Usuario, Fecha_Alta), UPDATE (`Contraseña`)
      ON adoptauy.Usuario TO 'adoptauy_particular'@'%';
GRANT SELECT (ID_Usuario, Nombre, Apellido, Telefono), UPDATE (Nombre, Apellido, Telefono)
      ON adoptauy.Particular TO 'adoptauy_particular'@'%';

-- -----------------------------------------------------------------------------
-- 4. adoptauy_refugio · Refugio validado
--    Publica, gestiona solicitudes recibidas, revisa el seguimiento, edita su perfil
--    y sus cuentas bancarias, y reporta (RF:0024 a RF:0029, RF:0035 a RF:0037).
--    No crea solicitudes: un refugio no adopta.
-- -----------------------------------------------------------------------------
GRANT SELECT, INSERT, UPDATE ON adoptauy.Publicacion_Animal   TO 'adoptauy_refugio'@'%';
GRANT SELECT, INSERT, UPDATE ON adoptauy.Publicacion_Adopcion TO 'adoptauy_refugio'@'%';
GRANT SELECT, INSERT, UPDATE ON adoptauy.Publicacion_Perdida  TO 'adoptauy_refugio'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON adoptauy.Foto_Animal  TO 'adoptauy_refugio'@'%';
GRANT SELECT, UPDATE (Estado_Solicitud) ON adoptauy.Solicitud_Adopcion TO 'adoptauy_refugio'@'%';
GRANT SELECT, INSERT         ON adoptauy.Adopcion    TO 'adoptauy_refugio'@'%';
GRANT SELECT, INSERT, UPDATE ON adoptauy.Seguimiento TO 'adoptauy_refugio'@'%';
GRANT SELECT, INSERT         ON adoptauy.Reporte     TO 'adoptauy_refugio'@'%';
-- Cuentas y datos de contacto: se pueden borrar (no son registros con baja lógica)
GRANT SELECT, INSERT, DELETE ON adoptauy.Cuenta_Bancaria        TO 'adoptauy_refugio'@'%';
GRANT SELECT, INSERT, DELETE ON adoptauy.Refugio_Datos_Contacto TO 'adoptauy_refugio'@'%';
-- Su perfil (RF:0029): puede cambiar nombre, ubicación y foto, no su estado de validación
GRANT SELECT, UPDATE (Nombre_Refugio, Ubicacion, Latitud, Longitud, URL_Foto) ON adoptauy.Refugio TO 'adoptauy_refugio'@'%';
GRANT SELECT (ID_Usuario, Email, Tipo_Usuario, Estado_Usuario, Fecha_Alta), UPDATE (`Contraseña`)
      ON adoptauy.Usuario TO 'adoptauy_refugio'@'%';
-- Datos de contacto de sus solicitantes (RF:0050)
GRANT SELECT (ID_Usuario, Nombre, Apellido, Telefono) ON adoptauy.Particular TO 'adoptauy_refugio'@'%';

-- -----------------------------------------------------------------------------
-- 5. adoptauy_admin · Administrador (RF:0001 a RF:0014, RF:0044, RF:0060)
--    Consulta y modifica todo, valida refugios y resuelve reportes. Sin DELETE
--    (baja lógica) y sin cambiar la estructura.
-- -----------------------------------------------------------------------------
GRANT SELECT, INSERT, UPDATE ON adoptauy.* TO 'adoptauy_admin'@'%';

-- -----------------------------------------------------------------------------
-- 6. adoptauy_sistema · tareas programadas y colas de Laravel
--    Aviso del día 15 y control del día 22 (RF:0055, RF:0058), vencimiento de
--    mascotas perdidas (RF:0047), umbrales de reportes (RF:0061) y correos (RF:0063).
-- -----------------------------------------------------------------------------
GRANT SELECT ON adoptauy.Adopcion           TO 'adoptauy_sistema'@'%';
GRANT SELECT ON adoptauy.Solicitud_Adopcion TO 'adoptauy_sistema'@'%';
GRANT SELECT ON adoptauy.Reporte            TO 'adoptauy_sistema'@'%';
GRANT SELECT, UPDATE ON adoptauy.Seguimiento TO 'adoptauy_sistema'@'%';
GRANT SELECT, UPDATE (Estado_Perdida)    ON adoptauy.Publicacion_Perdida TO 'adoptauy_sistema'@'%';
GRANT SELECT, UPDATE (Estado_Moderacion) ON adoptauy.Publicacion_Animal  TO 'adoptauy_sistema'@'%';
GRANT SELECT, UPDATE (Estado_Validacion) ON adoptauy.Refugio             TO 'adoptauy_sistema'@'%';
GRANT SELECT (ID_Usuario, Email, Tipo_Usuario, Estado_Usuario), UPDATE (Estado_Usuario)
      ON adoptauy.Usuario TO 'adoptauy_sistema'@'%';
GRANT SELECT (ID_Usuario, Nombre, Apellido) ON adoptauy.Particular TO 'adoptauy_sistema'@'%';

-- -----------------------------------------------------------------------------
-- 7. adoptauy_migraciones · solo para `php artisan migrate`
--    El único que crea y modifica tablas. La aplicación en marcha nunca lo usa.
-- -----------------------------------------------------------------------------
GRANT ALL PRIVILEGES ON adoptauy.* TO 'adoptauy_migraciones'@'%';

-- -----------------------------------------------------------------------------
-- 8. adoptauy_respaldo · mysqldump del respaldo diario (RNF:0014)
--    Solo lectura: lo mínimo que pide mysqldump --single-transaction.
-- -----------------------------------------------------------------------------
GRANT SELECT, SHOW VIEW, TRIGGER, EVENT, LOCK TABLES ON adoptauy.* TO 'adoptauy_respaldo'@'localhost';
GRANT PROCESS ON *.* TO 'adoptauy_respaldo'@'localhost';   -- información de tablespaces

FLUSH PRIVILEGES;
