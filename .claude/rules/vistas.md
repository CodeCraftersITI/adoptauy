---
paths:
  - "resources/views/**"
  - "resources/css/**"
  - "resources/js/**"
  - "routes/**"
  - "app/Http/**"
---

# Vistas, rutas y controladores

- Pantallas y componentes: `docs/contexto/diseno-ui.md`. Interfaz en español, adaptable desde 360 px de ancho (RNF:0003).
- Cada transición de estado se valida en el servidor contra las tablas de `docs/contexto/reglas-negocio.md`; la interfaz solo ofrece las transiciones válidas.
- Validación con FormRequest: campos obligatorios de 13.2.2, entre 1 y 6 fotos (adopción) o entre 1 y 3 (perdida), JPG/PNG/WEBP de hasta 8 MB, CI con dígito verificador, contraseña de 8 caracteres o más.
- Datos personales (RNF:0013): la CI no se muestra a otros usuarios; el teléfono y el correo de un solicitante solo los ven el publicante y el Administrador.
- Errores de inicio de sesión con un único mensaje genérico. Desde el Sprint 5, el ingreso exige el correo confirmado.
- Un usuario con la cuenta restringida por seguimiento solo puede enviar la foto pendiente; un Refugio no puede solicitar adopciones.
- En el mapa, los refugios no registrados se muestran con la leyenda «No registrado en AdoptaUY» y sin enlace a perfil ni datos bancarios.
