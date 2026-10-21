-- =============================================================================
--  AdoptaUY · Datos de prueba
--  Equipo CodeCrafters · TC4, ITI CETP-UTU
--
--  Todos los datos son FICTICIOS (Ley 18.331): personas, refugios, cédulas,
--  teléfonos y cuentas inventados para probar. Los correos usan el dominio
--  reservado .test, que no existe. Ningún refugio corresponde a uno real.
--
--  Cubre al menos un registro por estado de cada tabla, para probar listados,
--  filtros y transiciones. Contraseña de todas las cuentas: Prueba2026!
--  (guardada como hash bcrypt, RNF:0007).
--
--  Uso, después de adoptauy.sql:  mysql -u root -p adoptauy < datos-prueba.sql
-- =============================================================================

SET NAMES utf8mb4;
USE adoptauy;

SET @clave = '$2y$12$xe.onSn5JZgonGlDAIIAbuGMqPYK9lFGgNxVA/5VKQWfx6BBa4T5u';  -- Prueba2026!

-- -----------------------------------------------------------------------------
-- Usuarios: 1 administrador, 4 particulares y 3 refugios
-- -----------------------------------------------------------------------------
INSERT INTO Usuario (ID_Usuario, Email, Contraseña, Tipo_Usuario, Estado_Usuario, Fecha_Alta, Email_Verificado) VALUES
  (1, 'admin@adoptauy.test',          @clave, 'Administrador', 'Activo',      '2026-08-01 09:00:00', '2026-08-01 09:05:00'),
  (2, 'ana.suarez@prueba.test',       @clave, 'Particular',    'Activo',      '2026-08-05 10:00:00', '2026-08-05 10:10:00'),
  (3, 'bruno.ferreira@prueba.test',   @clave, 'Particular',    'Activo',      '2026-08-06 11:00:00', '2026-08-06 11:20:00'),
  (4, 'carla.rodriguez@prueba.test',  @clave, 'Particular',    'Activo',      '2026-08-07 12:00:00', NULL),
  (5, 'diego.silva@prueba.test',      @clave, 'Particular',    'Activo',      '2026-08-08 13:00:00', '2026-08-08 13:30:00'),
  (6, 'refugio.norte@prueba.test',    @clave, 'Refugio',       'Activo',      '2026-08-10 09:00:00', '2026-08-10 09:15:00'),
  (7, 'refugio.sur@prueba.test',      @clave, 'Refugio',       'Activo',      '2026-10-18 15:00:00', '2026-10-18 15:05:00'),
  (8, 'refugio.oeste@prueba.test',    @clave, 'Refugio',       'Activo',      '2026-09-01 16:00:00', '2026-09-01 16:10:00');

INSERT INTO Administrador (ID_Usuario) VALUES (1);

-- Cédulas ficticias con dígito verificador válido; teléfonos 09XXXXXXX inventados
INSERT INTO Particular (ID_Usuario, CI, Nombre, Apellido, Telefono) VALUES
  (2, '12345672', 'Ana',   'Suárez',    '099000001'),
  (3, '23456783', 'Bruno', 'Ferreira',  '099000002'),
  (4, '34567894', 'Carla', 'Rodríguez', '099000003'),
  (5, '7654323',  'Diego', 'Silva',     '099000004');   -- cédula de 7 dígitos

-- Un refugio por estado relevante: Validado, Pendiente de validación y Rechazado
INSERT INTO Refugio (ID_Usuario, Nombre_Refugio, Certificado_Personeria, Comprobante_Domicilio, Ubicacion,
                     Latitud, Longitud, Estado_Validacion, Motivo_Rechazo, Fecha_Solicitud, Fecha_Resolucion, URL_Foto) VALUES
  (6, 'Refugio de Prueba Norte', 'refugios/6/certificado.pdf', 'refugios/6/domicilio.pdf', 'Calle Ficticia 100, Montevideo',
      -34.850000, -56.180000, 'Validado', NULL, '2026-08-10 09:00:00', '2026-08-12 10:00:00', 'refugios/6/foto.jpg'),
  (7, 'Refugio de Prueba Sur',   'refugios/7/certificado.pdf', 'refugios/7/domicilio.pdf', 'Calle Ficticia 200, Montevideo',
      NULL, NULL, 'Pendiente de validación', NULL, '2026-10-18 15:00:00', NULL, NULL),
  (8, 'Refugio de Prueba Oeste', 'refugios/8/certificado.pdf', 'refugios/8/domicilio.pdf', 'Calle Ficticia 300, Montevideo',
      NULL, NULL, 'Rechazado', 'El comprobante de domicilio no corresponde al predio declarado',
      '2026-09-01 16:00:00', '2026-09-03 11:00:00', NULL);

INSERT INTO Refugio_Datos_Contacto (ID_Usuario, Dato_Contacto) VALUES
  (6, '099000010'),
  (6, 'contacto.norte@prueba.test'),
  (7, '099000020'),
  (8, '099000030');

INSERT INTO Cuenta_Bancaria (ID_Usuario, ID_Cuenta_Banco, Banco, Tipo_Cuenta, Numero_Cuenta, Titular) VALUES
  (6, 1, 'Banco de Prueba',      'Caja de ahorro',   '000111222-0001', 'Refugio de Prueba Norte'),
  (6, 2, 'Otro Banco de Prueba', 'Cuenta corriente', '009988776',      'Refugio de Prueba Norte');

-- -----------------------------------------------------------------------------
-- Publicaciones: supertipo + subtipo
-- -----------------------------------------------------------------------------
INSERT INTO Publicacion_Animal (ID_Publicacion, ID_Usuario, Tipo_Publicacion, Especie, Raza, Sexo, Descripcion, Estado_Moderacion, Fecha_Creacion) VALUES
  (1, 2, 'Adopción', 'Perro', 'Mestizo',          'Macho',  'Juguetón y sociable con otros perros.', 'Visible',     '2026-09-20 10:00:00'),
  (2, 6, 'Adopción', 'Perro', 'Labrador',         'Hembra', 'Rescatada en agosto.',                  'Visible',     '2026-08-20 09:00:00'),
  (3, 6, 'Adopción', 'Gato',  'Mestizo',          'Macho',  NULL,                                    'Visible',     '2026-08-25 09:00:00'),
  (4, 2, 'Adopción', 'Gato',  'Siamés',           'Hembra', 'Cachorra, convive con niños.',          'Visible',     '2026-10-01 18:00:00'),
  (5, 3, 'Perdida',  'Perro', 'Beagle',           'Macho',  'Collar rojo con chapita.',              'Visible',     CURRENT_DATE - INTERVAL 5 DAY),
  (6, 6, 'Perdida',  'Gato',  'Mestizo',          'Hembra', 'Manchas negras en el lomo.',            'Visible',     '2026-09-10 08:00:00'),
  (7, 4, 'Perdida',  'Perro', 'Mestizo',          'Hembra', 'Pelo largo color canela.',              'Visible',     CURRENT_DATE - INTERVAL 40 DAY),
  (8, 5, 'Adopción', 'Perro', 'Ovejero alemán',   'Macho',  'Se entrega con costo.',                 'En revisión', '2026-10-05 12:00:00'),
  (9, 2, 'Adopción', 'Perro', 'Mestizo',          'Hembra', 'Tranquila, ideal para apartamento.',    'Visible',     '2026-08-28 10:00:00');

-- Edad_Meses < 12 = cachorro (filtro de edad, RF:0021)
INSERT INTO Publicacion_Adopcion (ID_Publicacion, Edad_Meses, Tamaño, Estado_Salud, Estado_Esterilizacion, Ubicacion_Aproximada, Estado_Adopcion) VALUES
  (1, 18, 'Mediano', 'Sano, vacunado',               'Esterilizado',    'Cordón',       'En adopción'),
  (2, 36, 'Grande',  'Sana, desparasitada',          'Esterilizado',    'Prado',        'Adoptado'),
  (3, 60, 'Pequeño', 'Sano',                         'No esterilizado', 'Malvín',       'Archivado'),
  (4,  4, 'Pequeño', 'Sana, primera vacuna',         'No esterilizado', 'Pocitos',      'En adopción'),
  (8, 24, 'Grande',  'Sano',                         'Esterilizado',    'Cerro',        'En adopción'),
  (9, 30, 'Mediano', 'Sana, vacunada',               'Esterilizado',    'Buceo',        'Adoptado');

-- Vigencia de 30 días desde la creación o la última renovación (RNE17)
INSERT INTO Publicacion_Perdida (ID_Publicacion, Nombre_Mascota, Fecha_Extravio, Zona_Extravio, Contacto, Fecha_Vencimiento, Estado_Perdida) VALUES
  (5, 'Toby',  CURRENT_DATE - INTERVAL 6 DAY,  'Parque Rodó',  '099000002', CURRENT_DATE + INTERVAL 25 DAY, 'Perdida'),
  (6, 'Luna',  '2026-09-08',                   'Prado',        '099000010', '2026-10-10',                    'Encontrada'),
  (7, 'Canela', CURRENT_DATE - INTERVAL 41 DAY, 'La Blanqueada', '099000003', CURRENT_DATE - INTERVAL 10 DAY, 'Archivada');

-- Entre 1 y 6 fotos en adopción y entre 1 y 3 en perdidas (RNE01)
INSERT INTO Foto_Animal (ID_Publicacion, ID_Foto, URL_Foto) VALUES
  (1, 1, 'publicaciones/1/1.jpg'), (1, 2, 'publicaciones/1/2.jpg'), (1, 3, 'publicaciones/1/3.webp'),
  (2, 1, 'publicaciones/2/1.jpg'),
  (3, 1, 'publicaciones/3/1.png'),
  (4, 1, 'publicaciones/4/1.jpg'), (4, 2, 'publicaciones/4/2.jpg'),
  (5, 1, 'publicaciones/5/1.jpg'), (5, 2, 'publicaciones/5/2.jpg'),
  (6, 1, 'publicaciones/6/1.jpg'),
  (7, 1, 'publicaciones/7/1.jpg'),
  (8, 1, 'publicaciones/8/1.jpg'),
  (9, 1, 'publicaciones/9/1.jpg');

-- -----------------------------------------------------------------------------
-- Solicitudes, adopciones y seguimiento
-- -----------------------------------------------------------------------------
-- Publicación 2: Bruno fue elegido (Adoptada) y Carla quedó Rechazada por el sistema
-- Publicación 4: solicitud Pendiente de Carla
-- Publicación 9: Diego adoptó y no envió la foto del seguimiento a tiempo
INSERT INTO Solicitud_Adopcion (ID_Solicitud, ID_Publicacion, ID_Usuario, Fecha_Solicitud, Estado_Solicitud) VALUES
  (1, 2, 3, '2026-08-22 10:00:00', 'Adoptada'),
  (2, 2, 4, '2026-08-23 11:00:00', 'Rechazada'),
  (3, 4, 4, '2026-10-02 09:00:00', 'Pendiente'),
  (4, 9, 5, '2026-08-30 14:00:00', 'Adoptada'),
  (5, 1, 3, '2026-09-25 16:00:00', 'Rechazada');      -- rechazo individual del publicante

INSERT INTO Adopcion (ID_Adopcion, ID_Solicitud, Fecha_Adopcion) VALUES
  (1, 1, '2026-08-28'),
  (2, 4, CURRENT_DATE - INTERVAL 30 DAY);

-- Aviso = adopción + 15 días; límite = aviso + 7 días (RNE20)
INSERT INTO Seguimiento (ID_Adopcion, Fecha_Aviso, Fecha_Limite, URL_Foto, Fecha_Envio, Estado_Evidencia) VALUES
  (1, '2026-09-12', '2026-09-19', 'seguimiento/1/foto.jpg', '2026-09-14 19:00:00', 'Aprobada'),
  (2, CURRENT_DATE - INTERVAL 15 DAY, CURRENT_DATE - INTERVAL 8 DAY, NULL, NULL, 'Pendiente');   -- vencido: cuenta restringida (RNE21)

-- -----------------------------------------------------------------------------
-- Reportes
-- -----------------------------------------------------------------------------
-- Publicación 8: 3 reportes de usuarios distintos, por eso quedó En revisión (RNE15)
INSERT INTO Reporte (ID_Reporte, ID_Usuario_Reportante, ID_Usuario_Reportado, ID_Publicacion_Reportada, Motivo, Fecha_Reporte, Estado_Reporte, Medida) VALUES
  (1, 2, NULL, 8, 'Venta de animales (la plataforma es solo para adopción)', '2026-10-06 10:00:00', 'Pendiente',   NULL),
  (2, 3, NULL, 8, 'Venta de animales (la plataforma es solo para adopción)', '2026-10-06 11:00:00', 'Pendiente',   NULL),
  (3, 4, NULL, 8, 'Publicación falsa o engañosa (el animal no existe o los datos no coinciden)', '2026-10-07 09:00:00', 'Pendiente', NULL),
  (4, 5, 3, NULL, 'Acoso o mensajes abusivos',                               '2026-09-28 20:00:00', 'Desestimado', NULL),
  (5, 2, 5, NULL, 'Usuario malicioso o fraudulento',                         '2026-10-07 18:00:00', 'Confirmado',  'Aviso');
