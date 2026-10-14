> Extraído del Plan de pruebas v2.0 (14/10/2026), aprobado por el equipo.

# AdoptaUY — Plan de pruebas v2.0 (Testing)
 · 
La versión 2.0 reemplaza al plan de pruebas del 28/09/2026 (v1.0), que se basaba en el alcance del Hito 1. Ahora se alinea con el Documento integrador 2.0. La v1.0 se conserva sin cambios para poder comparar ambas versiones (sección 10).

## Control del documento

| Campo | Valor |
|---|---|
| Proyecto | AdoptaUY — CodeCrafters (TC4, ITI CETP-UTU) |
| Asignatura | Testing — Hito 1: Diseño y planificación de pruebas |
| Docente | Carina Sindoni |
| Versión | 2.0 (borrador para revisión del equipo) |
| Base de requisitos | Documento integrador 2.0: 57 RF vigentes (RF:0001 a RF:0063, 6 dados de baja) y 14 RNF |
| Formato | La docente no fijó plantilla; se sigue la estructura de ISO/IEC/IEEE 29119-3, adaptada |

Supuestos confirmados por el equipo el 14/10/2026 (D57 a D62). Se marcan con su código donde se aplican:

| ID | Supuesto | Alternativa |
|---|---|---|
| S-01 | Entornos: local en cada máquina, la VM de VirtualBox (Ubuntu Server 24.04 + Docker) como entorno de pruebas integradas y GitHub Actions en cada pull request | Sin GitHub Actions |
| S-02 | Los defectos se registran en una lista "Defectos" del tablero de Trello, que es el tablero único del proyecto (integrador 12.2) | GitHub Issues |
| S-03 | Herramientas: PHPUnit para unitarias e integración y Laravel Dusk para funcionales automatizadas | Pest; Playwright; solo pruebas manuales |
| S-04 | Se mantienen los roles de testing de la v1.0 | Otra distribución |
| S-05 | Casos detallados para las funciones del Hito 2 y casos resumidos para el resto | Todos detallados |
| S-06 | Este plan se entrega el 16/10/2026 | Otra fecha |

## 1. Introducción
El plan define cómo se verifica que AdoptaUY cumple los 57 requisitos funcionales vigentes y los 14 no funcionales del Documento integrador 2.0. Las pruebas acompañan los tres sprints de desarrollo (4, 5 y 6) y no se concentran al final.
Objetivos

- Cada requisito vigente tiene al menos un caso de prueba trazable (sección 8).

- Cada función del Hito 2 se demuestra de punta a punta y cumple el criterio de aceptación de su RF.

- Detectar defectos dentro del mismo sprint en que se introducen.

- Aprobar cada incremento con criterios medibles (sección 6).
Referencias

- Documento integrador 2.0 de AdoptaUY: secciones 8, 9, 13.2 (reglas de negocio y estados), 13.3 (requisitos) y 14 (vistas por actor).

- ISO/IEC/IEEE 29119-3: documentación de pruebas.

- Ley N.º 18.331 de Protección de Datos Personales (Uruguay).

- Plan de pruebas v1.0 (28/09/2026), como línea base de comparación.

## 2. Alcance de las pruebas
Se prueban los cuatro actores (Usuario Invitado, Particular, Refugio y Administrador), las tareas programadas del sistema y los 14 RNF. Las integraciones externas (correo y mapa) se prueban con dobles de prueba y con Mailpit, nunca contra destinatarios reales.

| Elemento | Requisitos | Cómo se prueba |
|---|---|---|
| Registro, confirmación de correo y sesión | RF:0039, RF:0043, RF:0022, RF:0023, RF:0016, RF:0029 | Unitaria, integración y funcional |
| Publicaciones en adopción y sus estados | RF:0017 a RF:0021, RF:0024 a RF:0028, RF:0040, RF:0041, RF:0045 | Integración, funcional y transición de estados |
| Mascotas perdidas y vencimiento a 30 días | RF:0046 a RF:0048 | Integración, funcional y tarea programada |
| Solicitudes de adopción | RF:0049 a RF:0052, RF:0054 | Integración, funcional y transición de estados |
| Seguimiento posterior a la adopción | RF:0055 a RF:0058 | Tarea programada con fecha simulada, integración y funcional |
| Refugios: solicitud, validación, mapa, perfil y cuentas bancarias | RF:0005 a RF:0009, RF:0034 a RF:0038, RF:0042, RF:0044, RF:0062 | Integración y funcional |
| Reportes y umbrales | RF:0059 a RF:0061 | Unitaria (umbrales), integración y funcional |
| Administración de publicaciones y particulares | RF:0001 a RF:0004, RF:0010 a RF:0014 | Integración y funcional |
| Correos | RF:0063 | Integración con Mail::fake() y verificación en Mailpit |
| Control de acceso y datos personales | RNF:0013 y todas las rutas | Integración por rol |
| Requisitos no funcionales | RNF:0001 a RNF:0014 | Procedimiento propio de cada RNF (sección 9.3) |

Fuera de alcance

- Pagos y donaciones: la plataforma solo muestra cuentas bancarias y no procesa ni registra transferencias.

- Donaciones en especie, que se coordinan por contacto directo con el refugio.

- El contacto entre publicante y solicitante por fuera de la plataforma.

- Aplicación móvil nativa o PWA: se prueba la web adaptable desde 360 px de ancho.

- Cobertura fuera de Montevideo y precisión de la ubicación que ingresa el usuario.

- Requisitos dados de baja: RF:0015, RF:0030 a RF:0033 y RF:0053.

## 3. Revisión de testabilidad de los requisitos
Se hizo una revisión estática de los requisitos vigentes del integrador 2.0 y aparecieron 12 puntos en los que todavía no se puede escribir un resultado esperado verificable. Para cada uno se propone un criterio. El equipo confirmó las 12 propuestas el 14/10/2026 (D63 a D74) y se incorporaron al Documento integrador 2.0; los casos de prueba usan esos criterios.

| ID | Requisito | Problema | Propuesta verificable |
|---|---|---|---|
| H-01 | RF:0021, RF:0028, RF:0041 | El filtro de edad "permite ver solo cachorros", pero no se define hasta qué edad un animal es cachorro | Fijar el umbral (por ejemplo, menos de 12 meses) y probar los valores límite 11 y 12 meses |
| H-02 | RF:0039 | No se definen el formato de la cédula ni la política de contraseña | Cédula de 7 u 8 dígitos con dígito verificador válido; contraseña de 8 caracteres como mínimo |
| H-03 | RF:0039 | No se dice si la cédula puede repetirse entre cuentas | La cédula es única, igual que el correo |
| H-04 | RF:0017, RF:0046 | No se fijan formato ni tamaño máximo de las fotografías | JPG, PNG o WEBP de hasta 8 MB cada una, coherente con la configuración del servidor |
| H-05 | RF:0022, RF:0043, sección 8 | RF:0043 impide iniciar sesión sin confirmar el correo, pero la demo del Hito 2 no lo exige. Tampoco se dice qué pasa al intentar iniciar sesión con una cuenta inhabilitada, dada de baja o con un refugio Pendiente o Suspendido | En el Hito 2 se prueba el inicio de sesión sin confirmación. Desde el Sprint 5 se aplica la tabla de decisión de CP-04, con un mensaje genérico que no revela si el correo existe |
| H-06 | RF:0001, RF:0045 | La "baja" de una publicación por el Administrador no dice si la pasa a Archivado o la Suspende (marca de moderación) | La baja por el Administrador pasa la publicación a Archivado; la suspensión por reportes usa la marca de moderación |
| H-07 | RF:0006 | El refugio no tiene un estado "Baja" (13.2.1); no se puede verificar el resultado | Baja lógica: el refugio no puede iniciar sesión, no figura en el mapa y sus publicaciones pasan a Archivado |
| H-08 | RF:0047 | No queda claro si se puede renovar una publicación Perdida antes de que venza o solo cuando ya está Archivada | Se puede renovar en ambos casos; cada renovación fija el vencimiento en la fecha de renovación + 30 días |
| H-09 | RF:0055, RF:0058 | No se fija el horario de las tareas programadas, ni qué pasa si el publicante rechaza la foto después del día 22 | Las tareas corren una vez por día a las 03:00; los días son corridos; una foto rechazada vuelve a dejar pendiente la evidencia, y si ya pasó el día 22 la cuenta se restringe |
| H-10 | RF:0057 | "Si no la revisa, se considera presentada" no fija un plazo | La evidencia cuenta como presentada desde que se envía; el rechazo del publicante es lo único que la anula |
| H-11 | RNF:0013 | Exige HTTPS, pero el servidor local de desarrollo responde por HTTP | Probar HTTPS con un certificado autofirmado en la VM y, en el entorno final, con el certificado definitivo |
| H-12 | RNF:0002, RNF:0011 | La disponibilidad (Zabbix) y el escaneo desde Internet solo son medibles en la topología final, no en la VM local | Medirlos en el servidor local del equipo durante el Sprint 6 y dejar constancia de que la medición es en ese entorno |

## 4. Estrategia de pruebas
Se sigue la pirámide de pruebas: muchas pruebas unitarias, un número medio de integración y pocas funcionales de punta a punta. Todas se automatizan, salvo usabilidad, compatibilidad y aceptación.
Niveles de prueba

| Nivel | Qué se prueba en AdoptaUY | Cuándo |
|---|---|---|
| Unitarias | Reglas de validación (cédula, fotos, campos obligatorios), máquinas de estado (publicación, mascota perdida, solicitud, refugio), cálculo de vencimientos y umbrales de reportes | En cada commit |
| Integración | Controladores con MySQL, middleware de roles, políticas de acceso, colas, tareas programadas con fecha simulada (travelTo) y envío de correos (Mail::fake()) | En cada pull request |
| Funcionales | Flujos completos por actor en el navegador, en caja negra, contra el criterio de aceptación de cada RF | Al cierre de cada sprint |
| Aceptación | Demostración ante el Product Owner (docente) contra los criterios de la sección 8 del integrador | Hito 2 (23/10/2026) y Hito 3 (20/11/2026) |

Tipos de prueba no funcionales

- Rendimiento y carga: RNF:0001, RNF:0004, RNF:0005 y RNF:0006, con 15 usuarios concurrentes.

- Seguridad y datos personales: RNF:0007, RNF:0011, RNF:0013, más acceso por rol y OWASP Top 10 (inyección SQL, XSS, CSRF y acceso a recursos ajenos).

- Usabilidad: RNF:0008, con 5 usuarios que publican una mascota sin ayuda.

- Compatibilidad: RNF:0003, con navegadores y anchos de pantalla desde 360 px.

- Tolerancia a fallos: RNF:0010, con entradas inválidas y correo o mapa caídos.

- Mantenibilidad, escalabilidad y respaldos: RNF:0009, RNF:0012 y RNF:0014.

- Regresión: la suite automatizada completa corre antes de cada merge a la rama principal.
Técnicas de diseño de casos

- Partición de equivalencia: cédula, correo, especie, sexo, motivos de reporte.

- Valores límite: fotos de adopción (0, 1, 6, 7), fotos de mascota perdida (0, 1, 3, 4), edad de cachorro (H-01), días 14/15 del aviso, días 21/22 de la restricción, días 29/30 del vencimiento y umbrales de reportes (2/3, 4/5, 9/10).

- Tabla de decisión: inicio de sesión (correo confirmado × contraseña × estado de la cuenta) y permisos para solicitar adopción.

- Transición de estados: publicación en adopción, mascota perdida, solicitud y refugio, incluidas las transiciones inválidas.

- Casos de uso: flujos principales y alternativos por actor, tomados de las vistas de la sección 14 del integrador.

## 5. Herramientas y entornos
Todas las herramientas son gratuitas y de código abierto, compatibles con Laravel 13, MySQL 8.4 y Docker (S-03).

| Propósito | Herramienta | Justificación |
|---|---|---|
| Unitarias e integración | PHPUnit (php artisan test) | Viene con Laravel; factories, seeders y RefreshDatabase |
| Pruebas HTTP y de roles | Pruebas HTTP de Laravel (actingAs, assertForbidden) | Prueban rutas, middleware y políticas sin navegador |
| Dobles de servicios | Mail::fake(), Storage::fake(), Queue::fake(), Http::fake() | Aíslan correo, almacenamiento de fotos, colas y el servicio de mapas |
| Fechas simuladas | travelTo() de Laravel | Prueba los días 15, 22 y 30 sin esperar |
| Correo en el entorno de pruebas | Mailpit | Captura todos los correos; ninguno sale a Internet |
| Funcionales automatizadas | Laravel Dusk | Automatiza Chrome sobre las vistas Blade |
| Carga | Apache JMeter | Mide el percentil 95 y los errores con 15 usuarios concurrentes |
| Seguridad | OWASP ZAP (escaneo base), composer audit y nmap | Vulnerabilidades web, dependencias con CVE y puertos expuestos |
| Análisis estático | Larastan nivel 5 | Mantenibilidad (RNF:0012) |
| Integración continua | GitHub Actions (S-01) | Corre la suite en cada pull request |
| Defectos | Lista "Defectos" en Trello (S-02) | Tablero único del proyecto |

Entornos

| Entorno | Infraestructura | Base de datos | Uso |
|---|---|---|---|
| Local | Docker Compose en la máquina de cada integrante | MySQL adoptauy_test, recreada en cada prueba | Unitarias e integración durante el desarrollo |
| CI | GitHub Actions con un servicio MySQL 8.4 | Temporal, se crea en cada ejecución | Regresión automática en cada pull request |
| Servidor de pruebas | VM de VirtualBox con Ubuntu Server 24.04 y el mismo Docker Compose (Nginx, PHP-FPM, MySQL, cola, planificador y Mailpit) | MySQL con datos ficticios cargados por seeders | Funcionales, carga, seguridad, compatibilidad, respaldos y demos de cada hito |

Las pruebas usan MySQL y no SQLite, para detectar diferencias reales de tipos, índices y restricciones. Todos los datos de prueba son ficticios y los generan las factories; nunca se cargan datos personales reales (Ley 18.331).

## 6. Criterios y gestión de defectos
Un incremento se aprueba solo si pasa el 100 % de los casos de prioridad Alta de ese sprint y no quedan defectos Críticos ni Mayores abiertos.
Criterios de entrada

- Los RF del sprint tienen un criterio de aceptación verificable, o el punto de la sección 3 que los afecta está resuelto.

- El código está integrado en la rama del sprint y la suite pasa en CI.

- El servidor de pruebas está levantado, con los seeders cargados y Mailpit activo.

- Otro integrante revisó los casos del sprint.
Criterios de salida

- Se ejecutó y aprobó el 100 % de los casos Alta, y al menos el 90 % de los casos Media.

- La prueba de humo (CP-01, CP-04, CP-06) pasa en el servidor de pruebas.

- No quedan defectos Críticos ni Mayores abiertos; los Menores quedan en el backlog con su sprint asignado.

- Para el Hito 3: los RNF se midieron con su procedimiento y se restauró un respaldo (RNF:0014).
Suspensión y reanudación. Las pruebas se suspenden si el servidor de pruebas no está disponible o si un defecto Crítico bloquea más del 30 % de los casos. Se reanudan cuando se corrige el defecto y vuelve a pasar la prueba de humo.
Clasificación de defectos

| Severidad | Definición | Ejemplo en AdoptaUY | Plazo de corrección |
|---|---|---|---|
| Crítica | Bloquea un flujo principal o expone datos personales | Un solicitante ve el teléfono de otro solicitante | El mismo día |
| Mayor | Función incorrecta sin alternativa | La publicación no pasa a Adoptado al concretar la adopción | Dentro del sprint |
| Menor | Falla con alternativa disponible | El filtro por fecha no incluye el día final | En el sprint siguiente |
| Trivial | Estética o textos | Error de tipeo en una etiqueta | Cuando haya capacidad |

Cada defecto es una tarjeta en la lista "Defectos" de Trello (S-02), con caso de prueba, pasos, resultado esperado y obtenido, entorno, severidad y captura. Su ciclo de vida es: Nuevo → Asignado → En corrección → Resuelto → Verificado → Cerrado. Si la verificación falla, vuelve a Reabierto.

## 7. Roles, cronograma y riesgos
Las pruebas se reparten entre los cuatro integrantes (S-04) y acompañan a los Sprints 4, 5 y 6.
Roles

| Integrante | Rol en testing | Responsabilidades |
|---|---|---|
| Camilo Blanco | Líder de pruebas y aceptación | Aprueba los criterios de salida, prepara las demos de los hitos, prueba el módulo Administrador y los respaldos (RNF:0014) |
| Leandro Estévez | Diseño y documentación de pruebas | Mantiene este plan, la matriz de trazabilidad y los casos funcionales; redacta los informes por hito |
| Iñaki Pérez | Pruebas unitarias y de integración | Suite de PHPUnit, dobles de correo y mapa, pruebas de tareas programadas y GitHub Actions |
| Rodrigo González | Pruebas no funcionales | Servidor de pruebas, carga con JMeter, OWASP ZAP, escaneo de puertos y compatibilidad |

Cronograma de pruebas por sprint

| Sprint | Fechas | Actividad de pruebas | Entregable |
|---|---|---|---|
| 4 | 06/10 al 23/10/2026 | Este plan, revisión de testabilidad, CI básico, casos CP-01 a CP-11 y CP-37, y prueba de humo | Plan v2.0 e informe de pruebas del Hito 2 |
| 5 | 24/10 al 06/11/2026 | Casos de refugios, solicitudes de adopción y seguimiento (CP-12 a CP-29), pruebas de tareas programadas y primera prueba de carga | Suite de integración en CI |
| 6 | 07/11 al 20/11/2026 | Reportes, mapa, cuentas bancarias y administración (CP-30 a CP-36), RNF (CP-37 a CP-46), regresión completa y restauración de un respaldo | Informe final de pruebas del Hito 3 |

Riesgos de pruebas

| Riesgo | Mitigación |
|---|---|
| Ningún refugio participa en la aceptación (no se pudo entrevistar a ninguno) | La aceptación la hace el Product Owner en las demos; los flujos del refugio se prueban con cuentas ficticias |
| Poco tiempo de prueba por la capacidad del equipo | Automatizar la regresión y priorizar los casos Alta |
| Las reglas de 15, 22 y 30 días no se pueden esperar en tiempo real | Fechas simuladas con travelTo() y ejecución manual del planificador |
| El servidor de pruebas es una PC del equipo y puede no estar disponible | El mismo Docker Compose se levanta en cualquier máquina; respaldo diario de la base |
| Puntos de la sección 3 sin resolver | Usar la columna Propuesta y actualizar los casos cuando el equipo decida |
| No hay equipos Apple para probar Safari (RNF:0003) | Confirmar si algún integrante tiene uno; si no, declararlo como limitación |
| Datos personales en las pruebas | Solo datos ficticios generados por factories |

## 8. Matriz de trazabilidad
Los 57 RF vigentes y los 14 RNF quedan cubiertos por 46 casos de prueba: 11 del Hito 2 (CP-01 a CP-11), 25 funcionales de los Sprints 5 y 6 (CP-12 a CP-36) y 10 no funcionales (CP-37 a CP-46).

| Requisito | Actor | Descripción breve | Hito | Casos |
|---|---|---|---|---|
| RF:0039 | Invitado | Registro de Particular | 2 | CP-01, CP-02 |
| RF:0043 | Particular | Confirmación de correo | 3 | CP-03 |
| RF:0022 | Todos | Inicio de sesión | 2 | CP-04 |
| RF:0023 | Todos | Cierre de sesión | 2 | CP-05 |
| RF:0017, RF:0024 | Particular, Refugio | Alta de publicación en adopción | 2 | CP-06, CP-07, CP-11 |
| RF:0040 | Invitado | Listado público En adopción y Perdida | 2 | CP-08 |
| RF:0048 | Todos | Consulta de mascotas perdidas | 2 | CP-08 |
| RF:0046 | Particular, Refugio | Alta de mascota perdida | 2 | CP-09, CP-10, CP-11 |
| RF:0021, RF:0028, RF:0041 | Todos | Consulta con filtros y cachorros | 3 | CP-12 |
| RF:0018, RF:0019, RF:0025, RF:0026, RF:0045 | Publicante, Administrador | Baja, modificación y estados de la publicación | 3 | CP-13, CP-15 |
| RF:0020, RF:0027 | Particular, Refugio | Listado de publicaciones propias | 3 | CP-13 |
| RF:0047 | Publicante, Sistema | Vencimiento y renovación de mascota perdida | 3 | CP-14 |
| RF:0016, RF:0029 | Particular, Refugio | Modificación del perfil propio | 3 | CP-16 |
| RF:0034 | Invitado | Solicitud de registro de refugio | 3 | CP-17 |
| RF:0005, RF:0044 | Administrador | Validación o rechazo de la solicitud | 3 | CP-18 |
| RF:0007, RF:0008, RF:0009 | Administrador | Modificación, consulta y listado de refugios | 3 | CP-18 |
| RF:0006 | Administrador | Baja de refugio | 3 | CP-19 |
| RF:0038, RF:0042, RF:0062 | Todos | Mapa, listado y perfil público del refugio | 3 | CP-20 |
| RF:0035, RF:0036, RF:0037 | Refugio | Cuentas bancarias | 3 | CP-21 |
| RF:0049 | Particular | Solicitar adopción | 3 | CP-22 |
| RF:0050 | Publicante | Solicitudes recibidas | 3 | CP-23 |
| RF:0051 | Publicante | Rechazo de una solicitud | 3 | CP-24 |
| RF:0052 | Publicante | Concretar adopción | 3 | CP-25 |
| RF:0054 | Particular | Solicitudes enviadas | 3 | CP-26 |
| RF:0055 | Sistema | Aviso de seguimiento del día 15 | 3 | CP-27 |
| RF:0056, RF:0057 | Adoptante, Publicante | Envío y revisión de la evidencia | 3 | CP-28 |
| RF:0058 | Sistema | Restricción por seguimiento incumplido | 3 | CP-29 |
| RF:0059 | Particular, Refugio | Reportar | 3 | CP-30 |
| RF:0061 | Sistema | Umbrales de reportes | 3 | CP-31 |
| RF:0060 | Administrador | Resolución de reportes | 3 | CP-32 |
| RF:0001 a RF:0004, RF:0011, RF:0012 | Administrador | Gestión de publicaciones | 3 | CP-33 |
| RF:0010, RF:0013, RF:0014 | Administrador | Gestión de particulares | 3 | CP-34 |
| RF:0063 | Sistema | Correos por evento | 3 | CP-03, CP-17, CP-18, CP-22, CP-27, CP-35 |
| Transversal | Todos | Control de acceso por rol | 2 y 3 | CP-11, CP-15, CP-36 |
| RNF:0007 | — | Almacenamiento de contraseñas | 2 | CP-37 |
| RNF:0001, RNF:0004, RNF:0005, RNF:0006 | — | Rendimiento y carga | 3 | CP-38 |
| RNF:0003 | — | Compatibilidad | 3 | CP-39 |
| RNF:0010 | — | Tolerancia a fallos | 3 | CP-40 |
| RNF:0011 | — | Seguridad de la red | 3 | CP-41 |
| RNF:0008 | — | Usabilidad | 3 | CP-42 |
| RNF:0009, RNF:0012 | — | Escalabilidad y mantenimiento | 3 | CP-43 |
| RNF:0002 | — | Disponibilidad | 3 | CP-44 |
| RNF:0013 | — | Datos personales | 3 | CP-23, CP-45 |
| RNF:0014 | — | Respaldos | 3 | CP-46 |

Requisitos dados de baja sin casos: RF:0015 (unificado en RF:0039), RF:0030 a RF:0033 (insumos) y RF:0053 (revertir adopción).

## 9. Diseño de casos de prueba
Los casos del Hito 2 se detallan con pasos, datos y resultado esperado; los demás se resumen y se detallan al planificar su sprint (S-05). La prueba de humo está formada por CP-01, CP-04 y CP-06. Todos los datos son ficticios.

### 9.1 Casos detallados del Hito 2

| ID | Requisito | Caso | Precondición | Pasos y datos | Resultado esperado | Técnica | Nivel | Prioridad |
|---|---|---|---|---|---|---|---|---|
| CP-01 | RF:0039 | Registro de Particular válido | El correo y la cédula no están registrados | Registrarse con cédula 1.234.567-2, nombre Ana, apellido Suárez, teléfono 099 123 456, correo ana@prueba.uy y contraseña de 8 caracteres | Se crea la cuenta con rol Particular y se muestra la confirmación del registro | Partición de equivalencia | Funcional | Alta |
| CP-02 | RF:0039 (H-02, H-03) | Registro con datos inválidos | Existe ana@prueba.uy con cédula 1.234.567-2 | a) sin teléfono; b) correo ana@prueba.uy; c) correo sin @; d) cédula con dígito verificador incorrecto; e) cédula repetida; f) contraseña de 7 caracteres | Cada envío se rechaza con un mensaje junto al campo y no se crea ningún usuario | Partición y valores límite | Integración | Alta |
| CP-03 | RF:0043, RF:0063 | Confirmación de correo (se ejecuta desde el Sprint 5) | Particular recién registrado | Abrir el correo en Mailpit y seguir el enlace; repetir con un enlace alterado | Llega un único correo; el enlace válido confirma la dirección y el alterado se rechaza | Caso de uso | Integración | Alta |
| CP-04 | RF:0022 (H-05) | Inicio de sesión | Particular activo creado en CP-01 | a) correo y contraseña correctos; b) contraseña incorrecta; c) correo inexistente. Desde el Sprint 5: d) correo sin confirmar; e) cuenta inhabilitada | a) Entra a su panel; b) a e) acceso denegado con el mismo mensaje genérico, sin revelar si el correo existe | Tabla de decisión | Integración | Alta |
| CP-05 | RF:0023 | Cierre de sesión | Usuario con sesión iniciada | Cerrar sesión y usar el botón Atrás del navegador | Vuelve al inicio; las páginas privadas piden iniciar sesión | Caso de uso | Funcional | Media |
| CP-06 | RF:0017, RF:0024 | Alta de publicación en adopción válida | Particular con sesión iniciada | Perro, mestizo, macho, 8 meses, mediano, sano, tranquilo, esterilizado, Pocitos y 3 fotos JPG | La publicación queda En adopción y aparece en el listado público | Partición de equivalencia | Funcional | Alta |
| CP-07 | RF:0017 (H-04) | Límites de fotos y campos obligatorios | Particular con sesión iniciada | Enviar con 0, 1, 6 y 7 fotos; enviar sin especie; enviar un archivo .exe renombrado a .jpg; enviar una foto de 9 MB | Se aceptan 1 y 6 fotos; los demás envíos se rechazan con un mensaje y no se crea la publicación | Valores límite | Integración | Alta |
| CP-08 | RF:0040, RF:0048 | Página de inicio con el listado público | Publicaciones en todos los estados: En adopción, Adoptado, Archivado, Perdida, Encontrada y Archivada | Abrir la página de inicio sin sesión | Solo se listan las En adopción y, por separado, las Perdida | Transición de estados | Funcional | Alta |
| CP-09 | RF:0046 | Alta de mascota perdida válida | Particular con sesión iniciada | Nombre Luna, gata, siamesa, hembra, collar rojo, perdida el 10/10/2026 en Malvín, contacto 098 765 432 y 2 fotos | Queda en estado Perdida, con vencimiento a 30 días, y es visible sin sesión | Partición de equivalencia | Funcional | Alta |
| CP-10 | RF:0046 | Límites de mascota perdida | Particular con sesión iniciada | Enviar con 0, 1, 3 y 4 fotos; enviar sin zona; enviar con fecha de extravío futura | Se aceptan 1 y 3 fotos; los demás envíos se rechazan con un mensaje | Valores límite | Integración | Alta |
| CP-11 | RF:0017, RF:0046, transversal | Acceso para publicar | Sin sesión | Abrir los formularios de publicación y enviar un POST directo a ambas rutas | Redirige al inicio de sesión; no se crea ninguna publicación | Tabla de decisión | Integración | Alta |

### 9.2 Casos resumidos de los Sprints 5 y 6

| ID | Requisito | Caso | Resultado esperado | Técnica | Prioridad |
|---|---|---|---|---|---|
| CP-12 | RF:0021, RF:0028, RF:0041 (H-01) | Filtros combinados y cachorros | Especie, raza, sexo y fecha se combinan; con el filtro de cachorros aparece el animal de 11 meses y no el de 12 | Partición y valores límite | Media |
| CP-13 | RF:0018 a RF:0020, RF:0025 a RF:0027, RF:0045 | Estados de la publicación en adopción | Se admiten En adopción → Archivado → En adopción y En adopción → Adoptado; se rechazan Adoptado → cualquier estado; el listado propio muestra cada estado | Transición de estados | Alta |
| CP-14 | RF:0047 (H-08) | Vencimiento y renovación de mascota perdida | Pasa a Archivada el día 30 y no el 29; al renovarse vuelve a Perdida con 30 días más; Encontrada y Archivada salen de la consulta pública | Valores límite y fecha simulada | Alta |
| CP-15 | RF:0019, RF:0026 | Modificar una publicación ajena | Un PUT con el identificador de una publicación de otro usuario devuelve HTTP 403 y no cambia nada | Seguridad | Alta |
| CP-16 | RF:0016, RF:0029 | Modificación del perfil propio | Se guardan los campos editables; la cédula y el correo no se pueden cambiar aunque se envíen en la petición | Partición | Media |
| CP-17 | RF:0034, RF:0063 | Solicitud de registro de refugio | Queda Pendiente de validación, aparece en el panel del Administrador y llega un único correo de recepción | Caso de uso | Alta |
| CP-18 | RF:0005, RF:0007 a RF:0009, RF:0044 | Validación o rechazo del refugio | Validado: puede iniciar sesión y recibe el correo de alta. Rechazado: el motivo es obligatorio y no puede iniciar sesión | Transición de estados | Alta |
| CP-19 | RF:0006 (H-07) | Baja y suspensión de refugio | El refugio pasa a Dado de baja: no puede iniciar sesión, sale del mapa y del listado, y sus publicaciones pasan a Archivado | Transición de estados | Alta |
| CP-20 | RF:0038, RF:0042, RF:0062 | Mapa, listado y perfil público | Solo figuran los refugios Validados; el perfil muestra nombre, ubicación, contacto y cuentas bancarias; si el mapa no carga, el listado sigue disponible | Partición | Media |
| CP-21 | RF:0035 a RF:0037 | Cuentas bancarias | Una cuenta completa se publica; sin número o sin titular se rechaza; la baja la quita del perfil público | Partición | Media |
| CP-22 | RF:0049, RF:0063 | Solicitar adopción | Queda Pendiente y el publicante recibe un correo. Se rechaza si no acepta compartir sus datos, si la publicación es propia, si ya tiene una solicitud pendiente sobre ella, si la cuenta está restringida o si quien solicita es un Refugio | Tabla de decisión | Alta |
| CP-23 | RF:0050, RNF:0013 | Solicitudes recibidas | El publicante ve nombre, teléfono, correo, fecha y estado de cada solicitante; otro usuario recibe HTTP 403 al abrir esa lista | Seguridad | Alta |
| CP-24 | RF:0051 | Rechazo de una solicitud | Pasa a Rechazada y deja de figurar entre las pendientes; la publicación no cambia de estado | Transición de estados | Media |
| CP-25 | RF:0052 | Concretar adopción | Con 3 solicitudes: la elegida pasa a Adoptada, las otras 2 a Rechazada, la publicación a Adoptado y se guarda la fecha; ningún cambio posterior la devuelve a En adopción | Transición de estados | Alta |
| CP-26 | RF:0054 | Solicitudes enviadas | El solicitante ve solo sus solicitudes, con el estado actualizado | Caso de uso | Media |
| CP-27 | RF:0055, RF:0063 | Aviso del día 15 | Con la fecha simulada en el día 14 no hay aviso; en el día 15 se envía un único correo al adoptante, y no se repite en el día 16 | Valores límite y fecha simulada | Alta |
| CP-28 | RF:0056, RF:0057 | Envío y revisión de la evidencia | La foto queda Enviada y visible para el publicante; si la rechaza, se pide otra; sin revisión cuenta como presentada | Transición de estados | Alta |
| CP-29 | RF:0058 (H-09) | Restricción del día 22 | En el día 21 no hay restricción; en el día 22, sin foto, no puede solicitar ni publicar pero sí enviar la foto, y al enviarla se levanta la restricción | Valores límite y fecha simulada | Alta |
| CP-30 | RF:0059 | Reportar | El reporte queda Pendiente con el motivo elegido; un segundo reporte del mismo usuario sobre lo mismo se rechaza; sin sesión no se puede reportar | Partición | Alta |
| CP-31 | RF:0061 | Umbrales de reportes | Publicación: con 2 reportes sigue visible y con 3 se oculta. Particular: revisión con 5 e inhabilitación con 10. Refugio: Suspendido con 3. Solo cuentan usuarios distintos | Valores límite | Alta |
| CP-32 | RF:0060 | Resolución de reportes | Desestimar devuelve la visibilidad; confirmar suspende la publicación; queda registrada la medida con su fecha | Transición de estados | Alta |
| CP-33 | RF:0001 a RF:0004, RF:0011, RF:0012 (H-06) | Gestión de publicaciones por el Administrador | Los filtros por fecha, hora, tipo de animal y usuario devuelven solo coincidencias; la edición se guarda y la baja pasa la publicación a Archivado | Partición | Media |
| CP-34 | RF:0010, RF:0013, RF:0014 | Gestión de particulares | La baja lógica conserva el registro y sus publicaciones en la base e impide iniciar sesión | Caso de uso | Alta |
| CP-35 | RF:0063 | Un correo por evento | Cada uno de los 5 eventos de la tabla 13.2.6 genera exactamente un correo al destinatario indicado | Partición | Alta |
| CP-36 | Transversal | Control de acceso por rol | Particular contra rutas de Refugio y Administrador, Refugio contra rutas de Administrador e Invitado contra rutas privadas: siempre HTTP 403 o redirección al inicio de sesión | Tabla de decisión | Alta |

### 9.3 Casos no funcionales

| ID | Requisito | Herramienta | Procedimiento | Criterio de aprobación | Prioridad |
|---|---|---|---|---|---|
| CP-37 | RNF:0007 | PHPUnit y consulta SQL | Registrar un usuario y leer la columna de la contraseña | Hash bcrypt (prefijo $2y$), nunca texto plano; Hash::check lo valida | Alta |
| CP-38 | RNF:0001, RNF:0004, RNF:0005, RNF:0006 | JMeter en el servidor de pruebas | 15 usuarios concurrentes durante 10 minutos: inicio de sesión, listado y consulta con filtros sobre 1.000 publicaciones | Percentil 95 menor a 2 s en consultas y en inicio de sesión; 0 errores 5xx | Alta |
| CP-39 | RNF:0003 | Dusk y prueba manual | Ejecutar la prueba de humo en Chrome, Firefox, Edge y Safari, en escritorio y en celular a 360 px | Mismo resultado en todos y sin desplazamiento horizontal | Media |
| CP-40 | RNF:0010 | PHPUnit y prueba manual | Campos vacíos, texto de 10.000 caracteres, fechas imposibles; servicio de correo y de mapa detenidos | Error de validación con mensaje, nunca error 500; las demás funciones siguen disponibles | Media |
| CP-41 | RNF:0011 (H-12) | nmap y OWASP ZAP | Escaneo de puertos desde fuera de la red del servidor y escaneo base de ZAP | Solo 80 y 443 abiertos; ninguna alerta alta en ZAP | Alta |
| CP-42 | RNF:0008 | Prueba con usuarios | 5 personas sin experiencia previa publican una mascota sin ayuda, con cronómetro | Al menos 4 de 5 terminan en menos de 3 minutos | Media |
| CP-43 | RNF:0009, RNF:0012 | Docker Compose, migraciones y Larastan | Desplegar en un host limpio siguiendo la documentación y aplicar una migración nueva sobre una copia de la base | El despliegue funciona sin pasos extra; no se pierden datos; Larastan nivel 5 sin errores | Media |
| CP-44 | RNF:0002 (H-12) | Zabbix | Monitoreo HTTP cada 5 minutos durante el Sprint 6 | Disponibilidad de al menos 99 % en el período medido | Baja |
| CP-45 | RNF:0013 (H-11) | Pruebas HTTP por rol | Revisar perfiles, solicitudes y fotos de seguimiento con cada rol; abrir el sitio por HTTP | La cédula nunca se muestra; teléfono, correo y fotos solo los ven quienes indica el RNF; HTTP redirige a HTTPS | Alta |
| CP-46 | RNF:0014 | Script de respaldo y restauración | Verificar el respaldo diario y restaurarlo en una base vacía | Existe un respaldo de las últimas 24 h; la base restaurada tiene los mismos registros e imágenes | Alta |

## 10. Comparación con la versión 1.0
La v2.0 pasa de 33 a 46 casos y cubre 57 RF vigentes, contra 42 en la v1.0. Elimina las pruebas de funciones que salieron del alcance y agrega las de adopción, seguimiento, reportes y datos personales.

| Aspecto | v1.0 (28/09/2026) | v2.0 (13/10/2026) | Motivo |
|---|---|---|---|
| Base de requisitos | 42 RF y 12 RNF del integrador 1.0 | 57 RF vigentes (63 en total, 6 dados de baja) y 14 RNF del integrador 2.0 | Reducción de alcance y devolución docente |
| Casos de prueba | 33 (CP-01 a CP-33) | 46 (CP-01 a CP-46), renumerados | Nuevos módulos; la numeración de la v1.0 no se conserva |
| Revisión de testabilidad | 8 hallazgos sobre el integrador 1.0 | 12 hallazgos sobre el integrador 2.0 | Los anteriores se resolvieron en la v2.0 del integrador |
| Donaciones | Mercado Pago en sandbox y comisión del 10 % (CP-33) | Solo cuentas bancarias publicadas (CP-21); pagos fuera de alcance | D4: sin pasarela de pago |
| Insumos | ABM de insumos (CP-20) | Sin casos: RF:0030 a RF:0033 dados de baja | Donaciones en especie fuera de la plataforma |
| Mascotas perdidas | Búsqueda de refugios por radio de 5 a 25 km (CP-32) | Publicación con vencimiento a 30 días y renovación (CP-09, CP-10, CP-14) | D3 y decisión de quitar la búsqueda por cercanía |
| Cachorros | Sección propia (CP-08) | Filtro de edad dentro de la consulta (CP-12) | D5 |
| Estados de publicación | Activa, Pausada, Adoptada y Archivada | En adopción, Adoptado y Archivado; Perdida, Encontrada y Archivada | D10 |
| Adopción y seguimiento | Sin casos | Solicitud, rechazo, concreción, aviso del día 15 y restricción del día 22 (CP-22 a CP-29) | D6 y D7 |
| Reportes | Sin casos | Reporte, umbrales y resolución (CP-30 a CP-32) | D8 |
| Correos | Sin casos | Un correo por evento, verificado en Mailpit (CP-03, CP-35) | D9 |
| Datos personales y respaldos | Sin RNF propio | RNF:0013 y RNF:0014 (CP-45, CP-46) | Devolución docente y Ley 18.331 |
| Métricas de RNF | 50 usuarios concurrentes, SUS ≥ 68, cobertura ≥ 70 % | 15 usuarios concurrentes, 4 de 5 usuarios publican en menos de 3 min, despliegue reproducible | Métricas acordadas en el integrador 2.0 |
| Entornos | Local, CI y staging de réplica | Local, CI y servidor de pruebas en VirtualBox | El despliegue es en un servidor local del equipo |
| Gestión de defectos | GitHub Issues | Lista "Defectos" en Trello (S-02) | Trello es el tablero único del proyecto |
| Aceptación | Con un refugio piloto | Con el Product Owner en las demos de cada hito | No se pudo contar con ningún refugio |
| Cronograma | Sprints 2 a 7 (8 sprints en total) | Sprints 4 a 6 (6 sprints en total) | Nueva planificación del integrador 2.0 |
| Nivel de detalle | Los 33 casos detallados | 11 detallados (Hito 2) y 35 resumidos (S-05) | Prioridad en lo que se demuestra el 23/10/2026 |

