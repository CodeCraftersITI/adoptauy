---
paths:
  - "src/laravel/database/**"
  - "src/laravel/app/Models/**"
---

# Migraciones y modelos

- Seguí el pasaje a tablas y las restricciones de `docs/contexto/modelo-datos.md` (15.2.1 y RNE01 a RNE24). Si falta un atributo o una relación, proponé el cambio y esperá confirmación; Camilo Blanco es el responsable de base de datos.
- Juego de caracteres utf8mb4 y collation utf8mb4_unicode_ci. Claves foráneas explícitas en todas las relaciones.
- Una migración ya integrada a la rama principal no se edita: los cambios van en una migración nueva (RNF:0009).
- Baja lógica: los usuarios, publicaciones, solicitudes y reportes cambian de estado; nunca se borran físicamente.
- Los valores de estado se escriben exactamente como en el documento:
  - Publicación en adopción: En adopción, Adoptado, Archivado. Mascota perdida: Perdida, Encontrada, Archivada.
  - Estado_Moderacion: Visible, En revisión, Suspendida.
  - Solicitud: Pendiente, Adoptada, Rechazada.
  - Estado_Validacion del refugio: Pendiente de validación, Validado, Rechazado, Suspendido, Dado de baja.
  - Estado_Usuario: Activo, En revisión, Suspendido, Inhabilitado, Baja.
  - Estado_Evidencia: Pendiente, Enviada, Aprobada, Rechazada.
- La CI del Particular tiene índice único (RNE23). El correo del usuario también es único.
- Fecha_Vencimiento de una mascota perdida = creación o última renovación + 30 días (RNE17). Fecha_Aviso = Fecha_Adopcion + 15 días; Fecha_Limite = Fecha_Aviso + 7 días (RNE20).
- Nombrá en un comentario de cada migración los RF y RNE que implementa.
