<div class="card">
    <div class="card-header">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--navy-800);">Pedidos de Clientes y Cumplimiento</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Detalle de solicitudes, compromisos y asignaciones de stock
            </div>
        </div>
        <div>
            <?= \App\Core\View::provenanceBadge('SIMULATED') ?>
        </div>
    </div>
    
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Código Pedido</th>
                        <th>Cliente</th>
                        <th>Fecha Solicitud</th>
                        <th>Fecha Compromiso</th>
                        <th>Líneas / Solicitado</th>
                        <th>Atendido</th>
                        <th>Estado</th>
                        <th>Procedencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pedidos)): ?>
                        <?php foreach ($pedidos as $p): ?>
                            <tr>
                                <td><strong style="color: var(--navy-800);"><?= e($p['codigo_pedido']) ?></strong></td>
                                <td>
                                    <strong><?= e($p['codigo_anonimizado']) ?></strong><br>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?= e($p['cliente_dpto'] ?? 'La Paz') ?></span>
                                </td>
                                <td><?= \App\Core\View::formatDate($p['fecha_solicitud']) ?></td>
                                <td><?= \App\Core\View::formatDate($p['fecha_compromiso']) ?></td>
                                <td>
                                    <?= (int)$p['lineas'] ?> líneas (<?= \App\Core\View::formatNumber($p['total_solicitado']) ?> u.)
                                </td>
                                <td>
                                    <strong><?= \App\Core\View::formatNumber($p['total_atendido']) ?></strong> u.
                                </td>
                                <td>
                                    <span class="badge" style="background-color: <?= $p['estado'] === 'ATENDIDO' ? '#ecfdf5; color: #065f46;' : '#eff6ff; color: #1e40af;' ?>">
                                        <?= e($p['estado']) ?>
                                    </span>
                                </td>
                                <td><?= \App\Core\View::provenanceBadge($p['naturaleza_dato']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted" style="padding: 1.5rem;">No hay pedidos registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

