---
paths:
  - "src/laravel/tests/**"
  - "src/laravel/phpunit.xml"
  - ".github/workflows/**"
---

# Pruebas

- Plan vigente: `docs/contexto/plan-pruebas.md`. Cada prueba indica en su nombre o docblock el caso (CP-xx) y los RF/RNF que cubre.
- PHPUnit para unitarias e integración y Laravel Dusk para funcionales. Las pruebas corren contra MySQL (base `adoptauy_test`, forzada en `phpunit.xml` para no borrar la base de desarrollo) con `RefreshDatabase`, no contra SQLite.
- Los plazos de 15, 22 y 30 días se prueban con `travelTo()` y valores límite (14/15, 21/22, 29/30). Los umbrales de reportes, con 2/3, 4/5 y 9/10 reportes de usuarios distintos.
- Correo con `Mail::fake()`, archivos con `Storage::fake()`, colas con `Queue::fake()`, mapa con `Http::fake()`.
- Datos siempre ficticios generados con factories; nunca datos personales reales. Una CI de prueba válida es 1.234.567-2.
- La prueba de humo es CP-01, CP-04 y CP-06 y tiene que pasar antes de cada demo.
- Toda ruta nueva lleva su prueba de acceso por rol (Invitado, Particular, Refugio, Administrador).
