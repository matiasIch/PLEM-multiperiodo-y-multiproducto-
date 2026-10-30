<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--navy-800);">Centro de Carga y Staging de Datos</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Carga segura de archivos CSV y XLSX, previsualización en staging y validación antes de confirmar
            </div>
        </div>
        <div>
            <?= \App\Core\View::provenanceBadge('OBSERVED') ?>
        </div>
    </div>
    
    <div class="card-body">
        <form action="<?= e(\App\Core\Router::url('/app/importaciones/upload')) ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(\App\Core\Session::getCsrfToken()) ?>">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label for="file" class="form-label">Archivo Fuente (CSV o XLSX) *</label>
                    <input type="file" name="file" id="file" class="form-control" accept=".csv, .xlsx, .txt" required>
                    <div class="form-text">Admite archivos de compras, inventarios, lotes o catálogo consolidado.</div>
                </div>

                <div class="form-group">
                    <label for="dataset_type" class="form-label">Tipo de Entidad Destino *</label>
                    <select name="dataset_type" id="dataset_type" class="form-control" required>
                        <option value="PRODUCTOS">Catálogo de Medicamentos (Productos)</option>
                        <option value="PROVEEDORES">Proveedores Farmacéuticos</option>
                        <option value="LOTES">Lotes y Vencimientos de Inventario</option>
                        <option value="DEMANDA">Demanda Periódica de Medicamentos</option>
                    </select>
                    <div class="form-text">Define las reglas de validación y tabla canónica destino.</div>
                </div>

                <div class="form-group">
                    <label for="nature" class="form-label">Etiqueta de Naturaleza del Dato *</label>
                    <select name="nature" id="nature" class="form-control" required>
                        <option value="OBSERVED">OBSERVADO (Dato real extraído de fuente de FARMAFÉ)</option>
                        <option value="SIMULATED">SIMULADO (Dato sintético generado para pruebas)</option>
                        <option value="ASSUMPTION">SUPUESTO (Parámetro configurable)</option>
                        <option value="PENDING_VALIDATION">PENDIENTE VALIDACIÓN (Requiere confirmación oficial)</option>
                    </select>
                    <div class="form-text">Etiqueta permanente de trazabilidad y calidad.</div>
                </div>

                <div class="form-group">
                    <label for="sheet_name" class="form-label">Nombre de Hoja Excel (Opcional)</label>
                    <input type="text" name="sheet_name" id="sheet_name" class="form-control" placeholder="Ej: Productos o Recuperado_Hoja1">
                    <div class="form-text">Si se omite, se procesará la primera hoja del libro Excel.</div>
                </div>
            </div>

            <div class="d-flex justify-between align-center">
                <span style="font-size: 0.8rem; color: var(--text-muted);">
                    Los datos se cargarán temporalmente en <strong>staging</strong> y no modificarán tablas maestras hasta su confirmación explícita.
                </span>
                <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.75rem;">
                    Cargar y Previsualizar en Staging
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Importaciones Previas -->
<div class="card">
    <div class="card-header">
        <h3 style="font-size: 1.05rem; color: var(--navy-800);">Historial de Archivos Cargados e Importaciones</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Archivo Original</th>
                        <th>Formato</th>
                        <th>Dataset</th>
                        <th>Hoja</th>
                        <th>Filas (Válidas / Total)</th>
                        <th>Naturaleza</th>
                        <th>Estado</th>
                        <th>Fecha Carga</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($importaciones)): ?>
                        <?php foreach ($importaciones as $imp): ?>
                            <tr>
                                <td>#<?= (int)$imp['id_importacion'] ?></td>
                                <td>
                                    <strong><?= e($imp['nombre_archivo_original']) ?></strong><br>
                                    <span style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">SHA: <?= substr(e($imp['hash_sha256']), 0, 12) ?>...</span>
                                </td>
                                <td><?= e($imp['formato']) ?></td>
                                <td><strong><?= e($imp['tipo_dataset']) ?></strong></td>
                                <td><?= e($imp['hoja'] ?? 'Principal') ?></td>
                                <td>
                                    <?= (int)$imp['filas_validas'] ?> / <?= (int)$imp['filas_totales'] ?>
                                    <?php if ((int)$imp['filas_con_error'] > 0): ?>
                                        <span style="color: #dc2626; font-size: 0.75rem;">(<?= (int)$imp['filas_con_error'] ?> err)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= \App\Core\View::provenanceBadge($imp['naturaleza_general']) ?></td>
                                <td>
                                    <span class="badge" style="background-color: <?= $imp['estado'] === 'CONFIRMADO' ? 'var(--success-bg); color: var(--success-text);' : 'var(--warning-bg); color: var(--warning-text);' ?>">
                                        <?= e($imp['estado']) ?>
                                    </span>
                                </td>
                                <td><?= e($imp['creado_en']) ?></td>
                                <td>
                                    <a href="<?= e(\App\Core\Router::url('/app/importaciones/preview/' . $imp['id_importacion'])) ?>" class="btn btn-secondary btn-sm">
                                        <?= $imp['estado'] === 'CONFIRMADO' ? 'Ver Staging' : 'Revisar y Confirmar' ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="text-center text-muted" style="padding: 1.5rem;">No hay importaciones registradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

