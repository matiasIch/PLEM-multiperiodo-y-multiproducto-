# Backlog XP — FARMAFÉ

## Reglas de trabajo XP

- Trabajar en historias pequeñas verticales que incluyan interfaz, lógica, persistencia y prueba.
- Antes de codificar una historia, registrar conversación breve de requisitos, criterios de aceptación y restricciones.
- Ejecutar pruebas después de cada cambio; refactorizar sin cambiar el comportamiento aprobado.
- No marcar historia como terminada solo porque existen archivos o pantallas. Debe ejecutarse el flujo y comprobarse.
- Todo cambio de esquema se entrega como migración versionada reversible cuando sea posible, más una forma de respaldar/restaurar.
- Commits pequeños si Git está disponible; si no lo está, conservar un registro de cambios y snapshots en documentación privada. No afirmar que hubo commit si no se realizó.
- Ningún emoji en software, mensajes o comentarios.

## Roles iniciales

- ADMIN: administración de usuarios, importaciones, maestros, configuración, auditoría.
- GERENCIA: consulta, creación/revisión de escenarios, comparación, aprobación de plan según política.
- VENTAS: clientes autorizados, pedidos/demanda, seguimiento de atención.
- ALMACEN: lotes, recepciones, movimientos, stock, preparación/despacho.

Permisos aplicados en backend por cada petición.

## Épica E0 — Reconstrucción controlada y diseño

### US-00 — Preservar y reconstruir la base del código
Como desarrollador del prototipo, quiero crear una implementación limpia basada en una especificación aprobada, para evitar que el nuevo sistema herede pantallas demostrativas y módulos incompletos.

Aceptación:
- Se respalda/copia la implementación anterior a `_archive/` sin copiar secretos ni exponer logs privados.
- No se elimina ni altera `Proyecto_Grado/Datos`, `Bibliografia`, `Instrumentos` o `Proyecto de Grado Versiones`.
- Se conserva la base de datos anterior sin `DROP`.
- El README identifica cómo instalar la nueva base `farmafe_app`.

## Épica E1 — Acceso y seguridad

### US-01 — Crear administrador seguro
Como operador local, quiero crear el primer administrador con una contraseña elegida por mí, para impedir que el sistema tenga credenciales demo predecibles.

Aceptación: no hay contraseña común codificada; password_hash/password_verify; asistente de primer uso se desactiva después de crear ADMIN; sesiones regeneradas al iniciar sesión; CSRF en formularios de estado.

### US-02 — Iniciar sesión y cerrar sesión
Como usuario autorizado, quiero autenticarme y cerrar sesión de forma segura, para acceder solo a las funciones que mi rol permite.

Aceptación: consultas PDO preparadas, errores genéricos para autenticación fallida, rate limit básico configurable, cookies HttpOnly/SameSite, Secure cuando HTTPS, cierre de sesión invalida la sesión.

### US-03 — Aplicar permisos por rol
Como administrador, quiero asignar roles, para que cada usuario acceda solo a los módulos autorizados.

Aceptación: pruebas de acceso permitido/denegado en el servidor; ocultar un botón no cuenta como control de acceso.

## Épica E2 — Importaciones

### US-04 — Cargar CSV/XLSX
Como usuario autorizado, quiero subir un archivo CSV/XLSX y seleccionar hoja, para cargar datos existentes sin transcribir manualmente.

Aceptación: límites de tamaño/extensión; validar MIME y estructura; nombre almacenado aleatorio fuera del webroot; no ejecutar macros/fórmulas; archivo fuente inmutable; registro de usuario/fecha/hash.

### US-05 — Mapear y previsualizar columnas
Como usuario, quiero asociar encabezados a campos canónicos y previsualizar registros, para evitar importar columnas mal interpretadas.

Aceptación: el mapeo ambiguo requiere confirmación; se muestran filas de ejemplo; fechas/decimales/unidades se interpretan con reglas documentadas.

### US-06 — Ver errores y confirmar importación
Como usuario, quiero saber qué filas fallan y por qué antes de confirmar, para corregir los datos sin corromper tablas finales.

Aceptación: errores por fila/campo, severidad, orientación; validas a staging; confirmación explícita; transacción; prevención de duplicados; resumen final.

### US-07 — Segregar naturaleza del dato
Como gerente, quiero identificar datos observados, derivados, simulados y supuestos, para no confundir escenarios con historia real.

Aceptación: etiquetas visibles; exportación conserva naturaleza; datos sintéticos nunca se mezclan en observados sin acción explícita y auditada.

## Épica E3 — Maestros

### US-08 — Consultar y mantener medicamentos
Como usuario autorizado, quiero buscar y revisar medicamentos, para comprobar código, presentación, unidad, ABC, estado regulatorio y requisitos de conservación.

Aceptación: unicidad de código; filtros/paginación; desactivación lógica; registro sanitario pendiente se muestra como pendiente; cadena de frío no identificada sigue sin confirmar.

### US-09 — Mantener proveedor y relación producto-proveedor
Como gerencia/administrador, quiero definir pares válidos producto-proveedor y sus plazos, costos, mínimos y condiciones, para que el modelo solo compre alternativas admitidas.

Aceptación: claves foráneas; 287 pares del workbook se usan solo tras revalidar la importación; no crear combinación para todos los productos/proveedores automáticamente.

### US-10 — Mantener clientes y ubicaciones
Como ventas/almacén, quiero consultar clientes anonimizados y ubicaciones validadas, para referenciar pedidos sin exponer datos innecesarios.

Aceptación: códigos consistentes, acceso por rol, campos no disponibles permanecen nulos/pending y no se inventan.

## Épica E4 — Compras, lotes e inventario

### US-11 — Registrar órdenes de compra
Como usuario autorizado, quiero registrar propuesta, aprobación y emisión de orden, para distinguir una recomendación de un compromiso real.

Aceptación: estados válidos; órdenes emitidas no se sobrescriben por una corrida PLEM; auditoría de cantidad/fecha/proveedor.

### US-12 — Registrar recepción y lote
Como almacén, quiero registrar lo efectivamente recibido y su lote/vencimiento, para hacer disponible solamente el inventario utilizable.

Aceptación: recepción vinculada a orden; validación de cantidades; lote/fecha; movimiento asociado; parcialidades soportadas; no asignar recepción futura antes del ingreso.

### US-13 — Consultar inventario por lote y zona
Como almacén, quiero consultar existencias utilizables, bloqueadas, reservadas y expuestas a vencimiento, para preparar pedidos con datos correctos.

Aceptación: saldos desde movimientos/snapshot reconciliable; FEFO entre lotes elegibles; saldos históricos inconsistentes se informan y no se corrigen silenciosamente.

### US-14 — Registrar movimiento de inventario
Como almacén autorizado, quiero registrar salida, devolución elegible, baja o ajuste documentado, para mantener trazabilidad de los cambios.

Aceptación: usuario/motivo/documento; devolución solo tras verificar condición; baja por vencimiento; sin negativos silenciosos.

## Épica E5 — Pedidos y demanda

### US-15 — Registrar/importar pedido detallado
Como ventas, quiero registrar pedidos y líneas cuando esa información exista, para medir atención y compromisos por cliente.

Aceptación: código pedido, producto, cantidad, unidad, fecha solicitud/compromiso cuando exista; atención parcial vinculada a movimientos; datos sintéticos etiquetados.

### US-16 — Importar demanda agregada
Como analista, quiero cargar cantidades por medicamento y periodo cuando solo haya ventas agregadas, para ejecutar escenarios sin fabricar pedido por pedido.

Aceptación: granularidad visible; venta no se rotula automáticamente como demanda solicitada; anual no se distribuye a meses como observación.

### US-17 — Gestionar pedidos pendientes/perdidos
Como analista, quiero configurar la política del escenario para los faltantes, para comparar backorder frente a demanda perdida cuando la regla real no está confirmada.

Aceptación: la conservación de demanda se verifica; escenario registra la política; no se atribuye como práctica histórica sin confirmación.

## Épica E6 — Pronóstico y escenarios

### US-18 — Crear escenarios de demanda
Como gerente/analista, quiero definir demanda para seis meses, para planificar con supuestos explícitos.

Aceptación: persistencia/promedio móvil solo si existe serie temporal compatible; escenarios futuros etiquetados; valores, unidad, periodo y fuente guardados.

### US-19 — Configurar parámetros operativos
Como gerente, quiero revisar presupuesto, capacidad, lead time, costos y reglas de lote, para conocer qué condiciones soportan el resultado.

Aceptación: cada valor incluye unidad, fuente/naturaleza; supuestos editables y versionados; costos simulados no se presentan como contables.

## Épica E7 — PLEM

### US-20 — Ejecutar optimización lexicográfica
Como analista, quiero maximizar el servicio y luego minimizar costo, para obtener un plan matemáticamente coherente.

Aceptación: solver MIP real; Etapa 1 guarda S*; Etapa 2 impone el servicio logrado; estado solver verificable; entradas y salidas versionadas; fallo o infactibilidad explicados.

### US-21 — Interpretar resultado del PLEM
Como gerente, quiero consultar compras, recibos planeados, inventario proyectado, atención, faltantes y diagnósticos, para evaluar y aprobar/rechazar las recomendaciones.

Aceptación: filtros por periodo/producto/proveedor; unidades; costos y supuestos visibles; no se muestra “óptimo” si no corresponde.

### US-22 — Recalcular horizonte móvil
Como gerente, quiero avanzar la ventana de seis meses usando datos nuevos, para actualizar decisiones todavía abiertas.

Aceptación: compras emitidas/comprometidas y pedidos confirmados congelados según reglas; decisiones abiertas recalculadas; plan anterior no se sobrescribe; una prueba demuestra que el resultado cambia cuando cambia un parámetro.

## Épica E8 — Evaluación y comparación

### US-23 — Comparar plan con resultado real
Como analista, quiero importar resultados observados después del periodo y compararlos con el plan guardado, para estudiar desviaciones.

Aceptación: mismo producto, unidad, periodo y definición; el plan original conserva su corte temporal; no se usan datos posteriores para recalcular el plan evaluado.

### US-24 — Comparar política de referencia con PLEM
Como gerente, quiero comparar referencia y optimización con iguales condiciones, para medir diferencias calculadas.

Aceptación: igualdad de demanda, inventario inicial, recursos y restricciones; indicadores no calculables se explican; separación de contrafactual y efecto real.

### US-25 — Generar reporte trazable
Como usuario autorizado, quiero exportar resultados con fuente/naturaleza y parámetros, para documentar la evaluación académica.

Aceptación: CSV/XLSX exportable si está implementado; cada reporte identifica ejecución y fecha; no incluye secretos ni datos personales innecesarios.

## Épica E9 — Calidad, seguridad y entrega

### US-26 — Auditar reglas y trazabilidad
Como administrador, quiero ejecutar controles de calidad de datos y balances, para detectar problemas antes de optimizar.

Aceptación: controles se ejecutan dinámicamente en DB; no se muestran resultados “OK” codificados; fallos incluyen registros implicados.

### US-27 — Manejar errores de forma útil
Como usuario, quiero mensajes concretos ante archivos inválidos, falta de permisos, infactibilidad o error técnico, para saber cómo continuar.

Aceptación: texto claro y no acusatorio; detalles sensibles solo en logs privados; identificador de incidencia.

### US-28 — Instalar/restaurar localmente
Como estudiante, quiero importar SQL en phpMyAdmin y seguir un README, para iniciar la aplicación en XAMPP.

Aceptación: SQL no destructivo; usuario DB de mínimo privilegio; creación de ADMIN sin password fija; prueba de backup/restauración documentada.

## Orden de iteraciones sugerido

- Iteración 0: respaldo, especificación, diagnóstico y base nueva vacía.
- Iteración 1: landing real, layout responsive, login seguro y navegación con permisos.
- Iteración 2: importación CSV/XLSX, staging, mapeo y errores.
- Iteración 3: catálogos y datos maestros con trazabilidad.
- Iteración 4: compras, recepciones, lotes y movimientos.
- Iteración 5: pedidos/demanda en modos detallado y agregado.
- Iteración 6: escenarios y versionado de datos.
- Iteración 7: primer PLEM pequeño comprobado manualmente.
- Iteración 8: PLEM ampliado y horizonte móvil.
- Iteración 9: backtesting, comparación con real y reportes.
- Iteración 10: regresión, seguridad, rendimiento y documentación final.
