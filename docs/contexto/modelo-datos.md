# Modelo de datos

> Extraído del Documento integrador 2.2 de AdoptaUY (rev. 21/10/2026). Si este archivo y el documento difieren, manda el documento; avisá la diferencia.
>
> **Numeración de RNE:** este archivo usa la numeración del repositorio (RNE23 = CI, RNE24 = Estado_Validacion, RNE25 = especialización, RNE26 = mascotas encontradas, RNE27 = Google). La V3 del integrador numera distinto: allí RNE23 es la adopción desde una solicitud Adoptada, RNE24 la especialización, RNE25 las mascotas encontradas y RNE26 Google. En el código y los commits se usa la del repositorio hasta que el equipo unifique.

## 15. Base de datos
El modelo entidad-relación (15.1.1) y el pasaje a tablas (15.2.1) coinciden con `docs/base-de-datos/adoptauy.sql`. Los diagramas (MER en notación de Chen) están en `docs/diagramas/uml/`.

### 15.1. Modelo conceptual

#### 15.1.1. D.E.R.
Cambios respecto del diagrama del Hito 1:

| Elemento | Cambio | Motivo |
|---|---|---|
| Usuario | Estado_Baja pasa a Estado_Usuario (Activo, En revisión, Suspendido, Inhabilitado, Baja). Se agregan Fecha_Alta y Email_Verificado. Se agrega Google_ID (identificador de la cuenta de Google vinculada) y Contraseña admite vacío. | D18, D19, D27, D93, RNE14, RNE27 |
| Persona | Pasa a llamarse Particular. | D23 |
| Administrador | Se agrega como tercer subtipo de Usuario. | RNE11 lo mencionaba sin existir |
| Refugio | Se agregan Certificado_Personeria, Latitud, Longitud, Motivo_Rechazo, Fecha_Solicitud y Fecha_Resolucion. | D1, D12, RNE05, RNE06 |
| Publicacion_Animal | Se elimina Es_Cachorro y Edad pasa a Edad_Meses. Pasa a ser supertipo, con especialización disjunta y total según Tipo_Publicacion (Adopción o Perdida): guarda los datos comunes y agrega Fecha_Creacion y Estado_Moderacion (Visible, En revisión, Suspendida). Subtipo Publicacion_Adopcion: Edad_Meses, Tamaño, Estado_Salud, Estado_Esterilizacion, Ubicacion_Aproximada y Estado_Adopcion. Subtipo Publicacion_Perdida: Tipo_Aviso, Nombre_Mascota, Fecha_Suceso, Zona_Suceso, Contacto, Fecha_Vencimiento y Estado_Perdida; guarda las mascotas perdidas y las encontradas, que se distinguen por Tipo_Aviso (Perdida o Encontrada). | D3, D5, D16, RNE14, RNE25, RNE26 |
| Metodos_Pago | Pasa a llamarse Cuenta_Bancaria. | D4 |
| Necesidad_Refugio | Se elimina, con la relación "tiene". | D17 |
| Solicitud_Adopcion | Entidad nueva, entre Particular (solicitante) y Publicacion_Adopcion. | D6, D14 |
| Adopcion | Entidad nueva, generada por la solicitud Adoptada. | D14, D16 |
| Seguimiento | Entidad débil nueva de Adopcion, una por adopción. | D36 |
| Reporte | Entidad nueva: un Usuario reporta a un Usuario o a una Publicacion_Animal. | D8, D18, RNE15 |

#### 15.1.2. RNE
Las restricciones dadas de baja conservan su código; las nuevas continúan desde RNE16.

| Código | Restricción | Estado |
|---|---|---|
| RNE01 | Toda publicación de mascota en adopción debe tener entre 1 y 6 fotos asociadas. Una de mascota perdida, entre 1 y 3. Cada fotografía es JPG, PNG o WEBP y pesa hasta 8 MB. | Modificada (D66) |
| RNE02 | El estado de una publicación solo puede cambiar según las transiciones de 13.2.2. Estado_Adopcion de Publicacion_Adopcion toma los valores En adopción, Adoptado y Archivado; Estado_Perdida de Publicacion_Perdida toma Vigente, Resuelta y Archivada, para las perdidas y las encontradas. | Modificada (D10, D16, D26, mascotas encontradas) |
| RNE03 | Solo el publicador dueño de la publicación o el Administrador pueden modificar o dar de baja esa publicación. | Vigente |
| RNE04 | Un Refugio solo puede publicar animales o dar de alta cuentas bancarias si su Estado_Validacion = "Validado". | Modificada (D17) |
| RNE05 | Si Estado_Validacion = "Rechazado", el campo Motivo_Rechazo es obligatorio; en cualquier otro estado debe ser nulo. | Vigente |
| RNE06 | La validación de un refugio debe resolverse dentro de los 5 días hábiles posteriores a Fecha_Solicitud. | Vigente |
| RNE07 | Dada de baja. Motivo: "cachorros" es un filtro por edad; se elimina Es_Cachorro. | Dada de baja (D5) |
| RNE08 | Dada de baja. Motivo: se elimina el buscador de refugios por radio. | Dada de baja (D11) |
| RNE09 | Dada de baja. Motivo: la plataforma no procesa donaciones ni cobra comisión. | Dada de baja (D4) |
| RNE10 | Un mismo ID_Usuario no puede existir simultáneamente en más de una de las tablas Particular, Refugio y Administrador. | Modificada |
| RNE11 | Todo ID_Usuario dado de alta en Usuario debe tener una fila correspondiente en Particular, Refugio o Administrador. | Vigente |
| RNE12 | Edad_Meses no puede ser negativa. Tamaño toma un valor de una lista cerrada (Pequeño, Mediano, Grande). Numero_Cuenta se almacena como texto. Edad_Meses no tiene máximo. Largos: Nombre y Apellido de 2 a 50 caracteres, Raza de 2 a 50, Ubicacion_Aproximada y Zona_Suceso de 3 a 100 y Email hasta 255. Telefono y Contacto tienen 8 o 9 dígitos. | Modificada |
| RNE13 | Las contraseñas se almacenan como hash, nunca en texto plano (RNF:0007). | Modificada |
| RNE14 | Fecha_Creacion de una publicación no puede ser anterior a la Fecha_Alta del usuario que la crea, ni posterior a la fecha actual. | Vigente |
| RNE15 | Una publicación con 3 reportes de usuarios distintos pasa a Estado_Moderacion = En revisión. Un Particular con 5 reportes, o con 3 publicaciones suspendidas, pasa a Estado_Usuario = En revisión; con 10 reportes pasa a Inhabilitado. Solo cuentan reportes de usuarios distintos que el Administrador no desestimó. Un Refugio con 3 reportes pasa a Estado_Validacion = Suspendido. | Modificada (D31) |
| RNE16 | Una publicación de tipo Perdida, sea una mascota perdida o una encontrada, puede crearla un Particular o un Refugio validado. | Nueva (D11, D49) |
| RNE17 | Fecha_Vencimiento de Publicacion_Perdida es la fecha de creación o de la última renovación más 30 días. | Nueva (D16) |
| RNE18 | Una solicitud solo puede crearla un Particular sobre una Publicacion_Adopcion en estado En adopción, y el solicitante no puede ser su publicador. | Nueva (D14) |
| RNE19 | Una publicación en estado Adoptado tiene exactamente una solicitud Adoptada; al aceptarla, las demás solicitudes Pendientes pasan a Rechazada. | Nueva (D25) |
| RNE20 | Cada adopción tiene exactamente un seguimiento, con Fecha_Aviso igual a Fecha_Adopcion más 15 días y Fecha_Limite igual a Fecha_Aviso más 7 días. | Nueva (D36) |
| RNE21 | Un usuario con un seguimiento cuya Fecha_Limite venció sin evidencia enviada, o con la evidencia rechazada y sin reenviar, no puede crear solicitudes de adopción ni publicaciones. | Nueva (D36, D71) |
| RNE22 | Un reporte se refiere a exactamente un usuario o una publicación, y el reportante no puede reportarse a sí mismo ni reportar dos veces lo mismo. | Nueva (D18) |
| RNE23 | La CI de un Particular es única, se guarda solo con números y tiene 7 u 8 dígitos con dígito verificador válido. | Nueva (D64, D65) |
| RNE24 | Estado_Validacion de un Refugio toma los valores Pendiente de validación, Validado, Rechazado, Suspendido y Dado de baja. Un refugio Dado de baja no puede iniciar sesión y todas sus publicaciones quedan en Archivado. | Nueva (D69) |
| RNE25 | Cada fila de Publicacion_Animal tiene exactamente una fila asociada, en Publicacion_Adopcion o en Publicacion_Perdida, según su Tipo_Publicacion. | Nueva |
| RNE26 | Tipo_Aviso de Publicacion_Perdida toma Perdida o Encontrada. Nombre_Mascota es obligatorio cuando Tipo_Aviso es Perdida y opcional cuando es Encontrada. Fecha_Suceso y Zona_Suceso son las del extravío en una perdida y las del hallazgo en una encontrada. | Nueva (mascotas encontradas) |
| RNE27 | Google_ID es único y solo puede tenerlo un Usuario de tipo Particular. Contraseña puede quedar vacía solo si el Usuario tiene Google_ID; cuando tiene valor, es un hash bcrypt (RNE13). | Nueva (D93; en la V3 del integrador es RNE26) |

### 15.2. Modelo lógico

#### 15.2.1. Pasaje a tablas
La clave primaria de cada tabla figura primero, en negrita.

- Usuario(ID_Usuario, Email, Contraseña, Tipo_Usuario, Estado_Usuario, Fecha_Alta, Email_Verificado, Google_ID). Email es único. Google_ID es único y admite vacío; Contraseña solo puede quedar vacía si hay Google_ID (RNE27). (ID_Usuario, Tipo_Usuario) es único y lo referencian los subtipos, para que cada usuario esté solo en la tabla de su tipo (RNE10). Email_Verificado guarda la fecha de confirmación del correo; vacío significa sin confirmar

- Particular(ID_Usuario, Tipo_Usuario, CI, Nombre, Apellido, Telefono) FK: (ID_Usuario, Tipo_Usuario) (Usuario). Tipo_Usuario vale siempre Particular. CI es clave candidata, con índice único (RNE23)

- Administrador(ID_Usuario, Tipo_Usuario) FK: (ID_Usuario, Tipo_Usuario) (Usuario). Tipo_Usuario vale siempre Administrador

- Refugio(ID_Usuario, Tipo_Usuario, Nombre_Refugio, Certificado_Personeria, Comprobante_Domicilio, Ubicacion, Latitud, Longitud, Estado_Validacion, Motivo_Rechazo, Fecha_Solicitud, Fecha_Resolucion, URL_Foto) FK: (ID_Usuario, Tipo_Usuario) (Usuario). Tipo_Usuario vale siempre Refugio. Estado_Validacion: Pendiente de validación, Validado, Rechazado, Suspendido o Dado de baja (RNE24)

- Refugio_Datos_Contacto(ID_Usuario, Dato_Contacto) FK: ID_Usuario (Refugio). Atributo multivaluado de Refugio

- Cuenta_Bancaria(ID_Usuario, ID_Cuenta_Banco, Banco, Tipo_Cuenta, Numero_Cuenta, Titular) FK: ID_Usuario (Refugio). Tipo_Cuenta es texto libre

- Publicacion_Animal(ID_Publicacion, ID_Usuario, Tipo_Publicacion, Especie, Raza, Sexo, Estado_Moderacion, Fecha_Creacion) FK: ID_Usuario (Usuario). Supertipo con los datos comunes a toda publicación. Tipo_Publicacion: Adopción o Perdida, e indica en qué subtipo está el resto de los datos (RNE25). (ID_Publicacion, Tipo_Publicacion) es único y lo referencian los subtipos. Estado_Moderacion: Visible, En revisión o Suspendida

- Publicacion_Adopcion(ID_Publicacion, Tipo_Publicacion, Edad_Meses, Tamaño, Estado_Salud, Estado_Esterilizacion, Ubicacion_Aproximada, Estado_Adopcion) FK: (ID_Publicacion, Tipo_Publicacion) (Publicacion_Animal). Tipo_Publicacion vale siempre Adopción. Estado_Esterilizacion: Esterilizado o No esterilizado. Estado_Adopcion: En adopción, Adoptado o Archivado

- Publicacion_Perdida(ID_Publicacion, Tipo_Publicacion, Tipo_Aviso, Nombre_Mascota, Caracteristicas, Fecha_Suceso, Zona_Suceso, Contacto, Fecha_Vencimiento, Estado_Perdida) FK: (ID_Publicacion, Tipo_Publicacion) (Publicacion_Animal). Tipo_Publicacion vale siempre Perdida. Tipo_Aviso: Perdida o Encontrada (RNE26). Nombre_Mascota admite nulo solo si Tipo_Aviso es Encontrada. Contacto es un teléfono de 8 o 9 dígitos. Estado_Perdida: Vigente, Resuelta o Archivada

- Foto_Animal(ID_Foto, ID_Publicacion, URL_Foto) FK: ID_Publicacion (Publicacion_Animal). ID_Foto es autonumérico. Cada publicación tiene de 1 a 6 fotos si es de adopción y de 1 a 3 si es de una mascota perdida o encontrada (RNE01)

- Solicitud_Adopcion(ID_Solicitud, ID_Publicacion, ID_Usuario, Fecha_Solicitud, Estado_Solicitud) FK: ID_Publicacion (Publicacion_Adopcion), ID_Usuario (Particular)

- Adopcion(ID_Adopcion, ID_Solicitud, Fecha_Adopcion) FK: ID_Solicitud (Solicitud_Adopcion).

- Seguimiento(ID_Adopcion, Fecha_Aviso, Fecha_Limite, URL_Foto, Fecha_Envio, Estado_Evidencia) FK: ID_Adopcion (Adopcion). Estado_Evidencia: Pendiente, Enviada, Aprobada o Rechazada.

- Reporte(ID_Reporte, ID_Usuario_Reportante, ID_Usuario_Reportado, ID_Publicacion_Reportada, Motivo, Fecha_Reporte, Estado_Reporte, Medida) FK: ID_Usuario_Reportante (Usuario), ID_Usuario_Reportado (Usuario), ID_Publicacion_Reportada (Publicacion_Animal). Estado_Reporte: Pendiente, Desestimado o Confirmado. Medida: Aviso, Suspensión o Baja lógica
Se eliminó la tabla Necesidad_Refugio (D17).

El modelo físico en MySQL, con estas restricciones, está en `docs/base-de-datos/adoptauy.sql`.

### Cambio pendiente de migración: Continuar con Google (RF:0066, RF:0067, RNE27)

El modelo ya está decidido (D93, D94) y los diagramas están en `docs/diagramas/AdoptaUY-Diagramas.drawio` (DER, MER, Clases, CU-1, CU-2, SEC-9 e Implementación). Falta la migración. Cuando se haga, `docs/base-de-datos/adoptauy.sql` y `usuarios.sql` se actualizan en el mismo pull request, para que sigan iguales a las migraciones. Camilo aprueba el cambio de base de datos.

La migración nueva (no se edita la de 2026-10-17) tiene que dejar Usuario así:

```sql
Contraseña  VARCHAR(255) NULL,   -- antes NOT NULL
Google_ID   VARCHAR(255) NULL,   -- después de Email_Verificado; es el «sub» que envía Google
UNIQUE KEY uq_usuario_google (Google_ID),
CONSTRAINT ck_usuario_hash   CHECK (`Contraseña` IS NULL OR (`Contraseña` LIKE '$2_$%' AND CHAR_LENGTH(`Contraseña`) = 60)),
CONSTRAINT ck_usuario_acceso CHECK (`Contraseña` IS NOT NULL OR Google_ID IS NOT NULL),   -- RNE27
CONSTRAINT ck_usuario_google CHECK (Google_ID IS NULL OR Tipo_Usuario = 'Particular')     -- RNE27
```

El CHECK `ck_usuario_hash` actual se elimina y se vuelve a crear con la condición nueva. Probado en MySQL 8.0 con los datos de `datos-prueba.sql`: corre sin errores y rechaza una cuenta sin contraseña ni Google_ID, un Google_ID repetido y un Google_ID en un Refugio.

Permisos de los usuarios de la base (`usuarios.sql`):

```sql
GRANT SELECT (ID_Usuario, Email, `Contraseña`, Tipo_Usuario, Estado_Usuario,
              Email_Verificado, Google_ID),
      UPDATE (Google_ID, Email_Verificado)
   ON adoptauy.Usuario TO 'adoptauy_login'@'%';

GRANT SELECT (ID_Usuario, Email, Google_ID), INSERT ON adoptauy.Usuario TO 'adoptauy_publico'@'%';
```

adoptauy_particular no cambia: para RF:0067 la contraseña actual se comprueba con adoptauy_login, que ya lee el hash, y la nueva se guarda con el UPDATE (Contraseña) que adoptauy_particular ya tiene.
