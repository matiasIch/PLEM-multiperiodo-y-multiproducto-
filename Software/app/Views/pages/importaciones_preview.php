<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <div>
            <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">
                Validación de Staging &bull; Importación #<?= (int)$importacion['id_importacion'] ?>
            </div>
            <h2 style="font-size: 1.25rem; color: var(--navy-800); margin-top: 0.25rem;">
                <?= e($importacion['nombre_archivo_original']) ?>
            </h2>
        </div>
        <div class="d-flex align-center gap-1">
            <?php if ($importacion['estado'] !== 'CONFIRMADO' && (int)$importacion['filas_validas'] > 0): ?>
                <form action="<?= e(\App\Core\Router::url('/app/importaciones/confirm/' . $importacion['id_importacion'])) ?>" method="POST" style="margin: 0;">
                    <input type="hidden" name="csrf_token" value="<?= e(\App\Core\Session::getCsrfToken()) ?>">
                    <button type="submit" class="btn btn-accent" style="font-weight: 700;">
                        Confirmar e Incorporar a Tablas Maestras
                    </button>
                </form>
            <?php elseif ($importacion['estado'] === 'CONFIRMADO'): ?>
                <span class="badge" style="background-color: var(--success-bg); color: var(--success-text); padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                    IMPORTACIÓN CONFIRMADA
                </span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <div>
                <span style="font-size: 0.75rem; color: var(--text-muted);">Entidad Destino:</span>
                <div style="font-size: 1rem; font-weight: 700; color: var(--navy-800);"><?= e($importacion['tipo_dataset']) ?></div>
            </div>
            <div>
                <span style="font-size: 0.75rem; color: var(--text-muted);">Etiqueta de Naturaleza:</span>
                <div><?= \App\Core\View::provenanceBadge($importacion['naturaleza_general']) ?></div>
            </div>
            <div>
                <span style="font-size: 0.75rem; color: var(--text-muted);">Filas Procesadas:</span>
                <div style="font-size: 1rem; font-weight: 700; color: var(--navy-800);">
                    <?= (int)$importacion['filas_validas'] ?> válidas / <?= (int)$importacion['filas_totales'] ?> total
                </div>
            </div>
            <div>
                <span style="font-size: 0.75rem; color: var(--text-muted);">Errores de Validación:</span>
                <div style="font-size: 1rem; font-weight: 700; color: <?= (int)$importacion['filas_con_error'] > 0 ? '#dc2626' : '#065f46' ?>;">
                    <?= (int)$importacion['filas_con_error'] ?> filas
                </div>
            </div>
        </div>

        <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
            Hash SHA-256: <?= e($importacion['hash_sha256']) ?> &bull; Tamaño: <?= round($importacion['tamano_bytes'] / 1024, 1) ?> KB &bull; Hoja: <?= e($importacion['hoja'] ?? 'Hoja1') ?>
        </div>
    </div>
</div>

<!-- Tabla de Filas en Staging -->
<div class="card">
    <div class="card-header">
        <h3 style="font-size: 1.05rem; color: var(--navy-800);">Registros en Staging (Primeras 100 Filas)</h3>
        <a href="<?= e(\App\Core\Router::url('/app/importaciones')) ?>" class="btn btn-secondary btn-sm">Volver a la lista</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table" style="font-size: 0.8rem;">
                <thead>
                    <tr>
                        <th># Fila</th>
                        <th>Identificador</th>
                        <th>Estado Fila</th>
                        <th>Mensaje / Validación</th>
                        <th>Datos Crudos Sanitizados</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($filas)): ?>
                        <?php foreach ($filas as $f): ?>
                            <tr style="<?= $f['estado'] === 'ERROR' ? 'background-color: #fef2f2;' : '' ?>">
                                <td><?= (int)$f['numero_fila'] ?></td>
                                <td><strong><?= e($f['identificador_negocio'] ?? '-') ?></strong></td>
                                <td>
                                    <span class="badge" style="background-color: <?= $f['estado'] === 'VALIDO' ? 'var(--success-bg); color: var(--success-text);' : ($f['estado'] === 'CONFIRMADO' ? '#dbeafe; color: #1e40af;' : '#fee2e2; color: #991b1b;') ?>">
                                        <?= e($f['estado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($f['mensaje_error']): ?>
                                        <span style="color: #dc2626; font-weight: 600;"><?= e($f['mensaje_error']) ?></span>
                                    <?php else: ?>
                                        <span style="color: #065f46;">Validación correcta</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code style="font-size: 0.725rem; word-break: break-all; color: #334155;">
                                        <?= e($f['datos_crudos_json']) ?>
                                    </code>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted" style="padding: 1.5rem;">No hay filas cargadas en staging.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

