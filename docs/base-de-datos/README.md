# Base de datos de AdoptaUY (script SQL)

Tablas en MySQL según el DER y el diccionario de datos. `Publicacion_Animal` guarda los datos comunes de toda publicación y tiene dos subtipos: `Publicacion_Adopcion` y `Publicacion_Perdida`.

| Archivo | Qué es |
|---|---|
| [`adoptauy.sql`](adoptauy.sql) | Crea la base `adoptauy` con sus 14 tablas: claves primarias y foráneas, NOT NULL, UNIQUE, DEFAULT y ENUM |
| [`probar.sh`](probar.sh) | 26 pruebas básicas sobre una base vacía: tablas, claves y valores por defecto (también corren en GitHub Actions, job `base-de-datos`) |

Por ahora el script no tiene triggers, procedimientos, funciones ni vistas: las restricciones RNE que no resuelven las claves las valida Laravel. Esos objetos se agregan en el Hito 3.

## Tablas

| Grupo | Tablas |
|---|---|
| Usuarios | `Usuario` (supertipo), `Particular`, `Refugio`, `Administrador`, `Refugio_Datos_Contacto`, `Cuenta_Bancaria` |
| Publicaciones | `Publicacion_Animal` (supertipo), `Publicacion_Adopcion`, `Publicacion_Perdida`, `Foto_Animal` |
| Adopción | `Solicitud_Adopcion` (sobre `Publicacion_Adopcion`, solo de un `Particular`), `Adopcion`, `Seguimiento` |
| Moderación | `Reporte` |

## Cómo usarlo

```bash
# Con el entorno de Docker levantado (MySQL en 127.0.0.1:3307):
mysql -h 127.0.0.1 -P 3307 -uroot -p < docs/base-de-datos/adoptauy.sql

# Pruebas (borran y recrean la base adoptauy: no usar contra datos reales)
MYSQL_PWD=root_dev MYSQL="mysql -h 127.0.0.1 -P 3307 -uroot" bash docs/base-de-datos/probar.sh
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
