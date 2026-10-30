<div class="card" style="border-top: 4px solid var(--yellow-500); padding: 2rem;">
    <div style="text-align: center; margin-bottom: 1.75rem;">
        <span class="brand-badge" style="font-size: 1.3rem; padding: 0.35rem 0.8rem;">FARMAFÉ</span>
        <h2 style="font-size: 1.25rem; margin-top: 0.75rem; color: var(--navy-800);">Inicio de Sesión</h2>
        <p style="font-size: 0.825rem; color: var(--text-muted); margin-top: 0.25rem;">
            Ingrese sus credenciales autorizadas
        </p>
    </div>

    <form action="<?= e(\App\Core\Router::url('/login')) ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(\App\Core\Session::getCsrfToken()) ?>">

        <div class="form-group">
            <label for="username" class="form-label">Usuario</label>
            <input type="text" id="username" name="username" class="form-control" required autofocus placeholder="Nombre de usuario">
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" id="password" name="password" class="form-control" required placeholder="Contraseña de acceso">
        </div>

        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary w-full" style="padding: 0.7rem;">
                Ingresar al Sistema
            </button>
        </div>
    </form>
</div>

