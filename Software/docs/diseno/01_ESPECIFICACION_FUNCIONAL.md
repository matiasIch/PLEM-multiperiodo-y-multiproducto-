# Especificación funcional — Sistema FARMAFÉ

## 1. Propósito del producto

Desarrollar una plataforma web local de apoyo a decisiones para planificación logística de compras, inventarios y atención de pedidos de FARMAFÉ. El sistema recibe archivos CSV/Excel, valida y normaliza la información, mantiene datos trazables en MariaDB, ejecuta una PLEM multiproducto y multiperiodo con prioridad lexicográfica y permite contrastar planes históricos guardados con resultados observados posteriores.

El sistema no reemplaza el sistema contable, no ejecuta compras/pagos/movimientos físicos automáticamente y no corrige por sí solo los descuadres físicos-contables. Sus resultados son recomendaciones calculadas; no se reportarán ahorros realizados sin evidencia de ejecución y medición posterior.

## 2. Principio de funcionamiento

El usuario debe distinguir siempre entre:

1. **Dato observado:** importado de una fuente real de FARMAFÉ, con fuente y fila rastreables.
2. **Dato derivado:** calculado de forma reproducible a partir de datos observados.
3. **Dato simulado:** generado únicamente para ensayos del prototipo.
4. **Supuesto/escenario:** parámetro editable usado para explorar alternativas.
5. **Pendiente de validación:** campo cuyo significado, valor o fuente no está confirmado.

La interfaz muestra una etiqueta de naturaleza junto a los campos e indicadores afectados. Ningún dato simulado puede convertirse silenciosamente en observado.

## 3. Modos de operación

### 3.1 Importación y saneamiento

Flujo: seleccionar archivo → seleccionar hoja (si es Excel) → detectar encabezados → mapear columnas al diccionario canónico → previsualizar → validar → resolver errores/advertencias → confirmar importación → registrar resultado. Antes de confirmar, los datos quedan en staging; al confirmar se guardan en entidades de negocio dentro de una transacción siempre que sea viable.

Capacidades:
- Admitir CSV y XLSX. Para XLS antiguo, el sistema debe informar si requiere conversión a XLSX/CSV, no fallar sin explicación.
- Detectar delimitador, codificación, encabezados, tipos de fecha, separadores decimales y números como texto.
- Seleccionar hoja y fila inicial de encabezado.
- Permitir mapeo explícito de columnas; sugerir coincidencias sin asumirlas sin revisión cuando sean ambiguas.
- Reportar filas válidas, inválidas, duplicados probables, campos ausentes y reglas que no pudieron evaluarse.
- Conservar archivo original en ubicación privada, hash SHA-256, nombre, usuario, fecha, tipo de datos, hoja, fila origen y resumen de validación.
- Prevenir duplicados mediante clave de negocio y/o hash de importación; requerir decisión explícita para reimportar.
- No borrar, editar ni reemplazar el archivo fuente.
- No generar pedidos detallados ficticios a partir de ventas anuales agregadas.

### 3.2 Datos maestros

Catálogos de medicamentos, proveedores, pares producto-proveedor, clientes con códigos anonimizados y ubicaciones/zonas de almacenamiento. CRUD con validación, desactivación lógica, historial y procedencia. Un dato con registro sanitario `PEND-*` se muestra como pendiente de validación, nunca como validado oficialmente. `NO_IDENTIFICADO` en cadena de frío no se transforma en una condición frigorífica inferida.

### 3.3 Abastecimiento e inventario

- Órdenes de compra con estados: propuesta, aprobada, emitida, parcialmente recibida, recibida, cerrada o cancelada (estados y transiciones deben ser validados).
- Separar orden emitida, recepción confirmada y saldo realmente utilizable.
- Registrar recepción vinculada con orden/línea y lote real cuando esté disponible.
- Manejar lote, vencimiento, estado, ubicación, cantidad y movimientos de inventario.
- FEFO: entre lotes elegibles se prioriza el que vence antes. La elegibilidad por condición y vigencia del pedido se evalúa antes de la prioridad FEFO.
- Movimiento físico exige motivo, usuario y fecha. El sistema registra movimientos, no inventa ajustes para forzar conciliación.
- El saldo puede derivarse del libro de movimientos; si se mantiene una tabla de saldo/snapshot, debe poder verificarse contra los movimientos.

### 3.4 Pedidos y demanda

Dos niveles de granularidad:

**Modo detallado:** cuando existen identificador, línea de pedido, medicamento, cantidad, fechas comprometidas y estado real, el sistema puede medir atención por pedido y puntualidad. Los datos sintéticos de prueba se etiquetan como simulados.

**Modo agregado:** cuando solo hay ventas por medicamento y gestión o periodo, el sistema trabaja con demanda agregada por producto-periodo. No calcula porcentaje histórico de pedidos completos/a tiempo porque no hay líneas de pedido ni fechas suficientes.

En cada periodo la demanda deberá tener una unidad definida y compatible con compras e inventario. La demanda puede ser observada, pronosticada o supuesta, siempre etiquetada. La venta realizada no equivale necesariamente a demanda solicitada si hubo faltantes.

### 3.5 Planificación futura y horizonte móvil

- Horizonte operativo inicial: seis periodos mensuales.
- El usuario crea escenario, define corte temporal, revisa datos iniciales, pronóstico/demanda de referencia, presupuesto, capacidad, costos y reglas.
- La PLEM optimiza simultáneamente los medicamentos seleccionados y todos los periodos del horizonte.
- Se guarda snapshot de entradas, versión de modelo, versión de solver, parámetros, estado de solución, objetivos, tiempos y resultados.
- Tras cerrar un periodo o incorporar datos nuevos, el usuario crea una nueva corrida móvil: actualiza el inventario con movimientos reales, conserva compras emitidas/comprometidas y pedidos confirmados según la regla vigente, y permite recalcular las decisiones abiertas.
- La corrida nueva no sobrescribe la anterior. Se puede comparar plan inicial, revisiones y resultado observado.
- Una decisión aprobada, comprometida o cerrada no puede modificarse sin acción autorizada, motivo y auditoría.
- Las compras emitidas no se pueden “deshacer” al recalcular. Las propuestas no emitidas sí son decisiones abiertas.

### 3.6 Motor PLEM

- Solver MIP real, invocado mediante adaptador documentado, sin simular resultados.
- Etapa 1: maximizar el nivel de servicio ponderado definido de forma consistente.
- Etapa 2: minimizar el costo logístico manteniendo el mejor servicio alcanzado en la etapa 1.
- Restricciones: balance de inventario, conservación de demanda, presupuesto, capacidad cuando el parámetro es defendible, lead time/recepción, compra mínima/empaque, elegibilidad por lote y vencimiento, decisiones comprometidas congeladas y dominios de variables.
- Presentar estado: óptimo, factible no probado como óptimo, infactible, límite de tiempo, error de configuración o error del solver.
- Guardar diagnóstico del solver y explicar restricciones que limitan solución cuando el solver permita obtener esa información.
- No afirmar optimalidad sin el estado confirmado del solver.

### 3.7 Evaluación retrospectiva y comparación

Cada plan usa información disponible a una fecha de corte. Después de que ocurra el periodo se importan resultados reales comparables. El módulo compara por producto-periodo y, solo si existe dato detallado, por pedido/línea.

Comparaciones mínimas:
- compra prevista vs. orden emitida/compra realmente recibida;
- recepción prevista vs. recepción observada;
- servicio previsto vs. servicio observado en la misma definición;
- inventario proyectado vs. saldo observado verificable;
- lote/stock expuesto a corto vencimiento, si existen lotes y fechas válidas;
- costo proyectado vs. costos observados comparables, sin doble conteo;
- pronóstico vs. demanda observada, solo en periodicidad equivalente.

El reporte separa tres conclusiones:
1. **Verificación matemática:** la solución respeta ecuaciones y restricciones.
2. **Evaluación contrafactual:** el modelo propone un resultado diferente bajo las mismas condiciones de referencia.
3. **Efecto observado:** solo puede afirmarse si se documenta la ejecución real de recomendaciones y se miden los resultados.

No utilizar información futura durante backtesting. Si las fuentes históricas no permiten reconstruir pedido atendido, costo o inventario de un periodo, mostrar “No calculable con los datos disponibles”.

### 3.8 Reportes

- Resumen del estado de datos e importaciones.
- Inventario disponible y lotes elegibles/no elegibles.
- Órdenes, recepciones y compromisos.
- Resultado de cada ejecución PLEM.
- Decisiones de compra y proyección de inventario por medicamento-periodo.
- Demanda atendida, pendiente y perdida según la política seleccionada.
- Comparación plan-realidad con naturaleza de los datos.
- Exportación CSV y, si se implementa, XLSX de tablas filtradas.
- Impresión/PDF únicamente si se implementa y se prueba; no prometer de antemano.

### 3.9 Usuarios y roles

- **Administrador:** usuarios, roles, configuración, importación, catálogos, auditoría y ajustes técnicos autorizados.
- **Gerencia:** consulta de indicadores, escenarios, resultados económicos y aprobación de planes.
- **Ventas:** pedidos, clientes autorizados, demanda y seguimiento de atención.
- **Almacén:** lotes, recepción, ubicación, inventario, preparación y despacho.

Aplicar mínimo privilegio en el servidor, no solo ocultando botones. No crear cuentas con contraseñas comunes predefinidas. El primer administrador se crea mediante asistente de instalación o comando local seguro, con password_hash.

## 4. Panel principal

Landing pública: identidad FARMAFÉ, propósito, funcionalidades y acceso; no mostrar instrucciones de desarrollo, número de etapa, README ni variables internas.

Panel privado: importaciones recientes, calidad de datos, inventario y lotes con riesgo, compras comprometidas/abiertas, último plan PLEM y estado, faltantes/servicio solo cuando calculables, comparaciones pendientes y accesos a tareas. Cada KPI debe proceder de consultas reales, mostrar periodo/fuente y no usar cifras hardcodeadas como respaldo.

## 5. Requisitos de experiencia y errores

- UI en español, responsive, accesible y sin emojis en ningún lugar del software.
- Colores de marca azul y amarillo, con contraste legible; amarillo como acento y no como texto pequeño sobre blanco.
- Mensajes de error claros, no acusatorios: explicar qué pasó, qué dato/regla falló, dónde corregirlo y un identificador de error cuando sea técnico.
- Distinguir errores de entrada, falta de permisos, datos insuficientes, infactibilidad matemática, timeout y fallo interno.
- No culpar automáticamente al usuario ni ocultar errores del sistema.
- Confirmación antes de acciones irreversibles; transacciones y auditoría.

## 6. Alcance excluido

Quedan fuera: contabilidad integral, módulo contable, conciliación física-contable automatizada, importación directa, trámites aduaneros, diseño y optimización de rutas, GPS, gestión de flota y validación bajo 21 CFR Part 11. El transporte solo entra en el costo logístico atribuible a FARMAFÉ si existe información validada que lo sustente.
