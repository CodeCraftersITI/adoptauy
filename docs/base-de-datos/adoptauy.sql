-- =============================================================================
--  AdoptaUY · Creación de la base de datos
--  Equipo CodeCrafters · TC4, ITI CETP-UTU
--
--  Sigue el DER y el diccionario de datos (15.2.2): Publicacion_Animal es el
--  supertipo y Publicacion_Adopcion y Publicacion_Perdida son sus subtipos.
--  Solo tablas, claves, NOT NULL, UNIQUE, DEFAULT y ENUM. Las demás
--  restricciones (RNE) las valida Laravel por ahora; triggers y
--  procedimientos quedan para el Hito 3.
--
--  Uso:  mysql -u root -p < adoptauy.sql
-- =============================================================================

-- Necesario para los nombres con tilde o ñ (Contraseña, Tamaño)
SET NAMES utf8mb4;

DROP DATABASE IF EXISTS adoptauy;
CREATE DATABASE adoptauy CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
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
  Email_Verificado  DATETIME NULL,                      -- fecha de confirmación; NULL = sin confirmar
  PRIMARY KEY (ID_Usuario),
  UNIQUE KEY uq_usuario_email (Email)
) ENGINE=InnoDB;

CREATE TABLE Particular (
  ID_Usuario  INT UNSIGNED NOT NULL,
  CI          VARCHAR(8)  NOT NULL,                     -- 7 u 8 dígitos, sin puntos ni guion
  Nombre      VARCHAR(50) NOT NULL,
  Apellido    VARCHAR(50) NOT NULL,
  Telefono    VARCHAR(9)  NOT NULL,                     -- 8 dígitos (fijo) o 9 (celular)
  PRIMARY KEY (ID_Usuario),
  UNIQUE KEY uq_particular_ci (CI),
  CONSTRAINT fk_particular_usuario FOREIGN KEY (ID_Usuario) REFERENCES Usuario (ID_Usuario)
) ENGINE=InnoDB;

CREATE TABLE Administrador (
  ID_Usuario  INT UNSIGNED NOT NULL,
  PRIMARY KEY (ID_Usuario),
  CONSTRAINT fk_administrador_usuario FOREIGN KEY (ID_Usuario) REFERENCES Usuario (ID_Usuario)
) ENGINE=InnoDB;

CREATE TABLE Refugio (
  ID_Usuario              INT UNSIGNED NOT NULL,
  Nombre_Refugio          VARCHAR(120) NOT NULL,
  Certificado_Personeria  VARCHAR(255) NOT NULL,        -- ruta del archivo
  Comprobante_Domicilio   VARCHAR(255) NOT NULL,        -- ruta del archivo
  Ubicacion               VARCHAR(255) NOT NULL,
  Latitud                 DECIMAL(9,6) NULL,
  Longitud                DECIMAL(9,6) NULL,
  Estado_Validacion       ENUM('Pendiente de validación','Validado','Rechazado','Suspendido','Dado de baja')
                          NOT NULL DEFAULT 'Pendiente de validación',
  Motivo_Rechazo          VARCHAR(500) NULL,            -- obligatorio si está Rechazado (RNE05)
  Fecha_Solicitud         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Fecha_Resolucion        DATETIME NULL,
  URL_Foto                VARCHAR(255) NULL,
  PRIMARY KEY (ID_Usuario),
  CONSTRAINT fk_refugio_usuario FOREIGN KEY (ID_Usuario) REFERENCES Usuario (ID_Usuario)
) ENGINE=InnoDB;

-- Atributo multivaluado de Refugio
CREATE TABLE Refugio_Datos_Contacto (
  ID_Usuario     INT UNSIGNED NOT NULL,
  Dato_Contacto  VARCHAR(120) NOT NULL,
  PRIMARY KEY (ID_Usuario, Dato_Contacto),
  CONSTRAINT fk_contacto_refugio FOREIGN KEY (ID_Usuario) REFERENCES Refugio (ID_Usuario)
) ENGINE=InnoDB;

-- Entidad débil de Refugio
CREATE TABLE Cuenta_Bancaria (
  ID_Usuario       INT UNSIGNED NOT NULL,
  ID_Cuenta_Banco  SMALLINT UNSIGNED NOT NULL,          -- se numera dentro de cada refugio
  Banco            VARCHAR(60)  NOT NULL,
  Tipo_Cuenta      VARCHAR(60)  NOT NULL,
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
  Tipo_Publicacion   ENUM('Adopción','Perdida') NOT NULL,
  Especie            ENUM('Perro','Gato') NOT NULL,
  Raza               VARCHAR(50) NOT NULL,              -- «Mestizo» si no tiene raza definida
  Sexo               ENUM('Macho','Hembra') NOT NULL,
  Descripcion        TEXT NULL,                         -- en perdidas: características de la mascota
  Estado_Moderacion  ENUM('Visible','En revisión','Suspendida') NOT NULL DEFAULT 'Visible',
  Fecha_Creacion     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (ID_Publicacion),
  CONSTRAINT fk_publicacion_usuario FOREIGN KEY (ID_Usuario) REFERENCES Usuario (ID_Usuario)
) ENGINE=InnoDB;

-- Subtipo: mascota en adopción
CREATE TABLE Publicacion_Adopcion (
  ID_Publicacion         INT UNSIGNED NOT NULL,
  Edad_Meses             SMALLINT UNSIGNED NOT NULL,    -- edad estimada al publicar
  Tamaño                 ENUM('Pequeño','Mediano','Grande') NOT NULL,
  Estado_Salud           VARCHAR(255) NOT NULL,
  Estado_Esterilizacion  ENUM('Esterilizado','No esterilizado') NOT NULL,
  Ubicacion_Aproximada   VARCHAR(100) NOT NULL,         -- barrio o zona, sin dirección exacta
  Estado_Adopcion        ENUM('En adopción','Adoptado','Archivado') NOT NULL DEFAULT 'En adopción',
  PRIMARY KEY (ID_Publicacion),
  CONSTRAINT fk_adopcion_publicacion FOREIGN KEY (ID_Publicacion) REFERENCES Publicacion_Animal (ID_Publicacion)
) ENGINE=InnoDB;

-- Subtipo: mascota perdida
CREATE TABLE Publicacion_Perdida (
  ID_Publicacion     INT UNSIGNED NOT NULL,
  Nombre_Mascota     VARCHAR(60)  NOT NULL,
  Fecha_Extravio     DATE         NOT NULL,
  Zona_Extravio      VARCHAR(100) NOT NULL,
  Contacto           VARCHAR(9)   NOT NULL,             -- teléfono de 8 o 9 dígitos; es público
  Fecha_Vencimiento  DATE         NOT NULL,             -- creación o última renovación + 30 días (RNE17)
  Estado_Perdida     ENUM('Perdida','Encontrada','Archivada') NOT NULL DEFAULT 'Perdida',
  PRIMARY KEY (ID_Publicacion),
  CONSTRAINT fk_perdida_publicacion FOREIGN KEY (ID_Publicacion) REFERENCES Publicacion_Animal (ID_Publicacion)
) ENGINE=InnoDB;

-- Entidad débil de Publicacion_Animal: de 1 a 6 fotos en adopción y de 1 a 3 en perdida (RNE01)
CREATE TABLE Foto_Animal (
  ID_Publicacion  INT UNSIGNED NOT NULL,
  ID_Foto         TINYINT UNSIGNED NOT NULL,
  URL_Foto        VARCHAR(255) NOT NULL,
  PRIMARY KEY (ID_Publicacion, ID_Foto),
  CONSTRAINT fk_foto_publicacion FOREIGN KEY (ID_Publicacion) REFERENCES Publicacion_Animal (ID_Publicacion)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- Adopción y seguimiento
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
-- Reportes
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
  CONSTRAINT fk_reporte_publicacion FOREIGN KEY (ID_Publicacion_Reportada) REFERENCES Publicacion_Animal (ID_Publicacion)
) ENGINE=InnoDB;
