<!-- Alerta de Calidad y Trazabilidad de Datos -->
<div class="alert alert-info d-flex justify-between align-center" style="margin-bottom: 1.5rem;">
    <div>
        <strong>Aviso de Integridad y Trazabilidad de Datos:</strong>
        Todos los indicadores de este panel proceden de consultas reales a <code>farmafe_app</code>.
        La tasa histórica de pedidos completos y a tiempo (OTIF) se reporta como <em>no calculable con datos históricos</em> debido a que los registros originales agregan ventas anuales sin líneas detalladas de pedidos con fechas solicitada/comprometida/entregada.
    </div>
    <div style="flex-shrink: 0; margin-left: 1rem;">
        <?= \App\Core\View::provenanceBadge('DERIVED') ?>
    </div>
</div>

<!-- KPI Grid Principal -->
<div class="kpi-grid">
    <!-- Medicamentos -->
    <div class="kpi-card">
        <div class="kpi-title">Catálogo de Medicamentos</div>
        <div class="kpi-value"><?= \App\Core\View::formatNumber($prodKpi['total'] ?? 0) ?></div>
        <div class="kpi-footer">
            <span>ABC: A(<?= (int)($prodKpi['total_a'] ?? 0) ?>) B(<?= (int)($prodKpi['total_b'] ?? 0) ?>) C(<?= (int)($prodKpi['total_c'] ?? 0) ?>)</span>
            <?= \App\Core\View::provenanceBadge('OBSERVED') ?>
        </div>
    </div>

    <!-- Oferta y Proveedores -->
    <div class="kpi-card">
        <div class="kpi-title">Proveedores y Pares Válidos</div>
        <div class="kpi-value"><?= (int)$totalProveedores ?> <span style="font-size: 1rem; font-weight: 500; color: var(--text-muted);">prov.</span></div>
        <div class="kpi-footer">
            <span><?= (int)$totalParesPP ?> pares producto-proveedor</span>
            <?= \App\Core\View::provenanceBadge('OBSERVED') ?>
        </div>
    </div>

    <!-- Stock Utilizable -->
    <div class="kpi-card">
        <div class="kpi-title">Inventario en Lotes Activos</div>
        <div class="kpi-value"><?= \App\Core\View::formatNumber($stockKpi['total_unidades'] ?? 0) ?></div>
        <div class="kpi-footer">
            <span><?= (int)($stockKpi['total_lotes'] ?? 0) ?> lotes disponibles</span>
            <?= \App\Core\View::provenanceBadge('OBSERVED') ?>
        </div>
    </div>

    <!-- Riesgo de Vencimiento -->
    <div class="kpi-card" style="<?= ((int)($stockKpi['lotes_corto_venc'] ?? 0) > 0) ? 'border-left: 4px solid var(--yellow-500);' : '' ?>">
        <div class="kpi-title">Lotes en Corto Vencimiento</div>
        <div class="kpi-value" style="color: #b45309;"><?= (int)($stockKpi['lotes_corto_venc'] ?? 0) ?></div>
        <div class="kpi-footer">
            <span>Vencimiento &le; 365 días</span>
            <?= \App\Core\View::provenanceBadge('DERIVED') ?>
        </div>
    </div>
</div>

<!-- Fila: Ultimo Plan PLEM y Resumen de Abastecimiento -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    <!-- Tarjeta Ultima Ejecucion PLEM -->
    <div class="card">
        <div class="card-header">
            <h3 style="font-size: 1rem; color: var(--navy-800);">Último Plan de Optimización PLEM</h3>
            <?php if ($lastPlem): ?>
                <span class="badge" style="background-color: var(--success-bg); color: var(--success-text); border: 1px solid #a7f3d0;">
                    <?= e($lastPlem['version_plan']) ?> &bull; <?= e($lastPlem['estado_solver_etapa2']) ?>
                </span>
            <?php else: ?>
                <span class="badge" style="background-color: #f1f5f9; color: var(--text-muted);">Sin ejecuciones</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if ($lastPlem): ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div style="background-color: #f8fafc; padding: 0.75rem; border-radius: 4px; border: 1px solid var(--border-color);">
                        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">Nivel de Servicio S* (Etapa 1)</div>
                        <div style="font-size: 1.4rem; font-weight: 800; color: var(--blue-700);">
                            <?= number_format((float)$lastPlem['nivel_servicio_optimo_s1'] * 100, 2) ?>%
                        </div>
                    </div>
                    <div style="background-color: #f8fafc; padding: 0.75rem; border-radius: 4px; border: 1px solid var(--border-color);">
                        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">Costo Logístico Total (Etapa 2)</div>
                        <div style="font-size: 1.4rem; font-weight: 800; color: var(--navy-800);">
                            <?= \App\Core\View::formatBs($lastPlem['costo_logistico_total_s2']) ?>
                        </div>
                    </div>
                </div>

                <p style="font-size: 0.825rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                    <strong>Escenario:</strong> <?= e($lastPlem['escenario_nombre']) ?> (Horizonte: <?= (int)$lastPlem['horizonte_meses'] ?> meses)
                </p>
                <p style="font-size: 0.825rem; color: var(--text-muted); margin-bottom: 1rem;">
                    <strong>Solver:</strong> <?= e($lastPlem['solver_usado']) ?> &bull; Tiempo: <?= e($lastPlem['tiempo_ejecucion_segundos']) ?>s &bull; Fecha: <?= e($lastPlem['fecha_ejecucion']) ?>
                </p>

                <div class="d-flex gap-1">
                    <a href="<?= e(\App\Core\Router::url('/app/planificacion/ejecuciones/' . $lastPlem['id_ejecucion'])) ?>" class="btn btn-primary btn-sm">
                        Ver Detalle del Plan y Proyección
                    </a>
                    <a href="<?= e(\App\Core\Router::url('/app/planificacion/ejecutar')) ?>" class="btn btn-secondary btn-sm">
                        Nueva Optimización
                    </a>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.25rem;">
                    Aún no se ha ejecutado una optimización PLEM sobre el horizonte móvil de 6 meses.
                </p>
                <a href="<?= e(\App\Core\Router::url('/app/planificacion/ejecutar')) ?>" class="btn btn-accent btn-sm">
                    Configurar y Ejecutar Primera Corrida PLEM
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tarjeta Centro de Importaciones Recientes -->
    <div class="card">
        <div class="card-header">
            <h3 style="font-size: 1rem; color: var(--navy-800);">Importaciones y Staging</h3>
            <a href="<?= e(\App\Core\Router::url('/app/importaciones')) ?>" class="btn btn-secondary btn-sm">Ir al Centro</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (!empty($recentImports)): ?>
                <table class="table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th>Archivo</th>
                            <th>Dataset</th>
                            <th>Registros</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentImports as $imp): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?= e($imp['nombre_archivo_original']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($imp['creado_en']) ?></div>
                                </td>
                                <td><?= e($imp['tipo_dataset']) ?></td>
                                <td>
                                    <?= (int)$imp['filas_validas'] ?> / <?= (int)$imp['filas_totales'] ?>
                                    <?php if ((int)$imp['filas_con_error'] > 0): ?>
                                        <span style="color: #dc2626; font-size: 0.75rem;">(<?= (int)$imp['filas_con_error'] ?> err)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($imp['estado'] === 'CONFIRMADO'): ?>
                                        <span class="badge" style="background-color: var(--success-bg); color: var(--success-text);">CONFIRMADO</span>
                                    <?php else: ?>
                                        <a href="<?= e(\App\Core\Router::url('/app/importaciones/preview/' . $imp['id_importacion'])) ?>" class="badge" style="background-color: var(--warning-bg); color: var(--warning-text); text-decoration: underline;">
                                            <?= e($imp['estado']) ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.875rem;">
                    No hay importaciones registradas. Puede cargar los archivos CSV o Excel de FARMAFÉ desde el Centro de Importaciones.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

