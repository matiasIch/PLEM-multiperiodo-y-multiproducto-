<div class="card">
    <div class="card-header">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--navy-800);">Demanda Periódica por Medicamento (<?= \App\Core\View::formatNumber($totalDemanda) ?> unidades en horizonte)</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Demanda multiperiodo requerida para el modelo de optimización PLEM de seis meses
            </div>
        </div>
        <div>
            <?= \App\Core\View::provenanceBadge('DERIVED') ?>
        </div>
    </div>
    
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Periodo Secuencial</th>
                        <th>Mes / Gestión</th>
                        <th>Código Medicamento</th>
                        <th>Descripción</th>
                        <th>Cantidad Demandada</th>
                        <th>Tipo de Demanda</th>
                        <th>Procedencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($demandas)): ?>
                        <?php foreach ($demandas as $d): ?>
                            <tr>
                                <td><strong style="color: var(--blue-700);">Mes <?= (int)$d['periodo_secuencial'] ?></strong></td>
                                <td>Mes <?= (int)$d['mes'] ?> / <?= (int)$d['gestion'] ?></td>
                                <td><strong><?= e($d['prod_codigo']) ?></strong></td>
                                <td><?= e($d['prod_desc']) ?></td>
                                <td><strong><?= \App\Core\View::formatNumber($d['cantidad']) ?></strong> <?= e($d['unidad_medida']) ?></td>
                                <td>
                                    <span class="badge" style="background-color: #f1f5f9; color: var(--text-main);">
                                        <?= e($d['tipo_demanda']) ?>
                                    </span>
                                </td>
                                <td><?= \App\Core\View::provenanceBadge($d['naturaleza_dato']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted" style="padding: 1.5rem;">No hay registros de demanda periódica cargados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

