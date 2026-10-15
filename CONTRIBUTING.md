# Cómo contribuir a AdoptaUY

Guía de trabajo del equipo CodeCrafters. Si algo de acá no se aplica a tu caso, consultalo en el grupo antes de improvisar.

## Resumen

1. `main` siempre funciona y se puede mostrar. **Nadie hace push directo a `main`.**
2. Cada tarea de Trello tiene **su rama**, que sale de `main` y vuelve a `main` por **pull request**.
3. Un PR se integra con **1 aprobación** de otro integrante y **la CI en verde**.
4. Cada entrega se marca con una etiqueta: `hito-2`, `hito-3`.

## Ramas

| Rama | Para qué | Vida |
|---|---|---|
| `main` | Versión estable, la que se muestra en las demos | Permanente |
| `tipo/descripcion` | Una por tarea de Trello | De horas a pocos días |

### Nombres

`tipo/RFxxxx-descripcion-corta`, en minúsculas, sin tildes ni espacios, con guiones.

| Tipo | Uso | Ejemplo |
|---|---|---|
| `feature/` | Funcionalidad nueva | `feature/RF0039-registro-particular` |
| `fix/` | Corrección de un defecto (lista «Defectos» de Trello) | `fix/login-mensaje-generico` |
| `test/` | Solo pruebas | `test/CP-07-limites-fotos` |
| `docs/` | Documentación | `docs/equivalencias-tablas` |
| `infra/` | Docker, CI, servidor | `infra/composer-lock` |
| `chore/` | Mantenimiento sin cambio funcional | `chore/actualizar-dependencias` |

## Flujo de una tarea, paso a paso

### 1. Partir de `main` actualizado

```bash
git switch main
git pull origin main
git switch -c feature/RF0039-registro-particular
```

Mové la tarjeta de Trello a «En curso» y pegale el nombre de la rama.

### 2. Trabajar y hacer commits chicos

```bash
docker compose up -d          # la primera vez: docker compose up -d --build
# ... programar ...
docker compose exec app php artisan test        # antes de cada commit
git add -A
git commit -m "feat(RF:0039): validación de la cédula con dígito verificador"
```

### 3. Mantener la rama al día con `main`

Hacelo al menos una vez por día y siempre antes de abrir el PR:

```bash
git fetch origin
git rebase origin/main
# si hay conflictos: resolverlos, luego  git add <archivo>  y  git rebase --continue
git push --force-with-lease
```

> `--force-with-lease` solo en **tu** rama, nunca en `main` ni en la rama de otro.

### 4. Abrir el pull request

```bash
git push -u origin feature/RF0039-registro-particular
```

En GitHub, **Compare & pull request** hacia `main`, con:

- **Título:** igual al commit principal, por ejemplo `feat(RF:0039): registro de Particular`.
- **Descripción:**
  - Requisitos que cubre (`RF:0039`) y casos de prueba (`CP-01`, `CP-02`).
  - Qué cambió y cómo probarlo.
  - Qué quedó afuera o supuesto.
  - Enlace a la tarjeta de Trello.
- **Revisor:** otro integrante.

### 5. Revisión

Quien revisa:

- Baja la rama y la prueba: `git fetch origin && git switch feature/RF0039-registro-particular`.
- Controla los criterios de aceptación del RF en [`docs/contexto/requisitos.md`](docs/contexto/requisitos.md) y las reglas de [`docs/contexto/reglas-negocio.md`](docs/contexto/reglas-negocio.md).
- Aprueba o pide cambios con comentarios concretos.

### 6. Integrar y limpiar

- Botón **Squash and merge**: el PR queda como un solo commit en `main`.
- La rama se borra sola después del merge.
- Mové la tarjeta de Trello a «Hecho».

```bash
git switch main
git pull origin main
git branch -d feature/RF0039-registro-particular
```

## Mensajes de commit

`tipo(alcance): descripción en español, en minúscula y sin punto final`

| Tipo | Cuándo |
|---|---|
| `feat` | Funcionalidad nueva |
| `fix` | Corrección de un defecto |
| `test` | Pruebas |
| `docs` | Documentación |
| `refactor` | Cambio interno sin cambiar comportamiento |
| `infra` | Docker, CI, servidor |
| `chore` | Mantenimiento |

El alcance es el requisito o el caso de prueba cuando aplica: `feat(RF:0046): vencimiento a 30 días`, `test(CP-07): límites de fotos`, `fix: mensaje genérico en login`.

## Antes de pedir revisión

- [ ] `docker compose exec app php artisan test` pasa completo.
- [ ] Cada regla nueva tiene su prueba, con el CP y el RF en el nombre o en el docblock.
- [ ] Toda ruta nueva tiene su prueba de acceso por rol (Invitado, Particular, Refugio, Administrador).
- [ ] Textos de la interfaz y mensajes de error en español.
- [ ] Nada de datos personales reales: los datos de prueba salen de factories (Ley N.º 18.331).
- [ ] No se suben `.env`, certificados ni respaldos.
- [ ] Si cambia el modelo de datos, lo revisó Camilo Blanco (responsable de base de datos).

## Defectos

Los defectos se registran en la lista **«Defectos»** de Trello con: caso de prueba, pasos, resultado esperado y obtenido, entorno, severidad (Crítica, Mayor, Menor, Trivial) y captura. La corrección va en una rama `fix/` que nombra la tarjeta.

## Entregas por hito

Al cerrar cada hito, con `main` estable y la CI en verde:

```bash
git switch main && git pull origin main
git tag -a hito-2 -m "Entrega del Hito 2 (23/10/2026)"
git push origin hito-2
```

## Buenas prácticas

- **Ramas cortas:** si una rama pasa de 3 o 4 días, partila en tareas más chicas.
- **Un PR, un tema:** no mezcles una funcionalidad con arreglos que no tienen que ver.
- **Preguntá antes de suponer:** si un requisito es ambiguo, consultalo en el grupo y dejalo anotado en el PR.
- **Claude Code:** lee [`CLAUDE.md`](CLAUDE.md) y las reglas de [`.claude/rules/`](.claude/rules/). Revisá lo que propone igual que el código de un compañero.
