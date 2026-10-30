# FARMAFÉ — Paquete de definición y reconstrucción del software

## Para qué sirve este paquete

Este paquete establece la definición funcional, la arquitectura de datos, las historias de usuario XP, el contrato matemático del PLEM y la instrucción de ejecución para Antigravity. Su finalidad es reconstruir la aplicación dentro de `Proyecto_Grado/Software/` sin perder los materiales originales del proyecto de grado.

## Orden de uso

1. Conservar una copia del proyecto completo y exportar cualquier base de datos local que tenga información útil.
2. Abrir en Antigravity la carpeta padre `Proyecto_Grado/`, no únicamente `Software/`.
3. Copiar estos documentos a `Proyecto_Grado/Software/docs/diseno/` o conservarlos temporalmente en la raíz de `Software/`.
4. Abrir `PROMPT_RECONSTRUCCION_DESDE_CERO_FARMAFE.txt`, copiar su contenido y enviarlo al agente.
5. Exigir que Antigravity lea y cite estos documentos internos antes de programar. Debe registrar decisiones y pruebas, no afirmar tareas no ejecutadas.

## Fuente de verdad

La fuente de negocio es la documentación y los datos proporcionados en `Proyecto_Grado/`, especialmente:

- `Proyecto de Grado Versiones/Perfil_FARMAFE_PLEM_V3.docx`.
- `Datos/Modelo_Datos_FARMAFE_Consolidado (1).xlsx`, incluidas las hojas `Requisitos`, `Calidad_datos`, `Auditoria`, `Productos`, `Proveedores`, `Producto_proveedor`, `Clientes`, `Ubicaciones`, `Ordenes_compra`, `Compras`, `Lotes`, `Inventario_mensual`, `Inventario_lotes`, `Ventas_anuales`, `Facturas`, `Pedidos`, `Pedido_detalle`, `Despachos`, `Demanda_modelada`, `Presupuesto`, `Costos_logisticos`, `PLEM_config`, `PLEM_tamano`, `Retro_anual`, `Retro_mensual`, `Proyeccion`, `Horizonte_movil`, `Esquema_DB`, `Fuentes_metodo` y `Diccionario`.
- Los archivos fuente de compras, ventas, inventario/lotes, manual de funciones, misión/visión y cuestionario que están en `Datos/` e `Instrumentos/`.
- Los antecedentes de `Bibliografia/`.

El código y los documentos generados por IA no prevalecen sobre la fuente original cuando existe contradicción. Si una fuente no permite sostener una función o un indicador, debe documentarse la limitación.

## Convenciones obligatorias

- Aplicación web en PHP, MariaDB/MySQL de XAMPP, SQL explícito, CSS y JavaScript local.
- Tailwind CSS compilado localmente; sin CDN en la versión entregable.
- Identidad visual basada en azul y amarillo de FARMAFÉ.
- Ningún emoji en la interfaz, en mensajes, en comentarios de código ni en datos de ejemplo presentados como reales.
- No fabricar indicadores, clientes, pedidos históricos, lotes observados, costos reales ni resultados de optimización.
- Mantener separados los datos `OBSERVADO`, `DERIVADO`, `SIMULADO`, `SUPUESTO` y `PENDIENTE_VALIDACION`.
- No borrar o sobrescribir la implementación existente ni la base `farmafe_db` sin respaldo y autorización. Para reconstruir desde cero, crear un espacio de trabajo nuevo y una base nueva, inicialmente `farmafe_app`, sin destruir la anterior.
- No incluir funciones excluidas del perfil: contabilidad integral, conciliación contable automática, importación directa, trámites aduaneros, diseño/ruteo de rutas, GPS ni validación bajo 21 CFR Part 11.

## Documentos del paquete

- `01_ESPECIFICACION_FUNCIONAL.md`: experiencia de usuario y flujo de extremo a extremo.
- `02_ARQUITECTURA_BASE_DATOS.md`: entidades, relaciones, restricciones y procedencia.
- `03_CONTRATO_MATEMATICO_PLEM.md`: variables, ecuaciones, objetivos, horizonte móvil y riesgos de formulación.
- `04_BACKLOG_HISTORIAS_XP.md`: historias con criterios de aceptación y orden de entrega.
- `05_ARQUITECTURA_TECNICA_UI.md`: carpetas PHP/CSS/JS, componentes y colores.
- `PROMPT_RECONSTRUCCION_DESDE_CERO_FARMAFE.txt`: instrucción maestra de ejecución para Antigravity.
