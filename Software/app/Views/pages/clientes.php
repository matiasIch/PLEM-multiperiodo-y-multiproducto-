<div class="card">
    <div class="card-header">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--navy-800);">Clientes Farmacéuticos (<?= \App\Core\View::formatNumber($totalClientes) ?> registros)</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Datos protegidos mediante códigos anonimizados conformes a las políticas de confidencialidad de FARMAFÉ
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
                        <th>Código Anonimizado</th>
                        <th>Tipo de Cliente</th>
                        <th>Segmento</th>
                        <th>Departamento</th>
                        <th>Ciudad</th>
                        <th>Procedencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($clientes)): ?>
                        <?php foreach ($clientes as $c): ?>
                            <tr>
                                <td><strong style="color: var(--navy-800);"><?= e($c['codigo_anonimizado']) ?></strong></td>
                                <td><?= e($c['tipo_cliente'] ?? 'FARMACIA_PRIVADA') ?></td>
                                <td><?= e($c['segmento_cliente'] ?? 'URBANO') ?></td>
                                <td><?= e($c['departamento'] ?? 'La Paz') ?></td>
                                <td><?= e($c['ciudad'] ?? 'La Paz') ?></td>
                                <td><?= \App\Core\View::provenanceBadge($c['naturaleza_dato']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted" style="padding: 1.5rem;">No hay clientes registrados en el catálogo.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

