<div class="card">
    <div class="card-header">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--navy-800);">Registro de Auditoría de Operaciones Críticas</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Trazabilidad completa de accesos, importaciones, cambios de parámetros y ejecuciones PLEM
            </div>
        </div>
        <div>
            <?= \App\Core\View::provenanceBadge('OBSERVED') ?>
        </div>
    </div>
    
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table" style="font-size: 0.8rem;">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Acción</th>
                        <th>Entidad Afectada</th>
                        <th>ID Entidad</th>
                        <th>Usuario</th>
                        <th>Motivo / Explicación</th>
                        <th>IP Origen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($eventos)): ?>
                        <?php foreach ($eventos as $ev): ?>
                            <tr>
                                <td><?= e($ev['fecha_hora']) ?></td>
                                <td>
                                    <span class="badge" style="background-color: #f1f5f9; color: var(--navy-800); font-weight: 700;">
                                        <?= e($ev['accion']) ?>
                                    </span>
                                </td>
                                <td><?= e($ev['entidad']) ?></td>
                                <td><?= e($ev['id_entidad'] ?? '-') ?></td>
                                <td>
                                    <strong><?= e($ev['nombre_usuario'] ?? 'Sistema') ?></strong>
                                </td>
                                <td><?= e($ev['motivo']) ?></td>
                                <td><code><?= e($ev['ip_origen']) ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted" style="padding: 1.5rem;">No hay registros de auditoría almacenados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

