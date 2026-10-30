<div class="card" style="text-align: center; padding: 3rem 2rem;">
    <h2 style="font-size: 3rem; color: var(--navy-800); margin-bottom: 0.5rem;">404</h2>
    <h3 style="margin-bottom: 1rem;">Página no encontrada</h3>
    <p class="text-muted" style="margin-bottom: 2rem;">
        <?= e($message ?? 'La dirección solicitada no existe o ha sido reubicada.') ?>
    </p>
    <div>
        <a href="<?= e(\App\Core\Router::url('/')) ?>" class="btn btn-primary">Ir al Inicio</a>
    </div>
</div>

