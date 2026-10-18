# Infraestructura

> Extraído del Documento integrador 2.1 de AdoptaUY (rev. 18/10/2026). Si este archivo y el documento difieren, manda el documento; avisá la diferencia.

## 12. Procedimientos, herramientas y tecnologías

### 12.1. Stack tecnológico

- Backend: PHP con Laravel 13, arquitectura MVC y ORM Eloquent.

- Frontend web: HTML5 y CSS3, con diseño adaptable a pantallas de escritorio y de celular. No hay aplicación móvil.

- Base de datos: MySQL (motor relacional).

- Tareas programadas y colas: planificador y colas de Laravel, para los avisos de seguimiento, el vencimiento de publicaciones y el envío de correos.

- Correo: Postfix como relay hacia un servidor SMTP externo por el puerto 587; en desarrollo, Mailpit captura los correos. Mapas: OpenStreetMap con la biblioteca Leaflet.

### 12.2. Herramientas de trabajo colaborativo

- Git y GitHub: control de versiones del código y trabajo colaborativo mediante ramas y pull requests. Cada pull request ejecuta con GitHub Actions la suite automatizada de pruebas (PHPUnit para unitarias e integración, Laravel Dusk para funcionales). La rama main está protegida: cada tarea de Trello tiene su propia rama (por ejemplo, feature/RF0039-registro-particular) y se integra por pull request con la aprobación de otro integrante y la integración continua en verde. El procedimiento está en el archivo CONTRIBUTING.md del repositorio (D78).

- Trello: tablero único de seguimiento del Product Backlog y del estado de las tareas por sprint. Los defectos que se encuentran en las pruebas se registran en su lista «Defectos».

- Discord y WhatsApp: canal de comunicación diaria del equipo.

## 16. Conectividad y seguridad de redes
La red se organiza en cuatro VLAN detrás de un firewall pfSense, que es el único gateway: todo el tráfico entre VLAN, hacia Internet y desde la VPN pasa por sus reglas. Los tres switches trabajan en capa 2. El detalle de configuración (comandos de los switches, reglas de pfSense por interfaz, NAT, VPN y plan de pruebas) está en el documento de infraestructura de red de la asignatura de Redes (D88).

### 16.1. Tabla de VLAN

| VLAN | Nombre | Red | Gateway (pfSense) | Función y acceso |
|---|---|---|---|---|
| 10 | DMZ_SERVIDORES | 10.10.10.0/24 | 10.10.10.1 | Servidores publicados. Desde Internet solo HTTP y HTTPS a SRV-WEB; no inicia conexiones hacia la LAN |
| 20 | ADMIN_MANAGEMENT | 10.10.20.0/24 | 10.10.20.1 | SRV-AD. Recibe solo los puertos de dominio, DNS y DHCP desde la VLAN 30 |
| 30 | USUARIOS_DEV | 10.10.30.0/24 | 10.10.30.1 | PC del equipo. Navegación web y SSH por usuario (16.4) |
| 99 | GESTION | 10.10.99.0/24 | 10.10.99.254 | Gestión de los switches y SRV-ZABBIX. Solo administradores |
| 999 | NATIVA_SIN_USO | Sin red | — | VLAN nativa de los troncales y destino de los puertos sin uso |

Las direcciones 10.10.99.1, .2 y .3 son las IP de gestión de los switches, así que el gateway de la VLAN 99 en pfSense es 10.10.99.254. La VLAN nativa es la 999 y no la 99: si la red de gestión viajara sin etiqueta, quedaría expuesta al salto de VLAN.

### 16.2. Direccionamiento IP

#### 16.2.1. LAN-CodeCrafters

| Dispositivo | IP | VLAN | Función | Asignación |
|---|---|---|---|---|
| SRV-AD (Windows) | 10.10.20.10 | 20 | AD DS / DNS / DHCP | Estática |
| PC Camilo | 10.10.30.11 | 30 | Desarrollo | Reserva DHCP |
| PC Leandro | 10.10.30.12 | 30 | Desarrollo | Reserva DHCP |
| PC Iñaki | 10.10.30.13 | 30 | Desarrollo | Reserva DHCP |
| PC Rodrigo | 10.10.30.14 | 30 | Desarrollo | Reserva DHCP |

El DHCP de la VLAN 30 lo da SRV-AD, en la VLAN 20; pfSense reenvía las solicitudes (DHCP relay). Cada PC tiene una reserva por dirección MAC, para que las reglas por usuario siempre apunten a la misma IP.

#### 16.2.2. LAN-DMZ

| Dispositivo | IP | VLAN | Función |
|---|---|---|---|
| SRV-WEB | 10.10.10.10 | 10 | Nginx + Laravel |
| SRV-DNS | 10.10.10.20 | 10 | BIND |
| SRV-MAIL | 10.10.10.30 | 10 | Postfix |
| SRV-BD Principal | 10.10.10.101 | 10 | MySQL (principal) |
| SRV-BD-MIRROR | 10.10.10.102 | 10 | MySQL (réplica) |

#### 16.2.3. Gestión

| Dispositivo | IP | VLAN | Función |
|---|---|---|---|
| SW-LAN | 10.10.99.1 | 99 | Switch de acceso LAN |
| SW-Core | 10.10.99.2 | 99 | Switch de distribución |
| SW-DMZ | 10.10.99.3 | 99 | Switch de acceso DMZ |
| SRV-ZABBIX | 10.10.99.50 | 99 | Monitoreo. Se conecta físicamente a SW-DMZ, en un puerto de la VLAN 99 |
| pfSense (interfaz de gestión) | 10.10.99.254 | 99 | Gateway de la VLAN 99 |

#### 16.2.4. Dispositivos de red

| Equipo | Rol |
|---|---|
| Router/ONT | Borde con el proveedor de Internet |
| pfSense | Firewall perimetral, NAT, ruteo entre VLAN y servidor de la VPN de acceso remoto |
| SW-Core | Distribución, capa 2 |
| SW-LAN y SW-DMZ | Acceso, capa 2 |
| SW-Clientes (ISP) | Equipos del cliente externo, fuera de la red de AdoptaUY |

### 16.3. Ruteo y enlaces troncales
pfSense rutea entre VLAN con una interfaz por VLAN sobre un único enlace troncal hacia SW-Core (router-on-a-stick). No hay rutas internas: todas las redes están conectadas directamente a pfSense, y la ruta por defecto sale por la WAN.

| Troncal | Entre | VLAN permitidas | Nativa |
|---|---|---|---|
| T1 | pfSense y SW-Core | 10, 20, 30, 99 | 999 |
| T2 | SW-Core y SW-LAN | 20, 30, 99 | 999 |
| T3 | SW-Core y SW-DMZ | 10, 99 | 999 |

### 16.4. Políticas de seguridad y NAT
Todo está bloqueado por defecto y se permite solo lo necesario. El tráfico entre servidores de la DMZ (por ejemplo, de SRV-WEB a MySQL) no pasa por pfSense, así que se controla con el firewall de cada servidor.

| Origen | Destino | Puerto | Acción |
|---|---|---|---|
| VLAN 30 | Internet | TCP 80 y 443 | Permitir |
| VLAN 30 | SRV-WEB | TCP 80 y 443 | Permitir |
| VLAN 30 | SRV-AD | Dominio, DNS y DHCP | Permitir |
| PC de cada usuario | Sus servidores (tabla siguiente) | TCP 22 | Permitir |
| VLAN 30 | SRV-WEB y cualquier otro destino | TCP 22 | Denegar (después de las excepciones por usuario) |
| SRV-WEB | SRV-BD | TCP 3306 | Permitir (firewall de SRV-BD) |
| SRV-BD-MIRROR | SRV-BD | TCP 3306 (replicación) | Permitir (firewall de SRV-BD) |
| SRV-ZABBIX | Servidores | TCP 10050 | Permitir |
| VLAN 10 (DMZ) | VLAN 20, 30 y 99 | Todos | Denegar |
| Internet | SRV-WEB | TCP 80 y 443 | Permitir, con NAT |
| Internet | pfSense | UDP 1194 (OpenVPN) | Permitir |
| Internet | Cualquier otro destino | Todos | Denegar |

SSH por usuario (D89). El firewall deja pasar el puerto 22 solo desde la PC de cada integrante hacia sus servidores, y cada servidor acepta solo el usuario de esa persona, con clave pública.

| Integrante | PC | Servidores por SSH |
|---|---|---|
| Camilo Blanco | 10.10.30.11 | SRV-BD y SRV-BD-MIRROR |
| Leandro Estévez | 10.10.30.12 | SRV-ZABBIX |
| Iñaki Pérez | 10.10.30.13 | SRV-WEB |
| Rodrigo González | 10.10.30.14 | SRV-WEB, SRV-DNS, SRV-MAIL, SRV-ZABBIX y los tres switches |

NAT. Las redes internas salen a Internet con la IP pública de la sede (NAT de salida). Hacia adentro solo se publican los puertos TCP 80 y 443, redirigidos a SRV-WEB (10.10.10.10). SSH, MySQL y las interfaces de administración no se publican.

### 16.5. VPN de acceso remoto
La VPN permite que cada integrante trabaje desde su casa conectándose a su PC de la empresa. No se usa para acceder a la aplicación, que es pública (D90).

| Aspecto | Definición |
|---|---|
| Tecnología | OpenVPN en pfSense, acceso remoto (cliente a sitio) |
| Autenticación | Certificado personal emitido por la autoridad certificadora interna de pfSense, más usuario y contraseña de cada integrante. El certificado autentica el equipo y la contraseña autentica a la persona |
| Cifrado | AES-256-GCM para los datos, con el canal de control protegido por TLS |
| Red del túnel | 10.10.50.0/24. Cada integrante recibe siempre la misma IP: Camilo 10.10.50.11, Leandro 10.10.50.12, Iñaki 10.10.50.13 y Rodrigo 10.10.50.14 |
| Puerto | UDP 1194 en la WAN de pfSense |
| Rutas | Solo se envían al cliente las rutas a su PC y a sus servidores. La navegación de la casa sigue por su propia conexión |
| Acceso permitido | Cada integrante llega a su propia PC por escritorio remoto (RDP, TCP 3389) y a sus servidores por SSH (tabla de 16.4). Nada más de la red |
| Registro | Las conexiones y los bloqueos quedan en los registros de pfSense |

### 16.6. Pruebas de red

| Prueba | Origen | Destino | Resultado esperado |
|---|---|---|---|
| PC usuario → Internet | PC de la VLAN 30 | Un sitio web externo, TCP 443 | Exitoso |
| PC usuario → SSH | PC de Iñaki (10.10.30.13) | SRV-WEB, TCP 22 | Exitoso; desde la PC de Leandro, el mismo acceso se bloquea |
| PC cliente → servidor interno | Equipo externo, desde Internet | SRV-BD (TCP 3306) y SRV-AD | Bloqueado |
| Internet → servidor web | Equipo externo | IP pública de la sede, TCP 80 y 443 | Exitoso (NAT hacia SRV-WEB) |
| VPN → PC propia | Integrante conectado por VPN | Su PC, TCP 3389 | Exitoso; hacia la PC de otro integrante, bloqueado |

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

- Runtime de la aplicación: PHP 8.4 (imagen oficial php-fpm), Laravel 13

- Servidor web: Nginx (contenedor, como proxy inverso hacia PHP-FPM, con TLS y redirección de HTTP a HTTPS; en el servidor local, con certificado autofirmado)

- Base de datos: MySQL 8.4 (contenedor, con volumen persistente). El modelo físico con sus restricciones está en el script docs/base-de-datos/adoptauy.sql del repositorio

- Certificados TLS: certificado autofirmado en el servidor local (D73); Let's Encrypt solo si el sistema se publica con un dominio propio

- Control de versiones: Git

- Monitoreo: Zabbix (SRV-ZABBIX, sección 16). Portainer queda como herramienta opcional de administración de contenedores.

### 17.3. Servidores y virtualización
Se opta por contenedores (virtualización a nivel de sistema operativo) en lugar de máquinas virtuales completas, priorizando menor consumo de recursos y mayor velocidad de despliegue. Cada servicio queda aislado en su propio contenedor, definido en un docker-compose.yml.
La topología de la sección 16 y este despliegue son una misma solución, montada en una PC del equipo que funciona como servidor local. La correspondencia entre los servidores de la sección 16 y los contenedores de este host se define con el docente de la asignatura.
El servidor final es una máquina virtual de VirtualBox con Ubuntu Server 24.04. Nginx escucha en los puertos 80 y 443 y la VM usa un adaptador puente, de modo que tiene su propia IP en la red y se accede con https://<IP de la VM> o con un nombre local. Si la red de la defensa no admite el adaptador puente, se usa NAT de VirtualBox con reenvío 80→80 y 443→443. En las PC de desarrollo el mismo docker-compose usa los puertos 8080 y 8443 (D83).

### 17.4. Servicios

- Nginx: proxy inverso y servidor web, redirige a PHP-FPM. En el servidor final expone los puertos 80 y 443; en las PC de desarrollo usa 8080 y 8443 para no chocar con otros servidores locales, como XAMPP (D83).

- MySQL: servicio de persistencia de datos, con volumen Docker dedicado para sobrevivir a recreaciones de contenedores.

- Planificador y colas de Laravel: contenedor o proceso adicional para las tareas programadas (aviso de seguimiento del día 15, restricción de la cuenta al día 22, vencimiento de publicaciones de mascotas perdidas a los 30 días) y los trabajos en cola (envío de correos, procesamiento de imágenes).

- Correo (SMTP): envía las notificaciones de la tabla de 13.2.6. Postfix corre como un contenedor más del docker-compose y reenvía los correos a un servidor SMTP externo por el puerto 587 con STARTTLS; el proveedor externo queda a definir. En desarrollo, Mailpit captura los correos y no salen a Internet.

- Almacenamiento de archivos: volumen local para fotografías de publicaciones, evidencia de seguimiento y documentación de refugios, con posibilidad de migrar a almacenamiento tipo S3 si el proyecto escala.

- Respaldos: copia diaria automática de la base de datos y de los archivos (RNF:0014), con una tarea cron del servidor a las 02:00 que ejecuta mysqldump y copia storage/, y conserva los últimos 7 días.

## Entorno local (servidor de pruebas y demos)

El repositorio trae `docker-compose.yml` y `docker/` en la raíz, y Laravel en `src/laravel/`. En cualquier máquina con Docker alcanza con `docker compose up -d --build`: el contenedor app instala Composer, genera APP_KEY y migra la base, y web genera el certificado autofirmado. `scripts/iniciar.sh` hace lo mismo con contraseñas aleatorias en un `.env`. En la VM del servidor local, `bash infra/setup.sh` instala antes Docker y el firewall y programa el respaldo. Decisiones D73 a D83.

| Elemento | Detalle |
|---|---|
| Host | PC del equipo con Oracle VirtualBox; VM Ubuntu Server 24.04 LTS (2 vCPU, 4 GB, 30 GB) |
| Red de la VM | Adaptador puente: la VM tiene su propia IP en la red y se entra con https://<IP de la VM>. Plan B: NAT de VirtualBox 80→80 y 443→443 (D83) |
| Contenedores | web (nginx:1.27-alpine, HTTPS con certificado autofirmado), app (php:8.4-fpm + Laravel 13), queue, scheduler, db (mysql:8.4, volumen db_data), mailpit (desarrollo), mail (boky/postfix, perfil produccion) |
| Correo | Desarrollo: Mailpit captura todo. Producción: Postfix relay al SMTP externo por el 587 (D54, D75); variables SMTP_RELAY_HOST, SMTP_RELAY_USER y SMTP_RELAY_PASSWORD |
| HTTPS | Certificado autofirmado en docker/nginx/certs; HTTP redirige a HTTPS (D73) |
| Tareas programadas | Contenedor scheduler; avisos y vencimientos a las 03:00, días corridos (D71) |
| Respaldo | cron del host a las 02:00: scripts/respaldo.sh (mysqldump + src/laravel/storage/app) en respaldos/, 7 días de retención (D76) |
| Base de pruebas | adoptauy_test, creada por docker/mysql/init/ y forzada en phpunit.xml |
| Puertos configurables | HTTP_PORT y HTTPS_PORT (80 y 443 en la VM; 8080 y 8443 por defecto en las PC de desarrollo, D83), DB_PORT_LOCAL (3307, solo 127.0.0.1) y MAILPIT_PORT en el .env de la raíz |
| Firewall | ufw: 22, 80, 443, 8025 |
