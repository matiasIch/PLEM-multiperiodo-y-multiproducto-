#!/usr/bin/env python3
# =============================================================================
# FARMAFÉ - Generador Canónico de Seed SQL desde Modelo Consolidado
# =============================================================================

import openpyxl
import os
import sys

def escape_sql(val):
    if val is None:
        return 'NULL'
    s = str(val).strip()
    if s == '' or s == 'None':
        return 'NULL'
    s = s.replace("'", "''").replace("\\", "\\\\")
    return f"'{s}'"

def num_sql(val, default='0'):
    if val is None:
        return default
    try:
        f = float(str(val).replace(',', '.').strip())
        return str(f)
    except:
        return default

def int_sql(val, default='0'):
    if val is None:
        return default
    try:
        i = int(float(str(val).replace(',', '.').strip()))
        return str(i)
    except:
        return default

def get_h_and_d(wb, sname):
    s = wb[sname]
    rows = list(s.iter_rows(values_only=True))
    h_idx = 3 # Todas las hojas tienen encabezado en la fila 4 (indice 3)
    headers = [str(c).strip().lower().replace(' ', '_') if c is not None else '' for c in rows[h_idx]]
    data_rows = []
    for r in rows[h_idx+1:]:
        if not any(r) or r[0] is None:
            continue
        rd = {headers[i]: r[i] for i in range(min(len(headers), len(r)))}
        data_rows.append(rd)
    return data_rows

def main():
    wb_path = os.path.join(os.path.dirname(__file__), '..', '..', '..', 'Datos', 'Modelo_Datos_FARMAFE_Consolidado (1).xlsx')
    out_sql = os.path.join(os.path.dirname(__file__), '02_farmafe_consolidado.sql')

    print(f"Cargando {wb_path}...")
    wb = openpyxl.load_workbook(wb_path, data_only=True)

    lines = [
        "-- =============================================================================",
        "-- DATOS CANONICOS FARMAFE: Importados del Libro Consolidado Oficial",
        "-- =============================================================================",
        "USE `farmafe_app`;",
        "SET FOREIGN_KEY_CHECKS = 0;"
    ]

    # 1. PRODUCTOS
    print("Procesando Productos...")
    prod_rows = get_h_and_d(wb, 'Productos')
    prod_vals = []
    for rd in prod_rows:
        code = rd.get('codigo') or rd.get('id_producto')
        if not code: continue
        desc = escape_sql(rd.get('descripcion') or 'Sin descripcion')
        pres = escape_sql(rd.get('presentacion') or 'UNIDAD')
        unit = escape_sql(rd.get('unidad') or 'UNIDAD')
        cost = num_sql(rd.get('costo_unitario_promedio_bs'))
        abc = str(rd.get('clasificacion_abc') or 'B').strip().upper()
        if abc not in ['A', 'B', 'C']: abc = 'B'
        frio = str(rd.get('cadena_frio') or 'NO_IDENTIFICADO').strip().upper()
        if frio not in ['SI', 'NO', 'NO_IDENTIFICADO']: frio = 'NO_IDENTIFICADO'
        reg = escape_sql(rd.get('registro_sanitario'))
        st_reg = "'PENDIENTE_VALIDACION'" if (reg == 'NULL' or 'PEND' in reg) else "'VIGENTE'"

        prod_vals.append(f"({escape_sql(code)}, {desc}, {pres}, {unit}, {cost}, '{abc}', 'AMBIENTE', '{frio}', {reg}, {st_reg}, 1, 'OBSERVED', NOW())")

    if prod_vals:
        lines.append("INSERT INTO `productos` (`codigo`, `descripcion`, `presentacion`, `unidad_medida`, `costo_unitario_promedio_bs`, `clasificacion_abc`, `condicion_almacenamiento`, `cadena_frio`, `registro_sanitario`, `estado_registro_sanitario`, `activo`, `naturaleza_dato`, `creado_en`) VALUES\n" + ",\n".join(prod_vals) + "\nON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);")

    # 2. PROVEEDORES
    print("Procesando Proveedores...")
    prov_rows = get_h_and_d(wb, 'Proveedores')
    prov_vals = []
    for rd in prov_rows:
        code = rd.get('id_proveedor')
        name = escape_sql(rd.get('razon_social') or code)
        tipo = escape_sql(rd.get('tipo') or 'DROGUERIA')
        dep = escape_sql(rd.get('departamento_prototipo') or 'La Paz')
        plazo = int_sql(rd.get('plazo_base_dias'), '7')
        var_plazo = int_sql(rd.get('variacion_plazo_dias'), '2')
        prov_vals.append(f"({escape_sql(code)}, {name}, {tipo}, {dep}, {plazo}, {var_plazo}, 1, 'OBSERVED', NOW())")

    if prov_vals:
        lines.append("INSERT INTO `proveedores` (`codigo`, `razon_social`, `tipo`, `departamento`, `plazo_base_dias`, `variacion_plazo_dias`, `activo`, `naturaleza_dato`, `creado_en`) VALUES\n" + ",\n".join(prov_vals) + "\nON DUPLICATE KEY UPDATE `razon_social` = VALUES(`razon_social`);")

    # 3. CLIENTES
    print("Procesando Clientes...")
    cli_rows = get_h_and_d(wb, 'Clientes')
    cli_vals = []
    for rd in cli_rows:
        code = rd.get('id_cliente') or rd.get('cliente')
        tipo = escape_sql(rd.get('tipo_cliente') or 'FARMACIA_PRIVADA')
        seg = escape_sql(rd.get('segmento_cliente') or 'URBANO')
        dep = escape_sql(rd.get('departamento_prototipo') or 'La Paz')
        ciudad = escape_sql(rd.get('ciudad_prototipo') or 'La Paz')
        cli_vals.append(f"({escape_sql(code)}, {tipo}, {seg}, {dep}, {ciudad}, 1, 'OBSERVED', NOW())")

    if cli_vals:
        lines.append("INSERT INTO `clientes` (`codigo_anonimizado`, `tipo_cliente`, `segmento_cliente`, `departamento`, `ciudad`, `activo`, `naturaleza_dato`, `creado_en`) VALUES\n" + ",\n".join(cli_vals) + "\nON DUPLICATE KEY UPDATE `tipo_cliente` = VALUES(`tipo_cliente`);")

    # 4. UBICACIONES
    print("Procesando Ubicaciones...")
    ub_rows = get_h_and_d(wb, 'Ubicaciones')
    ub_vals = []
    for rd in ub_rows:
        code = rd.get('id_ubicacion') or rd.get('zona')
        name = escape_sql(rd.get('almacen') or rd.get('zona') or code)
        cap = int_sql(rd.get('capacidad_empaques_prototipo'), '5000')
        ub_vals.append(f"({escape_sql(code)}, {name}, 'AMBIENTE', {cap}, 'EMPAQUES', 1, 'SIMULATED', NOW())")

    if ub_vals:
        lines.append("INSERT INTO `zonas_almacenamiento` (`codigo`, `nombre`, `condicion_almacenamiento`, `capacidad_empaques`, `unidad_capacidad`, `activo`, `naturaleza_dato`, `creado_en`) VALUES\n" + ",\n".join(ub_vals) + "\nON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);")

    # 5. PRODUCTO_PROVEEDOR
    print("Procesando Producto_Proveedor...")
    pp_rows = get_h_and_d(wb, 'Producto_proveedor')
    pp_vals = []
    for rd in pp_rows:
        p_code = rd.get('codigo') or rd.get('id_producto')
        prv_code = rd.get('id_proveedor') or rd.get('proveedor')
        cost = num_sql(rd.get('costo_unitario_referencia_bs') or rd.get('costo_unitario_bs') or '1.0')
        plazo = int_sql(rd.get('plazo_entrega_dias'), '7')
        mo = int_sql(rd.get('compra_minima_unidades'), '1')
        pack = int_sql(rd.get('multiplo_empaque_unidades'), '1')
        prio = int_sql(rd.get('prioridad'), '1')
        pp_vals.append(f"((SELECT id_producto FROM productos WHERE codigo = {escape_sql(p_code)} LIMIT 1), (SELECT id_proveedor FROM proveedores WHERE codigo = {escape_sql(prv_code)} LIMIT 1), {cost}, {plazo}, {mo}, {pack}, {prio}, 1, 'OBSERVED', NOW())")

    if pp_vals:
        lines.append("INSERT IGNORE INTO `producto_proveedor` (`id_producto`, `id_proveedor`, `costo_unitario_bs`, `plazo_dias`, `compra_minima`, `multiplo_empaque`, `prioridad`, `activo`, `naturaleza_dato`, `creado_en`) VALUES\n" + ",\n".join(pp_vals) + ";")

    # 6. LOTES
    print("Procesando Lotes...")
    lot_rows = get_h_and_d(wb, 'Lotes')
    lot_vals = []
    for rd in lot_rows:
        p_code = rd.get('codigo') or rd.get('id_producto')
        lote_code = rd.get('lote') or rd.get('codigo_lote')
        venc = rd.get('fecha_vencimiento') or '2026-12-31'
        venc_str = venc.strftime('%Y-%m-%d') if hasattr(venc, 'strftime') else str(venc)[:10]
        rec = rd.get('fecha_primera_recepcion') or '2024-01-01'
        rec_str = rec.strftime('%Y-%m-%d') if hasattr(rec, 'strftime') else str(rec)[:10]
        saldo = int_sql(rd.get('saldo_actual') or '100', '100')
        nat = 'OBSERVED' if 'simul' not in str(rd.get('naturaleza_registro') or '').lower() else 'SIMULATED'
        lot_vals.append(f"((SELECT id_producto FROM productos WHERE codigo = {escape_sql(p_code)} LIMIT 1), {escape_sql(lote_code)}, '{venc_str}', '{rec_str}', 'DISPONIBLE', 'AMBIENTE', 1, {saldo}, '{nat}', NOW())")

    if lot_vals:
        lines.append("INSERT IGNORE INTO `lotes` (`id_producto`, `codigo_lote`, `fecha_vencimiento`, `fecha_recepcion`, `estado_lote`, `condicion_almacenamiento`, `id_zona`, `saldo_actual`, `naturaleza_dato`, `creado_en`) VALUES\n" + ",\n".join(lot_vals) + ";")

    # 7. DEMANDA PERIODICA (Gestión 2026 Meses 1 a 6)
    print("Procesando Demanda Periódica...")
    dem_rows = get_h_and_d(wb, 'Demanda_modelada')
    dem_vals = []
    for rd in dem_rows:
        p_code = rd.get('codigo')
        gestion = int_sql(rd.get('gestion'), '2026')
        mes = int_sql(rd.get('mes'), '1')
        if gestion == 2026 and 1 <= mes <= 6:
            cant = int_sql(rd.get('demanda_solicitada_modelada') or rd.get('ventas_atendidas_reportadas_asignadas'), '100')
            dem_vals.append(f"((SELECT id_producto FROM productos WHERE codigo = {escape_sql(p_code)} LIMIT 1), {gestion}, {mes}, {mes}, {cant}, 'UNIDAD', 'DEMANDA_SOLICITADA', 'DERIVED', NOW())")

    if dem_vals:
        lines.append("INSERT IGNORE INTO `demanda_periodica` (`id_producto`, `gestion`, `mes`, `periodo_secuencial`, `cantidad`, `unidad_medida`, `tipo_demanda`, `naturaleza_dato`, `creado_en`) VALUES\n" + ",\n".join(dem_vals) + ";")

    # 8. PRESUPUESTO
    print("Procesando Presupuesto...")
    pres_rows = get_h_and_d(wb, 'Presupuesto')
    pres_vals = []
    for rd in pres_rows:
        mes = int_sql(rd.get('mes'), '1')
        gestion = int_sql(rd.get('gestion'), '2026')
        if gestion == 2026 and 1 <= mes <= 6:
            tot = num_sql(rd.get('presupuesto_disponible_prototipo_bs'), '250000.00')
            ord_b = num_sql(rd.get('presupuesto_ordinario_bs') or tot, tot)
            pres_vals.append(f"({gestion}, {mes}, {mes}, {tot}, 10.00, {ord_b}, 'SIMULATED', NOW())")

    if pres_vals:
        lines.append("INSERT IGNORE INTO `presupuestos_periodicos` (`gestion`, `mes`, `periodo_secuencial`, `presupuesto_total_bs`, `reserva_emergencia_pct`, `presupuesto_ordinario_bs`, `naturaleza_dato`, `creado_en`) VALUES\n" + ",\n".join(pres_vals) + ";")

    # 9. ESCENARIO INICIAL
    lines.append("""
INSERT INTO `escenarios_planificacion` (
    `id_escenario`, `codigo_escenario`, `nombre`, `descripcion`, `fecha_corte`,
    `periodo_inicio_gestion`, `periodo_inicio_mes`, `horizonte_meses`, `modo_demanda`,
    `politica_faltante`, `presupuesto_mensual_defecto_bs`, `tolerancia_epsilon`, `estado`,
    `naturaleza_dato`, `creado_en`
) VALUES (
    1, 'ESC-2026-BASE', 'Escenario Base 6 Meses (FARMAFÉ)',
    'Escenario canónico de planificación con horizonte de seis meses, demanda derivada de ventas y política de backorder.',
    '2026-01-01', 2026, 1, 6, 'AGREGADO', 'BACKORDER', 250000.00, 0.000100, 'ACTIVO', 'ASSUMPTION', NOW()
) ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);
""")

    lines.append("SET FOREIGN_KEY_CHECKS = 1;")

    print(f"Escribiendo en {out_sql}...")
    with open(out_sql, 'w', encoding='utf-8') as f:
        f.write("\n\n".join(lines) + "\n")
    print("Seed generado exitosamente.")

if __name__ == '__main__':
    main()
