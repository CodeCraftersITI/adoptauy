# Pantallas y sistema de diseño

> Extraído del Documento integrador 2.0 de AdoptaUY (rev. 14/10/2026). Si este archivo y el documento difieren, manda el documento; avisá la diferencia.

## 14. Análisis y diseño
Los prototipos del Hito 1 se rehacen con el alcance vigente. Para cada pantalla se indica si se mantiene, se modifica, se elimina o es nueva. Al rehacerlos debe conservarse una copia estable de cada enlace.

### 14.1. Introducción
Los artefactos de interfaz están organizados por actor del sistema: Usuario Invitado, Particular, Refugio y Administrador, cada uno con su propio prototipo interactivo. Para cada actor se presenta la tabla de requisitos funcionales que le corresponde y, para cada pantalla, el requisito que resuelve y el estado de su prototipo.

### 14.2. Requisitos no funcionales
Los requisitos no funcionales aplican de forma transversal a todas las pantallas y se definen una sola vez, en la sección 13.3.2. Se elimina la tabla duplicada del Hito 1, que contradecía a la original en RNF:0007.

### 14.3. Sistema de diseño
Los cuatro prototipos comparten un mismo sistema de diseño visual. La tipografía no cambia: Plus Jakarta Sans para títulos y encabezados, e Inter para texto de cuerpo, formularios y datos tabulares.

| Color | Nombre | Uso principal |
|---|---|---|
| #0E7C5A | Verde esmeralda | Color primario: acciones principales y estados En adopción, Perdida y Validado. |
| #B9741E | Ámbar | Estados pendientes: solicitud Pendiente, refugio Pendiente de validación, evidencia pendiente, usuario En revisión. |
| #8A4FBE | Ciruela | Adopciones concretadas (Adoptado, Encontrada) y el actor Particular en el panel de administrador. |
| #BF4747 | Rojo suave | Estados rechazados y acciones destructivas (baja, cerrar sesión, reportar). |
| #5A6B78 | Gris pizarra | Estados suspendidos, archivados o de baja lógica. |

Los componentes y patrones se mantienen: menú principal en tarjetas por módulo, paneles laterales para altas, modales para ediciones y confirmaciones, tablas con búsqueda y filtros, y un único modelo de datos de publicación compartido entre Particular y Refugio.

### 14.4. Actor: Usuario Invitado
El Usuario Invitado es cualquier visitante sin cuenta. Puede explorar el catálogo público, registrarse como Particular o enviar la solicitud de registro de un refugio. Requisitos: RF:0022, RF:0034, RF:0038 a RF:0043, RF:0048 y RF:0062.
Prototipo: sitio público y registro de refugio.

| Pantalla | Requisitos | Prototipo | Qué cambia |
|---|---|---|---|
| Inicio y navegación (Figura 1) | RF:0022, RF:0039 | Modificar | Agregar el acceso a Mascotas perdidas. |
| Animales: listado y consulta (Figura 2) | RF:0040, RF:0041 | Modificar | Muestra solo publicaciones En adopción (antes "Activa"). Edad y raza pasan de búsqueda de texto a filtros propios. |
| Mascotas perdidas: listado | RF:0048 | Nueva | Listado de publicaciones en estado Perdida. |
| Refugios: mapa y listado (Figura 3) | RF:0038, RF:0042 | Modificar | Mapa de Montevideo con refugios validados y acceso al perfil público. |
| Perfil público del refugio | RF:0062 | Nueva | Nombre, ubicación, contacto y cuentas bancarias para donaciones. |
| Ingreso y registro (Figura 4) | RF:0022, RF:0039, RF:0043 | Modificar | Se quita la referencia a RF:0015. Se agrega el aviso de confirmación de correo. "Persona" pasa a "Particular". |
| Registro de refugio (Figura 5) | RF:0034 | Modificar | La confirmación menciona el correo de recepción. |

### 14.5. Actor: Particular
El Particular es un usuario registrado que publica mascotas en adopción y mascotas perdidas, solicita adopciones y cumple el seguimiento. Requisitos: RF:0016 a RF:0023, RF:0045 a RF:0047 y RF:0049 a RF:0059.
Prototipo: panel del Particular.

| Pantalla | Requisitos | Prototipo | Qué cambia |
|---|---|---|---|
| Menú principal (Figura 6) | — | Modificar | Agregar tarjetas de Solicitudes y Seguimiento. |
| Mis publicaciones (Figura 7) | RF:0017 a RF:0021, RF:0045 a RF:0047 | Modificar | Estados En adopción, Adoptado y Archivado. Alta de mascota perdida, con vencimiento y renovación. |
| Mi perfil | RF:0016 | Nueva | Faltaba en el Hito 1. |
| Solicitar adopción | RF:0049 | Nueva | Botón en la publicación, con consentimiento para compartir el contacto. Deshabilitado si la cuenta está restringida. |
| Solicitudes recibidas | RF:0050 a RF:0053 | Nueva | Lista de solicitantes con contacto, rechazo individual y selección del adoptante. |
| Mis solicitudes | RF:0054 | Nueva | Solicitudes enviadas y su estado. |
| Seguimiento | RF:0055 a RF:0058 | Nueva | El adoptante envía la fotografía; el publicante la aprueba o la rechaza. |
| Reportar | RF:0059 | Nueva | Formulario con motivo, desde un perfil o una publicación. |

### 14.6. Actor: Refugio
El Refugio es una organización cuya alta valida el Administrador. Publica mascotas en adopción, gestiona solicitudes y publica sus cuentas bancarias. Requisitos: RF:0024 a RF:0029, RF:0034 a RF:0037, RF:0045, RF:0050 a RF:0053, RF:0057 y RF:0059.
Prototipo: panel del Refugio.

| Pantalla | Requisitos | Prototipo | Qué cambia |
|---|---|---|---|
| Menú principal (Figura 8) | — | Modificar | Quitar Insumos. "Métodos de pago" pasa a "Cuentas bancarias". Agregar Solicitudes y Seguimiento. |
| Insumos (Figura 9) | RF:0030 a RF:0033 | Eliminar | Requisitos dados de baja (D17). |
| Cuentas bancarias (Figura 10) | RF:0035 a RF:0037 | Modificar | Cambio de nombre. Sigue siendo solo alta, baja y listado. |
| Publicaciones | RF:0024 a RF:0028, RF:0045 | Modificar | Reutiliza la pantalla del Particular, con ambos tipos de publicación. |
| Perfil | RF:0029 | Nueva | El Hito 1 remitía a una "sección de perfil" que no existía. |
| Solicitudes recibidas y seguimiento | RF:0050 a RF:0053, RF:0057 | Nueva | Reutiliza las pantallas del Particular. |

### 14.7. Actor: Administrador
El Administrador modera el contenido, valida o rechaza a los refugios, resuelve los reportes y administra las cuentas. Requisitos: RF:0001 a RF:0014, RF:0044, RF:0060 y RF:0061.
Prototipo: panel del Administrador.

| Pantalla | Requisitos | Prototipo | Qué cambia |
|---|---|---|---|
| Menú principal (Figura 11) | — | Modificar | Agregar Reportes. "Personas" pasa a "Particulares". |
| Publicaciones (Figura 12) | RF:0001 a RF:0004, RF:0011, RF:0012 | Modificar | Estados nuevos y columna de tipo de publicación. |
| Refugios (Figura 13) | RF:0005 a RF:0009, RF:0044 | Modificar | Rechazo con motivo obligatorio. Solo se ofrecen las transiciones de 13.2.1. |
| Particulares (Figura 14) | RF:0010, RF:0013, RF:0014 | Modificar | Cambio de nombre. Estados Activo, En revisión, Suspendido, Inhabilitado y Baja, y cantidad de reportes. |
| Reportes | RF:0060, RF:0061 | Nueva | Listado de reportes, resolución y medida aplicada. |

### 14.8. Conclusiones
La organización por actor permite trazar cada pantalla contra el requisito funcional que la origina, lo que facilita la revisión con la cátedra y la implementación en Laravel: cada módulo del prototipo corresponde a un controlador y un conjunto de vistas Blade por actor. El modelo de publicación compartido entre Particular y Refugio evita duplicar lógica y se corresponde con el esquema de la sección 15. De las 26 pantallas listadas, 11 son nuevas y deben prototiparse antes de implementarse.
