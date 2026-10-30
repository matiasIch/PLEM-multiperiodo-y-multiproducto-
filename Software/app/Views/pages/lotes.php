<div class="card">
    <div class="card-header">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--navy-800);">
                Gestión de Lotes y Prioridad FEFO (<?= \App\Core\View::formatNumber($totalLotes) ?> lotes, <?= \App\Core\View::formatNumber($totalStock) ?> unidades)
            </h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Prioridad de despacho: First Expired, First Out entre lotes elegibles no vencidos
            </div>
        </div>
        <div>
            <?= \App\Core\View::provenanceBadge('OBSERVED') ?>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Busqueda y Filtro -->
        <form method="GET" action="<?= e(\App\Core\Router::url('/app/lotes')) ?>" class="d-flex gap-1" style="margin-bottom: 1.5rem; flex-wrap: wrap;">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Buscar por medicamento o código de lote..." class="form-control" style="max-width: 320px;">
            <select name="estado" class="form-control" style="max-width: 180px;">
                <option value="">Estado del Lote (Todos)</option>
                <option value="DISPONIBLE" <?= $estado === 'DISPONIBLE' ? 'selected' : '' ?>>DISPONIBLE</option>
                <option value="BLOQUEADO" <?= $estado === 'BLOQUEADO' ? 'selected' : '' ?>>BLOQUEADO</option>
                <option value="VENCIDO" <?= $estado === 'VENCIDO' ? 'selected' : '' ?>>VENCIDO</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
            <?php if ($search !== '' || $estado !== ''): ?>
                <a href="<?= e(\App\Core\Router::url('/app/lotes')) ?>" class="btn btn-secondary btn-sm">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Prioridad FEFO</th>
                        <th>Código Lote</th>
                        <th>Medicamento</th>
                        <th>Fecha Vencimiento</th>
                        <th>Días Restantes</th>
                        <th>Saldo Disponible</th>
                        <th>Zona Almacén</th>
                        <th>Estado</th>
                        <th>Procedencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($lotes)): ?>
                        <?php foreach ($lotes as $idx => $l): ?>
                            <?php 
                                $dias = (int)$l['dias_para_vencer']; 
                                $isExpired = $dias <= 0;
                                $isNearExp = $dias > 0 && $dias <= 365;
                            ?>
                            <tr style="<?= $isExpired ? 'background-color: #fef2f2;' : ($isNearExp ? 'background-color: #fffbeb;' : '') ?>">
                                <td style="font-weight: 700; color: var(--navy-800); text-align: center;">
                                    #<?= $idx + 1 ?>
                                </td>
                                <td>
                                    <strong><?= e($l['codigo_lote']) ?></strong>
                                </td>
                                <td>
                                    <strong><?= e($l['prod_codigo']) ?></strong> - <?= e($l['prod_desc']) ?>
                                    <span class="badge" style="background-color: #f1f5f9;"><?= e($l['clasificacion_abc']) ?></span>
                                </td>
                                <td><?= \App\Core\View::formatDate($l['fecha_vencimiento']) ?></td>
                                <td>
                                    <?php if ($isExpired): ?>
                                        <span class="badge" style="background-color: #fee2e2; color: #991b1b; font-weight: 700;">
                                            VENCIDO (<?= abs($dias) ?> d atrás)
                                        </span>
                                    <?php elseif ($isNearExp): ?>
                                        <span class="badge" style="background-color: #fef3c7; color: #92400e; font-weight: 700;">
                                            CORTO VENCIMIENTO (<?= $dias ?> d)
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #065f46; font-size: 0.85rem;"><?= $dias ?> días</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= \App\Core\View::formatNumber($l['saldo_actual']) ?></strong> u.</td>
                                <td><?= e($l['zona_nombre'] ?? 'Principal') ?></td>
                                <td>
                                    <span class="badge" style="background-color: <?= $l['estado_lote'] === 'DISPONIBLE' ? 'var(--success-bg); color: var(--success-text);' : '#fee2e2; color: #991b1b;' ?>">
                                        <?= e($l['estado_lote']) ?>
                                    </span>
                                </td>
                                <td><?= \App\Core\View::provenanceBadge($l['naturaleza_dato']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center text-muted" style="padding: 1.5rem;">No hay lotes registrados con esos criterios.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

