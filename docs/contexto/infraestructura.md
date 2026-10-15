# Infraestructura

> Extraído del Documento integrador 2.0 de AdoptaUY (rev. 14/10/2026). Si este archivo y el documento difieren, manda el documento; avisá la diferencia.

## 12. Procedimientos, herramientas y tecnologías

### 12.1. Stack tecnológico

- Backend: PHP con Laravel 13, arquitectura MVC y ORM Eloquent.

- Frontend web: HTML5 y CSS3, con diseño adaptable a pantallas de escritorio y de celular. No hay aplicación móvil.

- Base de datos: MySQL (motor relacional).

- Tareas programadas y colas: planificador y colas de Laravel, para los avisos de seguimiento, el vencimiento de publicaciones y el envío de correos.

- Correo: Postfix como relay hacia un servidor SMTP externo por el puerto 587; en desarrollo, Mailpit captura los correos. Mapas: OpenStreetMap con la biblioteca Leaflet.

### 12.2. Herramientas de trabajo colaborativo

- Git y GitHub: control de versiones del código y trabajo colaborativo mediante ramas y pull requests. Cada pull request ejecuta con GitHub Actions la suite automatizada de pruebas (PHPUnit para unitarias e integración, Laravel Dusk para funcionales).

- Trello: tablero único de seguimiento del Product Backlog y del estado de las tareas por sprint. Los defectos que se encuentran en las pruebas se registran en su lista «Defectos».

- Discord y WhatsApp: canal de comunicación diaria del equipo.

## 16. Conectividad y seguridad de redes

### 16.1. Tabla de VLAN

| VLAN ID | Nombre | Subred (/24) | Gateway (SVI) |
|---|---|---|---|
| 10 | DMZ_SERVIDORES | 10.10.10.0/24 | 10.10.10.1 |
| 20 | ADMIN_MANAGEMENT | 10.10.20.0/24 | 10.10.20.1 |
| 30 | USUARIOS_DEV | 10.10.30.0/24 | 10.10.30.1 |
| 99 | GESTION (SW) | 10.10.99.0/24 | 10.10.99.1 / .2 / .3 |

Las direcciones .1, .2 y .3 de la VLAN 99 corresponden a las IP de gestión de los tres switches.

### 16.2. Direccionamiento IP

#### 16.2.1. LAN-CodeCrafters

| Dispositivo | IP | VLAN | Función |
|---|---|---|---|
| SRV-AD (Windows) | 10.10.20.10 | 20 | AD DS / DNS / DHCP |
| Laptop Camilo | 10.10.30.11 | 30 | Dev |
| Laptop Leandro | 10.10.30.12 | 30 | Dev |
| Laptop Iñaki | 10.10.30.13 | 30 | Dev |
| Laptop Rodrigo | 10.10.30.14 | 30 | Dev |
| SW-LAN (Gestión) | 10.10.99.1 | 99 | Switch Acceso |
| SW-Core (Gestión) | 10.10.99.2 | 99 | Switch Core |

#### 16.2.2. LAN-DMZ

| Dispositivo | IP | VLAN | Función |
|---|---|---|---|
| SRV-WEB | 10.10.10.10 | 10 | Nginx + Laravel |
| SRV-BD Principal | 10.10.10.101 | 10 | MySQL (principal) |
| SRV-BD-MIRROR | 10.10.10.102 | 10 | MySQL (réplica) |
| SRV-DNS | 10.10.10.20 | 10 | BIND |
| SRV-MAIL | 10.10.10.30 | 10 | Postfix |
| SRV-ZABBIX | 10.10.99.50 | 99 | Monitoreo |
| SW-DMZ (Gestión) | 10.10.99.3 | 99 | Switch Acceso |

#### 16.2.3. Dispositivos de red

| Equipo | IP Gestión | Rol / Descripción |
|---|---|---|
| SW-LAN | 10.10.99.1 | Switch de acceso LAN |
| SW-DMZ | 10.10.99.3 | Switch de acceso DMZ |
| Router/ONT | - | Borde / ISP |
| Firewall (pfSense) | - | NAT / Seguridad |
| VPN Gateway | - | Acceso remoto |
| SW-Core | 10.10.99.2 | Switch de distribución |
| SW-Clientes (ISP) | - | Cliente externo |

## 17. Infraestructura, despliegue y soporte

### 17.1. Requisitos de hardware

| Componente | Mínimo | Recomendado |
|---|---|---|
| CPU | 2 vCPU / núcleos | 4 vCPU / núcleos |
| Memoria RAM | 4 GB | 8 GB |
| Almacenamiento | 40 GB SSD | 80 GB SSD (NVMe preferido) |
| Red | 100 Mbps | 1 Gbps |
| Alimentación / respaldo | — | UPS o proveedor cloud con SLA de energía |

El mínimo cubre los tres contenedores (Laravel, Nginx, MySQL) con tráfico bajo. El recomendado deja margen para picos de concurrencia, respaldos en paralelo y crecimiento de la base de datos y de las imágenes (fotografías de publicaciones, evidencia de seguimiento y documentación de refugios) sin degradar el rendimiento.

### 17.2. Requisitos de software

- Sistema operativo: Ubuntu Server 24.04 LTS

- Motor de contenedores: Docker Engine + Docker Compose

- Runtime de la aplicación: PHP 8.3+ (imagen oficial php-fpm), Laravel 13

- Servidor web: Nginx (contenedor, como proxy inverso hacia PHP-FPM, con TLS y redirección de HTTP a HTTPS; en el servidor local, con certificado autofirmado)

- Base de datos: MySQL 8.x (contenedor, con volumen persistente)

- Certificados TLS: Certbot / Let's Encrypt para HTTPS

- Control de versiones: Git

- Monitoreo: Zabbix (SRV-ZABBIX, sección 16). Portainer queda como herramienta opcional de administración de contenedores.

### 17.3. Servidores y virtualización
Se opta por contenedores (virtualización a nivel de sistema operativo) en lugar de máquinas virtuales completas, priorizando menor consumo de recursos y mayor velocidad de despliegue. Cada servicio queda aislado en su propio contenedor, definido en un docker-compose.yml.
La topología de la sección 16 y este despliegue son una misma solución, montada en una PC del equipo que funciona como servidor local. La correspondencia entre los servidores de la sección 16 y los contenedores de este host se define con el docente de la asignatura.

### 17.4. Servicios

- Nginx: proxy inverso y servidor web, expone los puertos 80 y 443 y redirige a PHP-FPM.

- MySQL: servicio de persistencia de datos, con volumen Docker dedicado para sobrevivir a recreaciones de contenedores.

- Planificador y colas de Laravel: contenedor o proceso adicional para las tareas programadas (aviso de seguimiento del día 15, restricción de la cuenta al día 22, vencimiento de publicaciones de mascotas perdidas a los 30 días) y los trabajos en cola (envío de correos, procesamiento de imágenes).

- Correo (SMTP): envía las notificaciones de la tabla de 13.2.6. Postfix corre como un contenedor más del docker-compose y reenvía los correos a un servidor SMTP externo por el puerto 587 con STARTTLS; el proveedor externo queda a definir. En desarrollo, Mailpit captura los correos y no salen a Internet.

- Almacenamiento de archivos: volumen local para fotografías de publicaciones, evidencia de seguimiento y documentación de refugios, con posibilidad de migrar a almacenamiento tipo S3 si el proyecto escala.

- Respaldos: copia diaria automática de la base de datos y de los archivos (RNF:0014), con una tarea cron del servidor a las 02:00 que ejecuta mysqldump y copia storage/, y conserva los últimos 7 días.

## Entorno local (servidor de pruebas y demos)

El repositorio trae `docker-compose.yml` y `docker/` en la raíz, y Laravel en `src/laravel/`. En cualquier máquina con Docker alcanza con `docker compose up -d --build`: el contenedor app instala Composer, genera APP_KEY y migra la base, y web genera el certificado autofirmado. `scripts/iniciar.sh` hace lo mismo con contraseñas aleatorias en un `.env`. En la VM del servidor local, `bash infra/setup.sh` instala antes Docker y el firewall y programa el respaldo. Decisiones D73 a D77.

| Elemento | Detalle |
|---|---|
| Host | PC del equipo con Oracle VirtualBox; VM Ubuntu Server 24.04 LTS (2 vCPU, 4 GB, 30 GB) |
| Red de la VM | NAT con reenvío 2222→22 (SSH), 8080→8080, 8443→8443, 8025→8025 (D77, ajustado el 15/10/2026) |
| Contenedores | web (nginx:1.27-alpine, HTTPS con certificado autofirmado), app (php:8.4-fpm + Laravel 13), queue, scheduler, db (mysql:8.4, volumen db_data), mailpit (desarrollo), mail (boky/postfix, perfil produccion) |
| Correo | Desarrollo: Mailpit captura todo. Producción: Postfix relay al SMTP externo por el 587 (D54, D75); variables SMTP_RELAY_HOST, SMTP_RELAY_USER y SMTP_RELAY_PASSWORD |
| HTTPS | Certificado autofirmado en docker/nginx/certs; HTTP redirige a HTTPS (D73) |
| Tareas programadas | Contenedor scheduler; avisos y vencimientos a las 03:00, días corridos (D71) |
| Respaldo | cron del host a las 02:00: scripts/respaldo.sh (mysqldump + src/laravel/storage/app) en respaldos/, 7 días de retención (D76) |
| Base de pruebas | adoptauy_test, creada por docker/mysql/init/ y forzada en phpunit.xml |
| Puertos | Por defecto HTTP 8080 y HTTPS 8443 en todos los entornos, para no chocar con XAMPP u otros servidores locales. Configurables: HTTP_PORT, HTTPS_PORT, DB_PORT_LOCAL (3307, solo 127.0.0.1) y MAILPIT_PORT en el .env de la raíz |
| Firewall | ufw: 22, 8080, 8443, 8025 |
