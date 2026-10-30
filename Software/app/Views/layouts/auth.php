<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'FARMAFÉ') ?> - Distribución Farmacéutica</title>
    <link rel="stylesheet" href="<?= e(\App\Core\Router::url('/assets/css/app.css')) ?>">
</head>
<body style="background-color: var(--navy-900); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem 1rem;">
    <div style="width: 100%; max-width: <?= isset($containerWidth) ? e($containerWidth) : '480px' ?>;">
        <!-- Mensajes Flash -->
        <?php if (!empty($flashes)): ?>
            <?php foreach ($flashes as $type => $message): ?>
                <div class="alert alert-<?= $type === 'error' ? 'danger' : e($type) ?>" style="margin-bottom: 1.5rem;" role="alert">
                    <?= e($message) ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?= $content ?>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.8rem; color: rgba(255, 255, 255, 0.5);">
            FARMAFÉ S.R.L. &copy; <?= date('Y') ?> &bull; Sistema de Gestión y Planificación Logística
        </div>
    </div>
</body>
</html>

