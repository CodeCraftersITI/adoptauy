# Actores y requisitos

> Extraído del Documento integrador 2.1 de AdoptaUY (rev. 18/10/2026). Si este archivo y el documento difieren, manda el documento; avisá la diferencia.

### 13.1. Actores del sistema

| Actor | Descripción |
|---|---|
| Usuario Invitado | Visitante sin cuenta. Puede consultar las publicaciones de mascotas en adopción y de mascotas perdidas, consultar el mapa y el listado de refugios validados, registrarse como Particular y enviar la solicitud de registro de un Refugio. No puede publicar ni solicitar adopciones. |
| Particular | Persona física registrada en el sistema. Publica mascotas en adopción y mascotas perdidas, solicita adopciones, gestiona las solicitudes que recibe en sus publicaciones, presenta la evidencia de seguimiento cuando adopta y la revisa cuando es publicante, y reporta usuarios y publicaciones. |
| Refugio | Organización cuya solicitud de registro fue validada por el Administrador. Publica mascotas en adopción y mascotas perdidas, no solicita adopciones, gestiona las solicitudes que recibe, revisa la evidencia de seguimiento de sus adopciones, publica los datos de su cuenta bancaria para recibir donaciones por transferencia y figura en el mapa de refugios. |
| Administrador | Responsable de validar o rechazar las solicitudes de registro de refugios en un plazo máximo de 5 días hábiles, moderar las publicaciones, resolver los reportes y aplicar avisos, suspensiones o bajas de cuentas. |

Adoptante y publicante no son actores, sino roles que un usuario registrado asume en una adopción concreta. El donante no se modela como actor, ya que la donación se realiza fuera de la plataforma.
Reportar exige tener cuenta, y pueden hacerlo tanto el Particular como el Refugio.

### 13.3. Requisitos
El catálogo tiene 63 requisitos funcionales (RF:0001 a RF:0063): 57 vigentes (37 del Hito 1 y 20 nuevos) y 6 dados de baja. No se renumeró ningún requisito. En todo el documento los códigos se escriben con el formato RF:0000 y RNF:0000.

#### 13.3.1. Requisitos funcionales

| Código | Nombre | Descripción | Estado |
|---|---|---|---|
| RF:0001 | Administrador Baja Publicaciones | El usuario Administrador puede dar de baja a una publicación en el sistema filtrando su búsqueda por fecha, hora, tipo animal y usuario. Criterio de aceptación: la baja pasa la publicación a Archivado; la suspensión por reportes se registra aparte, como marca de moderación (13.2.5). | Modificado (D68) |
| RF:0002 | Administrador Consulta Publicaciones | El usuario Administrador puede realizar una consulta de publicaciones en el sistema filtrando su búsqueda por fecha, hora, tipo animal y usuario. | Vigente |
| RF:0003 | Administrador Modificación Publicaciones | El usuario Administrador puede modificar una publicación en el sistema modificando sus atributos. | Vigente |
| RF:0004 | Administrador Listado Publicaciones | El usuario Administrador puede realizar un listado de publicaciones en el sistema. | Vigente |
| RF:0005 | Administrador Alta Refugio | El usuario Administrador puede dar de alta a un usuario Refugio a partir de una solicitud de registro (RF:0034) cuya documentación validó. Criterio de aceptación: el refugio pasa a Validado, puede iniciar sesión y recibe el correo de alta (RF:0063). | Modificado (D1, D19) |
| RF:0006 | Administrador Baja Refugio | El usuario Administrador puede dar de baja a un usuario Refugio filtrando su búsqueda por nombre. Criterio de aceptación: la baja es lógica; el refugio pasa a Dado de baja, no puede iniciar sesión, deja de figurar en el mapa y en el listado, y sus publicaciones pasan a Archivado. | Modificado (D69) |
| RF:0007 | Administrador Modificación Refugio | El usuario Administrador puede modificar un usuario Refugio registrado en el sistema, editando su Nombre, Datos de contacto, Ubicación y Estado de validación. | Modificado (C-002, devolución) |
| RF:0008 | Administrador Consulta Refugios | El usuario Administrador puede realizar consulta de refugios en el sistema filtrando por Nombre y Ubicación. | Vigente |
| RF:0009 | Administrador Listado Refugios | El usuario Administrador puede realizar un listado de Refugios en el sistema. | Vigente |
| RF:0010 | Administrador Baja Usuario Particular | El usuario Administrador puede realizar una baja lógica de un usuario Particular registrado. | Modificado (D23) |
| RF:0011 | Administrador Consulta publicaciones de Particulares | El usuario Administrador puede realizar consulta de las publicaciones realizadas por los usuarios Particular. | Modificado (D23) |
| RF:0012 | Administrador Consulta publicaciones de Refugios | El usuario Administrador puede realizar consulta de las publicaciones realizadas por los usuarios Refugio. | Vigente |
| RF:0013 | Administrador Modificación Particular | El usuario Administrador puede modificar cada uno de los atributos de un Particular. | Modificado (D23) |
| RF:0014 | Administrador Listado Particulares | El usuario Administrador puede listar los Particulares del sistema. | Modificado (D23) |
| RF:0015 | Persona Registro Persona | Dado de baja. Motivo: duplicaba a RF:0039; su contenido se unificó en ese requisito. | Dado de baja (C-001, D20) |
| RF:0016 | Particular Modificación Particular | El usuario Particular puede modificar sus atributos Nombre, Apellido, Teléfono y Contraseña. | Modificado (D23) |
| RF:0017 | Particular Alta Publicación | El usuario Particular puede dar de alta una publicación de mascota en adopción con los campos obligatorios de 13.2.2. Criterio de aceptación: el sistema rechaza el alta si falta un campo obligatorio o si hay menos de 1 o más de 6 fotografías, si alguna no es JPG, PNG o WEBP o supera los 8 MB, o si la raza no tiene entre 2 y 50 caracteres o la ubicación entre 3 y 100; en caso contrario la publicación queda En adopción y visible en la consulta pública. | Modificado (D3, D10, D23, D66, D80) |
| RF:0018 | Particular Baja Publicación | El usuario Particular puede dar de baja una publicación propia. La baja la pasa al estado Archivado o Archivada. | Modificado (D10, D23) |
| RF:0019 | Particular Modificación Publicación | El usuario Particular puede modificar una publicación propia. | Modificado (D23) |
| RF:0020 | Particular Listado de Publicaciones | El usuario Particular puede listar sus propias publicaciones, de ambos tipos, con su estado. | Modificado (D3, D23) |
| RF:0021 | Particular Consulta Publicación | El usuario Particular puede consultar publicaciones filtrando por Fecha, Edad, Especie, Raza y Sexo. Criterio de aceptación: cada filtro es un control propio, los filtros se combinan y el de edad permite ver solo cachorros, es decir, animales de menos de 12 meses de edad estimada. | Modificado (D5, D23) |
| RF:0022 | Inicio de Sesión | Los usuarios podrán iniciar sesión en el sistema con Mail y Contraseña. Criterio de aceptación: desde el Sprint 5 se rechaza el ingreso con correo sin confirmar (RF:0043), con una cuenta inhabilitada o dada de baja, o con un refugio que no está Validado, siempre con el mismo mensaje genérico, que no revela si el correo existe. | Modificado (D67) |
| RF:0023 | Cerrar Sesión | Los usuarios podrán cerrar sesión una vez hayan iniciado sesión en el sistema. | Vigente |
| RF:0024 | Refugio Alta Publicación | El usuario Refugio puede dar de alta una publicación de mascota en adopción, con los mismos campos y criterio de aceptación que RF:0017. | Modificado (D3, D10) |
| RF:0025 | Refugio Baja Publicación | El usuario Refugio puede dar de baja una publicación en el sistema. | Vigente |
| RF:0026 | Refugio Modificación Publicación | El usuario Refugio puede modificar una publicación realizada en el sistema. | Vigente |
| RF:0027 | Refugio Listado Publicaciones | El usuario Refugio puede listar Publicaciones en el sistema. | Vigente |
| RF:0028 | Refugio Consulta publicaciones | El usuario Refugio puede realizar una consulta filtrando por Fecha, Edad, Especie, Raza y Sexo, con el mismo criterio que RF:0021. | Modificado (D5) |
| RF:0029 | Refugio Modificación Refugio Perfil | El usuario Refugio puede modificar Nombre, Contacto, Ubicación, Foto y Contraseña de sí mismo en el sistema. | Vigente |
| RF:0030 | Refugio Alta Insumos | Dado de baja. Motivo: la plataforma no gestiona donaciones en especie; se coordinan por contacto directo con el refugio. | Dado de baja (C-003, D17) |
| RF:0031 | Refugio Baja Insumo | Dado de baja. Mismo motivo que RF:0030. | Dado de baja (C-003, D17) |
| RF:0032 | Refugio Modificación Insumo | Dado de baja. Mismo motivo que RF:0030. | Dado de baja (C-003, D17) |
| RF:0033 | Refugio Listado Insumo | Dado de baja. Mismo motivo que RF:0030. | Dado de baja (C-003, D17) |
| RF:0034 | Refugio Registro | El aspirante a usuario Refugio puede enviar una solicitud de registro con nombre, documentación (13.2.1), ubicación y datos de contacto. Criterio de aceptación: la solicitud queda Pendiente de validación, aparece en el panel del Administrador y el aspirante recibe el correo de recepción (RF:0063). | Modificado (D1, D19) |
| RF:0035 | Refugio Alta Cuenta bancaria | El usuario Refugio puede dar de alta una cuenta bancaria para recibir donaciones por transferencia, con banco, tipo de cuenta, número de cuenta y titular. La plataforma no procesa pagos. | Modificado (D4) |
| RF:0036 | Refugio Baja Cuenta bancaria | El usuario Refugio puede dar de baja una cuenta bancaria publicada. | Modificado (D4) |
| RF:0037 | Refugio Listado Cuentas bancarias | El usuario Refugio puede realizar un listado de las cuentas bancarias del refugio. | Modificado (D4) |
| RF:0038 | Consulta Mapa Refugios | Los usuarios y no usuarios podrán consultar un mapa de Montevideo con los refugios validados y con los refugios no registrados en la plataforma. Criterio de aceptación: los validados enlazan a su perfil (RF:0062); los no registrados muestran la leyenda «No registrado en AdoptaUY» y no ofrecen perfil ni datos bancarios; un refugio Pendiente, Rechazado, Suspendido o Dado de baja no aparece como validado. | Modificado (D12, D55, D56) |
| RF:0039 | Usuario Invitado Registrarse | El Usuario Invitado puede registrarse en el sistema ingresando CI, Nombre, Apellido, Teléfono, Mail y Contraseña. Criterio de aceptación: el sistema rechaza el registro si falta algún dato obligatorio, si el Mail ya pertenece a una cuenta existente, si la CI no tiene 7 u 8 dígitos, solo números, con dígito verificador válido, si la CI ya pertenece a otra cuenta, si el Nombre o el Apellido no tienen entre 2 y 50 caracteres, si el Teléfono no tiene 8 o 9 dígitos o si la Contraseña no tiene entre 8 y 72 caracteres; en caso contrario crea la cuenta. | Modificado (C-001, D20, D64, D65, D79 a D81) |
| RF:0040 | Usuario Invitado Listar Publicación | Los usuarios invitados pueden listar las publicaciones en estado En adopción y, por separado, las de mascotas en estado Perdida. | Modificado (D3, D26) |
| RF:0041 | Usuario Invitado Consulta Publicaciones | Los usuarios invitados pueden consultar las publicaciones de mascotas en adopción filtrando por Fecha, Edad, Especie, Raza y Sexo, con el mismo criterio que RF:0021. | Modificado (D5) |
| RF:0042 | Usuario Invitado Consulta Refugios | Los usuarios invitados pueden realizar una consulta para listar los refugios validados. | Modificado (D12) |

Requisitos incorporados en la versión 2.0:

| Código | Nombre | Descripción y criterio de aceptación | Estado |
|---|---|---|---|
| RF:0043 | Confirmación de correo del Particular | Al registrarse un Particular, el sistema envía un correo con un enlace de confirmación. Criterio: al abrir el enlace la dirección queda confirmada. Sin confirmar no se puede iniciar sesión. La exigencia rige desde el Sprint 5; en la demostración del Hito 2 todavía no se aplica (sección 8). | Nuevo (D19, D67) |
| RF:0044 | Administrador Rechazo de solicitud de Refugio | El Administrador puede rechazar una solicitud de registro de refugio. Criterio: el motivo es obligatorio y la solicitud queda Rechazada. | Nuevo (D1, devolución) |
| RF:0045 | Cambio de estado de publicación en adopción | El publicante o el Administrador pueden cambiar el estado de una publicación de mascota en adopción. Criterio: solo se admiten las transiciones de la tabla de 13.2.2. | Nuevo (D10, D16) |
| RF:0046 | Alta Publicación de mascota perdida | El Particular o el Refugio puede publicar una mascota perdida. Criterio: la publicación queda en estado Perdida con vencimiento a 30 días. Campos: los de 13.2.2 para mascotas perdidas. Las fotografías cumplen el mismo formato y tamaño máximo que en RF:0017. El contacto es un teléfono de 8 o 9 dígitos y la zona del extravío tiene entre 3 y 100 caracteres. | Nuevo (D11, D66, D80, D86) |
| RF:0047 | Vigencia y cierre de mascota perdida | El sistema archiva la publicación a los 30 días si el publicante no la renueva. El publicante puede renovarla en estado Perdida o Archivada; cada renovación fija el vencimiento en la fecha de renovación más 30 días. También puede marcarla Encontrada o archivarla. Criterio: una publicación Archivada o Encontrada no aparece en la consulta pública. | Nuevo (D16, D26, D70) |
| RF:0048 | Consulta de mascotas perdidas | Los usuarios, registrados o no, pueden listar las publicaciones en estado Perdida. | Nuevo (D26) |
| RF:0049 | Solicitar adopción | Un usuario registrado puede solicitar la adopción de una mascota En adopción. Criterio: exige aceptar que su nombre, teléfono y correo se compartan con el publicante; la solicitud queda Pendiente; se rechaza el envío si la cuenta está restringida (RF:0058) o si el usuario es un Refugio. No se puede solicitar una publicación propia ni tener dos solicitudes pendientes sobre la misma. | Nuevo (D6, D14) |
| RF:0050 | Consulta de solicitudes recibidas | El publicante puede listar las solicitudes de cada una de sus publicaciones, con nombre, teléfono, correo, fecha y estado de cada solicitante. Criterio: solo el publicante y el Administrador ven esos datos. | Nuevo (D6, D14) |
| RF:0051 | Rechazo de una solicitud | El publicante puede rechazar una solicitud Pendiente. Criterio: la solicitud pasa a Rechazada y deja de figurar entre las pendientes. | Nuevo (D38) |
| RF:0052 | Concretar adopción | El publicante selecciona al adoptante en la lista de solicitantes. Criterio: la publicación pasa a Adoptado, la solicitud elegida a Adoptada, las demás a Rechazada, y se registra la fecha de adopción. La adopción es definitiva: la publicación no vuelve a En adopción. | Nuevo (D14, D25) |
| RF:0053 | Revertir adopción | Dado de baja. Motivo: la adopción es definitiva y la publicación no vuelve a En adopción. | Dado de baja (D37) |
| RF:0054 | Consulta de solicitudes enviadas | El usuario puede listar las solicitudes de adopción que envió, con su estado. | Nuevo (D14) |
| RF:0055 | Aviso de seguimiento | A los 15 días de la fecha de adopción el sistema solicita al adoptante una fotografía del animal y le informa que tiene 7 días para enviarla. Criterio: la tarea programada corre una vez por día a las 03:00 y cuenta días corridos desde la fecha de adopción; el aviso se genera el día 15 y se envía por correo (RF:0063). | Nuevo (D36, D71) |
| RF:0056 | Envío de evidencia de seguimiento | El adoptante puede enviar la fotografía solicitada, también mientras su cuenta está restringida. Criterio: la evidencia queda Enviada y visible para el publicante. | Nuevo (D24) |
| RF:0057 | Revisión de evidencia de seguimiento | El publicante puede aprobar o rechazar la fotografía. Criterio: si la rechaza, el adoptante debe enviar otra; si no la revisa, la evidencia se considera presentada desde su envío; solo un rechazo explícito la anula. | Nuevo (D24, D72) |
| RF:0058 | Restricción de la cuenta por seguimiento incumplido | Si a los 7 días del aviso (día 22 desde la adopción) el adoptante no envió la fotografía, el sistema le impide solicitar adopciones y crear publicaciones de cualquier tipo. Criterio: con la cuenta restringida el usuario solo puede enviar la fotografía; al enviarla, la restricción se levanta. Si el publicante rechaza la fotografía después del día 22, la evidencia vuelve a quedar pendiente y la cuenta se restringe hasta que el adoptante envíe otra. | Nuevo (D36, D71) |
| RF:0059 | Reportar usuario o publicación | Un usuario registrado puede reportar una publicación, a un particular o a un refugio, eligiendo un motivo de la lista de 13.2.5. Criterio: el reporte queda Pendiente en el panel del Administrador. Un mismo usuario no puede reportar dos veces lo mismo. | Nuevo (D8, D18) |
| RF:0060 | Administrador Resolución de reportes | El Administrador puede listar los reportes y resolver cada uno: desestimarlo o aplicar un aviso, una suspensión o la baja de la cuenta. Si confirma un reporte sobre una publicación, la suspende; si lo desestima, la publicación vuelve a ser visible. Criterio: queda registrada la medida y la fecha. | Nuevo (D18) |
| RF:0061 | Umbrales de reportes | El sistema aplica los umbrales de 13.2.5: oculta una publicación con 3 reportes de usuarios distintos, envía a revisión la cuenta de un particular con 5 reportes o 3 publicaciones suspendidas, la inhabilita con 10 reportes, y suspende a un refugio con 3 reportes. Criterio: la medida se aplica al registrarse el reporte que alcanza el umbral. | Nuevo (D27) |
| RF:0062 | Consulta del perfil público del refugio | Los usuarios, registrados o no, pueden ver el perfil de un refugio validado con su nombre, ubicación, contacto y cuentas bancarias para donaciones. | Nuevo (D4, D17) |
| RF:0063 | Notificaciones por correo | El sistema envía un correo en cada evento de la tabla de 13.2.6. Criterio: cada evento genera un único correo al destinatario indicado. | Nuevo (D19) |

#### 13.3.2. Requisitos no funcionales

| Código | Nombre | Requisito y forma de verificación | Estado |
|---|---|---|---|
| RNF:0001 | Tiempo de respuesta de la base de datos | El 95 % de las consultas a la base de datos responde en menos de 2 segundos con 15 usuarios concurrentes. Verificación: prueba de carga. | Modificado |
| RNF:0002 | Disponibilidad del sistema | El sistema está disponible las 24 horas, con una disponibilidad mensual mínima del 99 %. Verificación: registro de monitoreo de Zabbix sobre el servidor local durante el Sprint 6. | Modificado (D74) |
| RNF:0003 | Compatibilidad del sistema | El sistema funciona en las dos últimas versiones de Chrome, Firefox, Edge y Safari, en escritorio y en celular, con anchos de pantalla desde 360 px. Verificación: lista de pruebas por navegador y ancho. | Modificado (D13) |
| RNF:0004 | Fiabilidad del sistema | Bajo la carga de RNF:0006 sostenida durante 10 minutos, el sistema cumple RNF:0001 y RNF:0005 sin errores del servidor. Verificación: prueba de carga. | Modificado |
| RNF:0005 | Rendimiento login del sistema | El 95 % de las autenticaciones responde en menos de 2 segundos con 15 usuarios concurrentes. Verificación: prueba de carga. | Modificado |
| RNF:0006 | Soporte del sistema | El sistema soporta como mínimo 15 usuarios concurrentes sin incumplir RNF:0001 ni RNF:0005. | Modificado |
| RNF:0007 | Almacenamiento de contraseñas | Las contraseñas se almacenan únicamente como hash con bcrypt, el algoritmo por defecto de Laravel; nunca en texto plano ni con cifrado reversible. Verificación: inspección de la tabla de usuarios. La protección de los demás datos personales se define en RNF:0013. | Modificado |
| RNF:0008 | Usabilidad del sistema | Un usuario nuevo completa el alta de una publicación en menos de 3 minutos sin ayuda. Verificación: prueba con 5 usuarios, de los cuales al menos 4 lo logran. | Modificado |
| RNF:0009 | Escalabilidad | Una actualización del sistema se aplica con migraciones versionadas, sin pérdida de datos y manteniendo RNF:0001. Verificación: actualización de prueba sobre una copia de la base. | Modificado |
| RNF:0010 | Tolerancia de fallos | Ante datos de entrada inválidos el sistema responde con un mensaje de error y sigue operativo. Si el servicio de mapas o de correo no responde, el resto de las funciones sigue disponible. Verificación: casos de prueba de entradas inválidas y de servicio externo caído. | Modificado |
| RNF:0011 | Seguridad de la red | Desde Internet solo son accesibles los puertos 80 y 443 del servidor web y el puerto de la VPN de acceso remoto (OpenVPN, UDP 1194); la administración se realiza por VPN. Verificación: escaneo de puertos desde fuera de la red del servidor local durante el Sprint 6. | Modificado (D74) |
| RNF:0012 | Mantenimiento | El código se versiona en Git con revisión por pull request y el despliegue se reproduce con Docker Compose desde el repositorio. Verificación: despliegue en un host limpio siguiendo la documentación. | Modificado |
| RNF:0013 | Minimización y protección de datos personales | La cédula de identidad nunca se muestra a otros usuarios. El teléfono y el correo de un solicitante solo los ve el publicante de la mascota solicitada y el Administrador. Las fotografías de seguimiento solo las ven el adoptante, el publicante y el Administrador. Todo el tráfico usa HTTPS; en el servidor local se usa un certificado autofirmado mientras no haya dominio, y las peticiones HTTP se redirigen a HTTPS. Verificación: pruebas de acceso por rol. | Nuevo (devolución, Ley 18.331, D73) |
| RNF:0014 | Respaldos | La base de datos y las imágenes se respaldan automáticamente una vez por día. Verificación: restauración de un respaldo en cada sprint. | Nuevo (devolución) |
