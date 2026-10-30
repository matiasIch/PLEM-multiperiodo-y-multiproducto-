<div class="card">
    <div class="card-header">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--navy-800);">Maestro de Medicamentos (<?= \App\Core\View::formatNumber($totalProductos) ?> total)</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Catálogo canónico de planificación logística para FARMAFÉ
            </div>
        </div>
        <div>
            <?= \App\Core\View::provenanceBadge('OBSERVED') ?>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Filtros y Busqueda -->
        <form method="GET" action="<?= e(\App\Core\Router::url('/app/medicamentos')) ?>" class="d-flex gap-1" style="margin-bottom: 1.5rem; flex-wrap: wrap;">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Buscar por código o descripción..." class="form-control" style="max-width: 300px;">
            <select name="abc" class="form-control" style="max-width: 150px;">
                <option value="">Clasificación ABC (Todas)</option>
                <option value="A" <?= $abc === 'A' ? 'selected' : '' ?>>Clase A (Alta rotación)</option>
                <option value="B" <?= $abc === 'B' ? 'selected' : '' ?>>Clase B (Media rotación)</option>
                <option value="C" <?= $abc === 'C' ? 'selected' : '' ?>>Clase C (Baja rotación)</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
            <?php if ($search !== '' || $abc !== ''): ?>
                <a href="<?= e(\App\Core\Router::url('/app/medicamentos')) ?>" class="btn btn-secondary btn-sm">Limpiar</a>
            <?php endif; ?>
        </form>

        <!-- Tabla -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Presentación / Unidad</th>
                        <th>Costo Promedio</th>
                        <th>ABC</th>
                        <th>Cadena de Frío</th>
                        <th>Registro Sanitario</th>
                        <th>Procedencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($productos)): ?>
                        <?php foreach ($productos as $p): ?>
                            <tr>
                                <td><strong style="color: var(--navy-800);"><?= e($p['codigo']) ?></strong></td>
                                <td><?= e($p['descripcion']) ?></td>
                                <td>
                                    <?= e($p['presentacion']) ?> 
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">(<?= e($p['unidad_medida']) ?>)</span>
                                </td>
                                <td><?= \App\Core\View::formatBs($p['costo_unitario_promedio_bs']) ?></td>
                                <td>
                                    <span class="badge" style="background-color: <?= $p['clasificacion_abc'] === 'A' ? '#fee2e2; color: #991b1b;' : ($p['clasificacion_abc'] === 'B' ? '#fef3c7; color: #92400e;' : '#e0e7ff; color: #3730a3;') ?>">
                                        Clase <?= e($p['clasificacion_abc']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($p['cadena_frio'] === 'SI'): ?>
                                        <span class="badge" style="background-color: #dbeafe; color: #1e40af;">Refrigerado</span>
                                    <?php elseif ($p['cadena_frio'] === 'NO'): ?>
                                        <span class="badge" style="background-color: #f1f5f9; color: var(--text-muted);">Ambiente</span>
                                    <?php else: ?>
                                        <span class="badge" style="background-color: #fffbeb; color: #b45309;">No Identificado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($p['estado_registro_sanitario'] === 'VIGENTE'): ?>
                                        <span style="font-size: 0.8rem; font-weight: 600; color: #065f46;"><?= e($p['registro_sanitario']) ?></span>
                                    <?php else: ?>
                                        <span style="font-size: 0.75rem; color: #b45309;" title="Pendiente de validación oficial">
                                            <?= e($p['registro_sanitario'] ?? 'PEND-VALIDACION') ?> (Pendiente)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= \App\Core\View::provenanceBadge($p['naturaleza_dato']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted" style="padding: 2rem;">
                                No se encontraron medicamentos registrados con los criterios especificados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

