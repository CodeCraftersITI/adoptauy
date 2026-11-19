# Prueba de carga CP-38

Verifica RNF:0001, RNF:0004, RNF:0005 y RNF:0006 con **Apache JMeter**:

- **Carga:** 15 usuarios concurrentes durante 10 minutos, sobre 1.000 publicaciones.
- **Criterio:** percentil 95 menor a 2 s en las consultas y en el inicio de sesión, y 0 errores 5xx.

Cada usuario simulado, en cada vuelta, hace esto:

1. Entra con su cuenta (`carga1@prueba.uy` a `carga15@prueba.uy`).
2. Abre Mi cuenta.
3. Recorre el inicio, el listado de adopción (con filtros y otra página), perdidas y encontradas.
4. Cierra la sesión.

Entre un pedido y el siguiente espera entre 0,5 y 1,5 s, como una persona.

| Archivo | Qué es |
|---|---|
| `cp38.jmx` | Plan de JMeter (se abre también con la interfaz de JMeter) |
| `analizar.py` | Calcula el percentil 95 por pedido y dice si se aprueba |
| `resultados/` | El `.jtl` con cada pedido y el informe HTML de JMeter (`informe/index.html`) |

## Cómo correrla

Todos los comandos van desde la raíz del proyecto. JMeter corre en un contenedor (`alpine/jmeter:5.6.3`), dentro de la red de Docker, y le pega a Nginx (`https://web`), igual que un navegador.

1. Crear la base de carga y cargarle los datos (solo la primera vez; no toca la base de desarrollo):

   ```bash
   docker compose exec db mysql -uroot -proot_dev -e "CREATE DATABASE IF NOT EXISTS adoptauy_carga CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL ON adoptauy_carga.* TO 'adoptauy'@'%';"
   docker compose exec -e DB_DATABASE=adoptauy_carga app php artisan migrate:fresh --seed --seeder=CargaSeeder --force
   ```

   `CargaSeeder` se niega a correr en otra base.

2. Apuntar el sitio a esa base, con el freno contra bots apagado. Los 15 usuarios simulados salen de la misma IP, y con el límite por minuto se cortarían:

   ```bash
   DB_DATABASE=adoptauy_carga ANTIBOT_ACTIVO=false LIMITES_ACTIVOS=false docker compose up -d app
   docker compose restart web
   ```

3. Correr la prueba (10 minutos) y analizarla:

   ```bash
   docker run --rm --network adoptauy_backend -v "$(pwd)/pruebas/carga:/carga" alpine/jmeter:5.6.3 -n -t /carga/cp38.jmx -l /carga/resultados/cp38.jtl -e -o /carga/resultados/informe -Jduracion=600
   python pruebas/carga/analizar.py pruebas/carga/resultados/cp38.jtl
   ```

   Para un ensayo corto se usa `-Jduracion=60`. Con `-Jusuarios=30` se prueba con más usuarios.

4. **Volver a dejar el sitio como estaba** (base de desarrollo y freno contra bots activo):

   ```bash
   docker compose up -d app
   docker compose restart web
   ```

> En el servidor de pruebas (la VM) es igual. Si JMeter corre en otra máquina, se usa `-Jservidor=<IP de la VM>`.
