# Base de datos de AdoptaUY (script SQL)

Modelo físico en MySQL del pasaje a tablas (15.2.1) y de las restricciones RNE01 a RNE24 del Documento integrador 2.0. Los nombres de tablas y columnas son los del DER (`Publicacion_Animal`, `ID_Usuario`, `Estado_Validacion`).

| Archivo | Qué es |
|---|---|
| [`adoptauy.sql`](adoptauy.sql) | Crea la base `adoptauy`: 12 tablas, 2 funciones, 13 triggers, 3 procedimientos y 3 vistas |
| [`probar.sh`](probar.sh) | 73 pruebas de las restricciones sobre una base vacía (también corren en GitHub Actions, job `base-de-datos`) |

## Cómo usarlo

```bash
# Con el entorno de Docker levantado (MySQL en 127.0.0.1:3307):
mysql -h 127.0.0.1 -P 3307 -uroot -p < docs/base-de-datos/adoptauy.sql

# Pruebas (borran y recrean la base adoptauy: no usar contra datos reales)
MYSQL="mysql -h 127.0.0.1 -P 3307 -uroot -proot_dev" bash docs/base-de-datos/probar.sh
```

> **Atención:** el script empieza con `DROP DATABASE IF EXISTS adoptauy`. En el entorno de Docker esa es la base de desarrollo de Laravel: las migraciones y este script no conviven en la misma base. Para probarlo sin perder datos, cambiá el nombre de la base en las primeras líneas.

## Dónde se cumple cada restricción

| Mecanismo | Restricciones |
|---|---|
| CHECK, claves únicas y foráneas | RNE05, RNE10, RNE12, RNE13, RNE22, formato de CI, teléfono y largos de campos (D79 a D81), campos y estados según el tipo de publicación (RNE02, RNE17) |
| Triggers | RNE01 (máximo de fotos), RNE02 (transiciones), RNE04, RNE14, RNE15 (umbrales de reportes), RNE16, RNE18, RNE20, RNE21, RNE23 (dígito verificador), RNE24 |
| Procedimientos | `sp_concretar_adopcion` (RNE19, RNE20), `sp_renovar_perdida` (RNE17, D70), `sp_archivar_perdidas_vencidas` (tarea diaria de las 03:00, D71) |
| Aplicación (Laravel) | Mínimo de 1 foto y tamaño de 8 MB (RNE01), fila de subtipo de cada usuario (RNE11), plazo de 5 días hábiles (RNE06), motivos de reporte según el objeto |

Ninguna clave foránea borra en cascada: las bajas son lógicas (cambio de estado).

## Supuestos a confirmar por el equipo

1. `Estado_Esterilizacion`: «Esterilizado» o «No esterilizado».
2. `Tipo_Cuenta`: texto libre.
3. `Estado_Reporte`: Pendiente, Desestimado o Confirmado. `Medida`: Aviso, Suspensión o Baja lógica.
4. Los reportes desestimados no cuentan para los umbrales de RNE15.
5. `Email_Verificado` guarda la fecha de confirmación; `NULL` significa sin confirmar.
6. Los nombres con tilde o ñ (`Contraseña`, `Tamaño`) se mantienen como en el DER; por eso el script empieza con `SET NAMES utf8mb4`.

Responsable de revisión: Camilo Blanco (base de datos).
