<div class="card" style="border-top: 4px solid var(--yellow-500); padding: 2rem;">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <span class="brand-badge" style="font-size: 1.3rem; padding: 0.35rem 0.8rem;">FARMAFÉ</span>
        <h2 style="font-size: 1.25rem; margin-top: 0.75rem; color: var(--navy-800);">Configuración Inicial</h2>
        <p style="font-size: 0.825rem; color: var(--text-muted); margin-top: 0.25rem;">
            Creación del Primer Administrador (Sin credenciales predeterminadas)
        </p>
    </div>

    <div class="alert alert-info" style="font-size: 0.8rem; margin-bottom: 1.25rem;">
        Por seguridad, defina una cuenta administrativa única. Este asistente se deshabilitará automáticamente una vez creado el primer usuario.
    </div>

    <form action="<?= e(\App\Core\Router::url('/setup')) ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(\App\Core\Session::getCsrfToken()) ?>">

        <div class="form-group">
            <label for="username" class="form-label">Nombre de Usuario *</label>
            <input type="text" id="username" name="username" class="form-control" required minlength="4" placeholder="Ej: admin">
            <div class="form-text">Mínimo 4 caracteres alfanuméricos.</div>
        </div>

        <div class="form-group">
            <label for="full_name" class="form-label">Nombre Completo *</label>
            <input type="text" id="full_name" name="full_name" class="form-control" required placeholder="Ej: Administrador Principal">
        </div>

        <div class="form-group">
            <label for="email" class="form-label">Correo Electrónico *</label>
            <input type="email" id="email" name="email" class="form-control" required placeholder="admin@farmafe.bo">
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Contraseña *</label>
            <input type="password" id="password" name="password" class="form-control" required minlength="8" placeholder="Contraseña segura (mínimo 8 caracteres)">
        </div>

        <div class="form-group">
            <label for="password_confirm" class="form-label">Confirmar Contraseña *</label>
            <input type="password" id="password_confirm" name="password_confirm" class="form-control" required minlength="8" placeholder="Repita la contraseña">
        </div>

        <div style="margin-top: 1.75rem;">
            <button type="submit" class="btn btn-accent w-full" style="padding: 0.7rem; font-weight: 700;">
                Crear Administrador e Iniciar
            </button>
        </div>
    </form>
</div>

