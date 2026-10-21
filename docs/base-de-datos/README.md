# Base de datos de AdoptaUY (script SQL)

Tablas en MySQL según el DER y el diccionario de datos. `Publicacion_Animal` guarda los datos comunes de toda publicación y tiene dos subtipos: `Publicacion_Adopcion` y `Publicacion_Perdida`.

| Archivo | Qué es |
|---|---|
| [`adoptauy.sql`](adoptauy.sql) | Crea la base `adoptauy` con sus 14 tablas: claves primarias y foráneas, NOT NULL, UNIQUE, DEFAULT y ENUM |
| [`datos-prueba.sql`](datos-prueba.sql) | Datos ficticios: 8 usuarios, 3 refugios, 9 publicaciones, solicitudes, adopciones, seguimiento y reportes, con al menos un registro por estado |
| [`usuarios.sql`](usuarios.sql) | 8 usuarios de MySQL con privilegio mínimo, uno por rol de la aplicación (ver abajo) |
| [`probar.sh`](probar.sh) | 65 pruebas: estructura, claves, valores por defecto, coherencia de los datos de prueba y cada privilegio permitido y denegado (también corren en GitHub Actions, job `base-de-datos`) |

Por ahora el script no tiene triggers, procedimientos, funciones ni vistas: las restricciones RNE que no resuelven las claves las valida Laravel. Esos objetos se agregan en el Hito 3.

## Tablas

| Grupo | Tablas |
|---|---|
| Usuarios | `Usuario` (supertipo), `Particular`, `Refugio`, `Administrador`, `Refugio_Datos_Contacto`, `Cuenta_Bancaria` |
| Publicaciones | `Publicacion_Animal` (supertipo), `Publicacion_Adopcion`, `Publicacion_Perdida`, `Foto_Animal` |
| Adopción | `Solicitud_Adopcion` (sobre `Publicacion_Adopcion`, solo de un `Particular`), `Adopcion`, `Seguimiento` |
| Moderación | `Reporte` |

## Usuarios de la base de datos

| Usuario | Lo usa | Puede | No puede |
|---|---|---|---|
| `adoptauy_login` | Inicio de sesión (RF:0022) | Leer correo, hash de contraseña y estado de la cuenta | Ver cédula, teléfono ni ningún otro dato; modificar nada |
| `adoptauy_publico` | Usuario Invitado | Leer lo público, registrar Particulares (RF:0039) y solicitudes de refugio (RF:0034) | Ver documentos de refugios, cédulas ni teléfonos; modificar |
| `adoptauy_particular` | Particular con sesión | Publicar, solicitar, gestionar solicitudes, seguimiento y reportar | Borrar publicaciones; ver cédulas; cambiar el estado de un refugio |
| `adoptauy_refugio` | Refugio validado | Publicar, gestionar solicitudes recibidas, editar su perfil y sus cuentas | Crear solicitudes; cambiar su estado de validación |
| `adoptauy_admin` | Administrador | Leer, agregar y modificar todo | Borrar (bajas lógicas) ni cambiar la estructura |
| `adoptauy_sistema` | Tareas programadas y colas | Archivar perdidas vencidas, actualizar seguimiento, estados por umbrales | Crear publicaciones ni cambiar otros datos |
| `adoptauy_migraciones` | Solo `php artisan migrate` | Crear y modificar tablas | — (nunca lo usa la aplicación en marcha) |
| `adoptauy_respaldo` | `mysqldump` del respaldo diario | Leer y bloquear tablas para el volcado | Modificar datos |

Las contraseñas de `usuarios.sql` son marcadores (`CAMBIAR_…`): se reemplazan en el servidor y se guardan solo en su `.env`. En el Hito 2 la aplicación usa un único usuario sin permisos de estructura; desde el Sprint 5 Laravel cambia de conexión según el rol de quien inició sesión. MySQL no filtra filas: que un Particular vea solo los solicitantes de sus propias publicaciones lo controla la aplicación (RNF:0013).

## Cómo usarlo

```bash
# Con el entorno de Docker levantado (MySQL en 127.0.0.1:3307):
mysql -h 127.0.0.1 -P 3307 -uroot -p < docs/base-de-datos/adoptauy.sql
mysql -h 127.0.0.1 -P 3307 -uroot -p < docs/base-de-datos/datos-prueba.sql   # opcional: datos ficticios
mysql -h 127.0.0.1 -P 3307 -uroot -p < docs/base-de-datos/usuarios.sql       # antes, reemplazar las contraseñas

# Pruebas (borran y recrean la base adoptauy: no usar contra datos reales)
MYSQL_PWD=root_dev MYSQL="mysql -h 127.0.0.1 -P 3307 -uroot" PUERTO=3307 bash docs/base-de-datos/probar.sh
```

> **Atención:** el script empieza con `DROP DATABASE IF EXISTS adoptauy`. En el entorno de Docker esa es la base de desarrollo de Laravel. Para probarlo sin perder datos, cambiá el nombre de la base en las primeras líneas.

## Valores y largos que toma del documento integrador

- Sin `Temperamento`.
- `Nombre` y `Apellido`: hasta 50 caracteres. `CI`: 7 u 8 dígitos. `Telefono` y `Contacto` (mascota perdida): 8 o 9 dígitos.
- `Raza`: hasta 50 caracteres. `Ubicacion_Aproximada` y `Zona_Extravio`: hasta 100.
- `Email_Verificado` guarda la fecha de confirmación; `NULL` significa sin confirmar.
- `Estado_Validacion`: Pendiente de validación, Validado, Rechazado, Suspendido o Dado de baja.
- `Tipo_Cuenta`: texto libre.
- `Estado_Reporte`: Pendiente, Desestimado o Confirmado. `Medida`: Aviso, Suspensión o Baja lógica.

Ninguna clave foránea borra en cascada: las bajas son lógicas (cambio de estado).

Responsable de revisión: Camilo Blanco (base de datos).
