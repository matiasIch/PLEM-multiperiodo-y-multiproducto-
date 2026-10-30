<div class="card">
    <div class="card-header">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--navy-800);">Recepciones Físicas en Almacén</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Registro documental de notas de ingreso y verificación de mercadería utilizable
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
                        <th>Nota Ingreso</th>
                        <th>Orden de Compra</th>
                        <th>Proveedor</th>
                        <th>Fecha Recepción</th>
                        <th>Ítems / Unidades</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recepciones)): ?>
                        <?php foreach ($recepciones as $r): ?>
                            <tr>
                                <td><strong style="color: var(--navy-800);"><?= e($r['numero_nota_ingreso']) ?></strong></td>
                                <td><?= e($r['codigo_orden'] ?? 'S/O') ?></td>
                                <td><?= e($r['prov_nombre'] ?? 'No especificado') ?></td>
                                <td><?= \App\Core\View::formatDate($r['fecha_recepcion']) ?></td>
                                <td><?= (int)$r['items_recibidos'] ?> líneas (<?= \App\Core\View::formatNumber($r['total_unidades']) ?> u.)</td>
                                <td>
                                    <span class="badge" style="background-color: var(--success-bg); color: var(--success-text);">
                                        <?= e($r['estado']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted" style="padding: 1.5rem;">No hay notas de recepción registradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

