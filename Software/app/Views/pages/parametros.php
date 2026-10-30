<div class="card">
    <div class="card-header">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--navy-800);">Parámetros Operativos y Costos de Escenario</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Configuración de supuestos económicos para la Etapa 2 de optimización y políticas de horizonte
            </div>
        </div>
        <div>
            <?= \App\Core\View::provenanceBadge('ASSUMPTION') ?>
        </div>
    </div>
    
    <div class="card-body">
        <form action="<?= e(\App\Core\Router::url('/app/parametros/guardar')) ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(\App\Core\Session::getCsrfToken()) ?>">

            <div class="table-responsive" style="margin-bottom: 1.5rem;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Parámetro</th>
                            <th>Categoría</th>
                            <th>Valor Configurado</th>
                            <th>Unidad</th>
                            <th>Descripción y Justificación</th>
                            <th>Procedencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parametros as $p): ?>
                            <tr>
                                <td>
                                    <strong><?= e($p['nombre_parametro']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge" style="background-color: #f1f5f9; color: var(--navy-800);">
                                        <?= e($p['categoria']) ?>
                                    </span>
                                </td>
                                <td style="width: 160px;">
                                    <?php if (\App\Core\Auth::hasRole('ADMIN')): ?>
                                        <input type="number" step="0.0001" name="valores[<?= e($p['nombre_parametro']) ?>]" value="<?= (float)$p['valor'] ?>" class="form-control" style="font-weight: 700;">
                                    <?php else: ?>
                                        <strong><?= \App\Core\View::formatNumber($p['valor'], 4) ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($p['unidad_medida']) ?></td>
                                <td><?= e($p['descripcion']) ?></td>
                                <td><?= \App\Core\View::provenanceBadge($p['naturaleza_dato']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (\App\Core\Auth::hasRole('ADMIN')): ?>
                <div class="d-flex justify-between align-center">
                    <span style="font-size: 0.8rem; color: var(--text-muted);">
                        Todo cambio en estos valores quedará registrado en el historial de auditoría con fecha y usuario responsable.
                    </span>
                    <button type="submit" class="btn btn-primary">
                        Guardar Parámetros de Escenario
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

