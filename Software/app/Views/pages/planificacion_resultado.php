<!-- Encabezado de la Corrida PLEM -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <div>
            <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">
                Resultado de Optimización PLEM &bull; Versión Inmutable
            </div>
            <h2 style="font-size: 1.35rem; color: var(--navy-800); margin-top: 0.25rem;">
                Plan de Planificación Logística <?= e($ejecucion['version_plan']) ?>
            </h2>
        </div>
        <div class="d-flex align-center gap-1">
            <a href="<?= e(\App\Core\Router::url('/app/planificacion/exportar/' . $ejecucion['id_ejecucion'])) ?>" class="btn btn-secondary btn-sm">
                Exportar Plan en CSV
            </a>
            <span class="badge" style="background-color: var(--success-bg); color: var(--success-text); border: 1px solid #a7f3d0; font-size: 0.85rem; padding: 0.4rem 0.8rem;">
                Estado: <?= e($ejecucion['estado_solver_etapa2']) ?>
            </span>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Panel de Metricas Clave -->
        <div class="kpi-grid" style="margin-bottom: 1.5rem;">
            <!-- Nivel de Servicio -->
            <div class="kpi-card" style="border-top: 4px solid var(--blue-600);">
                <div class="kpi-title">Nivel de Servicio Ponderado (S*)</div>
                <div class="kpi-value" style="color: var(--blue-700);">
                    <?= number_format((float)$ejecucion['nivel_servicio_optimo_s1'] * 100, 2) ?>%
                </div>
                <div class="kpi-footer">
                    <span>Etapa 1: Maximización de Cumplimiento</span>
                    <?= \App\Core\View::provenanceBadge('DERIVED') ?>
                </div>
            </div>

            <!-- Costo Logistico Total -->
            <div class="kpi-card" style="border-top: 4px solid var(--navy-800);">
                <div class="kpi-title">Costo Logístico Total</div>
                <div class="kpi-value">
                    <?= \App\Core\View::formatBs($ejecucion['costo_logistico_total_s2']) ?>
                </div>
                <div class="kpi-footer">
                    <span>Etapa 2: Minimización de Costos</span>
                    <?= \App\Core\View::provenanceBadge('DERIVED') ?>
                </div>
            </div>

            <!-- Total Compras Recomendadas -->
            <div class="kpi-card" style="border-top: 4px solid var(--yellow-500);">
                <div class="kpi-title">Compras Recomendadas (6M)</div>
                <div class="kpi-value">
                    <?= \App\Core\View::formatNumber($ejecucion['total_compras_unidades']) ?> <span style="font-size: 1rem; font-weight: 500; color: var(--text-muted);">u.</span>
                </div>
                <div class="kpi-footer">
                    <span>Subtotal compras: <?= \App\Core\View::formatBs($ejecucion['costo_compra_bs']) ?></span>
                    <?= \App\Core\View::provenanceBadge('DERIVED') ?>
                </div>
            </div>

            <!-- Demanda Atendida -->
            <div class="kpi-card">
                <div class="kpi-title">Demanda Solicitada / Atendida</div>
                <div class="kpi-value" style="font-size: 1.4rem;">
                    <?= \App\Core\View::formatNumber($ejecucion['total_demanda_atendida']) ?> / <?= \App\Core\View::formatNumber($ejecucion['total_demanda_solicitada']) ?>
                </div>
                <div class="kpi-footer">
                    <span>Faltante / Backorder: <?= \App\Core\View::formatNumber($ejecucion['total_demanda_perdida']) ?> u.</span>
                    <?= \App\Core\View::provenanceBadge('DERIVED') ?>
                </div>
            </div>
        </div>

        <!-- Desglose de Costos de la Etapa 2 -->
        <div style="background-color: #f8fafc; border: 1px solid var(--border-color); border-radius: 6px; padding: 1.25rem; margin-bottom: 1.5rem;">
            <h4 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--navy-800); margin-bottom: 0.75rem;">
                Desglose Analítico de Costos Logísticos (Etapa 2 PLEM):
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Costo de Compra (C_c)</div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: var(--navy-800);"><?= \App\Core\View::formatBs($ejecucion['costo_compra_bs']) ?></div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Costo de Emisión de Órdenes (C_o)</div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: var(--navy-800);"><?= \App\Core\View::formatBs($ejecucion['costo_orden_bs']) ?></div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Costo de Posesión / Stock (C_pos)</div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: var(--navy-800);"><?= \App\Core\View::formatBs($ejecucion['costo_posesion_bs']) ?></div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Costo por Faltante / Penalidad (C_falt)</div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: var(--navy-800);"><?= \App\Core\View::formatBs($ejecucion['costo_faltante_bs']) ?></div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Bajas por Vencimiento (C_baja)</div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: var(--navy-800);"><?= \App\Core\View::formatBs($ejecucion['costo_vencimiento_bs']) ?></div>
                </div>
            </div>
        </div>

        <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">
            <strong>Diagnóstico del Solver:</strong> <?= e($ejecucion['mensaje_diagnostico']) ?> &bull; 
            <strong>Hash SHA-256 de Entradas:</strong> <code><?= substr(e($ejecucion['hash_entradas']), 0, 16) ?>...</code> &bull; 
            <strong>Tiempo de cómputo:</strong> <?= e($ejecucion['tiempo_ejecucion_segundos']) ?> segundos.
        </div>
    </div>
</div>

<!-- 1. Compras Recomendadas -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3 style="font-size: 1.05rem; color: var(--navy-800);">1. Plan de Compras Recomendadas por Periodo y Proveedor</h3>
        <?= \App\Core\View::provenanceBadge('DERIVED') ?>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Periodo</th>
                        <th>Medicamento</th>
                        <th>Proveedor Seleccionado</th>
                        <th>Cantidad a Comprar</th>
                        <th>Costo Unitario</th>
                        <th>Subtotal Previsto</th>
                        <th>Lead Time</th>
                        <th>Llegada Estimada</th>
                        <th>Estado Decisión</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($compras)): ?>
                        <?php foreach ($compras as $pc): ?>
                            <tr>
                                <td><strong style="color: var(--blue-700);"><?= e($pc['periodo_nombre']) ?></strong></td>
                                <td>
                                    <strong><?= e($pc['prod_codigo']) ?></strong> - <?= e($pc['prod_desc']) ?>
                                </td>
                                <td><?= e($pc['prov_nombre']) ?></td>
                                <td><strong><?= \App\Core\View::formatNumber($pc['cantidad_propuesta']) ?></strong> u.</td>
                                <td><?= \App\Core\View::formatBs($pc['costo_unitario_bs']) ?></td>
                                <td><strong><?= \App\Core\View::formatBs($pc['subtotal_bs']) ?></strong></td>
                                <td><?= (int)$pc['lead_time_dias'] ?> días</td>
                                <td>Mes <?= (int)$pc['periodo_llegada_t'] ?></td>
                                <td>
                                    <span class="badge" style="background-color: var(--blue-50); color: var(--blue-700); border: 1px solid #bfdbfe;">
                                        <?= e($pc['estado_decision']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center text-muted" style="padding: 1.5rem;">No se requieren compras adicionales para este horizonte.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 2. Proyección de Inventario y Lotes -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3 style="font-size: 1.05rem; color: var(--navy-800);">2. Proyección de Inventario, Lotes y Bajas por Periodo</h3>
        <?= \App\Core\View::provenanceBadge('DERIVED') ?>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Periodo</th>
                        <th>Medicamento</th>
                        <th>Lote / Cohorte</th>
                        <th>Saldo Inicial</th>
                        <th>Entradas Compras</th>
                        <th>Asignación Demanda</th>
                        <th>Bajas Vencimiento</th>
                        <th>Saldo Final</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inventario)): ?>
                        <?php foreach ($inventario as $ip): ?>
                            <tr>
                                <td><strong style="color: var(--blue-700);"><?= e($ip['periodo_nombre']) ?></strong></td>
                                <td><?= e($ip['prod_codigo']) ?> - <?= e($ip['prod_desc']) ?></td>
                                <td>
                                    <?php if ($ip['codigo_lote']): ?>
                                        <span class="badge" style="background-color: #f1f5f9; color: var(--navy-800);"><?= e($ip['codigo_lote']) ?></span>
                                    <?php else: ?>
                                        <span class="badge" style="background-color: #ede9fe; color: #5b21b6;">Proy. Compras</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= \App\Core\View::formatNumber($ip['saldo_inicial']) ?></td>
                                <td><?= (int)$ip['recepciones_programadas'] > 0 ? '+' . \App\Core\View::formatNumber($ip['recepciones_programadas']) : '-' ?></td>
                                <td><?= (int)$ip['demanda_asignada'] > 0 ? '-' . \App\Core\View::formatNumber($ip['demanda_asignada']) : '-' ?></td>
                                <td>
                                    <?php if ((int)$ip['cantidad_vencida'] > 0): ?>
                                        <span style="color: #dc2626; font-weight: 700;">-<?= \App\Core\View::formatNumber($ip['cantidad_vencida']) ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= \App\Core\View::formatNumber($ip['saldo_final']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted" style="padding: 1.5rem;">No hay registros de inventario proyectado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 3. Demanda y Nivel de Cumplimiento -->
<div class="card">
    <div class="card-header">
        <h3 style="font-size: 1.05rem; color: var(--navy-800);">3. Nivel de Atención y Demanda por Periodo</h3>
        <?= \App\Core\View::provenanceBadge('DERIVED') ?>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Periodo</th>
                        <th>Medicamento</th>
                        <th>Demanda Solicitada</th>
                        <th>Demanda Atendida</th>
                        <th>Pendiente (Backorder)</th>
                        <th>Demanda Perdida</th>
                        <th>Porcentaje de Atención</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($demandas)): ?>
                        <?php foreach ($demandas as $dr): ?>
                            <tr>
                                <td><strong style="color: var(--blue-700);"><?= e($dr['periodo_nombre']) ?></strong></td>
                                <td><?= e($dr['prod_codigo']) ?> - <?= e($dr['prod_desc']) ?></td>
                                <td><strong><?= \App\Core\View::formatNumber($dr['cantidad_solicitada']) ?></strong></td>
                                <td><strong style="color: #065f46;"><?= \App\Core\View::formatNumber($dr['cantidad_atendida']) ?></strong></td>
                                <td><?= (int)$dr['cantidad_pendiente'] > 0 ? \App\Core\View::formatNumber($dr['cantidad_pendiente']) : '-' ?></td>
                                <td><?= (int)$dr['cantidad_perdida'] > 0 ? \App\Core\View::formatNumber($dr['cantidad_perdida']) : '-' ?></td>
                                <td>
                                    <span class="badge" style="background-color: <?= (float)$dr['porcentaje_atencion'] >= 100 ? 'var(--success-bg); color: var(--success-text);' : '#fef3c7; color: #92400e;' ?>">
                                        <?= number_format((float)$dr['porcentaje_atencion'], 2) ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted" style="padding: 1.5rem;">No hay registros de atención de demanda.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

