<div class="card">
    <div class="card-header">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--navy-800);">Órdenes de Compra y Compromisos</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Distingue órdenes propuestas por PLEM de órdenes emitidas o comprometidas operativamente
            </div>
        </div>
        <div>
            <?= \App\Core\View::provenanceBadge('OBSERVED') ?>
        </div>
    </div>
    
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Código Orden</th>
                        <th>Proveedor</th>
                        <th>Fecha Emisión</th>
                        <th>Fecha Prometida</th>
                        <th>Líneas / Monto</th>
                        <th>Estado Operativo</th>
                        <th>Comprometida (PLEM)</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ordenes)): ?>
                        <?php foreach ($ordenes as $oc): ?>
                            <tr>
                                <td><strong style="color: var(--navy-800);"><?= e($oc['codigo_orden']) ?></strong></td>
                                <td>
                                    <?= e($oc['prov_nombre']) ?><br>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?= e($oc['prov_codigo']) ?></span>
                                </td>
                                <td><?= \App\Core\View::formatDate($oc['fecha_emision']) ?></td>
                                <td><?= \App\Core\View::formatDate($oc['fecha_prometida']) ?></td>
                                <td>
                                    <?= (int)$oc['lineas_totales'] ?> ítems<br>
                                    <strong><?= \App\Core\View::formatBs($oc['monto_total_bs']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge" style="background-color: <?= $oc['estado'] === 'EMITIDA' ? '#dbeafe; color: #1e40af;' : ($oc['estado'] === 'RECIBIDA' ? '#ecfdf5; color: #065f46;' : '#fef3c7; color: #92400e;') ?>">
                                        <?= e($oc['estado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ((int)$oc['es_comprometida'] === 1): ?>
                                        <span class="badge" style="background-color: #fee2e2; color: #991b1b; font-weight: 700;">
                                            CONGELADA
                                        </span>
                                    <?php else: ?>
                                        <span class="badge" style="background-color: #f1f5f9; color: var(--text-muted);">
                                            ABIERTA
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form action="<?= e(\App\Core\Router::url('/app/compras/compromiso/' . $oc['id_orden'])) ?>" method="POST" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= e(\App\Core\Session::getCsrfToken()) ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm" title="Alternar si esta orden debe permanecer congelada e inmutable en re-optimizaciones">
                                            <?= (int)$oc['es_comprometida'] === 1 ? 'Descongelar' : 'Congelar' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted" style="padding: 1.5rem;">No hay órdenes de compra registradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

