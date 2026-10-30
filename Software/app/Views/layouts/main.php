<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'FARMAFÉ') ?> - Sistema de Planificación Logística</title>
    <link rel="stylesheet" href="<?= e(\App\Core\Router::url('/assets/css/app.css')) ?>">
</head>
<body>
<div class="layout-wrapper">
    <!-- Barra Lateral -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <span class="brand-badge">FARMAFÉ</span>
            <span class="brand-title">Planificación</span>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-title">Principal</div>
            <a href="<?= e(\App\Core\Router::url('/app')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app') !== false && !preg_match('#/app/(importaciones|medicamentos|proveedores|clientes|ubicaciones|compras|recepciones|lotes|inventario|pedidos|demanda|planificacion|evaluacion|parametros|administracion)#', $_SERVER['REQUEST_URI'] ?? '') ? 'active' : '' ?>">
                Panel de Control
            </a>

            <div class="nav-section-title">Integración de Datos</div>
            <a href="<?= e(\App\Core\Router::url('/app/importaciones')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app/importaciones') !== false ? 'active' : '' ?>">
                Centro de Importación
            </a>

            <div class="nav-section-title">Datos Maestros</div>
            <a href="<?= e(\App\Core\Router::url('/app/medicamentos')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app/medicamentos') !== false ? 'active' : '' ?>">
                Medicamentos
            </a>
            <a href="<?= e(\App\Core\Router::url('/app/proveedores')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app/proveedores') !== false ? 'active' : '' ?>">
                Proveedores y Oferta
            </a>
            <a href="<?= e(\App\Core\Router::url('/app/clientes')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app/clientes') !== false ? 'active' : '' ?>">
                Clientes (Anonimizados)
            </a>
            <a href="<?= e(\App\Core\Router::url('/app/ubicaciones')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app/ubicaciones') !== false ? 'active' : '' ?>">
                Zonas de Almacén
            </a>

            <div class="nav-section-title">Abastecimiento y Stock</div>
            <a href="<?= e(\App\Core\Router::url('/app/compras')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app/compras') !== false ? 'active' : '' ?>">
                Órdenes de Compra
            </a>
            <a href="<?= e(\App\Core\Router::url('/app/recepciones')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app/recepciones') !== false ? 'active' : '' ?>">
                Recepciones de Compra
            </a>
            <a href="<?= e(\App\Core\Router::url('/app/lotes')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app/lotes') !== false ? 'active' : '' ?>">
                Gestión de Lotes (FEFO)
            </a>
            <a href="<?= e(\App\Core\Router::url('/app/inventario/movimientos')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app/inventario/movimientos') !== false ? 'active' : '' ?>">
                Movimientos de Stock
            </a>

            <div class="nav-section-title">Demanda y Pedidos</div>
            <a href="<?= e(\App\Core\Router::url('/app/pedidos')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app/pedidos') !== false ? 'active' : '' ?>">
                Pedidos de Clientes
            </a>
            <a href="<?= e(\App\Core\Router::url('/app/demanda')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app/demanda') !== false ? 'active' : '' ?>">
                Demanda Periódica
            </a>

            <div class="nav-section-title">Optimización PLEM</div>
            <a href="<?= e(\App\Core\Router::url('/app/planificacion/ejecutar')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app/planificacion/ejecutar') !== false ? 'active' : '' ?>">
                Configurar y Ejecutar PLEM
            </a>
            <a href="<?= e(\App\Core\Router::url('/app/planificacion/versiones')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app/planificacion/versiones') !== false ? 'active' : '' ?>">
                Versiones del Plan (6M)
            </a>
            <a href="<?= e(\App\Core\Router::url('/app/evaluacion/comparaciones')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app/evaluacion') !== false ? 'active' : '' ?>">
                Comparación AS-IS / TO-BE
            </a>

            <div class="nav-section-title">Configuración y Control</div>
            <a href="<?= e(\App\Core\Router::url('/app/parametros')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app/parametros') !== false ? 'active' : '' ?>">
                Parámetros y Costos
            </a>
            <?php if (\App\Core\Auth::hasRole('ADMIN')): ?>
            <a href="<?= e(\App\Core\Router::url('/app/administracion/usuarios')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app/administracion/usuarios') !== false ? 'active' : '' ?>">
                Usuarios y Roles
            </a>
            <a href="<?= e(\App\Core\Router::url('/app/administracion/auditoria')) ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/app/administracion/auditoria') !== false ? 'active' : '' ?>">
                Auditoría del Sistema
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div>Entorno: Local XAMPP</div>
            <div>BD: farmafe_app</div>
        </div>
    </aside>

    <!-- Contenido Principal -->
    <main class="main-content">
        <header class="topbar">
            <div>
                <h1 style="font-size: 1.25rem; font-weight: 700; color: var(--navy-800);"><?= e($pageTitle ?? 'Panel de Control') ?></h1>
            </div>
            <div class="d-flex align-center gap-2">
                <span style="font-size: 0.85rem; color: var(--text-muted);">
                    Usuario: <strong style="color: var(--navy-800);"><?= e($currentUser['nombre_completo'] ?? $currentUser['nombre_usuario'] ?? 'Usuario') ?></strong>
                    <span class="badge" style="background-color: var(--blue-50); color: var(--blue-700); border: 1px solid #bfdbfe; margin-left: 0.25rem;">
                        <?= e($currentUser['rol_nombre'] ?? $currentUser['codigo_rol'] ?? 'ADMIN') ?>
                    </span>
                </span>
                <form action="<?= e(\App\Core\Router::url('/logout')) ?>" method="POST" style="margin: 0;">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <button type="submit" class="btn btn-secondary btn-sm">Cerrar Sesión</button>
                </form>
            </div>
        </header>

        <div class="page-body">
            <!-- Mensajes Flash -->
            <?php if (!empty($flashes)): ?>
                <?php foreach ($flashes as $type => $message): ?>
                    <div class="alert alert-<?= $type === 'error' ? 'danger' : e($type) ?>" role="alert">
                        <?= e($message) ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </main>
</div>
</body>
</html>

