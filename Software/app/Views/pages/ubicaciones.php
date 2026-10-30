<div class="card">
    <div class="card-header">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--navy-800);">Zonas de Almacén y Capacidad Física</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Zonas de almacenamiento, temperatura y capacidades de escenario
            </div>
        </div>
        <div>
            <?= \App\Core\View::provenanceBadge('SIMULATED') ?>
        </div>
    </div>
    
    <div class="card-body" style="padding: 0;">
        <table class="table">
            <thead>
                <tr>
                    <th>Código Zona</th>
                    <th>Nombre de la Zona</th>
                    <th>Condición Térmica</th>
                    <th>Capacidad Máxima</th>
                    <th>Unidad</th>
                    <th>Procedencia</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($zonas)): ?>
                    <?php foreach ($zonas as $z): ?>
                        <tr>
                            <td><strong style="color: var(--navy-800);"><?= e($z['codigo']) ?></strong></td>
                            <td><?= e($z['nombre']) ?></td>
                            <td>
                                <span class="badge" style="background-color: #f1f5f9; color: var(--text-main);">
                                    <?= e($z['condicion_almacenamiento']) ?>
                                </span>
                            </td>
                            <td><strong><?= \App\Core\View::formatNumber($z['capacidad_empaques']) ?></strong></td>
                            <td><?= e($z['unidad_capacidad']) ?></td>
                            <td><?= \App\Core\View::provenanceBadge($z['naturaleza_dato']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-muted" style="padding: 1.5rem;">No hay zonas de almacén configuradas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

