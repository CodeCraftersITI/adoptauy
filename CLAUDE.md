# AdoptaUY

Plataforma web de adopción responsable de perros y gatos en Montevideo. Proyecto final de la Tecnicatura en Redes y Software (ITI CETP-UTU), equipo CodeCrafters: Camilo Blanco (coordinador, base de datos), Leandro Estévez (subcoordinador), Iñaki Pérez y Rodrigo González. Product Owner: el docente Martín Viar.

La fuente de verdad es el **Documento integrador 2.0**. En `docs/contexto/` hay un extracto por tema; si algo no está ahí o se contradice, preguntá antes de decidir.

## Estado y prioridad

- Sprint 4 (06/10 al 23/10/2026). **Hito 2: 23/10/2026.** Hito 3: 20/11/2026.
- Demo del Hito 2, en este orden: registro de Particular (RF:0039), inicio y cierre de sesión (RF:0022, RF:0023), página de inicio con el listado público (RF:0040, RF:0048), publicar mascota en adopción (RF:0017) y publicar mascota perdida (RF:0046).
- En el Hito 2 el inicio de sesión **no** exige correo confirmado; RF:0043 se exige desde el Sprint 5 (D67).
- Lo demás (refugios, solicitudes, seguimiento, reportes, mapa, cuentas bancarias, administración) es para los Sprints 5 y 6.

## Stack y comandos

- Laravel 13 sobre PHP 8.4 (FPM), MySQL 8.4, Nginx 1.27, Docker Compose. Vistas Blade con HTML5 y CSS3, web adaptable desde 360 px. Sin app móvil ni PWA.
- Colas y planificador de Laravel en contenedores propios (`queue`, `scheduler`). Correo: Mailpit en desarrollo; Postfix como relay en producción (perfil `produccion`).
- Mapas: OpenStreetMap con Leaflet.
- **Laravel vive en `src/laravel/`** (en los contenedores: `/var/www/laravel`). La configuración de Docker está en la raíz: `docker-compose.yml` y `docker/`.
- Primera vez en cualquier máquina: `docker compose up -d --build` (el contenedor `app` instala Composer, genera APP_KEY y migra; ver `docker compose logs -f app`). En la VM del servidor local: `bash infra/setup.sh`.

```bash
docker compose up -d                              # levantar
docker compose exec app php artisan migrate       # migraciones
docker compose exec app php artisan test          # PHPUnit
docker compose exec app php artisan dusk          # funcionales (Dusk)
docker compose exec app php artisan schedule:run  # correr las tareas programadas a mano
docker compose logs -f web app queue
docker compose run --rm node npm install && docker compose run --rm node npm run build   # CSS/JS con Vite
```

Sitio: https://localhost:8443 (certificado autofirmado; HTTP en el 8080 redirige a HTTPS). Los puertos 8080/8443 evitan chocar con XAMPP u otros servidores locales. Correos de desarrollo: http://localhost:8025. MySQL: 127.0.0.1:3307 (adoptauy / adoptauy_dev).

## Contexto detallado (leer cuando la tarea lo toque)

- `docs/contexto/requisitos.md`: actores, RF:0001 a RF:0063 (57 vigentes, 6 dados de baja) y RNF:0001 a RNF:0014, con criterios de aceptación.
- `docs/contexto/modelo-datos.md`: cambios del DER, restricciones RNE01 a RNE24 y pasaje a tablas (15.2.1).
- `docs/base-de-datos/adoptauy.sql`: tablas en MySQL según el DER (supertipo Publicacion_Animal con los subtipos Publicacion_Adopcion y Publicacion_Perdida). Solo CREATE TABLE con claves, NOT NULL, UNIQUE, DEFAULT y ENUM; las RNE las valida Laravel y los triggers quedan para el Hito 3.
- `docs/contexto/diseno-ui.md`: pantallas por actor y sistema de diseño.
- `docs/contexto/plan-pruebas.md`: estrategia, herramientas y casos CP-01 a CP-46.
- `docs/contexto/infraestructura.md`: stack, red, servidores, servicios y entorno local.
- `docs/contexto/alcance.md`: objetivos, alcance, hitos, decisiones D1 a D90 y temas pendientes.
- `docs/diagramas/`: diagrama de implementación.

Las reglas de negocio (estados, plazos y umbrales) se cargan siempre:

@docs/contexto/reglas-negocio.md

## Reglas de trabajo del equipo

1. **Preguntá antes de suponer.** Si un requisito es ambiguo, falta o se contradice con otro, hacé preguntas concretas y numeradas, con opciones cuando se pueda. No rellenes huecos en silencio.
2. **Marcá cada cambio para que el equipo lo confirme.** Al terminar, listá qué archivos cambiaste, qué RF o RNF cubre cada cambio y qué quedó sin hacer o supuesto.
3. **Trazabilidad.** Cada migración, controlador, prueba y commit nombra los RF/RNF que cubre (`RF:0039`). Nunca renumeres requisitos; los dados de baja (RF:0015, RF:0030 a RF:0033, RF:0053) no se implementan.
4. **No inventes datos.** Ni refugios, ni cifras, ni textos legales. Los datos de prueba son ficticios y salen de factories (Ley 18.331).
5. **Español** en la interfaz, los mensajes de error, los nombres del dominio y los comentarios. Los nombres de entidades y atributos siguen el pasaje a tablas de `modelo-datos.md` (por ejemplo Publicacion_Animal, Estado_Validacion); si el equipo no definió cómo nombrar tablas y modelos en Laravel, preguntá antes de crear la primera migración.
6. **Seguridad y datos personales (RNF:0007, RNF:0013):** contraseñas solo con hash bcrypt; la cédula nunca se muestra a otros usuarios; el teléfono y el correo de un solicitante solo los ven el publicante y el Administrador; todo el tráfico va por HTTPS; los errores de inicio de sesión usan un mensaje genérico.
7. **Baja lógica, nunca borrado físico** de usuarios, publicaciones, solicitudes ni reportes.
8. Cada pull request debe pasar la suite (PHPUnit y Dusk) en GitHub Actions. Los defectos se registran en la lista «Defectos» de Trello.

## Temas pendientes (no decidir sin consultar)

- Proveedor externo del relay SMTP.
- Correspondencia entre los servidores de la sección 16 (SRV-WEB, SRV-BD, SRV-MAIL, SRV-ZABBIX) y los contenedores del servidor local.
- Lista real de refugios de Montevideo para el mapa (RF:0038); la releva el equipo de fuentes públicas.
- El DER en draw.io lo redibuja Leandro; si el código necesita un cambio de modelo, proponelo, no lo apliques.
