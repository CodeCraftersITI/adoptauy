# AdoptaUY

Plataforma web de adopción responsable de perros y gatos en Montevideo. Proyecto final de la Tecnicatura en Redes y Software (ITI CETP-UTU), equipo CodeCrafters: Camilo Blanco (coordinador, base de datos), Leandro Estévez (subcoordinador), Iñaki Pérez y Rodrigo González. Product Owner: el docente Martín Viar.

La fuente de verdad es el **Documento integrador (V3)**. En `docs/contexto/` hay un extracto por tema; si algo no está ahí o se contradice, preguntá antes de decidir.

## Estado y prioridad

- Sprint 6 (07/11 al 20/11/2026). **Hito 3: 20/11/2026.** El Hito 2 (23/10) ya se entregó.
- Sprint 5 (24/10 al 06/11): refugios y confirmación de correo, solicitudes de adopción, concreción y seguimiento, pruebas de acceso por rol. Sprint 6: reportes y moderación; mapa, perfil público y cuentas bancarias; administración de usuarios, refugios y publicaciones; integración, pruebas de carga, respaldos y documentación final.
- Agregado después del Hito 2, también para el Hito 3: mascotas encontradas (RF:0064, RF:0065, ya en el código) y **Continuar con Google** (RF:0066, RF:0067, D93, D94), que todavía no está implementado: ver la sección de abajo.
- Desde el Sprint 5 el inicio de sesión exige el correo confirmado (RF:0043, D67).

## Continuar con Google (RF:0066, RF:0067)

- Solo para el Particular, con Laravel Socialite y Google OAuth 2.0 (pedido del docente de Programación). Flujo completo en la pestaña SEC-9 de `docs/diagramas/AdoptaUY-Diagramas.drawio`; reglas en `reglas-negocio.md` (13.2.1), requisitos en `requisitos.md` y modelo en `modelo-datos.md` («Cambio pendiente de migración»).
- Modelo: Usuario suma Google_ID (único, admite NULL) y Contraseña pasa a admitir NULL (RNE27). Es un cambio de base de datos: Camilo lo aprueba, y `docs/base-de-datos/adoptauy.sql` y `usuarios.sql` se actualizan en el mismo PR que la migración.
- Rutas previstas: `/auth/google`, `/auth/google/callback` y `/auth/google/completar` (pantalla «Completar datos»: CI y teléfono, nombre y apellido precargados).
- Secretos: GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET y GOOGLE_REDIRECT_URI van solo en el `.env`, nunca en el repositorio. URI de redirección: `https://localhost:8443/auth/google/callback` (Google no acepta una IP como redirección).
- En las pruebas Google se simula; ningún test llama a Google (CP-49, CP-50).
- Antes de implementarlo, confirmá con el equipo los tres puntos abiertos que figuran en `alcance.md` («Aspectos a completar»).

## Stack y comandos

- Laravel 13 sobre PHP 8.4 (FPM), MySQL 8.4, Nginx 1.27, Docker Compose. Vistas Blade con HTML5 y CSS3, web adaptable desde 360 px. Sin app móvil ni PWA.
- Colas y planificador de Laravel en contenedores propios (`queue`, `scheduler`). Correo: Mailpit en desarrollo; en producción, Laravel envía por SMTP directo a un proveedor externo (puerto 587, sin Postfix, D95).
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

- `docs/contexto/requisitos.md`: actores, RF:0001 a RF:0067 (61 vigentes, 6 dados de baja) y RNF:0001 a RNF:0014, con criterios de aceptación.
- `docs/contexto/modelo-datos.md`: cambios del DER, restricciones RNE01 a RNE27 y pasaje a tablas (15.2.1). Usa la numeración de RNE del repositorio, que difiere de la V3 desde RNE23 (está explicado al principio del archivo).
- `docs/base-de-datos/adoptauy.sql`: tablas en MySQL según el DER (supertipo Publicacion_Animal con los subtipos Publicacion_Adopcion y Publicacion_Perdida). Solo CREATE TABLE con claves, NOT NULL, UNIQUE, DEFAULT y ENUM; las RNE las valida Laravel y los triggers quedan para el Hito 3.
- `docs/contexto/diseno-ui.md`: pantallas por actor, sistema de diseño (colores, tipografía Outfit) y lo que falta prototipar (sección 14 de la V3).
- `docs/contexto/plan-pruebas.md`: estrategia, herramientas y casos CP-01 a CP-50.
- `docs/contexto/infraestructura.md`: stack, red, servidores, servicios y entorno local.
- `docs/contexto/alcance.md`: objetivos, alcance, hitos, decisiones D1 a D95 y temas pendientes.
- `docs/diagramas/AdoptaUY-Diagramas.drawio`: todos los diagramas en pestañas (DER, MER, casos de uso, clases, secuencia SEC-1 a SEC-9, estados, implementación y red). `AdoptaUY-Diagrama-de-implementacion.drawio`: el diagrama de implementación anterior.

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

- Proveedor externo de SMTP (Gmail o Brevo).
- Numeración de RNE entre el repositorio y la V3 del integrador.
- Los tres puntos abiertos de Continuar con Google (`alcance.md`).
- Correspondencia entre los servidores de la sección 16 y los contenedores del servidor local: en la DMZ queda solo SRV-WEB (Nginx, Laravel y MySQL en contenedores); falta reflejarlo en el documento y en la pestaña Red del draw.io, que todavía muestra SRV-DNS.
- Lista real de refugios de Montevideo para el mapa (RF:0038); la releva el equipo de fuentes públicas.
- El DER en draw.io lo redibuja Leandro; si el código necesita un cambio de modelo, proponelo, no lo apliques.
