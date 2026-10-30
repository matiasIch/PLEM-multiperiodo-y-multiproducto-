<div class="card">
    <div class="card-header">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--navy-800);">Historial de Versiones del Plan PLEM (Horizonte Móvil)</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Cada ejecución del modelo se conserva de forma inmutable con su hash de entradas y diagnóstico
            </div>
        </div>
        <a href="<?= e(\App\Core\Router::url('/app/planificacion/ejecutar')) ?>" class="btn btn-accent btn-sm">
            Nueva Corrida PLEM
        </a>
    </div>
    
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Versión</th>
                        <th>Fecha de Ejecución</th>
                        <th>Escenario</th>
                        <th>Nivel de Servicio (S*)</th>
                        <th>Costo Logístico</th>
                        <th>Compras (u.)</th>
                        <th>Estado Solver</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($versiones)): ?>
                        <?php foreach ($versiones as $v): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--blue-700); font-size: 0.95rem;"><?= e($v['version_plan']) ?></strong>
                                </td>
                                <td>
                                    <?= e($v['fecha_ejecucion']) ?><br>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?= e($v['tiempo_ejecucion_segundos']) ?>s &bull; <?= e($v['nombre_usuario'] ?? 'Admin') ?></span>
                                </td>
                                <td><?= e($v['escenario_nombre']) ?></td>
                                <td>
                                    <strong style="color: #065f46; font-size: 1rem;">
                                        <?= number_format((float)$v['nivel_servicio_optimo_s1'] * 100, 2) ?>%
                                    </strong>
                                </td>
                                <td>
                                    <strong><?= \App\Core\View::formatBs($v['costo_logistico_total_s2']) ?></strong>
                                </td>
                                <td><?= \App\Core\View::formatNumber($v['total_compras_unidades']) ?> u.</td>
                                <td>
                                    <span class="badge" style="background-color: var(--success-bg); color: var(--success-text);">
                                        <?= e($v['estado_solver_etapa2']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= e(\App\Core\Router::url('/app/planificacion/ejecuciones/' . $v['id_ejecucion'])) ?>" class="btn btn-secondary btn-sm">
                                        Ver Plan
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted" style="padding: 2rem;">No hay versiones de planes PLEM registradas aún.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

