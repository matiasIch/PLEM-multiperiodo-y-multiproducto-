<div class="card" style="text-align: center; padding: 3rem 2rem;">
    <h2 style="font-size: 3rem; color: #d97706; margin-bottom: 0.5rem;">419</h2>
    <h3 style="margin-bottom: 1rem;">Sesión o Formulario Expirado</h3>
    <p class="text-muted" style="margin-bottom: 2rem;">
        <?= e($message ?? 'La sesión de seguridad o token CSRF expiró. Por favor recargue la página e intente de nuevo.') ?>
    </p>
    <div>
        <a href="<?= e(\App\Core\Router::url('/login')) ?>" class="btn btn-primary">Iniciar Sesión</a>
    </div>
</div>

