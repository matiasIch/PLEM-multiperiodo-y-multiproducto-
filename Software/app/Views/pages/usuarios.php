<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Formulario Crear Usuario -->
    <div class="card">
        <div class="card-header">
            <h3 style="font-size: 1.05rem; color: var(--navy-800);">Registrar Nuevo Usuario</h3>
        </div>
        <div class="card-body">
            <form action="<?= e(\App\Core\Router::url('/app/administracion/usuarios/crear')) ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(\App\Core\Session::getCsrfToken()) ?>">

                <div class="form-group">
                    <label for="username" class="form-label">Nombre de Usuario *</label>
                    <input type="text" id="username" name="username" class="form-control" required placeholder="Ej: jlopez">
                </div>

                <div class="form-group">
                    <label for="full_name" class="form-label">Nombre Completo *</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" required placeholder="Ej: Juan López">
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Correo Electrónico *</label>
                    <input type="email" id="email" name="email" class="form-control" required placeholder="jlopez@farmafe.bo">
                </div>

                <div class="form-group">
                    <label for="role_id" class="form-label">Rol del Sistema *</label>
                    <select id="role_id" name="role_id" class="form-control" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int)$r['id_rol'] ?>"><?= e($r['codigo_rol']) ?> - <?= e($r['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Contraseña de Acceso *</label>
                    <input type="password" id="password" name="password" class="form-control" required minlength="8" placeholder="Mínimo 8 caracteres">
                </div>

                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary w-full">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Usuarios -->
    <div class="card">
        <div class="card-header">
            <h3 style="font-size: 1.05rem; color: var(--navy-800);">Usuarios Registrados</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Último Acceso</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td>
                                <strong><?= e($u['nombre_usuario']) ?></strong><br>
                                <span style="font-size: 0.75rem; color: var(--text-muted);"><?= e($u['nombre_completo']) ?> (<?= e($u['email']) ?>)</span>
                            </td>
                            <td>
                                <span class="badge" style="background-color: var(--blue-50); color: var(--blue-700); border: 1px solid #bfdbfe;">
                                    <?= e($u['codigo_rol']) ?>
                                </span>
                            </td>
                            <td><?= e($u['ultimo_acceso'] ?? 'Nunca') ?></td>
                            <td>
                                <span class="badge" style="background-color: <?= (int)$u['activo'] === 1 ? 'var(--success-bg); color: var(--success-text);' : '#fee2e2; color: #991b1b;' ?>">
                                    <?= (int)$u['activo'] === 1 ? 'ACTIVO' : 'INACTIVO' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

