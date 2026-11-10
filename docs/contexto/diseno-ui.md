# Pantallas y sistema de diseño

> Extraído de la sección 14 de la V3 del Documento integrador, con los cambios de mascotas encontradas (RF:0064, RF:0065) y Continuar con Google (RF:0066, RF:0067). Si este archivo y el documento difieren, manda el documento; avisá la diferencia.
>
> Las tablas de pantallas describen lo que muestra el prototipo hoy. Lo que todavía no está en el prototipo figura en las listas «Falta prototipar»: para esas pantallas no hay diseño, así que seguí el sistema de diseño de 14.3 y preguntá si hay dudas.

## 14. Análisis y diseño

### 14.1. Introducción

El prototipo se diseñó como un sitio web adaptable que reúne a todos los actores en una misma navegación: lo que se muestra depende de quién está usando el sistema (Usuario Invitado, Particular, Refugio o Administrador). Se exploraron cuatro paletas para la página de inicio (rosa, celeste, verde menta y lavanda), cada una con una pantalla móvil de un caso de uso. Se eligió la paleta rosa, con la tipografía y los botones de la versión lavanda. El prototipo elegido es interactivo y se presenta en dos vistas: escritorio y celular. Las capturas de este capítulo se tomaron del prototipo a 1440 px de ancho para escritorio y a 390 px para celular.

Prototipo: AdoptaUY · Prototipos, tablero «Prototipo interactivo». Para recorrerlo, cualquier correo ingresa como Particular, y un correo que contenga «admin» ingresa como Administrador. Los datos que se muestran son ficticios, y los que dependen de cada usuario figuran entre corchetes, por ejemplo [Nombre Apellido].

### 14.2. Requisitos no funcionales

Los requisitos no funcionales aplican de forma transversal a todas las pantallas y se definen una sola vez, en la sección 13.3.2. El prototipo incorpora los que afectan a la interfaz:

- RNF:0003: el diseño se adapta desde 360 px de ancho. En el celular, el menú se abre con un botón y las tablas se desplazan dentro de su recuadro.
- RNF:0008: publicar una mascota es un único formulario, con los campos de cada tipo de publicación y la cantidad de fotos permitida a la vista.
- RNF:0010: los errores se muestran en español junto al campo que los causa, por ejemplo al ingresar sin correo. El listado de refugios está disponible aparte del mapa, por si este no carga.
- RNF:0013: la cédula no se muestra a nadie. El teléfono y el correo de un solicitante solo aparecen en las solicitudes recibidas del publicante y en el panel del Administrador.

### 14.3. Sistema de diseño

Todas las pantallas comparten un mismo sistema de diseño. La tipografía es Outfit, en pesos 400 (texto), 500 (navegación), 700 (botones y etiquetas) y 800 (títulos).

| Color | Nombre | Uso principal |
|---|---|---|
| #B03A69 | Rosa frambuesa | Color primario: botones principales, enlaces, opción activa del menú y marcadores de refugios validados en el mapa. |
| #3A1F2D | Ciruela oscuro | Botones secundarios, filtro y pestaña seleccionados, pie de página y estados Adoptado y Adoptada. |
| #F8D7E3 | Rosa suave | Filtros, botones suaves y estado En adopción (texto #7A1F45). |
| #FFF7FA | Rosa claro | Fondo general. Las tarjetas y los formularios van en blanco. |
| #FFF1D6 | Ámbar claro | Estado Pendiente (texto #7A4B00). |
| #FCE6D6 | Durazno | Estado Perdida (texto #7A3410). |
| #EEE3E8 | Gris rosado | Estados Rechazada y Archivado (texto #5E4652). |
| #9B1C3C | Rojo vino | Mensajes de error. |

Componentes y patrones: barra de navegación con Inicio, En adopción, Perdidas, Refugios y Sobre nosotros; tarjetas de mascota con foto, edad, sexo, tamaño, esterilización y zona; filtros en forma de botones seleccionables; formularios de una columna con la etiqueta sobre cada campo; pestañas para «Mi cuenta» y menú lateral para el panel del Administrador; etiquetas de color para cada estado; y una pantalla de confirmación al terminar cada acción. Los botones, filtros y campos principales miden al menos 44 px de alto, para tocarlos con el dedo.

En el celular, la barra de navegación se reduce al logotipo y un botón de menú, y las grillas y los formularios pasan a una columna (Figuras 1 y 2).

*Figura 1. Vista celular: inicio, mascotas en adopción, detalle y solicitud de adopción.*

*Figura 2. Vista celular: publicar mascota, seguimiento, solicitudes recibidas y panel del Administrador.*

### 14.4. Actor: Usuario Invitado

El Usuario Invitado es cualquier visitante sin cuenta. Puede explorar el catálogo público, registrarse como Particular o enviar la solicitud de registro de un refugio. Requisitos: RF:0022, RF:0034, RF:0038 a RF:0043, RF:0048, RF:0062, RF:0065 y RF:0066.

| Pantalla | Requisitos | Qué muestra el prototipo |
|---|---|---|
| Inicio (Figura 3) | RF:0038, RF:0040, RF:0048 | Tres accesos (Quiero adoptar, Perdí o encontré una mascota, Ayudar a un refugio), las mascotas recién publicadas con filtro rápido (Todos, Perros, Gatos, Cachorros), los pasos de la adopción y el seguimiento, las últimas mascotas perdidas y el mapa de refugios. |
| Mascotas en adopción (Figura 4) | RF:0040, RF:0041 | Búsqueda por raza o barrio y filtros de especie, edad (Cachorros, menos de 12 meses; 1 a 3 años; más de 3 años) y sexo, con la cantidad de resultados. Si ningún resultado coincide, ofrece limpiar los filtros. |
| Detalle de la mascota (Figura 5) | RF:0040 | Especie, raza, edad, ubicación aproximada, sexo, tamaño, esterilización, estado de salud y publicante. Los botones Solicitar adopción y Reportar piden iniciar sesión. |
| Mascotas perdidas (Figura 6) | RF:0048 | Nombre, fecha y zona del extravío y características. El teléfono de contacto se muestra al tocar el botón. |
| Refugios (Figura 7) | RF:0038, RF:0042 | Mapa de Montevideo (OpenStreetMap) con marcadores rosados para los refugios validados y blancos para los no registrados, que se muestran solo con fines informativos, y el listado de refugios. |
| Perfil del refugio (Figura 8) | RF:0062 | Nombre, zona y contacto; cuentas bancarias con un botón para copiar el número y el aviso de que la plataforma no procesa pagos ni cobra comisión; mascotas en adopción del refugio. |
| Ingresar (Figura 9) | RF:0022 | Correo y contraseña. Si se llegó desde una acción que requiere sesión, avisa que hay que iniciarla y, al ingresar, vuelve a esa acción. |
| Crear cuenta (Figuras 10 y 11) | RF:0039, RF:0043, RF:0034 | Selector Particular o Refugio. Particular: cédula sin puntos ni guion, nombre, apellido, teléfono, correo y contraseña; al terminar indica que se envió el correo de confirmación. Refugio: nombre, contacto, ubicación, certificado de personería, comprobante de domicilio, correo y contraseña; al terminar indica que el Administrador resuelve en un máximo de 5 días hábiles. |
| Sobre nosotros (Figura 12) | — | Misión de la plataforma, cómo se cuida la adopción responsable, el equipo y el contacto. |

*Figura 3. Inicio.*

*Figura 4. Mascotas en adopción, con búsqueda y filtros.*

*Figura 5. Detalle de la mascota.*

*Figura 6. Mascotas perdidas.*

*Figura 7. Refugios: mapa y listado.*

*Figura 8. Perfil público del refugio.*

*Figura 9. Ingresar.*

*Figura 10. Crear cuenta: Particular.*

*Figura 11. Crear cuenta: solicitud de registro de Refugio.*

*Figura 12. Sobre nosotros.*

Falta prototipar o definir:

- El filtro por fecha de publicación que pide RF:0041.
- El enlace «Olvidé mi contraseña»: ningún requisito lo cubre. Hay que agregar un requisito o quitarlo del prototipo.
- La casilla de aceptación de los términos y condiciones y la política de privacidad, cuyos textos no están redactados.
- El botón Continuar con Google en Ingresar y en Crear cuenta: Particular (figuras 9 y 10), y la pantalla Completar datos, que pide la CI y el teléfono (RF:0066).

### 14.5. Actor: Particular

El Particular es un usuario registrado que publica mascotas en adopción, perdidas y encontradas, solicita adopciones y cumple el seguimiento. Requisitos: RF:0016 a RF:0023, RF:0045 a RF:0052, RF:0054 a RF:0059, RF:0064, RF:0066 y RF:0067.

| Pantalla | Requisitos | Qué muestra el prototipo |
|---|---|---|
| Navegación con sesión | RF:0022, RF:0023 | El menú cambia «Ingresar» por «Mi cuenta» y agrega «Publicar». «Cerrar sesión» está en Mi cuenta. |
| Publicar mascota (Figuras 13 y 14) | RF:0017, RF:0046 | Selector En adopción o Mascota perdida. Comunes: fotos (1 a 6 o 1 a 3), especie, raza y sexo. En adopción: tamaño, edad en años y meses, esterilización, ubicación aproximada y estado de salud. Perdida: nombre, fecha y zona del extravío, contacto público y características. La confirmación de una perdida indica que queda visible 30 días. |
| Solicitar adopción (Figura 15) | RF:0049 | Muestra el nombre, el teléfono y el correo que se compartirán, aclara que la cédula nunca se comparte y exige aceptar la cesión de esos datos (Ley 18.331). Al enviar, la solicitud queda Pendiente y el publicante recibe un correo. |
| Reportar (Figura 16) | RF:0059 | Desde el detalle de una publicación o el perfil de un refugio, con la lista de motivos de 13.2.5. |
| Mi cuenta: Mis publicaciones (Figura 17) | RF:0020, RF:0045, RF:0047 | Publicaciones propias de ambos tipos, con su estado y la fecha de vencimiento de las perdidas. |
| Mi cuenta: Solicitudes enviadas (Figura 18) | RF:0054 | Solicitudes propias con la fecha y el estado (Pendiente, Adoptada o Rechazada). |
| Mi cuenta: Solicitudes recibidas (Figura 19) | RF:0050, RF:0051, RF:0052 | Por cada publicación, los solicitantes con teléfono y correo, y los botones Concretar adopción y Rechazar. Al concretar, la publicación pasa a Adoptado, las demás solicitudes pendientes pasan a Rechazada y se informa el seguimiento de los 15 días. |
| Mi cuenta: Seguimiento (Figura 20) | RF:0055, RF:0056, RF:0058 | Fecha de adopción, días que quedan para enviar la foto y fecha de vencimiento, carga de la foto y aviso de la restricción de la cuenta si no se envía a tiempo. |

*Figura 13. Publicar mascota en adopción.*

*Figura 14. Publicar mascota perdida.*

*Figura 15. Solicitar adopción, con el consentimiento para compartir los datos.*

*Figura 16. Reportar una publicación.*

*Figura 17. Mi cuenta: Mis publicaciones.*

*Figura 18. Mi cuenta: Solicitudes enviadas.*

*Figura 19. Mi cuenta: Solicitudes recibidas.*

*Figura 20. Mi cuenta: Seguimiento posterior a la adopción.*

Falta prototipar:

- La edición de los datos propios (RF:0016).
- Las acciones sobre una publicación propia: modificar, archivar, reactivar y renovar (RF:0018, RF:0019, RF:0045, RF:0047).
- La revisión de la foto de seguimiento por el publicante, con aprobar y rechazar (RF:0057).
- El motivo de reporte «Otro» con campo de texto, los dos motivos de publicación que faltan («Sospecha de maltrato o criadero ilegal» y «Datos de contacto falsos o incorrectos») y el reporte de un particular.
- El campo de contacto de una mascota perdida admite «teléfono o correo», pero 13.2.2 define un teléfono de 8 o 9 dígitos.
- La publicación de una mascota encontrada y su apartado en la página de inicio (RF:0064, RF:0065), que se agregaron al alcance después de diseñar el prototipo.
- La definición o el cambio de la contraseña en el perfil (RF:0067).

### 14.6. Actor: Refugio

El Refugio es una organización cuya alta valida el Administrador. Publica mascotas en adopción, perdidas y encontradas, gestiona las solicitudes que recibe, revisa el seguimiento de sus adopciones y publica sus cuentas bancarias. No solicita adopciones. Requisitos: RF:0022 a RF:0029, RF:0034 a RF:0037, RF:0045 a RF:0047, RF:0050 a RF:0052, RF:0057, RF:0059 y RF:0064.

| Pantalla | Requisitos | Qué muestra el prototipo |
|---|---|---|
| Solicitud de registro | RF:0034 | Opción Refugio de «Crear cuenta» (Figura 11). |
| Publicar mascota | RF:0024, RF:0046 | La misma pantalla que el Particular (Figuras 13 y 14). |
| Mi cuenta: Mis publicaciones y Solicitudes recibidas | RF:0027, RF:0050, RF:0051, RF:0052 | Las mismas pestañas que el Particular (Figuras 17 y 19). |
| Perfil público | RF:0062 | Lo que ven los demás usuarios (Figura 8). |

Falta prototipar:

- Ocultar las pestañas «Solicitudes enviadas» y «Seguimiento» en la cuenta del Refugio, porque no solicita adopciones. El prototipo usa la misma cuenta para ambos actores.
- La edición del perfil del refugio: nombre, contacto, ubicación, foto y contraseña (RF:0029).
- La gestión de cuentas bancarias: alta, baja y listado (RF:0035 a RF:0037).
- Las acciones sobre una publicación propia (RF:0025, RF:0026) y la revisión de la foto de seguimiento (RF:0057), igual que para el Particular.

### 14.7. Actor: Administrador

El Administrador modera el contenido, valida o rechaza a los refugios, resuelve los reportes y administra las cuentas. Requisitos: RF:0001 a RF:0014, RF:0022, RF:0023, RF:0044, RF:0060 y RF:0061.

El panel tiene un menú lateral de seis secciones; las que tienen tareas pendientes muestran la cantidad.

| Pantalla | Requisitos | Qué muestra el prototipo |
|---|---|---|
| Resumen (Figura 21) | — | Indicadores de solicitudes de refugio pendientes, reportes sin resolver, publicaciones visibles y particulares activos, y la lista de lo que requiere atención. |
| Solicitudes de refugio (Figura 22) | RF:0005, RF:0044 | Fecha de la solicitud, zona, días que quedan del plazo de 5 días hábiles y los documentos adjuntos. «Validar y dar de alta» envía el correo de alta. «Rechazar» exige escribir el motivo. |
| Reportes (Figura 23) | RF:0060, RF:0061 | Objeto reportado, cantidad de reportes de usuarios distintos, motivos y medida automática aplicada. Acciones: desestimar, enviar aviso, suspender o dar de baja. |
| Publicaciones (Figura 24) | RF:0001, RF:0002, RF:0004, RF:0011, RF:0012 | Búsqueda por publicación o publicante, filtro por tipo y tabla con tipo, publicante, fecha, estado y marca de moderación. Acción: archivar. |
| Refugios (Figura 25) | RF:0006, RF:0007, RF:0009 | Tabla con nombre, ubicación y estado. Acciones: editar y dar de baja. |
| Particulares (Figura 26) | RF:0010, RF:0013, RF:0014 | Tabla con nombre, correo, fecha de alta y estado. Acciones: editar y dar de baja. |

*Figura 21. Panel del Administrador: resumen.*

*Figura 22. Panel del Administrador: solicitudes de refugio.*

*Figura 23. Panel del Administrador: reportes.*

*Figura 24. Panel del Administrador: publicaciones.*

*Figura 25. Panel del Administrador: refugios.*

*Figura 26. Panel del Administrador: particulares.*

Falta prototipar o corregir:

- La modificación de una publicación (RF:0003) y la búsqueda de refugios por nombre y ubicación (RF:0008).
- El prototipo permite al Administrador reactivar una publicación archivada, pero según 13.2.2 solo la reactiva el publicante.
- El prototipo permite restituir un refugio dado de baja, pero según 13.2.1 esa baja es definitiva. Debe ofrecer suspender y levantar la suspensión.

### 14.8. Diagramas de casos de uso

Los casos de uso se agrupan en cuatro diagramas por módulo. Cada caso de uso indica los requisitos funcionales que lo originan; los requisitos dados de baja no figuran. Se usan dos actores abstractos: Visitante, que generaliza al Usuario Invitado y al usuario registrado para las consultas públicas, y Publicante, que generaliza al Particular y al Refugio para las operaciones sobre publicaciones propias. Las tareas automáticas se modelan con el actor secundario Planificador (tareas programadas que corren todos los días a las 03:00), el envío de correos con el actor secundario Sistema de correo y el registro e inicio de sesión con Google con el actor secundario Google.

*Diagrama: CU-1 · Usuario Invitado* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

*Diagrama: CU-2 · Usuarios registrados: publicaciones y cuenta* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

*Diagrama: CU-3 · Adopción y seguimiento* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

*Diagrama: CU-4 · Administrador* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

### 14.9. Diagrama de clases

El diagrama de clases representa el dominio de la aplicación. Usuario y PublicacionAnimal son clases abstractas: la primera se especializa en Particular, Refugio y Administrador, y la segunda en PublicacionAdopcion y PublicacionPerdida, igual que en el modelo entidad-relación de la sección 16. Las fotografías, las cuentas bancarias y el seguimiento se modelan como composiciones, porque no existen sin la publicación, el refugio o la adopción a la que pertenecen. PublicacionPerdida guarda las mascotas perdidas y las encontradas, que se distinguen por el atributo tipoAviso (enumeración TipoAviso). En Usuario, googleId y contraseña son opcionales: solo un Particular puede tener googleId y, si no lo tiene, la contraseña es obligatoria (RNE27; en la V3 del integrador, RNE26).

*Diagrama: Clases · Diagrama de clases del dominio* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

### 14.10. Diagramas de secuencia

Los diagramas de secuencia describen los flujos que se presentan en la demostración del Hito 2: registro de Particular, inicio y cierre de sesión, publicación de mascota en adopción y publicación de mascota perdida o encontrada. Se agrega, para el Hito 3, el registro e inicio de sesión con Google. Todas las solicitudes viajan por HTTPS y las contraseñas se almacenan con hash bcrypt (RNF:0007).

*Diagrama: SEC-1 · Registro de Particular y confirmación de correo* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

*Diagrama: SEC-2 · Consultar publicaciones con filtros* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

*Diagrama: SEC-3 · Solicitar adopción* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

*Diagrama: SEC-4 · Concretar adopción* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

*Diagrama: SEC-5 · Seguimiento automático posterior a la adopción* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

*Diagrama: SEC-6 · Inicio y cierre de sesión* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

*Diagrama: SEC-7 · Publicar mascota en adopción* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

*Diagrama: SEC-8 · Publicar mascota perdida o encontrada* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

*Diagrama: SEC-9 · Continuar con Google* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

### 14.11. Diagramas de estados

Los diagramas de estados representan las transiciones válidas para la publicación en adopción, la publicación de mascota perdida o encontrada, la solicitud de adopción, el refugio y el seguimiento posterior a la adopción.

*Diagrama: EST-1 · Publicación de mascota en adopción* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

*Diagrama: EST-2 · Publicación de mascota perdida o encontrada* (estados Vigente, Resuelta y Archivada) (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

*Diagrama: EST-3 · Solicitud de adopción* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

*Diagrama: EST-4 · Refugio* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

*Diagrama: EST-5 · Evidencia de seguimiento* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

*Diagrama: EST-6 · Cuenta de Particular* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)

*Diagrama: EST-7 · Moderación de una publicación* (pestaña del mismo nombre en `docs/diagramas/AdoptaUY-Diagramas.drawio`)
