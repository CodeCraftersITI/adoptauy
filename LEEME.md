# AdoptaUY · Entrega de Programación

Plataforma web de adopción responsable de perros y gatos en Montevideo.
Equipo CodeCrafters (ITI CETP-UTU): Camilo Blanco, Leandro Estévez, Iñaki Pérez y Rodrigo González.

Hecha con Laravel 13 (PHP 8.4), MySQL 8.4 y Nginx, todo en contenedores de Docker.

## Qué hace falta

- Docker Desktop (Windows o Mac) o Docker Engine con Compose (Linux).
- Los puertos 8080, 8443, 3307 y 8025 libres.
- Internet la primera vez, para bajar las imágenes y las dependencias de Composer.

## Cómo levantarla

1. Descomprimir el zip y abrir una terminal en la carpeta `adoptauy`.
2. Levantar los contenedores:

   ```bash
   docker compose up -d --build
   ```

   La primera vez tarda unos minutos: instala las dependencias, crea las tablas y los
   usuarios de MySQL. Se puede seguir el avance con `docker compose logs -f app`
   hasta que aparezca `[adoptauy] Laravel listo`.

3. Cargar los datos de prueba:

   ```bash
   docker compose exec app php artisan db:seed
   docker compose exec app php artisan db:seed --class=RefugiosDemoSeeder
   docker compose exec app php artisan db:seed --class=DatosDemoSeeder
   ```

   El último baja fotos de perros y gatos de internet; sin conexión usa imágenes de color.

4. Entrar a **https://localhost:8443**. El certificado es autofirmado: el navegador
   avisa y hay que aceptar para continuar («Avanzado» → «Continuar»).

Para apagarla: `docker compose down` (los datos quedan guardados).
Para borrar todo y empezar de cero: `docker compose down -v`.

## Usuarios de prueba

Todos los datos son ficticios. La contraseña de todas las cuentas es **adoptauy-prueba**.

| Rol | Correo |
|---|---|
| Administrador | admin@adoptauy.test |
| Refugio | refugio@adoptauy.test |
| Particular | particular@adoptauy.test |
| Refugios con mapa, cuentas y mascotas | refugio1@prueba.uy, refugio2@prueba.uy, … |

También se puede crear una cuenta nueva desde «Crear cuenta». El correo de confirmación
no sale a internet: llega a **http://localhost:8025** (Mailpit), donde está el enlace para confirmar.

## Qué se puede probar

- **Invitado:** ver mascotas en adopción, perdidas y encontradas con filtros, el detalle
  de cada una y el mapa de refugios.
- **Particular:** publicar (adopción, perdida o encontrada), editar y archivar sus
  publicaciones, solicitar adopciones, elegir al adoptante entre los solicitantes,
  el seguimiento con foto después de la adopción, reportar y editar su perfil.
- **Refugio:** lo mismo que el Particular para publicar, más «Mi refugio»: ubicación en
  el mapa y cuentas bancarias para donaciones.
- **Administrador:** validar o rechazar solicitudes de refugios, administrar refugios,
  particulares y publicaciones, y resolver reportes.

«Continuar con Google» necesita credenciales de Google que no van en la entrega
(GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET en `src/laravel/.env`). Sin ellas, el resto
funciona igual. En las pruebas automáticas Google se simula.

## Base de datos

- Las tablas se crean con las migraciones de Laravel (`src/laravel/database/migrations`).
  El mismo modelo en SQL está en `docs/base-de-datos/adoptauy.sql`.
- La aplicación no entra a MySQL con un solo usuario con todos los permisos: cada rol usa
  el suyo (`adoptauy_publico`, `adoptauy_login`, `adoptauy_particular`, `adoptauy_refugio`,
  `adoptauy_admin` y `adoptauy_sistema`), con privilegio mínimo. Están en
  `docs/base-de-datos/usuarios.sql` y los crea el servicio `usuarios-bd` al levantar.
- Para verla con un cliente (Workbench, DBeaver): `127.0.0.1:3307`, usuario `adoptauy`,
  contraseña `adoptauy_dev`, base `adoptauy`.

## Dónde está cada cosa

| Carpeta | Contenido |
|---|---|
| `src/laravel/routes/web.php` | Rutas, agrupadas por rol con el middleware `rol` |
| `src/laravel/app/Http/Controllers` | Controladores |
| `src/laravel/app/Http/Middleware` | `VerificarRol` (acceso por rol), `ConexionPorRol` (usuario de MySQL) y `AntiBot` |
| `src/laravel/app/Models` | Modelos de Eloquent, uno por tabla |
| `src/laravel/resources/views` | Vistas Blade |
| `src/laravel/database` | Migraciones, factories y seeders |
| `src/laravel/tests` | Pruebas con PHPUnit (`Feature`, `Unit`) y Laravel Dusk (`Browser`) |
| `docker`, `docker-compose.yml` | Contenedores: PHP-FPM, Nginx, MySQL, Mailpit, cola y tareas programadas |

## Pruebas automáticas

```bash
docker compose exec app php artisan test
```
