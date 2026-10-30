<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--navy-800);">Evaluación Contrafactual y Comparación de Planes</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Metodología de contraste académico entre línea base AS-IS y escenario optimizado TO-BE
            </div>
        </div>
        <div>
            <?= \App\Core\View::provenanceBadge('DERIVED') ?>
        </div>
    </div>
    
    <div class="card-body">
        <div class="alert alert-warning" style="margin-bottom: 1.5rem;">
            <strong>Principio de Veracidad y Calidad de Datos:</strong>
            El sistema distingue rigurosamente tres tipos de conclusiones para evitar atribuir mejoras falsas:
            <ol style="margin-left: 1.5rem; margin-top: 0.5rem; line-height: 1.6;">
                <li><strong>Verificación Matemática:</strong> Comprueba que el plan PLEM respeta balances de masa, no genera inventario negativo ni utiliza compras antes de su fecha de llegada.</li>
                <li><strong>Evaluación Contrafactual (TO-BE vs AS-IS):</strong> Demuestra qué decisiones habría propuesto el modelo bajo idéntica demanda inicial, presupuesto y costos. Es una comparación calculada, no un ahorro histórico probado.</li>
                <li><strong>Efecto Observado en la Empresa:</strong> Solo puede declararse cuando exista evidencia de que FARMAFÉ ejecutó físicamente las recomendaciones y se midieron resultados reales con posterioridad.</li>
            </ol>
        </div>

        <div class="alert alert-info" style="margin-bottom: 1.5rem;">
            <strong>Limitación Histórica Documentada:</strong>
            En las fuentes originales (2023-2025), las facturas carecen de detalle de líneas de pedido y no se registraron fechas solicitadas frente a fechas prometidas/entregadas. Por ende, la tasa histórica de pedidos completos y puntuales (OTIF) no es calculable con la información observada y no se fabrica artificialmente.
        </div>

        <!-- Tabla de Versiones para Comparacion -->
        <h3 style="font-size: 1rem; color: var(--navy-800); margin-bottom: 0.75rem;">Versiones Disponibles para Comparación</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Versión</th>
                        <th>Fecha Ejecución</th>
                        <th>Escenario</th>
                        <th>Nivel Servicio (S*)</th>
                        <th>Costo Logístico Total</th>
                        <th>Compras (u.)</th>
                        <th>Estado Solver</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($planes)): ?>
                        <?php foreach ($planes as $p): ?>
                            <tr>
                                <td><strong style="color: var(--blue-700);"><?= e($p['version_plan']) ?></strong></td>
                                <td><?= e($p['fecha_ejecucion']) ?></td>
                                <td><?= e($p['escenario_nombre']) ?></td>
                                <td><strong style="color: #065f46;"><?= number_format((float)$p['nivel_servicio_optimo_s1'] * 100, 2) ?>%</strong></td>
                                <td><?= \App\Core\View::formatBs($p['costo_logistico_total_s2']) ?></td>
                                <td><?= \App\Core\View::formatNumber($p['total_compras_unidades']) ?></td>
                                <td>
                                    <span class="badge" style="background-color: var(--success-bg); color: var(--success-text);">
                                        <?= e($p['estado_solver_etapa2']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= e(\App\Core\Router::url('/app/planificacion/ejecuciones/' . $p['id_ejecucion'])) ?>" class="btn btn-secondary btn-sm">
                                        Examinar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted" style="padding: 1.5rem;">No hay ejecuciones PLEM registradas para comparar.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

