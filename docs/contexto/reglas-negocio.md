# Reglas de negocio por módulo

> Extraído del Documento integrador 2.1 de AdoptaUY (rev. 18/10/2026). Si este archivo y el documento difieren, manda el documento; avisá la diferencia.

### 13.2. Módulos

#### 13.2.1. Usuarios y acceso

- El Usuario Invitado se registra como Particular con cédula de identidad, nombre, apellido, teléfono, correo y contraseña. La cédula se ingresa solo con números, sin puntos ni guion, tiene 7 u 8 dígitos con dígito verificador válido y no puede repetirse entre cuentas. El nombre y el apellido tienen entre 2 y 50 caracteres; el teléfono, 8 dígitos (fijo) o 9 (celular), solo números; el correo, hasta 255 caracteres, y la contraseña, entre 8 y 72. El sistema envía un correo para confirmar la dirección.

- El aspirante a Refugio envía una solicitud de registro con certificado de personería jurídica u organización sin fines de lucro, comprobante de domicilio del predio y datos de contacto verificables.

- El Administrador resuelve la solicitud en un plazo máximo de 5 días hábiles.

- Los usuarios registrados inician sesión con correo y contraseña. Desde el Sprint 5 el ingreso exige el correo confirmado; ante un correo sin confirmar, una cuenta inhabilitada o dada de baja, o un refugio que no está Validado, el sistema muestra un mensaje genérico que no revela si el correo existe.
Estados del refugio y transiciones válidas:

| Desde | Hacia | Quién | Condición |
|---|---|---|---|
| Pendiente de validación | Validado | Administrador | Documentación correcta; se crea el usuario Refugio y se envía el correo de alta |
| Pendiente de validación | Rechazado | Administrador | Motivo de rechazo obligatorio |
| Validado | Suspendido | Administrador | Reportes o incumplimientos posteriores |
| Suspendido | Validado | Administrador | Levantamiento de la suspensión |
| Validado | Dado de baja | Administrador | Baja lógica (RF:0006): no puede iniciar sesión, sale del mapa y del listado, y sus publicaciones pasan a Archivado. Es definitivo |
| Suspendido | Dado de baja | Administrador | Se confirma la baja después de la suspensión; mismas consecuencias |

#### 13.2.2. Publicaciones
Mascotas en adopción (particulares y refugios):

- Campos obligatorios: especie, raza (o "mestizo"), sexo, edad estimada, tamaño, estado de salud, estado de esterilización, ubicación aproximada y entre 1 y 6 fotografías en formato JPG, PNG o WEBP, de hasta 8 MB cada una. No se exige certificado veterinario.

- Se publican de forma automática y quedan sujetas al sistema de reportes.

- La consulta pública permite filtrar por fecha, edad, especie, raza y sexo. "Cachorros" no es una sección: se obtiene con el filtro de edad, que considera cachorro a un animal de menos de 12 meses de edad estimada.

| Desde | Hacia | Quién | Condición |
|---|---|---|---|
| En adopción | Adoptado | Publicante | Selecciona al adoptante entre los solicitantes. Es definitivo |
| En adopción | Archivado | Publicante o Administrador | Retiro de la publicación |
| Archivado | En adopción | Publicante | Reactivación |

Mascotas perdidas (particulares y refugios):

- Campos: nombre de la mascota, especie, raza, sexo, características, fecha del extravío, zona del extravío, contacto del publicante y entre 1 y 3 fotografías, con el mismo formato y tamaño máximo que las de adopción.

- El contacto es un número de teléfono de 8 o 9 dígitos y es público, porque la publicación la ve cualquier visitante.

- Vigencia de 30 días desde la creación o la última renovación.

- Consulta pública, sin necesidad de registro.

| Desde | Hacia | Quién | Condición |
|---|---|---|---|
| Perdida | Encontrada | Publicante | La mascota apareció |
| Perdida | Archivada | Sistema | Pasaron 30 días sin renovación |
| Perdida | Archivada | Publicante o Administrador | Retiro de la publicación |
| Archivada | Perdida | Publicante | Renueva: vence 30 días después de la renovación |
| Perdida | Perdida | Publicante | Renueva antes del vencimiento: vence 30 días después de la renovación |

#### 13.2.3. Adopción y seguimiento

- Un usuario registrado solicita la adopción desde la publicación. Al enviar, acepta compartir su nombre, teléfono y correo con el publicante.

- Una publicación En adopción puede recibir varias solicitudes sin cambiar de estado. El publicante ve la lista de solicitantes y se comunica con ellos por fuera de la plataforma; ese contacto no se registra en el sistema.

- Cuando la adopción se concreta, el publicante elige al adoptante de la lista y la publicación pasa a Adoptado.

| Solicitud: desde | Hacia | Quién | Condición |
|---|---|---|---|
| Pendiente | Adoptada | Publicante | La elige al concretar la adopción |
| Pendiente | Rechazada | Sistema | El publicante eligió a otro solicitante |
| Pendiente | Rechazada | Publicante | Rechazo individual |

Adoptada y Rechazada son estados finales.
Seguimiento posterior a la adopción:

- A los 15 días de la fecha de adopción el sistema avisa por correo al adoptante, que tiene 7 días para enviar una fotografía del animal. Es el único aviso. Los plazos se cuentan en días corridos y la tarea programada que los controla corre todos los días a las 03:00.

- El publicante revisa la fotografía. Si la rechaza, el adoptante debe enviar otra; si el rechazo ocurre después del día 22, la cuenta queda restringida hasta el nuevo envío. Si no la revisa, la fotografía se considera presentada desde su envío.

- Si al vencer los 7 días (día 22 desde la adopción) el adoptante no envió la fotografía, su cuenta queda restringida: no puede solicitar adopciones ni crear publicaciones de ningún tipo. Solo puede enviar la fotografía, y al hacerlo la cuenta se reactiva.

#### 13.2.4. Refugios y donaciones

- Los refugios validados se muestran en un mapa de Montevideo y en un listado con nombre, ubicación y contacto. El mapa muestra además los refugios de Montevideo que no están registrados en la plataforma, tomados de un archivo de datos públicos que mantiene el equipo; se distinguen con la leyenda «No registrado en AdoptaUY» y no tienen perfil ni cuentas bancarias. El mapa es solo de consulta.

- Cada refugio validado puede publicar en su perfil una o más cuentas bancarias (banco, tipo de cuenta, número y titular) para recibir donaciones por transferencia.

- La plataforma no procesa pagos, no cobra comisión y no registra las donaciones. Las donaciones en especie se coordinan por contacto directo con el refugio.

#### 13.2.5. Reportes y moderación

- Un usuario registrado puede reportar una publicación, a un particular o a un refugio, eligiendo un motivo de la lista que corresponde.

- El Administrador ve los reportes en su panel y resuelve cada uno: lo desestima o aplica un aviso, una suspensión o la baja lógica.

- Al alcanzarse un umbral, el sistema aplica una medida automática hasta que el Administrador resuelva.

| Objeto reportado | Umbral | Medida automática | Resolución del Administrador |
|---|---|---|---|
| Publicación | 3 reportes | Pasa a revisión y se oculta de la consulta pública | La restituye o la suspende |
| Particular | 5 reportes, o 3 publicaciones suspendidas | Revisión de cuenta | Baja lógica si confirma los reportes |
| Particular | 10 reportes | Cuenta inhabilitada | Puede restituirla si desestima los reportes |
| Refugio | 3 reportes | Estado Suspendido | Levanta la suspensión o da de baja al refugio |

En todos los casos solo cuentan los reportes de usuarios distintos, y no cuentan los que el Administrador desestimó. El refugio se suspende con menos reportes que un particular (3 frente a 5) porque difunde datos bancarios para donaciones. Se mantiene de D27 la inhabilitación automática del particular a los 10 reportes (D42).
La revisión de una publicación se modela como una marca de moderación (Visible, En revisión, Suspendida) independiente de su estado, para conservar los tres estados definidos en 13.2.2.
Motivos de reporte:

| Publicación | Particular | Refugio |
|---|---|---|
| Publicación falsa o engañosa (el animal no existe o los datos no coinciden) | Usuario malicioso o fraudulento | Refugio no legítimo (documentación falsa) |
| Publicación duplicada o spam | Suplantación de identidad | La cuenta bancaria publicada no pertenece al refugio |
| Animal ya adoptado o no disponible | Acoso o mensajes abusivos | Datos de ubicación o contacto falsos |
| Fotos o contenido inapropiado | Cuenta falsa o duplicada | Maltrato animal o condiciones inadecuadas |
| Venta de animales (la plataforma es solo para adopción) | Otro (con campo de texto) | Suplantación de otro refugio |
| Sospecha de maltrato o criadero ilegal |  | Otro (con campo de texto) |
| Datos de contacto falsos o incorrectos |  |  |
| Otro (con campo de texto) |  |  |

#### 13.2.6. Notificaciones por correo

| Evento | Destinatario |
|---|---|
| Registro de un Particular | El Particular, para confirmar su dirección |
| Solicitud de registro de un refugio recibida | El refugio solicitante |
| Alta definitiva de un refugio | El refugio |
| Solicitud de adopción recibida | El publicante |
| Aviso de seguimiento del día 15 | El adoptante |
