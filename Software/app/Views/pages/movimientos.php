<div class="card">
    <div class="card-header">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--navy-800);">Libro Diario de Movimientos de Stock</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Registro auditable de entradas por compra, salidas por pedido, bajas por vencimiento y devoluciones
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
                        <th>Fecha / Hora</th>
                        <th>Tipo Movimiento</th>
                        <th>Medicamento</th>
                        <th>Lote</th>
                        <th>Cantidad</th>
                        <th>Saldo Resultante</th>
                        <th>Motivo / Referencia</th>
                        <th>Procedencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($movimientos)): ?>
                        <?php foreach ($movimientos as $m): ?>
                            <tr>
                                <td><?= e($m['fecha_movimiento']) ?></td>
                                <td>
                                    <span class="badge" style="background-color: <?= strpos($m['tipo_movimiento'], 'ENTRADA') !== false ? '#ecfdf5; color: #065f46;' : (strpos($m['tipo_movimiento'], 'SALIDA') !== false ? '#eff6ff; color: #1e40af;' : '#fee2e2; color: #991b1b;') ?>">
                                        <?= e($m['tipo_movimiento']) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= e($m['prod_codigo']) ?></strong> - <?= e($m['prod_desc']) ?>
                                </td>
                                <td><?= e($m['codigo_lote'] ?? '-') ?></td>
                                <td><strong><?= \App\Core\View::formatNumber($m['cantidad']) ?></strong> u.</td>
                                <td><?= \App\Core\View::formatNumber($m['saldo_resultante']) ?> u.</td>
                                <td>
                                    <?= e($m['motivo']) ?>
                                    <?php if (!empty($m['documento_referencia'])): ?>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">Doc: <?= e($m['documento_referencia']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= \App\Core\View::provenanceBadge($m['naturaleza_dato']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted" style="padding: 1.5rem;">No se registran movimientos de inventario en el ledger.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

