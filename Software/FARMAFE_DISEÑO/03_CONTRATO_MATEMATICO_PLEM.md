# Contrato matemático para la PLEM de FARMAFÉ

## 1. Regla de trazabilidad matemática

El software debe utilizar las variables, ecuaciones y lógica de optimización documentadas en `Perfil_FARMAFE_PLEM_V3.docx`, sección 6.16, y en `Modelo_Datos_FARMAFE_Consolidado (1).xlsx`, hoja `PLEM_config`. No se permite sustituir la PLEM por reglas heurísticas y presentarlas como optimización matemática.

Antes de codificar el solver, Antigravity debe comparar las ecuaciones del perfil, `PLEM_config`, `PLEM_tamano`, `Horizonte_movil` y este contrato. Hay diferencias de notación/granularidad, especialmente en el indicador de servicio, el índice de demanda y el conteo computacional. Toda reconciliación debe quedar documentada en `docs/DECISIONES_TECNICAS.md` y `docs/MODELO_PLEM.md`, manteniendo la intención del proyecto y explicando el cambio. Nunca ocultar una discrepancia matemática.

## 2. Conjuntos e índices

- `i ∈ I`: medicamentos. El catálogo del workbook contiene 195 productos como referencia sujeta a depuración.
- `s ∈ S`: proveedores del prototipo. El workbook identifica tres registros de proveedor.
- `(i,s) ∈ PP`: pares producto-proveedor realmente válidos. El workbook indica 287 pares; no construir automáticamente la matriz completa 195 × 3.
- `t ∈ T`: periodos mensuales del horizonte de decisión; seis periodos inicialmente.
- `l ∈ L_i`: lotes existentes del medicamento `i`; los lotes de respaldo simulados deben quedar identificados como simulados. El conteo 848 no debe declararse como 848 lotes físicos observados si mezcla observados y simulados.
- `c ∈ C_t`: pedido/línea de pedido del periodo `t`, solo cuando exista granularidad detallada. En modo agregado, usar demanda `D_it` por producto y periodo; no fabricar líneas de pedido históricas.
- `z ∈ Z`: zonas de almacenamiento, solo si sus capacidades y relaciones son utilizables. Capacidad simulada se identifica como parámetro de escenario.

## 3. Parámetros y unidades

- `d_ict` o `D_it`: demanda solicitada por cohorte de pedido/periodo o demanda agregada de referencia. Cantidad y periodo deben estar definidos.
- `c_is`: costo unitario de compra del producto `i` al proveedor `s`, Bs/unidad de planificación, con fuente/naturaleza.
- `LT_is`: lead time del par producto-proveedor, en días; convertir a recepción mensual con una regla temporal documentada.
- `B_t`: presupuesto de compra del periodo en Bs, fuente real o supuesto de escenario.
- `CAP_zt`: capacidad utilizable de zona/periodo, con unidad explícita; no mezclar volumen, cajas, empaques o unidades sin factor de conversión validado.
- `v_l`: fecha/periodo límite de utilización comercial del lote `l`; no usar vencimiento físico y umbral comercial como si fueran lo mismo.
- `MO_is`: cantidad mínima de compra y `pack_i`/múltiplo comercial, solo cuando estén documentados o etiquetados como escenario.
- `w`: ponderaciones de servicio documentadas; si no existen prioridades verificadas, usar pesos uniformes como escenario de referencia y someter la sensibilidad a evaluación.
- Costos de orden, posesión, faltante, baja y despacho son parámetros editables de escenario cuando no hay comprobación contable. Los valores propuestos en hojas del modelo no son automáticamente costos reales de FARMAFÉ.
- `M_ist`: cota superior válida para Big-M, derivada de presupuesto, capacidad, límite de suministro o límite explícito y justificado. No usar una constante arbitrariamente grande.

Todas las cantidades deben usar una unidad de planificación canónica por producto. Si las presentaciones son indivisibles, compras/asignaciones deben respetar enteros y múltiplos comerciales.

## 4. Variables que deben estar representadas

Mantener estas variables base del diseño:

- `x_ist ∈ Z_≥0`: cantidad a comprar del producto `i` al proveedor `s` en el periodo `t`.
- `z_ist ∈ {0,1}`: activación de compra, vinculada a una decisión real y a las restricciones de mínimo/múltiplo/orden.
- `I_ilt ∈ Z_≥0` (o dominio continuo solo con justificación): inventario final utilizable del lote `l` de producto `i` al final de `t`.
- `a_ict ∈ Z_≥0`: cantidad de demanda atendida para la cohorte/ítem `i,c,t`, con significado formal compatible con el indicador de servicio.
- `p_ict ∈ Z_≥0`: cantidad pendiente que conserva compromiso para el periodo posterior, si la política de escenario utiliza backorders.
- `q_ict ∈ Z_≥0`: cantidad definitivamente perdida, cuando la política registra demanda perdida.
- `W_ilt ∈ Z_≥0`: cantidad dada de baja por vencimiento, cuando corresponda.
- Variable de asignación por lote (p. ej. `g_iclt ∈ Z_≥0`): unidades del lote asignadas a la línea/cohorte en el periodo de atención. Esta variable suplementaria es necesaria para que el balance por lote y la atención tengan vínculo matemático explícito.
- Variables auxiliares de servicio/cumplimiento por cohorte cuando sean necesarias para distinguir atención dentro del plazo frente a atención tardía.

Cada variable debe tener: conjunto de índices, unidad, dominio, fuente de parámetros y ecuaciones donde participa.

## 5. Funciones objetivo

### Etapa 1 — maximizar servicio

Intención del proyecto: maximizar primero el cumplimiento/atención ponderada de la demanda.

Para modo detallado puede definirse:

`max S = Σ_(i,c,t: d_ict>0) w_ict · (a_ict / d_ict)`

con `0 ≤ a_ict ≤ d_ict` y `Σ w_ict = 1` sobre todas las cohortes elegibles de esa ejecución. La variable `a_ict` del indicador debe medir la cantidad de la cohorte atendida dentro del plazo de compromiso, no sumar sin límite backorders anteriores a la demanda del periodo actual.

Para modo agregado:

`max S_agg = Σ_(i,t: D_it>0) w_it · (A_it / D_it)`

con `0 ≤ A_it ≤ D_it`, pesos normalizados y demanda agregada de escenario/observada claramente identificada. Este valor representa atención ponderada de demanda, no porcentaje de pedidos completos.

### Etapa 2 — minimizar costo logístico

`min C = C_compra + C_orden + C_posesion + C_faltante + C_baja + C_despacho`

Los términos se incluyen solo si son atribuibles a decisiones del modelo y sus parámetros existen/están identificados como escenario. Evitar doble conteo de egresos. El costo de un despacho no debe cobrarse por unidad si el parámetro es por envío; la unidad debe coincidir.

Sujeto a todas las restricciones de la etapa 1 y a mantener el servicio óptimo encontrado `S*`. Preferentemente imponer `S ≥ S* − ε`, con `ε=0` salvo que se justifique una tolerancia numérica. Si se utiliza `S ≥ S*(1−ε)`, documentar el efecto de tolerancia y resolver el caso `S*=0`.

## 6. Restricciones obligatorias

### 6.1 Balance de inventario por lote

`I_ilt = I_il,t-1 + R_ilt − Σ_c g_iclt − W_ilt`

En `t=1`, `I_il0` es el saldo inicial utilizable. `R_ilt` corresponde a recepciones realmente planificadas para ese lote/cohorte. Las compras emitidas no incrementan inventario hasta una recepción utilizable.

Si se incorporan devoluciones, deben tener variable separada (p. ej. `Dev_ilt`) y solo entrar al saldo cuando su condición esté validada. No reutilizar `U` para devolución y faltante simultáneamente.

### 6.2 Conservación de demanda

Intención de la fuente:

`demanda nueva + pendiente previo = cantidad atendida + pendiente final + demanda perdida`

En forma detallada, definir la indexación por cohorte y su evolución para que ninguna unidad pendiente se cuente dos veces, ni pueda quedar simultáneamente atendida y perdida. La cantidad atendida debe vincularse a las asignaciones `g` por producto/lote/periodo.

En modo agregado usar una ecuación agregada equivalente por producto-periodo. No generar pedido/ciente ficticio para cumplir índices matemáticos.

### 6.3 Presupuesto

`Σ_(i,s):(i,s)∈PP c_is · x_ist ≤ B_t` para cada periodo `t`, incluyendo solo compras y cargos realmente atribuibles.

### 6.4 Capacidad

`Σ_(i,l en zona z) vol_i · I_ilt ≤ CAP_zt` para cada zona y periodo, solo si `vol_i` y `CAP_zt` están en unidades compatibles y sustentadas. Si la capacidad solo existe como supuesto derivado, la solución debe indicar que está condicionada al escenario.

### 6.5 Activación binaria, pedido mínimo y empaque

`MO_is · z_ist ≤ x_ist ≤ M_ist · z_ist`

Si existe múltiplo comercial `pack_i`, `x_ist = pack_i · n_ist`, con `n_ist` entero no negativo, o una formulación equivalente. `M_ist` debe ser ajustado y justificado. La variable binaria debe utilizarse solo para decisiones reales, como activar una orden sujeta a mínimo/umbral/costo fijo.

### 6.6 Lead time y recepciones

Relacionar las compras con el periodo de recepción mediante `LT_is`. Si el plazo cae entre periodos o un pedido llega fuera del horizonte, definir una convención temporal y mantener el compromiso futuro en el estado correspondiente. Las órdenes comprometidas provenientes de la situación real son parámetros congelados, no decisiones libres.

### 6.7 Lotes, vigencia y FEFO

- Excluir asignaciones a lotes no utilizables, bloqueados o no elegibles para la vigencia mínima requerida por el pedido.
- Aplicar el criterio comercial de menos de 365 días como corto vencimiento solo como regla declarada por la empresa y verificar si aplica a todas las categorías de cliente.
- No permitir inventario utilizable después de la fecha/periodo límite. La baja debe reconciliar el inventario que expira.
- FEFO se aplica entre lotes elegibles. Si se afirma que FEFO es una restricción estricta de la PLEM, implementarla mediante precedencia/activación correctamente formulada o explicar y probar una capa determinista de asignación que no altere el óptimo ni viole balances. No basta con mostrar una etiqueta FEFO.
- Las fechas y ubicaciones simuladas no deben presentarse como dato físico confirmado.

### 6.8 Horizonte móvil y decisiones congeladas

No congelar indiscriminadamente todas las compras del primer periodo. Congelar únicamente las líneas de orden que ya estén emitidas/comprometidas y otras decisiones aprobadas que la regla operativa declare inmutables, independientemente de dónde caigan dentro del horizonte. Las decisiones futuras no comprometidas quedan abiertas. Mantener orden original, aprobaciones, nuevas revisiones y cambios auditados.

### 6.9 Dominios

Declarar dominios enteros, continuos y binarios para cada variable. Cantidades de medicamento no serán negativas. Demandas nulas deben excluirse del cociente de servicio para evitar división entre cero. Todos los balances y restricciones tendrán índices completos y unidades coherentes.

## 7. Modo histórico versus escenario

El workbook declara las ventas como observadas en una granularidad anual; facturas con fechas e importes sin necesariamente tener detalle de medicamento/cantidad/lote/pedido en una misma línea; demanda mensual, proveedores/plazos, capacidades y varios costos aparecen como simulados o de escenario. Por ello:

- No distribuir ventas anuales en meses y presentarlas como observaciones mensuales.
- No calcular cumplimiento histórico por pedido sin pedidos y fechas reales.
- Para probar el algoritmo sí se pueden generar datos de prueba deterministas, claramente identificados como SIMULADOS.
- La comparación histórica debe respetar un corte temporal y no usar información futura.

## 8. Pruebas matemáticas mínimas

1. Caso pequeño que pueda verificarse manualmente con 2 medicamentos, 1 proveedor válido y 2 periodos.
2. Caso de inventario suficiente sin compras innecesarias.
3. Caso de presupuesto restrictivo que obligue a priorizar servicio.
4. Caso de lead time que impida usar una compra antes de recepcionarla.
5. Caso de lote vencido/no elegible que no pueda asignarse.
6. Caso de compra mínima/empaque con activación binaria.
7. Caso de pedido pendiente frente a venta perdida, comprobando conservación de demanda.
8. Caso con orden de compra comprometida que permanezca fija tras el desplazamiento del horizonte.
9. Caso de segunda etapa que reduzca costo manteniendo el nivel de servicio óptimo.
10. Caso infactible que se informe como tal y no se presente como éxito.

Cada prueba guarda datos de entrada, solución esperada, salida real, tolerancia y criterio de aceptación.
