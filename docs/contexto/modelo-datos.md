# Modelo de datos

> Extraído del Documento integrador 2.1 de AdoptaUY (rev. 18/10/2026). Si este archivo y el documento difieren, manda el documento; avisá la diferencia.

## 15. Base de datos
El diagrama entidad-relación se redibuja según los cambios de esta sección (Anexo 19.1).

### 15.1. Modelo conceptual

#### 15.1.1. D.E.R.
Cambios respecto del diagrama del Hito 1:

| Elemento | Cambio | Motivo |
|---|---|---|
| Usuario | Estado_Baja pasa a Estado_Usuario (Activo, En revisión, Suspendido, Inhabilitado, Baja). Se agregan Fecha_Alta y Email_Verificado. | D18, D19, D27, RNE14 |
| Persona | Pasa a llamarse Particular. | D23 |
| Administrador | Se agrega como tercer subtipo de Usuario. | RNE11 lo mencionaba sin existir |
| Refugio | Se agregan Certificado_Personeria, Latitud, Longitud, Motivo_Rechazo, Fecha_Solicitud y Fecha_Resolucion. | D1, D12, RNE05, RNE06 |
| Publicacion_Animal | Se elimina Es_Cachorro. Edad pasa a Edad_Meses. Se agregan Fecha_Creacion y Fecha_Vencimiento. Tipo_Publicacion toma los valores Adopción y Perdida. Se agregan Estado_Moderacion (Visible, En revisión, Suspendida) y, para mascotas perdidas, Nombre_Mascota, Fecha_Extravio, Zona_Extravio y Contacto. | D3, D5, D16, RNE14 |
| Metodos_Pago | Pasa a llamarse Cuenta_Bancaria. | D4 |
| Necesidad_Refugio | Se elimina, con la relación "tiene". | D17 |
| Solicitud_Adopcion | Entidad nueva, entre Usuario (solicitante) y Publicacion_Animal. | D6, D14 |
| Adopcion | Entidad nueva, generada por la solicitud Adoptada. | D14, D16 |
| Seguimiento | Entidad débil nueva de Adopcion, una por adopción. | D36 |
| Reporte | Entidad nueva: un Usuario reporta a un Usuario o a una Publicacion_Animal. | D8, D18, RNE15 |

#### 15.1.2. RNE
Las restricciones dadas de baja conservan su código; las nuevas continúan desde RNE16.

| Código | Restricción | Estado |
|---|---|---|
| RNE01 | Toda publicación de mascota en adopción debe tener entre 1 y 6 fotos asociadas. Una de mascota perdida, entre 1 y 3. Cada fotografía es JPG, PNG o WEBP y pesa hasta 8 MB. | Modificada (D66) |
| RNE02 | El estado de una publicación solo puede cambiar según las transiciones de 13.2.2. Una publicación de tipo Adopción toma los estados En adopción, Adoptado y Archivado; una de tipo Perdida toma Perdida, Encontrada y Archivada. | Modificada (D10, D16, D26) |
| RNE03 | Solo el publicador dueño de la publicación o el Administrador pueden modificar o dar de baja esa publicación. | Vigente |
| RNE04 | Un Refugio solo puede publicar animales o dar de alta cuentas bancarias si su Estado_Validacion = "Validado". | Modificada (D17) |
| RNE05 | Si Estado_Validacion = "Rechazado", el campo Motivo_Rechazo es obligatorio; en cualquier otro estado debe ser nulo. | Vigente |
| RNE06 | La validación de un refugio debe resolverse dentro de los 5 días hábiles posteriores a Fecha_Solicitud. | Vigente |
| RNE07 | Dada de baja. Motivo: "cachorros" es un filtro por edad; se elimina Es_Cachorro. | Dada de baja (D5) |
| RNE08 | Dada de baja. Motivo: se elimina el buscador de refugios por radio. | Dada de baja (D11) |
| RNE09 | Dada de baja. Motivo: la plataforma no procesa donaciones ni cobra comisión. | Dada de baja (D4) |
| RNE10 | Un mismo ID_Usuario no puede existir simultáneamente en más de una de las tablas Particular, Refugio y Administrador. | Modificada |
| RNE11 | Todo ID_Usuario dado de alta en Usuario debe tener una fila correspondiente en Particular, Refugio o Administrador. | Vigente |
| RNE12 | Edad_Meses no puede ser negativa. Tamaño toma un valor de una lista cerrada (Pequeño, Mediano, Grande). Numero_Cuenta se almacena como texto. Edad_Meses no tiene máximo. Largos: Nombre y Apellido de 2 a 50 caracteres, Raza de 2 a 50, Ubicacion_Aproximada y Zona_Extravio de 3 a 100 y Email hasta 255. Telefono y Contacto tienen 8 o 9 dígitos. | Modificada |
| RNE13 | Las contraseñas se almacenan como hash, nunca en texto plano (RNF:0007). | Modificada |
| RNE14 | Fecha_Creacion de una publicación no puede ser anterior a la Fecha_Alta del usuario que la crea, ni posterior a la fecha actual. | Vigente |
| RNE15 | Una publicación con 3 reportes de usuarios distintos pasa a Estado_Moderacion = En revisión. Un Particular con 5 reportes, o con 3 publicaciones suspendidas, pasa a Estado_Usuario = En revisión; con 10 reportes pasa a Inhabilitado. Solo cuentan reportes de usuarios distintos que el Administrador no desestimó. Un Refugio con 3 reportes pasa a Estado_Validacion = Suspendido. | Modificada (D31) |
| RNE16 | Una publicación de tipo Perdida puede crearla un Particular o un Refugio validado. | Nueva (D11, D49) |
| RNE17 | Fecha_Vencimiento de una publicación de tipo Perdida es la fecha de creación o de la última renovación más 30 días; en una de tipo Adopción es nula. | Nueva (D16) |
| RNE18 | Una solicitud solo puede crearse sobre una publicación de tipo Adopción en estado En adopción, y el solicitante no puede ser su publicador. | Nueva (D14) |
| RNE19 | Una publicación en estado Adoptado tiene exactamente una solicitud Adoptada; al aceptarla, las demás solicitudes Pendientes pasan a Rechazada. | Nueva (D25) |
| RNE20 | Cada adopción tiene exactamente un seguimiento, con Fecha_Aviso igual a Fecha_Adopcion más 15 días y Fecha_Limite igual a Fecha_Aviso más 7 días. | Nueva (D36) |
| RNE21 | Un usuario con un seguimiento cuya Fecha_Limite venció sin evidencia enviada, o con la evidencia rechazada y sin reenviar, no puede crear solicitudes de adopción ni publicaciones. | Nueva (D36, D71) |
| RNE22 | Un reporte se refiere a exactamente un usuario o una publicación, y el reportante no puede reportarse a sí mismo ni reportar dos veces lo mismo. | Nueva (D18) |
| RNE23 | La CI de un Particular es única, se guarda solo con números y tiene 7 u 8 dígitos con dígito verificador válido. | Nueva (D64, D65) |
| RNE24 | Estado_Validacion de un Refugio toma los valores Pendiente de validación, Validado, Rechazado, Suspendido y Dado de baja. Un refugio Dado de baja no puede iniciar sesión y todas sus publicaciones quedan en Archivado. | Nueva (D69) |

### 15.2. Modelo lógico

#### 15.2.1. Pasaje a tablas
La clave primaria de cada tabla figura primero, en negrita.

- Usuario(ID_Usuario, Email, Contraseña, Tipo_Usuario, Estado_Usuario, Fecha_Alta, Email_Verificado). Email_Verificado guarda la fecha de confirmación del correo; vacío significa sin confirmar

- Particular(ID_Usuario, CI, Nombre, Apellido, Telefono) FK: ID_Usuario (Usuario). CI es clave candidata, con índice único (RNE23)

- Administrador(ID_Usuario) FK: ID_Usuario (Usuario)

- Refugio(ID_Usuario, Nombre_Refugio, Certificado_Personeria, Comprobante_Domicilio, Ubicacion, Latitud, Longitud, Estado_Validacion, Motivo_Rechazo, Fecha_Solicitud, Fecha_Resolucion, URL_Foto) FK: ID_Usuario (Usuario). Estado_Validacion: Pendiente de validación, Validado, Rechazado, Suspendido o Dado de baja (RNE24)

- Refugio_Datos_Contacto(ID_Usuario, Dato_Contacto) FK: ID_Usuario (Refugio)

- Cuenta_Bancaria(ID_Cuenta_Banco, ID_Usuario, Banco, Tipo_Cuenta, Numero_Cuenta, Titular) FK: ID_Usuario (Refugio). Tipo_Cuenta es texto libre

- Publicacion_Animal(ID_Publicacion, ID_Usuario, Tipo_Publicacion, Especie, Raza, Sexo, Edad_Meses, Tamaño, Estado_Salud, Estado_Esterilizacion, Ubicacion_Aproximada, Descripcion, Estado_Publicacion, Estado_Moderacion, Fecha_Creacion, Fecha_Vencimiento, Nombre_Mascota, Fecha_Extravio, Zona_Extravio, Contacto) FK: ID_Usuario (Usuario). Fecha_Vencimiento y los cuatro últimos atributos solo se usan en el tipo Perdida; Estado_Salud y Estado_Esterilizacion, solo en el tipo Adopción. Estado_Esterilizacion: Esterilizado o No esterilizado. Contacto es un teléfono de 8 o 9 dígitos (D86).

- Foto_Animal(ID_Foto, ID_Publicacion, URL_Foto) FK: ID_Publicacion (Publicacion_Animal)

- Solicitud_Adopcion(ID_Solicitud, ID_Publicacion, ID_Usuario, Fecha_Solicitud, Estado_Solicitud) FK: ID_Publicacion (Publicacion_Animal), ID_Usuario (Usuario)

- Adopcion(ID_Adopcion, ID_Solicitud, Fecha_Adopcion) FK: ID_Solicitud (Solicitud_Adopcion).

- Seguimiento(ID_Adopcion, Fecha_Aviso, Fecha_Limite, URL_Foto, Fecha_Envio, Estado_Evidencia) FK: ID_Adopcion (Adopcion). Estado_Evidencia: Pendiente, Enviada, Aprobada o Rechazada.

- Reporte(ID_Reporte, ID_Usuario_Reportante, ID_Usuario_Reportado, ID_Publicacion_Reportada, Motivo, Fecha_Reporte, Estado_Reporte, Medida) FK: ID_Usuario_Reportante (Usuario), ID_Usuario_Reportado (Usuario), ID_Publicacion_Reportada (Publicacion_Animal). Estado_Reporte: Pendiente, Desestimado o Confirmado. Medida: Aviso, Suspensión o Baja lógica
Se eliminó la tabla Necesidad_Refugio (D17).

El modelo físico en MySQL, con estas restricciones, está en `docs/base-de-datos/adoptauy.sql`.
