# Diagramas de análisis y diseño (PlantUML)

Fuentes `.puml` y PNG generados. El MER coincide con [`docs/base-de-datos/adoptauy.sql`](../../base-de-datos/adoptauy.sql); si cambia el modelo, se actualizan los dos.

| Archivo | Contenido | Sección del integrador |
|---|---|---|
| `mer-general` | MER (Chen), vista general sin atributos | 15.1.1 |
| `mer-usuarios`, `mer-publicaciones`, `mer-adopcion`, `mer-reportes` | MER por módulo, con atributos | 15.1.1 |
| `cu-acceso-consulta`, `cu-publicaciones`, `cu-adopcion`, `cu-administracion` | Casos de uso con los RF que cubren | 14 |
| `clases` | Clases del dominio, asociaciones, multiplicidades y enumeraciones | 14 |
| `sec-registro`, `sec-login`, `sec-publicar-adopcion`, `sec-publicar-perdida` | Secuencia de los flujos de la demo del Hito 2 | 14 |
| `est-adopcion`, `est-perdida`, `est-solicitud`, `est-refugio`, `est-seguimiento` | Máquinas de estado de 13.2 | 14 |
| `red-microsoft` | Entorno Microsoft: SRV-AD, PC del dominio e integración con los servidores de AdoptaUY | 17 |

Notación del MER: rectángulo doble = entidad débil; rombo doble = relación identificadora; línea gruesa = participación total; círculo «d» = especialización disjunta y total; elipse doble = atributo multivaluado; atributo subrayado = clave.

## Regenerar

```bash
# Requiere Java y Graphviz; plantuml.jar desde https://github.com/plantuml/plantuml/releases
cd docs/diagramas/uml
java -DPLANTUML_LIMIT_SIZE=16384 -jar plantuml.jar -tpng *.puml
```

`_estilo.iuml` tiene el estilo común (lo incluye cada diagrama UML).
