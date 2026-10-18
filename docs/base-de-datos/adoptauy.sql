-- =============================================================================
--  AdoptaUY · Script de creación de la base de datos
--  Equipo CodeCrafters · TC4, ITI CETP-UTU
--
--  Base: pasaje a tablas (15.2.1) y restricciones RNE01 a RNE24 del Documento
--  integrador 2.1. Los nombres de tablas y columnas son los del DER.
--
--  Motor: MySQL 8.4 (probado también en MySQL 8.0). Juego de caracteres utf8mb4.
--  Ejecutar con un usuario con permiso para crear funciones y triggers, por
--  ejemplo:  mysql -u root -p < adoptauy.sql
--
--  Cómo se cumplen las restricciones:
--    · CHECK y claves: lo que se puede comprobar con los datos de la misma fila.
--    · Triggers: lo que depende de otras tablas o de transiciones de estado.
--    · Procedimientos: operaciones que cambian varias tablas a la vez.
--    · Aplicación (Laravel): lo que la base no puede verificar sola; se indica
--      en cada caso con "(Aplicación)".
--
--  Baja lógica: ninguna clave foránea borra en cascada y no hay DELETE en el
--  flujo normal; los registros cambian de estado.
-- =============================================================================

-- Obligatorio: sin esto, un cliente con otro juego de caracteres rompe las
-- tildes y la ñ (Contraseña, Tamaño, 'Adopción').
SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci;

DROP DATABASE IF EXISTS adoptauy;
CREATE DATABASE adoptauy
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_0900_ai_ci;
USE adoptauy;

-- -----------------------------------------------------------------------------
-- 1. Usuarios
-- -----------------------------------------------------------------------------

-- Usuario: datos comunes a los tres tipos de cuenta.
-- RNE13: la contraseña se guarda como hash bcrypt (60 caracteres, empieza con $2).
CREATE TABLE Usuario (
  ID_Usuario        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  Email             VARCHAR(255) NOT NULL,
  `Contraseña`      VARCHAR(255) NOT NULL,
  Tipo_Usuario      ENUM('Particular', 'Refugio', 'Administrador') NOT NULL,
  Estado_Usuario    ENUM('Activo', 'En revisión', 'Suspendido', 'Inhabilitado', 'Baja')
                      NOT NULL DEFAULT 'Activo',
  Fecha_Alta        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Email_Verificado  DATETIME NULL COMMENT 'Fecha de confirmación del correo; NULL = sin confirmar (RF:0043)',
  CONSTRAINT pk_usuario PRIMARY KEY (ID_Usuario),
  CONSTRAINT uq_usuario_email UNIQUE (Email),
  -- Clave candidata usada por los subtipos para cumplir RNE10.
  CONSTRAINT uq_usuario_id_tipo UNIQUE (ID_Usuario, Tipo_Usuario),
  CONSTRAINT ck_usuario_email CHECK (Email LIKE '_%@_%._%'),
  CONSTRAINT ck_usuario_hash CHECK (`Contraseña` LIKE '$2_$%' AND CHAR_LENGTH(`Contraseña`) = 60)
) ENGINE = InnoDB;

-- RNE10: cada subtipo repite Tipo_Usuario con un valor fijo y lo referencia
-- junto con ID_Usuario. Así un mismo ID no puede estar en dos subtipos.
-- RNE11 (todo usuario tiene su fila de subtipo) se cumple creando ambas filas
-- en la misma transacción (Aplicación).

-- Particular. RNE23: CI única, 7 u 8 dígitos, solo números (D79), con dígito
-- verificador válido (lo controla el trigger tr_particular_ci_*).
-- Largos de nombre y apellido (2 a 50) y teléfono de 8 o 9 dígitos (D80, D81).
CREATE TABLE Particular (
  ID_Usuario    INT UNSIGNED NOT NULL,
  Tipo_Usuario  ENUM('Particular', 'Refugio', 'Administrador') NOT NULL DEFAULT 'Particular',
  CI            VARCHAR(8)  NOT NULL,
  Nombre        VARCHAR(50) NOT NULL,
  Apellido      VARCHAR(50) NOT NULL,
  Telefono      VARCHAR(9)  NOT NULL,
  CONSTRAINT pk_particular PRIMARY KEY (ID_Usuario),
  CONSTRAINT uq_particular_ci UNIQUE (CI),
  CONSTRAINT fk_particular_usuario FOREIGN KEY (ID_Usuario, Tipo_Usuario)
    REFERENCES Usuario (ID_Usuario, Tipo_Usuario) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT ck_particular_tipo CHECK (Tipo_Usuario = 'Particular'),
  CONSTRAINT ck_particular_ci CHECK (REGEXP_LIKE(CI, '^[0-9]{7,8}$')),
  CONSTRAINT ck_particular_nombre CHECK (CHAR_LENGTH(Nombre) BETWEEN 2 AND 50),
  CONSTRAINT ck_particular_apellido CHECK (CHAR_LENGTH(Apellido) BETWEEN 2 AND 50),
  CONSTRAINT ck_particular_telefono CHECK (REGEXP_LIKE(Telefono, '^[0-9]{8,9}$'))
) ENGINE = InnoDB;

CREATE TABLE Administrador (
  ID_Usuario    INT UNSIGNED NOT NULL,
  Tipo_Usuario  ENUM('Particular', 'Refugio', 'Administrador') NOT NULL DEFAULT 'Administrador',
  CONSTRAINT pk_administrador PRIMARY KEY (ID_Usuario),
  CONSTRAINT fk_administrador_usuario FOREIGN KEY (ID_Usuario, Tipo_Usuario)
    REFERENCES Usuario (ID_Usuario, Tipo_Usuario) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT ck_administrador_tipo CHECK (Tipo_Usuario = 'Administrador')
) ENGINE = InnoDB;

-- Refugio. RNE05: Motivo_Rechazo obligatorio solo si está Rechazado.
-- RNE24: estados de validación. RNE06 (resolver en 5 días hábiles) se
-- controla desde el panel del Administrador (Aplicación).
CREATE TABLE Refugio (
  ID_Usuario              INT UNSIGNED NOT NULL,
  Tipo_Usuario            ENUM('Particular', 'Refugio', 'Administrador') NOT NULL DEFAULT 'Refugio',
  Nombre_Refugio          VARCHAR(100) NOT NULL,
  Certificado_Personeria  VARCHAR(255) NOT NULL COMMENT 'Ruta del archivo subido',
  Comprobante_Domicilio   VARCHAR(255) NOT NULL COMMENT 'Ruta del archivo subido',
  Ubicacion               VARCHAR(255) NOT NULL,
  Latitud                 DECIMAL(9,6) NULL,
  Longitud                DECIMAL(9,6) NULL,
  Estado_Validacion       ENUM('Pendiente de validación', 'Validado', 'Rechazado', 'Suspendido', 'Dado de baja')
                            NOT NULL DEFAULT 'Pendiente de validación',
  Motivo_Rechazo          VARCHAR(500) NULL,
  Fecha_Solicitud         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Fecha_Resolucion        DATETIME NULL,
  URL_Foto                VARCHAR(255) NULL,
  CONSTRAINT pk_refugio PRIMARY KEY (ID_Usuario),
  CONSTRAINT fk_refugio_usuario FOREIGN KEY (ID_Usuario, Tipo_Usuario)
    REFERENCES Usuario (ID_Usuario, Tipo_Usuario) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT ck_refugio_tipo CHECK (Tipo_Usuario = 'Refugio'),
  CONSTRAINT ck_refugio_rne05 CHECK (
    (Estado_Validacion = 'Rechazado' AND Motivo_Rechazo IS NOT NULL AND CHAR_LENGTH(TRIM(Motivo_Rechazo)) > 0)
    OR (Estado_Validacion <> 'Rechazado' AND Motivo_Rechazo IS NULL)),
  CONSTRAINT ck_refugio_resolucion CHECK (Fecha_Resolucion IS NULL OR Fecha_Resolucion >= Fecha_Solicitud),
  CONSTRAINT ck_refugio_latitud CHECK (Latitud IS NULL OR Latitud BETWEEN -90 AND 90),
  CONSTRAINT ck_refugio_longitud CHECK (Longitud IS NULL OR Longitud BETWEEN -180 AND 180),
  INDEX ix_refugio_estado (Estado_Validacion)
) ENGINE = InnoDB;

-- Atributo multivaluado de Refugio.
CREATE TABLE Refugio_Datos_Contacto (
  ID_Usuario     INT UNSIGNED NOT NULL,
  Dato_Contacto  VARCHAR(255) NOT NULL,
  CONSTRAINT pk_refugio_contacto PRIMARY KEY (ID_Usuario, Dato_Contacto),
  CONSTRAINT fk_refugio_contacto_refugio FOREIGN KEY (ID_Usuario)
    REFERENCES Refugio (ID_Usuario) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE = InnoDB;

-- Cuenta bancaria del refugio. RNE12: Numero_Cuenta se guarda como texto.
-- RNE04: solo un refugio Validado puede darla de alta (trigger).
CREATE TABLE Cuenta_Bancaria (
  ID_Cuenta_Banco  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ID_Usuario       INT UNSIGNED NOT NULL,
  Banco            VARCHAR(100) NOT NULL,
  Tipo_Cuenta      VARCHAR(50)  NOT NULL,
  Numero_Cuenta    VARCHAR(34)  NOT NULL,
  Titular          VARCHAR(100) NOT NULL,
  CONSTRAINT pk_cuenta_bancaria PRIMARY KEY (ID_Cuenta_Banco),
  CONSTRAINT fk_cuenta_refugio FOREIGN KEY (ID_Usuario)
    REFERENCES Refugio (ID_Usuario) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT uq_cuenta UNIQUE (ID_Usuario, Banco, Numero_Cuenta)
) ENGINE = InnoDB;

-- -----------------------------------------------------------------------------
-- 2. Publicaciones
-- -----------------------------------------------------------------------------

-- Publicacion_Animal: guarda los dos tipos de publicación (Adopción y Perdida).
-- RNE02: cada tipo tiene sus propios estados; las transiciones las controla
--        el trigger tr_publicacion_bu.
-- RNE12: Edad_Meses no negativa (UNSIGNED) y Tamaño de lista cerrada.
-- RNE17: Fecha_Vencimiento solo en Perdida (el trigger la calcula al crear).
-- Largos de raza (2 a 50), ubicación y zona (3 a 100): D80. Contacto: D86.
-- Sin Temperamento (D87).
CREATE TABLE Publicacion_Animal (
  ID_Publicacion          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ID_Usuario              INT UNSIGNED NOT NULL,
  Tipo_Publicacion        ENUM('Adopción', 'Perdida') NOT NULL,
  Especie                 ENUM('Perro', 'Gato') NOT NULL,
  Raza                    VARCHAR(50)  NOT NULL,
  Sexo                    ENUM('Macho', 'Hembra') NOT NULL,
  Edad_Meses              SMALLINT UNSIGNED NULL,
  `Tamaño`                ENUM('Pequeño', 'Mediano', 'Grande') NULL,
  Estado_Salud            VARCHAR(255) NULL,
  Estado_Esterilizacion   ENUM('Esterilizado', 'No esterilizado') NULL,
  Ubicacion_Aproximada    VARCHAR(100) NULL,
  Descripcion             TEXT NULL COMMENT 'En Perdida guarda las características de la mascota',
  Estado_Publicacion      ENUM('En adopción', 'Adoptado', 'Archivado', 'Perdida', 'Encontrada', 'Archivada') NOT NULL,
  Estado_Moderacion       ENUM('Visible', 'En revisión', 'Suspendida') NOT NULL DEFAULT 'Visible',
  Fecha_Creacion          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Fecha_Vencimiento       DATE NULL,
  Nombre_Mascota          VARCHAR(50)  NULL,
  Fecha_Extravio          DATE NULL,
  Zona_Extravio           VARCHAR(100) NULL,
  Contacto                VARCHAR(9)   NULL COMMENT 'Teléfono de 8 o 9 dígitos (D86)',
  CONSTRAINT pk_publicacion PRIMARY KEY (ID_Publicacion),
  CONSTRAINT fk_publicacion_usuario FOREIGN KEY (ID_Usuario)
    REFERENCES Usuario (ID_Usuario) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT ck_publicacion_raza CHECK (CHAR_LENGTH(Raza) BETWEEN 2 AND 50),
  CONSTRAINT ck_publicacion_ubicacion CHECK (Ubicacion_Aproximada IS NULL OR CHAR_LENGTH(Ubicacion_Aproximada) BETWEEN 3 AND 100),
  CONSTRAINT ck_publicacion_zona CHECK (Zona_Extravio IS NULL OR CHAR_LENGTH(Zona_Extravio) BETWEEN 3 AND 100),
  CONSTRAINT ck_publicacion_contacto CHECK (Contacto IS NULL OR REGEXP_LIKE(Contacto, '^[0-9]{8,9}$')),
  CONSTRAINT ck_publicacion_extravio CHECK (Fecha_Extravio IS NULL OR Fecha_Extravio <= DATE(Fecha_Creacion)),
  -- RNE02 y RNE17: campos y estados según el tipo.
  CONSTRAINT ck_publicacion_tipo CHECK (
    (Tipo_Publicacion = 'Adopción'
      AND Estado_Publicacion IN ('En adopción', 'Adoptado', 'Archivado')
      AND Edad_Meses IS NOT NULL AND `Tamaño` IS NOT NULL
      AND Estado_Salud IS NOT NULL
      AND Estado_Esterilizacion IS NOT NULL AND Ubicacion_Aproximada IS NOT NULL
      AND Fecha_Vencimiento IS NULL AND Nombre_Mascota IS NULL
      AND Fecha_Extravio IS NULL AND Zona_Extravio IS NULL AND Contacto IS NULL)
    OR
    (Tipo_Publicacion = 'Perdida'
      AND Estado_Publicacion IN ('Perdida', 'Encontrada', 'Archivada')
      AND Nombre_Mascota IS NOT NULL AND Fecha_Extravio IS NOT NULL
      AND Zona_Extravio IS NOT NULL AND Contacto IS NOT NULL
      AND Estado_Salud IS NULL AND Estado_Esterilizacion IS NULL)),
  INDEX ix_publicacion_listado (Tipo_Publicacion, Estado_Publicacion, Estado_Moderacion, Fecha_Creacion),
  INDEX ix_publicacion_filtros (Especie, Sexo, Edad_Meses),
  INDEX ix_publicacion_vencimiento (Tipo_Publicacion, Estado_Publicacion, Fecha_Vencimiento)
) ENGINE = InnoDB;

-- Foto_Animal. RNE01: formato JPG, PNG o WEBP (por extensión) y un máximo de
-- 6 fotos en Adopción y 3 en Perdida (trigger). El mínimo de 1 foto y el
-- tamaño de 8 MB se controlan al subir los archivos (Aplicación).
CREATE TABLE Foto_Animal (
  ID_Foto         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ID_Publicacion  INT UNSIGNED NOT NULL,
  URL_Foto        VARCHAR(255) NOT NULL,
  CONSTRAINT pk_foto PRIMARY KEY (ID_Foto),
  CONSTRAINT fk_foto_publicacion FOREIGN KEY (ID_Publicacion)
    REFERENCES Publicacion_Animal (ID_Publicacion) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT ck_foto_formato CHECK (REGEXP_LIKE(URL_Foto, '\\.(jpe?g|png|webp)$', 'i'))
) ENGINE = InnoDB;

-- -----------------------------------------------------------------------------
-- 3. Adopción y seguimiento
-- -----------------------------------------------------------------------------

-- Solicitud_Adopcion. RNE18 (trigger): solo sobre una publicación de tipo
-- Adopción En adopción, y no sobre la propia. Un mismo usuario no puede tener
-- dos solicitudes Pendientes sobre la misma publicación (columna generada).
CREATE TABLE Solicitud_Adopcion (
  ID_Solicitud      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ID_Publicacion    INT UNSIGNED NOT NULL,
  ID_Usuario        INT UNSIGNED NOT NULL,
  Fecha_Solicitud   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Estado_Solicitud  ENUM('Pendiente', 'Adoptada', 'Rechazada') NOT NULL DEFAULT 'Pendiente',
  Pendiente_Clave   INT UNSIGNED GENERATED ALWAYS AS
                      (IF(Estado_Solicitud = 'Pendiente', ID_Publicacion, NULL)) STORED,
  CONSTRAINT pk_solicitud PRIMARY KEY (ID_Solicitud),
  CONSTRAINT fk_solicitud_publicacion FOREIGN KEY (ID_Publicacion)
    REFERENCES Publicacion_Animal (ID_Publicacion) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_solicitud_usuario FOREIGN KEY (ID_Usuario)
    REFERENCES Usuario (ID_Usuario) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT uq_solicitud_pendiente UNIQUE (ID_Usuario, Pendiente_Clave),
  INDEX ix_solicitud_publicacion (ID_Publicacion, Estado_Solicitud)
) ENGINE = InnoDB;

-- Adopcion: una por solicitud Adoptada (RNE19).
CREATE TABLE Adopcion (
  ID_Adopcion     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ID_Solicitud    INT UNSIGNED NOT NULL,
  Fecha_Adopcion  DATE NOT NULL DEFAULT (CURRENT_DATE),
  CONSTRAINT pk_adopcion PRIMARY KEY (ID_Adopcion),
  CONSTRAINT uq_adopcion_solicitud UNIQUE (ID_Solicitud),
  CONSTRAINT fk_adopcion_solicitud FOREIGN KEY (ID_Solicitud)
    REFERENCES Solicitud_Adopcion (ID_Solicitud) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE = InnoDB;

-- Seguimiento: entidad débil de Adopcion, uno por adopción.
-- RNE20: Fecha_Aviso = Fecha_Adopcion + 15 días (trigger) y
--        Fecha_Limite = Fecha_Aviso + 7 días (CHECK).
CREATE TABLE Seguimiento (
  ID_Adopcion       INT UNSIGNED NOT NULL,
  Fecha_Aviso       DATE NOT NULL,
  Fecha_Limite      DATE NOT NULL,
  URL_Foto          VARCHAR(255) NULL,
  Fecha_Envio       DATETIME NULL,
  Estado_Evidencia  ENUM('Pendiente', 'Enviada', 'Aprobada', 'Rechazada') NOT NULL DEFAULT 'Pendiente',
  CONSTRAINT pk_seguimiento PRIMARY KEY (ID_Adopcion),
  CONSTRAINT fk_seguimiento_adopcion FOREIGN KEY (ID_Adopcion)
    REFERENCES Adopcion (ID_Adopcion) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT ck_seguimiento_limite CHECK (Fecha_Limite = Fecha_Aviso + INTERVAL 7 DAY),
  CONSTRAINT ck_seguimiento_evidencia CHECK (
    Estado_Evidencia = 'Pendiente' OR (URL_Foto IS NOT NULL AND Fecha_Envio IS NOT NULL)),
  CONSTRAINT ck_seguimiento_foto CHECK (URL_Foto IS NULL OR REGEXP_LIKE(URL_Foto, '\\.(jpe?g|png|webp)$', 'i')),
  INDEX ix_seguimiento_limite (Estado_Evidencia, Fecha_Limite)
) ENGINE = InnoDB;

-- -----------------------------------------------------------------------------
-- 4. Reportes
-- -----------------------------------------------------------------------------

-- Reporte. RNE22: se refiere a exactamente un usuario o una publicación, el
-- reportante no se reporta a sí mismo y no reporta dos veces lo mismo.
-- Los motivos posibles son los de la tabla de 13.2.5 (los valida la Aplicación,
-- porque dependen del tipo de objeto reportado).
CREATE TABLE Reporte (
  ID_Reporte                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ID_Usuario_Reportante     INT UNSIGNED NOT NULL,
  ID_Usuario_Reportado      INT UNSIGNED NULL,
  ID_Publicacion_Reportada  INT UNSIGNED NULL,
  Motivo                    VARCHAR(255) NOT NULL,
  Fecha_Reporte             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Estado_Reporte            ENUM('Pendiente', 'Desestimado', 'Confirmado') NOT NULL DEFAULT 'Pendiente',
  Medida                    ENUM('Aviso', 'Suspensión', 'Baja lógica') NULL,
  Objeto_Clave              VARCHAR(20) GENERATED ALWAYS AS (
                              IF(ID_Usuario_Reportado IS NOT NULL,
                                 CONCAT('U', ID_Usuario_Reportado),
                                 CONCAT('P', ID_Publicacion_Reportada))) STORED,
  CONSTRAINT pk_reporte PRIMARY KEY (ID_Reporte),
  CONSTRAINT fk_reporte_reportante FOREIGN KEY (ID_Usuario_Reportante)
    REFERENCES Usuario (ID_Usuario) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_reporte_reportado FOREIGN KEY (ID_Usuario_Reportado)
    REFERENCES Usuario (ID_Usuario) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_reporte_publicacion FOREIGN KEY (ID_Publicacion_Reportada)
    REFERENCES Publicacion_Animal (ID_Publicacion) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT uq_reporte_unico UNIQUE (ID_Usuario_Reportante, Objeto_Clave),
  CONSTRAINT ck_reporte_objeto CHECK ((ID_Usuario_Reportado IS NULL) <> (ID_Publicacion_Reportada IS NULL)),
  CONSTRAINT ck_reporte_propio CHECK (ID_Usuario_Reportado IS NULL OR ID_Usuario_Reportado <> ID_Usuario_Reportante),
  CONSTRAINT ck_reporte_medida CHECK (Medida IS NULL OR Estado_Reporte = 'Confirmado'),
  INDEX ix_reporte_reportado (ID_Usuario_Reportado, Estado_Reporte),
  INDEX ix_reporte_publicacion (ID_Publicacion_Reportada, Estado_Reporte)
) ENGINE = InnoDB;

-- -----------------------------------------------------------------------------
-- 5. Funciones
-- -----------------------------------------------------------------------------

DELIMITER $$

-- RNE23: dígito verificador de la cédula uruguaya.
-- Se completan con ceros a la izquierda los primeros 7 dígitos y se aplican
-- los pesos 2, 9, 8, 7, 6, 3 y 4; el verificador es (10 - suma mod 10) mod 10.
CREATE FUNCTION fn_ci_valida(p_ci VARCHAR(8))
RETURNS BOOLEAN
DETERMINISTIC
NO SQL
BEGIN
  DECLARE v_base CHAR(7);
  DECLARE v_suma INT DEFAULT 0;
  DECLARE i INT DEFAULT 1;
  IF p_ci IS NULL OR NOT REGEXP_LIKE(p_ci, '^[0-9]{7,8}$') THEN
    RETURN FALSE;
  END IF;
  SET v_base = LPAD(LEFT(p_ci, CHAR_LENGTH(p_ci) - 1), 7, '0');
  WHILE i <= 7 DO
    SET v_suma = v_suma + CAST(SUBSTRING(v_base, i, 1) AS UNSIGNED)
                        * CAST(SUBSTRING('2987634', i, 1) AS UNSIGNED);
    SET i = i + 1;
  END WHILE;
  RETURN (10 - MOD(v_suma, 10)) MOD 10 = CAST(RIGHT(p_ci, 1) AS UNSIGNED);
END$$

-- RNE21: un usuario queda restringido si tiene un seguimiento vencido sin
-- evidencia enviada, o con la evidencia rechazada y sin reenviar.
CREATE FUNCTION fn_usuario_restringido(p_usuario INT UNSIGNED)
RETURNS BOOLEAN
READS SQL DATA
BEGIN
  RETURN EXISTS (
    SELECT 1
      FROM Seguimiento sg
      JOIN Adopcion a            ON a.ID_Adopcion = sg.ID_Adopcion
      JOIN Solicitud_Adopcion s  ON s.ID_Solicitud = a.ID_Solicitud
     WHERE s.ID_Usuario = p_usuario
       AND sg.Fecha_Limite < CURRENT_DATE
       AND sg.Estado_Evidencia IN ('Pendiente', 'Rechazada'));
END$$

-- -----------------------------------------------------------------------------
-- 6. Triggers
-- -----------------------------------------------------------------------------

-- RNE23: dígito verificador de la cédula.
CREATE TRIGGER tr_particular_ci_bi BEFORE INSERT ON Particular
FOR EACH ROW
BEGIN
  IF NOT fn_ci_valida(NEW.CI) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RNE23: la cédula no tiene un dígito verificador válido';
  END IF;
END$$

CREATE TRIGGER tr_particular_ci_bu BEFORE UPDATE ON Particular
FOR EACH ROW
BEGIN
  IF NEW.CI <> OLD.CI AND NOT fn_ci_valida(NEW.CI) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RNE23: la cédula no tiene un dígito verificador válido';
  END IF;
END$$

-- RNE24: transiciones válidas del refugio (13.2.1).
CREATE TRIGGER tr_refugio_bu BEFORE UPDATE ON Refugio
FOR EACH ROW
BEGIN
  IF NEW.Estado_Validacion <> OLD.Estado_Validacion THEN
    IF NOT (
         (OLD.Estado_Validacion = 'Pendiente de validación' AND NEW.Estado_Validacion IN ('Validado', 'Rechazado'))
      OR (OLD.Estado_Validacion = 'Validado'   AND NEW.Estado_Validacion IN ('Suspendido', 'Dado de baja'))
      OR (OLD.Estado_Validacion = 'Suspendido' AND NEW.Estado_Validacion IN ('Validado', 'Dado de baja'))) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RNE24: transición de estado de refugio no permitida';
    END IF;
    IF OLD.Estado_Validacion = 'Pendiente de validación' THEN
      SET NEW.Fecha_Resolucion = CURRENT_TIMESTAMP;
    END IF;
  END IF;
END$$

-- RNE24: un refugio Dado de baja deja sus publicaciones activas archivadas.
-- (Las publicaciones Adoptado y Encontrada son estados finales y no cambian.)
CREATE TRIGGER tr_refugio_au AFTER UPDATE ON Refugio
FOR EACH ROW
BEGIN
  IF NEW.Estado_Validacion = 'Dado de baja' AND OLD.Estado_Validacion <> 'Dado de baja' THEN
    UPDATE Publicacion_Animal
       SET Estado_Publicacion = IF(Tipo_Publicacion = 'Adopción', 'Archivado', 'Archivada')
     WHERE ID_Usuario = NEW.ID_Usuario
       AND Estado_Publicacion IN ('En adopción', 'Archivado', 'Perdida', 'Archivada');
  END IF;
END$$

-- RNE04: solo un refugio Validado da de alta cuentas bancarias.
CREATE TRIGGER tr_cuenta_bi BEFORE INSERT ON Cuenta_Bancaria
FOR EACH ROW
BEGIN
  IF (SELECT Estado_Validacion FROM Refugio WHERE ID_Usuario = NEW.ID_Usuario) <> 'Validado' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RNE04: solo un refugio validado puede publicar cuentas bancarias';
  END IF;
END$$

-- Alta de publicación: RNE04, RNE14, RNE16, RNE17 y RNE21.
CREATE TRIGGER tr_publicacion_bi BEFORE INSERT ON Publicacion_Animal
FOR EACH ROW
BEGIN
  DECLARE v_tipo   VARCHAR(20);
  DECLARE v_estado VARCHAR(20);
  DECLARE v_alta   DATETIME;

  SELECT Tipo_Usuario, Estado_Usuario, Fecha_Alta
    INTO v_tipo, v_estado, v_alta
    FROM Usuario WHERE ID_Usuario = NEW.ID_Usuario;

  IF v_tipo = 'Administrador' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El Administrador no crea publicaciones';
  END IF;
  IF v_estado <> 'Activo' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cuenta no está activa';
  END IF;
  -- RNE04 y RNE16
  IF v_tipo = 'Refugio'
     AND (SELECT Estado_Validacion FROM Refugio WHERE ID_Usuario = NEW.ID_Usuario) <> 'Validado' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RNE04: solo un refugio validado puede publicar';
  END IF;
  -- RNE21
  IF fn_usuario_restringido(NEW.ID_Usuario) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RNE21: la cuenta está restringida por un seguimiento pendiente';
  END IF;
  -- RNE14
  IF NEW.Fecha_Creacion < v_alta OR NEW.Fecha_Creacion > CURRENT_TIMESTAMP THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RNE14: fecha de creación fuera de rango';
  END IF;
  -- RNE02: estado inicial según el tipo
  IF NEW.Tipo_Publicacion = 'Adopción' THEN
    SET NEW.Estado_Publicacion = 'En adopción';
  ELSE
    SET NEW.Estado_Publicacion = 'Perdida';
    -- RNE17: vence 30 días después de la creación
    SET NEW.Fecha_Vencimiento = DATE(NEW.Fecha_Creacion) + INTERVAL 30 DAY;
  END IF;
END$$

-- RNE02: transiciones válidas de la publicación (13.2.2) y tipo inmutable.
-- Una renovación de una mascota perdida (Perdida → Perdida o Archivada →
-- Perdida) debe actualizar Fecha_Vencimiento a la fecha de renovación + 30 días.
CREATE TRIGGER tr_publicacion_bu BEFORE UPDATE ON Publicacion_Animal
FOR EACH ROW
BEGIN
  IF NEW.Tipo_Publicacion <> OLD.Tipo_Publicacion OR NEW.ID_Usuario <> OLD.ID_Usuario THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El tipo y el publicante de una publicación no cambian';
  END IF;
  IF NEW.Estado_Publicacion <> OLD.Estado_Publicacion THEN
    IF NOT (
         (OLD.Estado_Publicacion = 'En adopción' AND NEW.Estado_Publicacion IN ('Adoptado', 'Archivado'))
      OR (OLD.Estado_Publicacion = 'Archivado'   AND NEW.Estado_Publicacion = 'En adopción')
      OR (OLD.Estado_Publicacion = 'Perdida'     AND NEW.Estado_Publicacion IN ('Encontrada', 'Archivada'))
      OR (OLD.Estado_Publicacion = 'Archivada'   AND NEW.Estado_Publicacion = 'Perdida')) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RNE02: transición de estado de publicación no permitida';
    END IF;
  END IF;
  IF NEW.Tipo_Publicacion = 'Perdida' AND NEW.Fecha_Vencimiento IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RNE17: una mascota perdida necesita fecha de vencimiento';
  END IF;
END$$

-- RNE15: un Particular con 3 publicaciones suspendidas pasa a revisión.
CREATE TRIGGER tr_publicacion_au AFTER UPDATE ON Publicacion_Animal
FOR EACH ROW
BEGIN
  IF NEW.Estado_Moderacion = 'Suspendida' AND OLD.Estado_Moderacion <> 'Suspendida' THEN
    IF (SELECT COUNT(*) FROM Publicacion_Animal
         WHERE ID_Usuario = NEW.ID_Usuario AND Estado_Moderacion = 'Suspendida') >= 3 THEN
      UPDATE Usuario
         SET Estado_Usuario = 'En revisión'
       WHERE ID_Usuario = NEW.ID_Usuario
         AND Tipo_Usuario = 'Particular'
         AND Estado_Usuario = 'Activo';
    END IF;
  END IF;
END$$

-- RNE01: máximo de fotos por tipo de publicación.
CREATE TRIGGER tr_foto_bi BEFORE INSERT ON Foto_Animal
FOR EACH ROW
BEGIN
  DECLARE v_tipo VARCHAR(10);
  DECLARE v_cant INT;
  SELECT Tipo_Publicacion INTO v_tipo FROM Publicacion_Animal WHERE ID_Publicacion = NEW.ID_Publicacion;
  SELECT COUNT(*) INTO v_cant FROM Foto_Animal WHERE ID_Publicacion = NEW.ID_Publicacion;
  IF (v_tipo = 'Adopción' AND v_cant >= 6) OR (v_tipo = 'Perdida' AND v_cant >= 3) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RNE01: se superó la cantidad máxima de fotos';
  END IF;
END$$

-- RNE18 y RNE21: quién puede solicitar una adopción.
CREATE TRIGGER tr_solicitud_bi BEFORE INSERT ON Solicitud_Adopcion
FOR EACH ROW
BEGIN
  DECLARE v_tipo_pub   VARCHAR(10);
  DECLARE v_estado_pub VARCHAR(20);
  DECLARE v_publicante INT UNSIGNED;
  SELECT Tipo_Publicacion, Estado_Publicacion, ID_Usuario
    INTO v_tipo_pub, v_estado_pub, v_publicante
    FROM Publicacion_Animal WHERE ID_Publicacion = NEW.ID_Publicacion;

  IF v_tipo_pub <> 'Adopción' OR v_estado_pub <> 'En adopción' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RNE18: la publicación no está En adopción';
  END IF;
  IF v_publicante = NEW.ID_Usuario THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RNE18: no se puede solicitar la adopción de una publicación propia';
  END IF;
  IF (SELECT Tipo_Usuario FROM Usuario WHERE ID_Usuario = NEW.ID_Usuario) <> 'Particular'
     OR (SELECT Estado_Usuario FROM Usuario WHERE ID_Usuario = NEW.ID_Usuario) <> 'Activo' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo un Particular activo puede solicitar una adopción';
  END IF;
  IF fn_usuario_restringido(NEW.ID_Usuario) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RNE21: la cuenta está restringida por un seguimiento pendiente';
  END IF;
  SET NEW.Estado_Solicitud = 'Pendiente';
END$$

-- Estados de la solicitud (13.2.3): Adoptada y Rechazada son finales.
CREATE TRIGGER tr_solicitud_bu BEFORE UPDATE ON Solicitud_Adopcion
FOR EACH ROW
BEGIN
  IF NEW.Estado_Solicitud <> OLD.Estado_Solicitud AND OLD.Estado_Solicitud <> 'Pendiente' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Una solicitud Adoptada o Rechazada no cambia de estado';
  END IF;
END$$

-- RNE20: el aviso es a los 15 días de la adopción.
CREATE TRIGGER tr_seguimiento_bi BEFORE INSERT ON Seguimiento
FOR EACH ROW
BEGIN
  SET NEW.Fecha_Aviso  = (SELECT Fecha_Adopcion FROM Adopcion WHERE ID_Adopcion = NEW.ID_Adopcion) + INTERVAL 15 DAY;
  SET NEW.Fecha_Limite = NEW.Fecha_Aviso + INTERVAL 7 DAY;
END$$

-- RNE15: medidas automáticas al alcanzar los umbrales de reportes.
-- Solo cuentan reportes de usuarios distintos y que no fueron desestimados.
CREATE TRIGGER tr_reporte_ai AFTER INSERT ON Reporte
FOR EACH ROW
BEGIN
  DECLARE v_cant INT;
  DECLARE v_tipo VARCHAR(20);

  IF NEW.ID_Publicacion_Reportada IS NOT NULL THEN
    SELECT COUNT(DISTINCT ID_Usuario_Reportante) INTO v_cant
      FROM Reporte
     WHERE ID_Publicacion_Reportada = NEW.ID_Publicacion_Reportada
       AND Estado_Reporte <> 'Desestimado';
    IF v_cant >= 3 THEN
      UPDATE Publicacion_Animal
         SET Estado_Moderacion = 'En revisión'
       WHERE ID_Publicacion = NEW.ID_Publicacion_Reportada
         AND Estado_Moderacion = 'Visible';
    END IF;
  ELSE
    SELECT COUNT(DISTINCT ID_Usuario_Reportante) INTO v_cant
      FROM Reporte
     WHERE ID_Usuario_Reportado = NEW.ID_Usuario_Reportado
       AND Estado_Reporte <> 'Desestimado';
    SELECT Tipo_Usuario INTO v_tipo FROM Usuario WHERE ID_Usuario = NEW.ID_Usuario_Reportado;

    IF v_tipo = 'Particular' THEN
      IF v_cant >= 10 THEN
        UPDATE Usuario SET Estado_Usuario = 'Inhabilitado'
         WHERE ID_Usuario = NEW.ID_Usuario_Reportado AND Estado_Usuario IN ('Activo', 'En revisión');
      ELSEIF v_cant >= 5 THEN
        UPDATE Usuario SET Estado_Usuario = 'En revisión'
         WHERE ID_Usuario = NEW.ID_Usuario_Reportado AND Estado_Usuario = 'Activo';
      END IF;
    ELSEIF v_tipo = 'Refugio' AND v_cant >= 3 THEN
      UPDATE Refugio SET Estado_Validacion = 'Suspendido'
       WHERE ID_Usuario = NEW.ID_Usuario_Reportado AND Estado_Validacion = 'Validado';
    END IF;
  END IF;
END$$

-- -----------------------------------------------------------------------------
-- 7. Procedimientos
-- -----------------------------------------------------------------------------

-- RNE19 y RNE20: concretar una adopción en una sola transacción.
-- La solicitud elegida pasa a Adoptada, las demás Pendientes a Rechazada,
-- la publicación a Adoptado, y se crean la adopción y su seguimiento.
CREATE PROCEDURE sp_concretar_adopcion(IN p_solicitud INT UNSIGNED, IN p_publicante INT UNSIGNED)
BEGIN
  DECLARE v_publicacion INT UNSIGNED;
  DECLARE v_dueno       INT UNSIGNED;
  DECLARE v_estado      VARCHAR(20);
  DECLARE v_adopcion    INT UNSIGNED;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  SELECT s.ID_Publicacion, p.ID_Usuario, s.Estado_Solicitud
    INTO v_publicacion, v_dueno, v_estado
    FROM Solicitud_Adopcion s
    JOIN Publicacion_Animal p ON p.ID_Publicacion = s.ID_Publicacion
   WHERE s.ID_Solicitud = p_solicitud
     FOR UPDATE;

  IF v_dueno IS NULL OR v_dueno <> p_publicante THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RNE03: solo el publicante concreta la adopción';
  END IF;
  IF v_estado <> 'Pendiente' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La solicitud elegida no está Pendiente';
  END IF;

  UPDATE Solicitud_Adopcion SET Estado_Solicitud = 'Adoptada'  WHERE ID_Solicitud = p_solicitud;
  UPDATE Solicitud_Adopcion SET Estado_Solicitud = 'Rechazada'
   WHERE ID_Publicacion = v_publicacion AND ID_Solicitud <> p_solicitud AND Estado_Solicitud = 'Pendiente';
  UPDATE Publicacion_Animal SET Estado_Publicacion = 'Adoptado' WHERE ID_Publicacion = v_publicacion;

  INSERT INTO Adopcion (ID_Solicitud) VALUES (p_solicitud);
  SET v_adopcion = LAST_INSERT_ID();
  INSERT INTO Seguimiento (ID_Adopcion, Fecha_Aviso, Fecha_Limite) VALUES (v_adopcion, CURRENT_DATE, CURRENT_DATE);

  COMMIT;
END$$

-- RNE17 y D70: renovar una mascota perdida (Perdida o Archivada).
CREATE PROCEDURE sp_renovar_perdida(IN p_publicacion INT UNSIGNED, IN p_publicante INT UNSIGNED)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM Publicacion_Animal
                  WHERE ID_Publicacion = p_publicacion AND ID_Usuario = p_publicante
                    AND Tipo_Publicacion = 'Perdida' AND Estado_Publicacion IN ('Perdida', 'Archivada')) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo el publicante renueva una mascota Perdida o Archivada';
  END IF;
  UPDATE Publicacion_Animal
     SET Estado_Publicacion = 'Perdida',
         Fecha_Vencimiento  = CURRENT_DATE + INTERVAL 30 DAY
   WHERE ID_Publicacion = p_publicacion;
END$$

-- Tarea diaria de las 03:00 (D71): archiva las mascotas perdidas vencidas.
-- La ejecuta el planificador de Laravel; también se puede llamar a mano.
CREATE PROCEDURE sp_archivar_perdidas_vencidas()
BEGIN
  UPDATE Publicacion_Animal
     SET Estado_Publicacion = 'Archivada'
   WHERE Tipo_Publicacion = 'Perdida'
     AND Estado_Publicacion = 'Perdida'
     AND Fecha_Vencimiento <= CURRENT_DATE;
  SELECT ROW_COUNT() AS Publicaciones_Archivadas;
END$$

DELIMITER ;

-- -----------------------------------------------------------------------------
-- 8. Vistas para la consulta pública (RF:0040, RF:0048)
-- -----------------------------------------------------------------------------

-- Mascotas en adopción visibles. "Cachorro" = menos de 12 meses (D63).
CREATE VIEW v_publicaciones_en_adopcion AS
SELECT p.ID_Publicacion, p.Especie, p.Raza, p.Sexo, p.Edad_Meses,
       (p.Edad_Meses < 12) AS Es_Cachorro,
       p.`Tamaño`, p.Estado_Salud, p.Estado_Esterilizacion,
       p.Ubicacion_Aproximada, p.Descripcion, p.Fecha_Creacion, p.ID_Usuario
  FROM Publicacion_Animal p
 WHERE p.Tipo_Publicacion = 'Adopción'
   AND p.Estado_Publicacion = 'En adopción'
   AND p.Estado_Moderacion = 'Visible';

-- Mascotas perdidas visibles y vigentes. El contacto es público (13.2.2).
CREATE VIEW v_mascotas_perdidas AS
SELECT p.ID_Publicacion, p.Nombre_Mascota, p.Especie, p.Raza, p.Sexo,
       p.Descripcion AS Caracteristicas, p.Fecha_Extravio, p.Zona_Extravio,
       p.Contacto, p.Fecha_Creacion, p.Fecha_Vencimiento
  FROM Publicacion_Animal p
 WHERE p.Tipo_Publicacion = 'Perdida'
   AND p.Estado_Publicacion = 'Perdida'
   AND p.Estado_Moderacion = 'Visible';

-- Refugios validados para el mapa y el listado (RF:0038). Sin datos privados.
CREATE VIEW v_refugios_publicos AS
SELECT r.ID_Usuario, r.Nombre_Refugio, r.Ubicacion, r.Latitud, r.Longitud, r.URL_Foto
  FROM Refugio r
 WHERE r.Estado_Validacion = 'Validado';

-- =============================================================================
-- Fin del script
-- =============================================================================
