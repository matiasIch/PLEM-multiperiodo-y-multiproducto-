<div class="card" style="text-align: center; padding: 3rem 2rem;">
    <h2 style="font-size: 3rem; color: #dc2626; margin-bottom: 0.5rem;">500</h2>
    <h3 style="margin-bottom: 1rem;">Error Interno del Sistema</h3>
    <p class="text-muted" style="margin-bottom: 2rem;">
        <?= e($message ?? 'Ocurrió un error inesperado al procesar su solicitud. Los detalles fueron registrados para auditoría.') ?>
    </p>
    <div>
        <a href="<?= e(\App\Core\Router::url('/app')) ?>" class="btn btn-primary">Volver al Panel</a>
    </div>
</div>

