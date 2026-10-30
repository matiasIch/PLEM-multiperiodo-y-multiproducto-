<?php

// Punto de entrada unico a la aplicacion FARMAFÉ
define('FARMAFE_START', microtime(true));

// Iniciar bootstrap
$config = require dirname(__DIR__) . '/bootstrap/app.php';

// Detectar base path automaticamente
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$basePath = ($scriptDir === '/' || $scriptDir === '\\') ? '' : str_replace('\\', '/', $scriptDir);
\App\Core\Router::setBasePath($basePath);

// Cargar rutas
require dirname(__DIR__) . '/routes/web.php';

// Despachar peticion
try {
    \App\Core\Router::dispatch($_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET');
} catch (\Throwable $e) {
    if (!empty($config['app']['debug'])) {
        echo "<div style='font-family: monospace; background: #fee2e2; color: #991b1b; padding: 2rem; margin: 2rem;'>";
        echo "<h2>Error del Sistema (Modo Depuracion)</h2>";
        echo "<p><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div>";
    } else {
        http_response_code(500);
        \App\Core\View::render('errors/500', [
            'title' => 'Error Interno',
            'message' => 'Ocurrio un problema al procesar la solicitud. El incidente fue registrado.'
        ]);
    }
}

