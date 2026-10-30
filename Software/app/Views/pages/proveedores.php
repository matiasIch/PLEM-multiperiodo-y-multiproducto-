<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--navy-800);">Proveedores Farmacéuticos del Prototipo</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Droguerías y laboratorios autorizados para abastecimiento
            </div>
        </div>
        <div>
            <?= \App\Core\View::provenanceBadge('OBSERVED') ?>
        </div>
    </div>
    
    <div class="card-body" style="padding: 0;">
        <table class="table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Razón Social</th>
                    <th>Tipo</th>
                    <th>Sede / Dpto</th>
                    <th>Plazo Base (Días)</th>
                    <th>Procedencia</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($proveedores)): ?>
                    <?php foreach ($proveedores as $prv): ?>
                        <tr>
                            <td><strong style="color: var(--navy-800);"><?= e($prv['codigo']) ?></strong></td>
                            <td><?= e($prv['razon_social']) ?></td>
                            <td><?= e($prv['tipo']) ?></td>
                            <td><?= e($prv['departamento']) ?></td>
                            <td><?= (int)$prv['plazo_base_dias'] ?> días (&plusmn; <?= (int)$prv['variacion_plazo_dias'] ?>)</td>
                            <td><?= \App\Core\View::provenanceBadge($prv['naturaleza_dato']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-muted" style="padding: 1.5rem;">No hay proveedores registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--navy-800);">
                Pares Válidos Producto - Proveedor (<?= \App\Core\View::formatNumber($totalPares) ?> registrados)
            </h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Solo los pares producto-proveedor admitidos por contrato comercial son elegibles en la PLEM (no se genera matriz completa ficticia)
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
                        <th>Medicamento</th>
                        <th>Proveedor</th>
                        <th>Costo Unitario</th>
                        <th>Plazo Entrega</th>
                        <th>Compra Mínima</th>
                        <th>Múltiplo Empaque</th>
                        <th>Prioridad</th>
                        <th>Procedencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pares)): ?>
                        <?php foreach ($pares as $pp): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--navy-800);"><?= e($pp['prod_codigo']) ?></strong><br>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?= e($pp['prod_desc']) ?></span>
                                </td>
                                <td><?= e($pp['prov_nombre']) ?></td>
                                <td><strong><?= \App\Core\View::formatBs($pp['costo_unitario_bs']) ?></strong></td>
                                <td><?= (int)$pp['plazo_dias'] ?> días</td>
                                <td><?= (int)$pp['compra_minima'] ?> u.</td>
                                <td><?= (int)$pp['multiplo_empaque'] ?> u.</td>
                                <td>Prioridad <?= (int)$pp['prioridad'] ?></td>
                                <td><?= \App\Core\View::provenanceBadge($pp['naturaleza_dato']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted" style="padding: 1.5rem;">No hay pares producto-proveedor cargados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

