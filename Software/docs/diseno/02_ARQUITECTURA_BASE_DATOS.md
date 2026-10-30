# Arquitectura de base de datos — FARMAFÉ

## 1. Reglas generales

- Motor objetivo de desarrollo local: MariaDB en XAMPP. El diagnóstico previo encontró MariaDB 10.4.32, pero es un entorno antiguo; antes de desplegar fuera de `localhost` se debe actualizar bajo un plan respaldado y validado. No sustituir componentes de XAMPP sin autorización.
- Base nueva recomendada: `farmafe_app`, para reconstruir sin destruir `farmafe_db` existente.
- Codificación `utf8mb4`, motor InnoDB, llaves foráneas e índices de búsqueda.
- Dinero `DECIMAL`, no `FLOAT`; cantidades y unidades consistentes. No mezclar piezas, cajas y empaques sin factor de conversión verificado.
- Todas las tablas de negocio importantes deben incluir claves, fechas, usuario de creación/modificación y referencias de origen cuando aplique.
- No exponer el directorio privado `storage/`, archivos importados, backups, logs, `.env` ni migraciones en el servidor público.
- No importar clientes personales sin anonimización; usar los códigos CLI del libro consolidado cuando corresponda.
- Los campos derivados, simulados o pendientes deben portar un campo `naturaleza_dato` o una relación trazable con su lote de importación.

## 2. Dominios y tablas propuestas

Nombres conceptuales; Antigravity deberá comparar con `Esquema_DB` y `Diccionario` y documentar cualquier diferencia antes de generar la migración definitiva.

### A. Seguridad y auditoría

1. `roles`: código/nombre de rol (ADMIN, GERENCIA, VENTAS, ALMACEN), activo.
2. `usuarios`: nombre de acceso único, hash de contraseña, nombre visible genérico, activo, último acceso, cambio obligatorio de contraseña si se habilita asistente inicial. No guardar contraseñas en texto plano.
3. `usuario_roles`: relación usuario-rol si se permiten múltiples roles; de lo contrario documentar rol único.
4. `auditoria_eventos`: usuario, acción, entidad, id de entidad, fecha, motivo, valores anterior/nuevo sanitizados, IP local si se necesita. No registrar contraseñas ni secretos.

### B. Fuentes e importaciones

5. `importaciones`: nombre original, nombre almacenado privado, hash SHA-256, tamaño, extensión, tipo de dataset, hoja, usuario, fechas, estado, conteos y naturaleza general.
6. `importacion_mapeos`: columna de origen, campo canónico, regla de conversión, confirmado por, fecha.
7. `importacion_filas`: lote, hoja/fila origen, estado, identificador de negocio, datos crudos sanitizados almacenados como JSON/texto, mensaje de validación. Evitar duplicar información personal innecesaria.
8. `importacion_errores`: fila, campo, código, severidad, explicación y sugerencia de corrección.
9. `fuentes_datos`: registro de fuentes/documentos o datasets lógicos y versión.

Staging es temporal y separado de las tablas definitivas. La importación confirmada debe ejecutarse de forma transaccional, con control de duplicados e idempotencia cuando sea posible.

### C. Maestros

10. `productos`: código único, descripción, presentación, unidad de planificación, factor de empaque verificado/pendiente, clasificación ABC y naturaleza, requisitos de conservación, estado de catálogo, registro sanitario y estado de validación. `PEND-*` nunca implica validación oficial.
11. `proveedores`: código, razón social, estado, procedencia/naturaleza.
12. `producto_proveedor`: producto, proveedor, costo de referencia, plazo en días, compra mínima, múltiplo de empaque, umbral de promoción, descuento, disponibilidad de referencia, fuente y naturaleza. Solo los pares existentes se consideran elegibles; no crear matriz completa ficticia. El libro tiene 287 pares válidos declarados, sujeto a revalidación al importar.
13. `clientes`: código anonimizado, descripción genérica/anonimizada, localidad/departamento solo si está disponible, naturaleza y estado. Evitar exponer nombres originales si no son necesarios.
14. `zonas_almacenamiento`: código, nombre, condición, capacidad y unidad, naturaleza/validación. No asumir capacidad real si el valor es de escenario.

### D. Abastecimiento e inventario

15. `ordenes_compra`: código, proveedor, fecha, estado, compromiso, fecha prevista, fecha de emisión, usuario y fuente.
16. `orden_compra_detalles`: orden, producto, cantidad, unidad, costo unitario, mínimo/múltiplo aplicado, cantidades recibidas y saldo pendiente derivados.
17. `recepciones`: documento/orden, fecha, estado, usuario, referencia de importación.
18. `recepcion_detalles`: recepción, detalle de compra, producto, cantidad y costo recibidos.
19. `lotes`: producto, código de lote, fecha de vencimiento, fecha de recepción, estado, condición de almacenamiento, origen observado/simulado y referencia a recepción si existe. No crear lote real al proyectar compras: lotes futuros serán cohortes proyectadas del escenario, claramente marcadas.
20. `ubicaciones`: zona/ubicación del almacén y condiciones; solo las existentes/confirmadas.
21. `inventario_movimientos`: producto, lote, ubicación origen/destino si aplica, tipo de movimiento, cantidad, fecha, documento fuente, usuario y motivo. El ledger de movimientos es la base auditable del saldo operativo.
22. `inventario_saldos_snapshot` (opcional): saldo por lote/ubicación/corte calculado para rendimiento; debe reconciliar contra movimientos y etiquetarse como derivado. No sustituye el historial.

### E. Demanda, pedidos y observaciones históricas

23. `pedidos`: código, cliente si se conoce, fecha de solicitud, fecha compromiso cuando exista, estado, naturaleza y origen.
24. `pedido_detalles`: pedido, producto, cantidad solicitada, unidad, fecha/ventana de compromiso y referencia de origen.
25. `atenciones_pedido`: detalle de pedido, cantidad, fecha, despacho, estado y referencia del movimiento de inventario. Una cantidad atendida debe estar respaldada por entrega o despacho según la regla adoptada.
26. `despachos`: código, fecha de preparación/despacho/entrega cuando exista, estado e incidencia; no incluye optimización de rutas ni GPS.
27. `demanda_periodica`: producto, periodo inicial/final, granularidad (DIA/MES/ANIO), cantidad observada/estimada, unidad, tipo (VENTA/DEMANDA_SOLICITADA/PRONOSTICO), naturaleza y fuente. Una venta anual no se divide en 12 meses observados.
28. `presupuestos_periodicos`: periodo, monto, moneda, naturaleza, fuente/escenario.
29. `costos_escenario`: escenario, tipo de costo, valor, unidad, vigencia, naturaleza, fuente y justificación. Los valores simulados del workbook (p. ej., costo de orden, posesión, faltante, merma o despacho) son parámetros editables de escenario, no costos históricos reales.

### F. Escenarios, PLEM, horizonte móvil y evaluación

30. `escenarios_planificacion`: nombre, fecha de corte/as-of date, periodo inicial, número de periodos (6 por defecto), modo de demanda (DETALLADO/AGREGADO), estado, autor y naturaleza.
31. `escenario_parametros`: escenario, parámetro, valor, unidad, fuente, naturaleza y confirmación.
32. `pronosticos`: escenario/método, producto, periodo, valor, unidad, método, periodo de entrenamiento y naturaleza.
33. `ejecuciones_plem`: escenario, versión de formulación, solver/versión, estado (OPTIMO/FACTIBLE/INFACTIBLE/TIME_LIMIT/ERROR), fechas, duración, gap si aplica, S óptimo, costo, hash de entrada y mensaje técnico.
34. `ejecucion_entradas_snapshot`: ejecución, versión/ID de datos, parámetros y hash; debe permitir reproducir entradas sin confiar en datos maestros modificados luego.
35. `planes_compras`: ejecución, periodo, producto, proveedor, cantidad propuesta, estado de decisión (PROPUESTA/APROBADA/COMPROMETIDA/EJECUTADA/CERRADA) y relación con orden real si aplica.
36. `inventario_proyectado`: ejecución, producto, lote real o cohorte proyectada, periodo, saldo inicial, recepción, asignación, baja y saldo final; un lote simulado futuro no se presenta como lote físico existente.
37. `asignaciones_plan`: ejecución, línea/cohorte de demanda, producto, lote elegible, periodo de atención, cantidad prevista.
38. `demanda_resultado_plan`: ejecución, producto/linea/cohorte, periodo, solicitado, atendido, pendiente, perdido y naturaleza de la demanda.
39. `ejecucion_diagnosticos`: ejecución, restricción o advertencia, severidad, explicación y datos que ayuden al usuario a entender el resultado.
40. `comparaciones`: ejecución de plan, periodo cerrado, lote de importación real, fecha de corte, estado y metodología de comparación.
41. `comparacion_metricas`: comparación, indicador, valor previsto, valor observado, diferencia, unidad, disponibilidad y explicación.
42. `comparacion_detalles` (opcional): comparación, producto/periodo/pedido si existe detalle, valor planificado/real y referencia de fuente.

### G. Catálogos auxiliares y migraciones

43. `migrations`: versión, nombre, checksum y fecha de aplicación.
44. tablas de catálogos para estados/tipos de movimiento si la arquitectura lo requiere. Evitar tablas de referencia excesivas para valores que no necesitan administración.

## 3. Relaciones clave

- Producto 1:N lotes, movimientos, detalles de pedido, detalles de compra y datos de demanda.
- Proveedor N:M productos mediante `producto_proveedor`; únicamente pares válidos.
- Orden de compra 1:N detalles; recepción 1:N detalles; recepción puede crear/asociar lote(s).
- Pedido 1:N detalles; detalle puede tener varias atenciones parciales.
- Escenario 1:N ejecuciones; ejecución 1:N compras planeadas, inventario proyectado, asignaciones, resultados y diagnósticos.
- Importación 1:N filas y errores; las entidades importadas conservan relación con la fuente cuando sea posible.
- Ejecución de plan 1:N comparaciones con resultados observados posteriores. Nunca sobrescribir la ejecución original.

## 4. Reglas de integridad

- No permitir saldos negativos en movimientos operativos confirmados sin un flujo explícito de excepción documentada. La carga histórica puede contener discrepancias: se registra como observación/error y no se “arregla” silenciosamente.
- Para cada periodo y cohorte de demanda, conservar demanda en atendida, pendiente o perdida según la política seleccionada.
- La compra solo afecta el inventario disponible al producirse la recepción utilizable, respetando lead time.
- Pedidos/órdenes ya comprometidos quedan fijos en la siguiente corrida; nuevas decisiones abiertas pueden recalcularse.
- Fecha vencimiento/condición debe poder impedir la asignación. FEFO se aplica entre lotes que sean elegibles para el pedido.
- Los cambios manuales que afecten cantidades, costos o vencimiento deben dejar auditoría.
- CSV/XLSX se valida con mapeo a campos canónicos; encabezados arbitrarios no crean columnas nuevas de base automáticamente.
- Nunca importar scripts SQL ni fórmulas de Excel como código ejecutable.

## 5. Estrategia SQL y despliegue local

- Crear una base nueva `farmafe_app` mediante script importable en phpMyAdmin, sin `DROP DATABASE` ni `DROP TABLE` destructivos.
- Proporcionar `database/sql/00_schema_fresh.sql` con esquema completo de la primera versión y migraciones versionadas para evolución.
- Proporcionar un script separado para roles mínimos y datos de referencia no sensibles. No insertar contraseñas por defecto.
- El administrador inicial se crea con comando PHP CLI o asistente de primer uso de una sola ejecución; contraseña definida por el operador y almacenada con `password_hash()`.
- Una importación real de los CSV preparados debe ser una acción explícita de UI, con validación y resumen previo. No insertar automáticamente datos derivados/simulados como si fueran reales durante el primer arranque.
- Debe incluir instrucciones para respaldar, importar, verificar y restaurar.
