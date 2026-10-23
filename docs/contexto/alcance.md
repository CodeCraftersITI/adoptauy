# Alcance, hitos y decisiones

> Extraído del Documento integrador 2.1 de AdoptaUY (rev. 18/10/2026). Si este archivo y el documento difieren, manda el documento; avisá la diferencia.

## 1. Resumen ejecutivo
AdoptaUY es una aplicación web adaptable (responsive) que centraliza y facilita el proceso de adopción responsable de perros y gatos en Montevideo, integrando a particulares, refugios y adoptantes en una misma plataforma. El proyecto surge para dar respuesta a la fragmentación actual del proceso de adopción, que se desarrolla de forma dispersa entre redes sociales, carteles físicos y comunicación boca a boca.
La solución contempla seis módulos funcionales: usuarios y acceso, publicaciones de mascotas en adopción y de mascotas perdidas, adopción y seguimiento posterior, refugios y donaciones, reportes y moderación, y notificaciones por correo. Los actores del sistema son el Usuario Invitado, el Particular, el Refugio y el Administrador. La plataforma no procesa pagos: las donaciones se realizan por transferencia a la cuenta bancaria que publica cada refugio.
El proyecto se organiza en seis sprints, dos por hito. Los Sprints 1 y 2 cerraron con la entrega del Hito 1 el 18/09/2026. Los Sprints 3 y 4 (19/09/2026 al 23/10/2026) comprenden la reformulación del alcance, la corrección de este documento y el primer incremento funcional. En el Hito 2 se demuestran el registro de usuarios, el inicio de sesión, la página de inicio y la publicación de mascotas en adopción y de mascotas perdidas.

## 4. Objetivos

### 4.1. Objetivo general
Desarrollar una aplicación web adaptable que centralice y facilite el proceso de adopción responsable de perros y gatos en Montevideo, integrando a particulares, refugios y adoptantes en una misma plataforma y promoviendo el bienestar animal.

### 4.2. Objetivos específicos

- Centralizar la oferta de adopción: permitir la publicación de animales en adopción con un formulario de 9 campos obligatorios (especie, raza, sexo, edad estimada, tamaño, estado de salud, esterilización, entre 1 y 6 fotografías y ubicación aproximada), con un tiempo de carga estimado inferior a 3 minutos, y su consulta pública con filtros por fecha, edad, especie, raza y sexo.

- Registrar y ubicar refugios: habilitar la solicitud de registro de refugios con validación documental por parte del Administrador en un plazo máximo de 5 días hábiles, y mostrar los refugios validados en un mapa de Montevideo.

- Difundir mascotas perdidas: permitir que un usuario registrado publique una mascota perdida, con una vigencia de 30 días renovable y consulta pública sin necesidad de registro.

- Gestionar la adopción y su seguimiento: permitir que un usuario registrado solicite una adopción, que el publicante reciba la solicitud con los datos de contacto, y que el sistema exija al adoptante una fotografía del animal a los 15 días de concretada la adopción, con un plazo de 7 días para enviarla.

- Facilitar el aporte económico a los refugios: permitir que cada refugio validado publique los datos de su cuenta bancaria para recibir donaciones por transferencia, sin que la plataforma procese pagos.

- Resguardar a la comunidad: permitir el reporte de usuarios y publicaciones, con umbrales que ocultan la publicación reportada o envían la cuenta a revisión del Administrador.

## 5. Alcance, exclusiones y limitaciones

### 5.1. Alcance
El sistema AdoptaUY comprende el diseño, desarrollo, pruebas y despliegue de una aplicación web adaptable (responsive), accesible desde navegadores de escritorio y de dispositivos móviles, orientada a centralizar y facilitar el proceso de adopción responsable de perros y gatos en Montevideo. No se desarrolla una aplicación móvil nativa ni una PWA. El proyecto contempla los siguientes módulos:

- Usuarios y acceso: registro de usuarios de tipo Particular, con confirmación de la dirección de correo, e inicio de sesión para usuarios registrados. Los refugios envían una solicitud de registro con certificado de personería jurídica u organización sin fines de lucro, comprobante de domicilio y datos de contacto verificables; el Administrador la valida o la rechaza y, si corresponde, da de alta al usuario Refugio.

- Publicaciones: los usuarios registrados pueden crear publicaciones de dos tipos. Las de mascotas en adopción, a cargo de particulares y refugios, incluyen especie, raza o condición de mestizo, sexo, edad estimada, tamaño, estado de salud, esterilización y entre 1 y 6 fotografías, y manejan los estados En adopción, Adoptado y Archivado. Las de mascotas perdidas, a cargo del particular o del refugio que perdió la mascota, manejan los estados Perdida, Encontrada y Archivada, y tienen una vigencia de 30 días, renovable por el publicante por períodos de 30 días; vencido el plazo sin renovación, la publicación se archiva. Ambos tipos son de consulta pública, y las mascotas en adopción pueden filtrarse por fecha, edad, especie, raza y sexo.

- Adopción y seguimiento: un usuario registrado puede solicitar la adopción de una mascota publicada. El publicante recibe la lista de solicitantes con sus datos de contacto y cada solicitud maneja los estados Pendiente, Adoptada y Rechazada. Cuando la adopción se concreta, el publicante selecciona al adoptante y marca la publicación como Adoptado; las solicitudes restantes pasan a Rechazada. La adopción es definitiva: la publicación no vuelve al estado En adopción. A los 15 días de la adopción, el sistema solicita al adoptante una fotografía que evidencie el estado del animal, que es revisada por el publicante. Si pasados 7 días no la envió, su cuenta queda restringida: no puede solicitar adopciones ni crear publicaciones hasta enviarla.

- Refugios y donaciones: los refugios validados se muestran en un mapa de Montevideo y en un listado con sus datos de contacto. Cada refugio puede publicar los datos de su cuenta bancaria para recibir donaciones por transferencia. La plataforma no procesa pagos ni interviene en las transferencias.

- Reportes y moderación: los usuarios registrados pueden reportar usuarios y publicaciones. El Administrador resuelve los reportes desde su panel y puede aplicar un aviso, una suspensión o la baja de la cuenta. Una publicación con 3 reportes de usuarios distintos se oculta hasta que el Administrador resuelva; un particular con 5 reportes pasa a revisión de cuenta y con 10 queda inhabilitado, y un refugio se suspende con 3 reportes.

- Notificaciones por correo: el sistema envía un correo al recibirse una solicitud de adopción, en el aviso de seguimiento del día 15, al registrarse un particular para confirmar su dirección, al recibirse la solicitud de registro de un refugio y al concretarse su alta.

- Infraestructura y seguridad: el sistema será desplegado sobre Ubuntu Server 24.04 LTS, utilizando Docker para la ejecución aislada de los componentes principales: aplicación Laravel 13, servidor web Nginx y base de datos MySQL. Se contempla comunicación mediante HTTPS/TLS, control de acceso basado en roles, principio de mínimo privilegio, copias de seguridad automatizadas y registro de eventos de seguridad y operaciones administrativas.

### 5.2. Exclusiones

- Gestión logística de transporte y entregas: la plataforma no realizará traslados de animales ni gestionará el transporte o envío de donaciones. Estas actividades serán responsabilidad de los usuarios y refugios involucrados.

- Atención médica y urgencias veterinarias: el sistema no proporcionará consultas veterinarias, asistencia médica en línea ni servicios de atención de urgencias para animales.

- Procesamiento y registro de donaciones: la plataforma no integra pasarelas de pago ni procesa, audita o registra donaciones monetarias. Las donaciones en especie se coordinan por contacto directo con el refugio y tampoco se registran en el sistema.

- Validación en terreno e inspección física: la validación de refugios se limitará a la revisión documental realizada por el Administrador. El seguimiento posterior a la adopción se limita a la evidencia fotográfica; el sistema no contempla visitas presenciales.

- Derivación de animales encontrados: el sistema no busca refugios por cercanía ni deriva animales encontrados en la vía pública. El mapa de refugios es solo de consulta.

- Aplicación móvil nativa y PWA: el acceso desde celulares se resuelve con diseño adaptable en el navegador.

- Códigos QR de identificación: no forman parte del alcance.

- Cobertura geográfica fuera de Montevideo en la primera etapa: el desarrollo, las pruebas y la operación inicial estarán centrados en el departamento de Montevideo. La extensión hacia otras regiones del país podrá considerarse como una etapa futura.

### 5.3. Limitaciones

| Categoría | Descripción de la limitación |
|---|---|
| Limitaciones técnicas | El mapa de refugios depende de un servicio externo de mapas y las notificaciones dependen del servicio de correo. La disponibilidad y el comportamiento de esos servicios pueden afectar esas funcionalidades. |
| Limitaciones temporales | El desarrollo está condicionado por el cronograma académico, del 15/08/2026 al 20/11/2026, organizado en seis sprints, dos por hito, alineados con los hitos de la cátedra: 18/09/2026, 23/10/2026 y 20/11/2026. |
| Limitaciones económicas y de recursos | Debido al carácter académico del proyecto, el desarrollo se realizará priorizando tecnologías de código abierto y capas gratuitas o educativas de infraestructura y servicios. El equipo está conformado por cuatro integrantes, quienes distribuyen entre sí las tareas de desarrollo, análisis, infraestructura, redes, base de datos, seguridad, pruebas y documentación. |
| Limitaciones legales y normativas | El sistema deberá contemplar la normativa uruguaya de protección de datos personales, particularmente la Ley N.º 18.331. Los datos de contacto del solicitante se comparten con el publicante solo con su consentimiento expreso al enviar la solicitud, y se minimiza la exposición pública de datos personales de usuarios y refugios (RNF:0013). |

## 8. Entregables e hitos
Las funciones del Hito 2 las definió el equipo. El registro comprende solo el alta del Particular; la solicitud de registro de refugio (RF:0034) se suma al Hito 2 únicamente si el tiempo lo permite. La confirmación de correo (RF:0043) llega en el Sprint 5; en la demostración del Hito 2 el inicio de sesión todavía no la exige.

| Hito | Fecha | Entregable | Funciones demostrables | Criterio de aceptación |
|---|---|---|---|---|
| Hito 1 | 18/09/2026 | Plan consolidado y primeros avances | Documento integrador 1.0, prototipos por actor, DER y topología de red | Entregado |
| Hito 2 | 23/10/2026 | Documento integrador 2.0 y primer incremento funcional | Registro de usuarios (RF:0039); inicio y cierre de sesión (RF:0022, RF:0023); página de inicio con el listado público (RF:0040, RF:0048); publicación de mascota en adopción (RF:0017); publicación de mascota perdida (RF:0046) | Cada función se demuestra de punta a punta y cumple el criterio de aceptación de su RF |
| Hito 3 | 20/11/2026 | Proyecto consolidado y documentado | Confirmación de correo (RF:0043); solicitud y alta de refugios (RF:0034, RF:0005, RF:0044); solicitudes de adopción (RF:0049 a RF:0052, RF:0054); seguimiento (RF:0055 a RF:0058); reportes (RF:0059 a RF:0061); mapa y cuentas bancarias (RF:0038, RF:0035 a RF:0037, RF:0062); correo (RF:0063); administración (RF:0001 a RF:0014) | Todos los RF vigentes demostrados; RNF verificados con su procedimiento; restauración de un respaldo probada |

## 9. Planificación y seguimiento
El proyecto se organiza en seis sprints, dos por hito, de dos a dos semanas y media cada uno. Esta planificación reemplaza a la de ocho sprints del Hito 1, que no se correspondía con el calendario real.

| Sprint | Inicio | Fin | Días hábiles | Días de fin de semana | Horas de equipo | Actividad principal | Entregable | Estado |
|---|---|---|---|---|---|---|---|---|
| Sprint 1 | 15/08/2026 | 01/09/2026 | 12 | 6 | 216 | Organización del equipo, problemática, objetivos, metodología y stack tecnológico | — | Cerrado |
| Sprint 2 | 02/09/2026 | 18/09/2026 | 13 | 4 | 184 | Requisitos, encuesta, DER preliminar, prototipos por actor y topología de red | Hito 1 | Cerrado |
| Sprint 3 | 19/09/2026 | 05/10/2026 | 11 | 6 | 208 | Reducción del alcance y análisis de la devolución docente | — | Cerrado |
| Sprint 4 | 06/10/2026 | 23/10/2026 | 14 | 4 | 192 | Corrección del documento integrador; registro, inicio de sesión, página de inicio y publicación de mascotas en adopción y perdidas | Hito 2 | En curso |
| Sprint 5 | 24/10/2026 | 06/11/2026 | 10 | 4 | 160 | Refugios, solicitudes de adopción y seguimiento | — | Pendiente |
| Sprint 6 | 07/11/2026 | 20/11/2026 | 10 | 4 | 160 | Reportes, mapa, cuentas bancarias, administración, integración, pruebas y documentación final | Hito 3 | Pendiente |

Las horas de equipo resultan de 4 integrantes con 2 horas por día hábil y 5 horas por día de fin de semana cada uno (D52): 1.120 horas en los 98 días del proyecto.
Al 06/10/2026, inicio del Sprint 4, no hay funciones implementadas.
Las pruebas de seguridad no se concentran al final: desde el Sprint 4 cada función se entrega con su prueba de acceso por rol y de validación de archivos, y en los Sprints 5 y 6 se suman las pruebas de errores de servicios externos y de restauración de respaldos.

### Backlog priorizado
El backlog se ordena por valor, riesgo y dependencia. Los grupos 1 a 4 corresponden al Hito 2; el esfuerzo de cada grupo figura en la estimación.

| Prioridad | Grupo de requisitos | Valor | Depende de | Sprint |
|---|---|---|---|---|
| 1 | Registro de usuarios e inicio de sesión | Habilita todo lo demás | — | 4 |
| 2 | Página de inicio y listado público | Puerta de entrada al producto | — | 4 |
| 3 | Publicación de mascotas en adopción | Núcleo del producto | Prioridad 1 | 4 |
| 4 | Publicación de mascotas perdidas | Segundo tipo de publicación | Prioridad 1 | 4 |
| 5 | Solicitud, validación y alta de refugios; confirmación de correo | Habilita al actor Refugio | Prioridad 1 y servicio de correo | 5 |
| 6 | Solicitud de adopción y lista de solicitantes | Núcleo del producto | Prioridad 3 | 5 |
| 7 | Concreción de la adopción y seguimiento | Diferencial del producto | Prioridad 6 y tareas programadas | 5 |
| 8 | Reportes y moderación | Seguridad de la comunidad | Prioridades 1 y 3 | 6 |
| 9 | Mapa de refugios y cuentas bancarias | Apoyo a refugios | Prioridad 5 y servicio de mapas | 6 |
| 10 | Administración de usuarios, refugios y publicaciones | Operación | Prioridades 1 a 5 | 6 |

### Estimación de esfuerzo
Estimación en horas de equipo, a revisar al cierre de cada sprint.

| Sprint | Trabajo | Horas estimadas | Capacidad |
|---|---|---|---|
| 4 | Entorno base (repositorio, Docker, base de datos y migraciones) | 30 |  |
| 4 | Registro e inicio de sesión | 20 |  |
| 4 | Página de inicio y listado público | 24 |  |
| 4 | Publicación de mascotas en adopción | 36 |  |
| 4 | Publicación de mascotas perdidas | 24 |  |
| 4 | Documento integrador 2.0 | 10 |  |
| 4 | Total Sprint 4 | 144 | 192 |
| 5 | Refugios y confirmación de correo | 32 |  |
| 5 | Solicitudes de adopción | 28 |  |
| 5 | Concreción de la adopción y seguimiento | 36 |  |
| 5 | Pruebas de acceso por rol y de validación de archivos | 16 |  |
| 5 | Total Sprint 5 | 112 | 160 |
| 6 | Reportes y moderación | 32 |  |
| 6 | Mapa, perfil público y cuentas bancarias | 24 |  |
| 6 | Administración de usuarios, refugios y publicaciones | 36 |  |
| 6 | Integración, pruebas de carga, respaldos y documentación final | 40 |  |
| 6 | Total Sprint 6 | 132 | 160 |

La estimación suma 388 horas frente a 512 de capacidad. El margen es de 48 horas en el Sprint 4, 48 en el Sprint 5 y 28 en el Sprint 6.

### Planificado frente a logrado

| Sprint | Planificado | Logrado | Desvío, causa y ajuste |
|---|---|---|---|
| Sprints 1 y 2 | Plan consolidado y primeros avances | Documento integrador 1.0 entregado el 18/09/2026 | Tras la entrega se redujo el alcance y la devolución del 29/09/2026 pidió correcciones; ajuste: reformulación en los Sprints 3 y 4 |
| Sprint 3 | Módulo de mascotas perdidas, según el cronograma del Hito 1 | Decisiones de reducción de alcance y lectura de la devolución; sin funciones implementadas | El sprint se dedicó a redefinir el alcance; ajuste: todo el incremento del Hito 2 pasa al Sprint 4 |
| Sprint 4 | Documento 2.0 y primer incremento funcional | En curso; al 06/10/2026 no hay funciones implementadas | Se evalúa en el Hito 2 |

## Decisiones de alcance aplicadas

| ID | Decisión |
|---|---|
| D1 | Dos tipos de usuario registrado: Particular y Refugio. El refugio solicita el registro y el Administrador lo valida. |
| D2 | Inicio de sesión para usuarios registrados. |
| D3 | Publicaciones de dos tipos: mascotas en adopción y mascotas perdidas. |
| D4 | Sin pasarela de pago ni comisión. El refugio publica su cuenta bancaria. |
| D5 | "Cachorros" es un filtro por edad. |
| D6 | La solicitud de adopción llega al publicante con los datos de contacto. |
| D7 | Seguimiento posterior a la adopción con fotografía (precisado por D15 y D24). |
| D8 | Sistema de reportes. |
| D9 | Sistema de correo para avisos. |
| D10 | Estados de la publicación en adopción: En adopción, Adoptado y Archivado. |
| D11 | Mascotas perdidas: solo publicación del Particular que perdió su mascota. Sin buscador por radio. |
| D12 | Se mantiene el mapa de refugios de Montevideo, solo de consulta. |
| D13 | Web adaptable. Sin app móvil ni PWA. |
| D14 | La solicitud requiere cuenta; estados Pendiente, Aceptada y Rechazada; el contacto se comparte al enviar; el publicante elige al adoptante. |
| D15 | Avisos a los 15 y 30 días; sin evidencia al día 30 se bloquea la solicitud de adopciones hasta presentarla. |
| D16 | Una adopción fallida vuelve a En adopción. La publicación de mascota perdida dura 30 días, renovable. |
| D17 | Solo donaciones monetarias por transferencia; en especie, por contacto directo. |
| D18 | Se reportan usuarios y publicaciones; resuelve el Administrador con aviso, suspensión o baja. |
| D19 | Correos: registro de Particular, solicitud y alta de refugio, solicitud de adopción, avisos de los días 15 y 30. |
| D20 | Actores unificados; RF:0015 y RF:0039 unificados. |
| D21 | Trello como tablero único; tres sprints, uno por hito. |
| D22 | Sección 16 y 17.3 son una misma solución; SRV-ZABBIX es 10.10.99.50. |
| D23 | El actor registrado se llama Particular. |
| D24 | Fotografía en cada aviso; si el publicante la rechaza se reenvía; si no la revisa no hay consecuencia. |
| D25 | Una publicación En adopción recibe varias solicitudes; al concretarse, las no elegidas pasan a Rechazada. |
| D26 | Mascota perdida: estados Perdida, Encontrada y Archivada; visible para el Invitado; se archiva. |
| D27 | 5 reportes: revisión. 10 reportes: cuenta inhabilitada. |
| D28 | Sprints: 15/08 a 18/09, 19/09 a 23/10 y 24/10 a 20/11 de 2026. |
| D29 | Scrum Master: Camilo Blanco y Leandro Estévez. |

Decisiones del 06/10/2026 que precisan o reemplazan a las anteriores:

| ID | Decisión | Reemplaza o precisa |
|---|---|---|
| D30 | Estados de la solicitud: Pendiente, Rechazada y Adoptada. El contacto entre publicante y solicitante ocurre fuera de la plataforma y no se registra. | D14 ("Aceptada") |
| D31 | Reportes por tipo: publicación con 3 reportes pasa a revisión y se oculta; particular con 5 reportes o 3 publicaciones suspendidas pasa a revisión de cuenta, con baja lógica si se confirma; refugio con 3 reportes pasa a Suspendido. | D27 |
| D32 | Listas de motivos de reporte para publicaciones, particulares y refugios (13.2.5). | D18 |
| D33 | La publicación de mascota perdida lleva nombre de la mascota, fecha y zona del extravío, características y contacto. | D11 |
| D34 | Product Owner: Martín Viar, en representación del ITI. | — |
| D35 | Seis sprints, dos por hito. | D21 (tres sprints) y D28 (fechas) |
| D36 | Seguimiento: una sola fotografía. Aviso a los 15 días de la adopción y 7 días para enviarla. Si no se envía, la cuenta no puede solicitar adopciones ni publicar hasta enviarla. | D15 y D24 |
| D37 | La adopción es definitiva: la publicación no vuelve a En adopción. RF:0053 se da de baja. | D16 (primera parte) |
| D38 | El publicante puede rechazar una solicitud individual. | D25 |
| D39 | Umbral del refugio: 3 reportes. | D31 |
| D40 | El motivo "Estafa en donaciones" pasa a "La cuenta bancaria publicada no pertenece al refugio". | D32 |
| D41 | Fechas de corte de los sprints: 01/09, 05/10 y 06/11 de 2026. | D35 |
| D42 | Se mantiene la inhabilitación automática del particular a los 10 reportes. Todos los umbrales cuentan reportes de usuarios distintos. | D27, D31 |
| D43 | La mascota perdida lleva además especie, raza, sexo y entre 1 y 3 fotografías. | D33 |
| D44 | En el Hito 2 se demuestran registro de usuarios, inicio de sesión, página de inicio, publicación de mascota en adopción y publicación de mascota perdida. | — |
| D45 | Cada integrante dispone de 2 horas por día. | — |
| D46 | La necesidad de ubicar refugios se atiende con el mapa de refugios de Montevideo. La búsqueda automática por cercanía queda fuera del alcance. | D11, D12 |
| D47 | Seguimiento: el único aviso es el del día 15; no hay aviso a los 30 días. Las 2 horas diarias por integrante incluyen sábados y domingos. El registro del Hito 2 es solo el del Particular. | D36, D44, D45 |
| D48 | Confirmado: el Product Owner es el docente; RNF:0006 se refiere a usuarios concurrentes y se aceptan las métricas propuestas; reportar exige cuenta; hay que confirmar el correo para iniciar sesión; una publicación Archivada puede reactivarse; la cédula es obligatoria en el registro; la inhabilitación a los 10 reportes aplica solo al particular. | D34, D42 |
| D49 | El Refugio no puede solicitar adopciones y sí puede publicar mascotas perdidas. | D11 |
| D50 | Camilo Blanco es responsable de base de datos. Los prototipos se rehacen. El IEEE 830 se reemplaza por una norma más reciente y el equipo redibuja el DER. | — |
| D51 | El sistema se despliega en un servidor local. No se relevan costos de alojamiento. | — |
| D52 | Cada integrante dispone de 2 horas por día hábil y 5 horas por día de fin de semana. | D45, D47 |
| D53 | El documento lo revisa Leandro Estévez y lo aprueba Camilo Blanco; el archivo se nombra TC4-CodeCrafters-AdoptaUY-V y el número de versión. El servidor local es una PC del equipo. La difusión con refugios piloto no se realizó. | D51 |
| D54 | Correo: Postfix como relay hacia un servidor SMTP externo por el puerto 587. | D19 |
| D55 | Mapas con OpenStreetMap y Leaflet. | D12 |
| D56 | El mapa muestra todos los refugios de Montevideo: los validados, con enlace a su perfil, y los no registrados, tomados de un archivo de datos públicos y marcados «No registrado en AdoptaUY». | D12, D46 |
| D57 | Entornos de prueba: local, servidor de pruebas en la VM de VirtualBox y GitHub Actions en cada pull request. | — |
| D58 | Los defectos se registran en una lista «Defectos» del tablero de Trello. | — |
| D59 | Herramientas de prueba: PHPUnit para unitarias e integración y Laravel Dusk para funcionales. | — |
| D60 | Roles de testing: Camilo Blanco lidera y acepta; Leandro Estévez diseña y documenta; Iñaki Pérez hace unitarias, integración y CI; Rodrigo González hace las no funcionales y el servidor de pruebas. | — |
| D61 | Plan de pruebas: casos detallados para el Hito 2 y resumidos para el resto, que se detallan al planificar su sprint. | — |
| D62 | El plan de pruebas se entrega a la docente de Testing el 16/10/2026. | — |
| D63 | Cachorro: animal de menos de 12 meses de edad estimada. | D5 |
| D64 | La CI tiene 7 u 8 dígitos con dígito verificador válido; la contraseña tiene al menos 8 caracteres. | — |
| D65 | La CI es única: no puede repetirse entre cuentas. | — |
| D66 | Fotografías en JPG, PNG o WEBP, de hasta 8 MB cada una. | — |
| D67 | En la demostración del Hito 2 se inicia sesión sin confirmar el correo; desde el Sprint 5 se exige, con un mensaje genérico ante cualquier ingreso rechazado. | D48 (confirmación de correo) |
| D68 | La baja de una publicación por el Administrador la pasa a Archivado; la suspensión por reportes es una marca de moderación aparte. | — |
| D69 | La baja de un refugio es lógica y agrega el estado Dado de baja. | — |
| D70 | Una mascota perdida se renueva estando Perdida o Archivada; cada renovación vence 30 días después de la fecha de renovación. | D16 |
| D71 | Las tareas programadas corren a diario a las 03:00 y cuentan días corridos. Si el publicante rechaza la fotografía después del día 22, la cuenta se restringe hasta el nuevo envío. | D36 |
| D72 | La fotografía de seguimiento cuenta como presentada desde su envío; solo un rechazo explícito la anula. | D24 |
| D73 | HTTPS con certificado autofirmado en el servidor local; HTTP redirige a HTTPS. | — |
| D74 | La disponibilidad (Zabbix) y el escaneo de puertos se miden en el servidor local durante el Sprint 6. | — |
| D75 | Postfix corre como contenedor del docker-compose para que el despliegue sea reproducible (RNF:0012); en desarrollo, Mailpit captura los correos. | D54 |
| D76 | Respaldo diario con cron del servidor a las 02:00 (mysqldump y copia de storage/), con retención de 7 días. | — |
| D77 | La VM del servidor local usa NAT de VirtualBox con reenvío de puertos: 2222→22, 8080→80, 8443→443 y 8025→8025. | — |
| D78 | Flujo de trabajo en GitHub: la rama main está protegida; cada tarea tiene su rama (por ejemplo, feature/RF0039-registro-particular) y se integra por pull request con la aprobación de otro integrante y la integración continua en verde (CONTRIBUTING.md). | — |
| D79 | La CI se ingresa solo con números, sin puntos ni guion. | D64 |
| D80 | Largos de los campos: nombre y apellido de 2 a 50 caracteres; raza de 2 a 50; ubicación aproximada y zona del extravío de 3 a 100; correo hasta 255; contraseña de 8 a 72 (72 es lo máximo que toma bcrypt). | D64 (solo el mínimo de 8) |
| D81 | El teléfono se ingresa solo con números: 8 dígitos (fijo) o 9 (celular). | — |
| D82 | La edad estimada no tiene máximo. | — |
| D83 | Puertos: en las PC de desarrollo el sitio usa 8080 y 8443. En el servidor final (VM), Nginx usa 80 y 443 y VirtualBox usa adaptador puente; se accede con https://<IP de la VM> o un nombre local. Plan B: NAT de VirtualBox 80→80 y 443→443. | D77 |
| D84 | Valores del modelo: Estado_Esterilizacion es Esterilizado o No esterilizado; Tipo_Cuenta es texto libre; Estado_Reporte es Pendiente, Desestimado o Confirmado; Medida es Aviso, Suspensión o Baja lógica; Email_Verificado guarda la fecha de confirmación. | — |
| D85 | Los reportes que el Administrador desestimó no cuentan para los umbrales automáticos. | D42 (la precisa) |
| D86 | El contacto de una mascota perdida es un teléfono de 8 o 9 dígitos. | D33 (la precisa) |
| D87 | Se elimina Temperamento de la publicación de mascotas en adopción. | — |
| D88 | Red: pfSense es el único gateway y rutea entre VLAN; los switches trabajan en capa 2; la VLAN nativa es la 999, sin uso; el gateway de la VLAN 99 es 10.10.99.254; SRV-ZABBIX pertenece a la VLAN 99. | D22 (ubicación de SRV-ZABBIX) |
| D89 | SSH por usuario: Camilo a SRV-BD y SRV-BD-MIRROR; Iñaki a SRV-WEB; Rodrigo a SRV-WEB, SRV-DNS, SRV-MAIL, SRV-ZABBIX y los switches; Leandro a SRV-ZABBIX. | — |
| D90 | VPN de acceso remoto con OpenVPN en pfSense para que el equipo trabaje desde su casa: certificado más usuario y contraseña, red del túnel 10.10.50.0/24; cada integrante llega solo a su PC por escritorio remoto y a sus servidores por SSH. | «VPN Gateway» como equipo aparte |

## Aspectos a completar

| Aspecto | Sección |
|---|---|
| Proveedor externo del relay SMTP (por ejemplo, Gmail u otro) | 12.1, 17.4 |
| Correspondencia entre los servidores de la sección 16 y los contenedores del servidor local | 17.3 |
| DER y prototipos actualizados al alcance vigente | 14, 15 |
| Matriz de trazabilidad | 19.6 |
| Copia de la encuesta sin la columna de correos | 19.7 |
| Fechas del relevamiento: las actas 003 y 004 lo ubican en agosto y las respuestas son de septiembre | 13.0, control documental |
| Relevamiento a refugios | 13.0 |
| Revisión directa de Espacio Animal y del Portal de Adopciones | 3 |
| Carpeta donde se guarda la versión oficial del documento | Control documental |
