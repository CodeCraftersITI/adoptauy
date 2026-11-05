-- =============================================================================
--  AdoptaUY · Creación de la base de datos
--  Equipo CodeCrafters · TC4, ITI CETP-UTU
--
--  Crea la misma base que las migraciones de Laravel (src/laravel/database/
--  migrations) y sigue el pasaje a tablas y el diccionario de datos del
--  documento integrador. Publicacion_Animal es el supertipo y
--  Publicacion_Adopcion y Publicacion_Perdida son sus subtipos.
--
--  Especialización: cada subtipo repite el tipo de su supertipo (Tipo_Usuario o
--  Tipo_Publicacion) con un CHECK que fija su valor, y lo usa en una clave
--  foránea compuesta. Así una fila no puede estar en dos subtipos a la vez
--  (RNE10, RNE25).
--
--  Cuenta_Bancaria, Solicitud_Adopcion, Adopcion, Seguimiento y Reporte son de
--  los Sprints 5 y 6 y todavía no tienen migración: cuando se implementen, la
--  migración tiene que crear exactamente lo que está acá.
--
--  Las RNE que dependen de otras filas o de la fecha actual (por ejemplo, el
--  límite de fotos o la restricción por seguimiento) las valida Laravel.
--
--  Uso:  mysql -u root -p < adoptauy.sql
-- =============================================================================

-- Necesario para los nombres con tilde o ñ (Contraseña, Tamaño)
SET NAMES utf8mb4;

DROP DATABASE IF EXISTS adoptauy;
CREATE DATABASE adoptauy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE adoptauy;

-- -----------------------------------------------------------------------------
-- Usuarios
-- -----------------------------------------------------------------------------

-- Supertipo de Particular, Refugio y Administrador
CREATE TABLE Usuario (
  ID_Usuario        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  Email             VARCHAR(255) NOT NULL,
  Contraseña        VARCHAR(255) NOT NULL,              -- hash bcrypt (RNE13)
  Tipo_Usuario      ENUM('Particular','Refugio','Administrador') NOT NULL,
  Estado_Usuario    ENUM('Activo','En revisión','Suspendido','Inhabilitado','Baja')
                    NOT NULL DEFAULT 'Activo',
  Fecha_Alta        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Email_Verificado  DATETIME NULL,                      -- fecha de confirmación del correo; NULL = sin confirmar (RF:0043)
  PRIMARY KEY (ID_Usuario),
  UNIQUE KEY uq_usuario_email (Email),
  UNIQUE KEY uq_usuario_id_tipo (ID_Usuario, Tipo_Usuario),   -- lo referencian los subtipos (RNE10)
  CONSTRAINT ck_usuario_email CHECK (Email LIKE '_%@_%._%'),
  CONSTRAINT ck_usuario_hash  CHECK (`Contraseña` LIKE '$2_$%' AND CHAR_LENGTH(`Contraseña`) = 60)
) ENGINE=InnoDB;

CREATE TABLE Particular (
  ID_Usuario    INT UNSIGNED NOT NULL,
  Tipo_Usuario  ENUM('Particular','Refugio','Administrador') NOT NULL DEFAULT 'Particular',
  CI            VARCHAR(8)  NOT NULL,                   -- 7 u 8 dígitos, sin puntos ni guion (RNE23)
  Nombre        VARCHAR(50) NOT NULL,
  Apellido      VARCHAR(50) NOT NULL,
  Telefono      VARCHAR(9)  NOT NULL,                   -- 8 dígitos (fijo) o 9 (celular)
  PRIMARY KEY (ID_Usuario),
  UNIQUE KEY uq_particular_ci (CI),
  CONSTRAINT fk_particular_usuario FOREIGN KEY (ID_Usuario, Tipo_Usuario)
    REFERENCES Usuario (ID_Usuario, Tipo_Usuario),
  CONSTRAINT ck_particular_tipo     CHECK (Tipo_Usuario = 'Particular'),
  CONSTRAINT ck_particular_ci       CHECK (REGEXP_LIKE(CI, '^[0-9]{7,8}$')),
  CONSTRAINT ck_particular_nombre   CHECK (CHAR_LENGTH(Nombre) BETWEEN 2 AND 50),
  CONSTRAINT ck_particular_apellido CHECK (CHAR_LENGTH(Apellido) BETWEEN 2 AND 50),
  CONSTRAINT ck_particular_telefono CHECK (REGEXP_LIKE(Telefono, '^[0-9]{8,9}$'))
) ENGINE=InnoDB;

CREATE TABLE Administrador (
  ID_Usuario    INT UNSIGNED NOT NULL,
  Tipo_Usuario  ENUM('Particular','Refugio','Administrador') NOT NULL DEFAULT 'Administrador',
  PRIMARY KEY (ID_Usuario),
  CONSTRAINT fk_administrador_usuario FOREIGN KEY (ID_Usuario, Tipo_Usuario)
    REFERENCES Usuario (ID_Usuario, Tipo_Usuario),
  CONSTRAINT ck_administrador_tipo CHECK (Tipo_Usuario = 'Administrador')
) ENGINE=InnoDB;

CREATE TABLE Refugio (
  ID_Usuario              INT UNSIGNED NOT NULL,
  Tipo_Usuario            ENUM('Particular','Refugio','Administrador') NOT NULL DEFAULT 'Refugio',
  Nombre_Refugio          VARCHAR(100) NOT NULL,
  Certificado_Personeria  VARCHAR(255) NOT NULL,        -- ruta del archivo
  Comprobante_Domicilio   VARCHAR(255) NOT NULL,        -- ruta del archivo
  Ubicacion               VARCHAR(255) NOT NULL,
  Latitud                 DECIMAL(9,6) NULL,
  Longitud                DECIMAL(9,6) NULL,
  Estado_Validacion       ENUM('Pendiente de validación','Validado','Rechazado','Suspendido','Dado de baja')
                          NOT NULL DEFAULT 'Pendiente de validación',   -- RNE24
  Motivo_Rechazo          VARCHAR(500) NULL,
  Fecha_Solicitud         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Fecha_Resolucion        DATETIME NULL,
  URL_Foto                VARCHAR(255) NULL,
  PRIMARY KEY (ID_Usuario),
  KEY ix_refugio_estado (Estado_Validacion),
  CONSTRAINT fk_refugio_usuario FOREIGN KEY (ID_Usuario, Tipo_Usuario)
    REFERENCES Usuario (ID_Usuario, Tipo_Usuario),
  CONSTRAINT ck_refugio_tipo CHECK (Tipo_Usuario = 'Refugio'),
  CONSTRAINT ck_refugio_rne05 CHECK (
    (Estado_Validacion = 'Rechazado' AND Motivo_Rechazo IS NOT NULL AND CHAR_LENGTH(TRIM(Motivo_Rechazo)) > 0)
    OR (Estado_Validacion <> 'Rechazado' AND Motivo_Rechazo IS NULL)),
  CONSTRAINT ck_refugio_resolucion CHECK (Fecha_Resolucion IS NULL OR Fecha_Resolucion >= Fecha_Solicitud),
  CONSTRAINT ck_refugio_latitud    CHECK (Latitud IS NULL OR Latitud BETWEEN -90 AND 90),
  CONSTRAINT ck_refugio_longitud   CHECK (Longitud IS NULL OR Longitud BETWEEN -180 AND 180)
) ENGINE=InnoDB;

-- Atributo multivaluado de Refugio
CREATE TABLE Refugio_Datos_Contacto (
  ID_Usuario     INT UNSIGNED NOT NULL,
  Dato_Contacto  VARCHAR(255) NOT NULL,
  PRIMARY KEY (ID_Usuario, Dato_Contacto),
  CONSTRAINT fk_refugio_contacto_refugio FOREIGN KEY (ID_Usuario) REFERENCES Refugio (ID_Usuario)
) ENGINE=InnoDB;

-- Entidad débil de Refugio (Sprint 6, sin migración todavía)
CREATE TABLE Cuenta_Bancaria (
  ID_Usuario       INT UNSIGNED NOT NULL,
  ID_Cuenta_Banco  SMALLINT UNSIGNED NOT NULL,          -- se numera dentro de cada refugio
  Banco            VARCHAR(60)  NOT NULL,
  Tipo_Cuenta      VARCHAR(60)  NOT NULL,               -- texto libre
  Numero_Cuenta    VARCHAR(30)  NOT NULL,               -- texto: conserva ceros y guiones (RNE12)
  Titular          VARCHAR(120) NOT NULL,
  PRIMARY KEY (ID_Usuario, ID_Cuenta_Banco),
  CONSTRAINT fk_cuenta_refugio FOREIGN KEY (ID_Usuario) REFERENCES Refugio (ID_Usuario)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- Publicaciones
-- -----------------------------------------------------------------------------

-- Supertipo: datos comunes de toda publicación
CREATE TABLE Publicacion_Animal (
  ID_Publicacion     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ID_Usuario         INT UNSIGNED NOT NULL,
  Tipo_Publicacion   ENUM('Adopción','Perdida') NOT NULL,   -- las mascotas encontradas también son Perdida (RNE25)
  Especie            ENUM('Perro','Gato') NOT NULL,
  Raza               VARCHAR(50) NOT NULL,              -- «mestizo» si no tiene raza definida
  Sexo               ENUM('Macho','Hembra') NOT NULL,
  Estado_Moderacion  ENUM('Visible','En revisión','Suspendida') NOT NULL DEFAULT 'Visible',
  Fecha_Creacion     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (ID_Publicacion),
  UNIQUE KEY uq_publicacion_id_tipo (ID_Publicacion, Tipo_Publicacion),   -- lo referencian los subtipos
  CONSTRAINT fk_publicacion_usuario FOREIGN KEY (ID_Usuario) REFERENCES Usuario (ID_Usuario),
  CONSTRAINT ck_publicacion_raza CHECK (CHAR_LENGTH(TRIM(Raza)) > 0)
) ENGINE=InnoDB;

-- Subtipo: mascota en adopción
CREATE TABLE Publicacion_Adopcion (
  ID_Publicacion         INT UNSIGNED NOT NULL,
  Tipo_Publicacion       ENUM('Adopción','Perdida') NOT NULL DEFAULT 'Adopción',
  Edad_Meses             SMALLINT UNSIGNED NOT NULL,    -- edad estimada al publicar; < 12 es cachorro
  Tamaño                 ENUM('Pequeño','Mediano','Grande') NOT NULL,
  Estado_Salud           VARCHAR(255) NOT NULL,
  Estado_Esterilizacion  ENUM('Esterilizado','No esterilizado') NOT NULL,
  Ubicacion_Aproximada   VARCHAR(100) NOT NULL,         -- barrio o zona, sin dirección exacta
  Estado_Adopcion        ENUM('En adopción','Adoptado','Archivado') NOT NULL DEFAULT 'En adopción',
  PRIMARY KEY (ID_Publicacion),
  KEY ix_adopcion_estado (Estado_Adopcion),
  CONSTRAINT fk_adopcion_publicacion FOREIGN KEY (ID_Publicacion, Tipo_Publicacion)
    REFERENCES Publicacion_Animal (ID_Publicacion, Tipo_Publicacion),
  CONSTRAINT ck_adopcion_tipo CHECK (Tipo_Publicacion = 'Adopción')
) ENGINE=InnoDB;

-- Subtipo: mascota perdida o encontrada. Comparten los datos y Tipo_Aviso las distingue:
-- Perdida = quien publica perdió su mascota; Encontrada = encontró una ajena (RF:0064)
CREATE TABLE Publicacion_Perdida (
  ID_Publicacion     INT UNSIGNED NOT NULL,
  Tipo_Publicacion   ENUM('Adopción','Perdida') NOT NULL DEFAULT 'Perdida',
  Tipo_Aviso         ENUM('Perdida','Encontrada') NOT NULL DEFAULT 'Perdida',
  Nombre_Mascota     VARCHAR(50)  NULL,                 -- obligatorio en las perdidas, opcional en las encontradas (RNE26)
  Caracteristicas    TEXT         NOT NULL,             -- color, collar, señas para reconocerla
  Fecha_Suceso       DATE         NOT NULL,             -- del extravío o del hallazgo, según Tipo_Aviso
  Zona_Suceso        VARCHAR(100) NOT NULL,             -- del extravío o del hallazgo, según Tipo_Aviso
  Contacto           VARCHAR(100) NOT NULL,             -- teléfono de 8 o 9 dígitos (lo valida Laravel); es público
  Fecha_Vencimiento  DATE         NOT NULL,             -- creación o última renovación + 30 días (RNE17)
  Estado_Perdida     ENUM('Vigente','Resuelta','Archivada') NOT NULL DEFAULT 'Vigente',  -- Resuelta = la mascota volvió con su dueño
  PRIMARY KEY (ID_Publicacion),
  KEY ix_perdida_tipo_estado_vencimiento (Tipo_Aviso, Estado_Perdida, Fecha_Vencimiento),
  CONSTRAINT fk_perdida_publicacion FOREIGN KEY (ID_Publicacion, Tipo_Publicacion)
    REFERENCES Publicacion_Animal (ID_Publicacion, Tipo_Publicacion),
  CONSTRAINT ck_perdida_tipo        CHECK (Tipo_Publicacion = 'Perdida'),
  CONSTRAINT ck_perdida_vencimiento CHECK (Fecha_Vencimiento > Fecha_Suceso),
  CONSTRAINT ck_perdida_nombre      CHECK (Tipo_Aviso = 'Encontrada' OR Nombre_Mascota IS NOT NULL)
) ENGINE=InnoDB;

-- Fotos de una publicación: de 1 a 6 en adopción y de 1 a 3 en perdida o encontrada (RNE01, lo valida Laravel)
CREATE TABLE Foto_Animal (
  ID_Foto         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ID_Publicacion  INT UNSIGNED NOT NULL,
  URL_Foto        VARCHAR(255) NOT NULL,                -- ruta dentro del disco public
  PRIMARY KEY (ID_Foto),
  CONSTRAINT fk_foto_publicacion FOREIGN KEY (ID_Publicacion) REFERENCES Publicacion_Animal (ID_Publicacion)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- Adopción y seguimiento (Sprint 5, sin migración todavía)
-- -----------------------------------------------------------------------------

CREATE TABLE Solicitud_Adopcion (
  ID_Solicitud      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ID_Publicacion    INT UNSIGNED NOT NULL,
  ID_Usuario        INT UNSIGNED NOT NULL,              -- Particular que solicita
  Fecha_Solicitud   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Estado_Solicitud  ENUM('Pendiente','Adoptada','Rechazada') NOT NULL DEFAULT 'Pendiente',
  PRIMARY KEY (ID_Solicitud),
  CONSTRAINT fk_solicitud_publicacion FOREIGN KEY (ID_Publicacion) REFERENCES Publicacion_Adopcion (ID_Publicacion),
  CONSTRAINT fk_solicitud_particular  FOREIGN KEY (ID_Usuario)     REFERENCES Particular (ID_Usuario)
) ENGINE=InnoDB;

CREATE TABLE Adopcion (
  ID_Adopcion     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ID_Solicitud    INT UNSIGNED NOT NULL,
  Fecha_Adopcion  DATE NOT NULL,
  PRIMARY KEY (ID_Adopcion),
  UNIQUE KEY uq_adopcion_solicitud (ID_Solicitud),     -- una adopción por solicitud
  CONSTRAINT fk_adopcion_solicitud FOREIGN KEY (ID_Solicitud) REFERENCES Solicitud_Adopcion (ID_Solicitud)
) ENGINE=InnoDB;

-- Entidad débil de Adopcion: un seguimiento por adopción (RNE20)
CREATE TABLE Seguimiento (
  ID_Adopcion       INT UNSIGNED NOT NULL,
  Fecha_Aviso       DATE NOT NULL,                      -- Fecha_Adopcion + 15 días
  Fecha_Limite      DATE NOT NULL,                      -- Fecha_Aviso + 7 días
  URL_Foto          VARCHAR(255) NULL,
  Fecha_Envio       DATETIME NULL,
  Estado_Evidencia  ENUM('Pendiente','Enviada','Aprobada','Rechazada') NOT NULL DEFAULT 'Pendiente',
  PRIMARY KEY (ID_Adopcion),
  CONSTRAINT fk_seguimiento_adopcion FOREIGN KEY (ID_Adopcion) REFERENCES Adopcion (ID_Adopcion)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- Reportes (Sprint 6, sin migración todavía)
-- -----------------------------------------------------------------------------

-- Se completa ID_Usuario_Reportado o ID_Publicacion_Reportada, nunca los dos (RNE22)
CREATE TABLE Reporte (
  ID_Reporte                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ID_Usuario_Reportante     INT UNSIGNED NOT NULL,
  ID_Usuario_Reportado      INT UNSIGNED NULL,
  ID_Publicacion_Reportada  INT UNSIGNED NULL,
  Motivo                    VARCHAR(120) NOT NULL,
  Fecha_Reporte             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Estado_Reporte            ENUM('Pendiente','Desestimado','Confirmado') NOT NULL DEFAULT 'Pendiente',
  Medida                    ENUM('Aviso','Suspensión','Baja lógica') NULL,   -- se completa al resolver
  PRIMARY KEY (ID_Reporte),
  CONSTRAINT fk_reporte_reportante  FOREIGN KEY (ID_Usuario_Reportante)    REFERENCES Usuario (ID_Usuario),
  CONSTRAINT fk_reporte_reportado   FOREIGN KEY (ID_Usuario_Reportado)     REFERENCES Usuario (ID_Usuario),
  CONSTRAINT fk_reporte_publicacion FOREIGN KEY (ID_Publicacion_Reportada) REFERENCES Publicacion_Animal (ID_Publicacion),
  CONSTRAINT ck_reporte_objeto CHECK ((ID_Usuario_Reportado IS NULL) <> (ID_Publicacion_Reportada IS NULL))
) ENGINE=InnoDB;
