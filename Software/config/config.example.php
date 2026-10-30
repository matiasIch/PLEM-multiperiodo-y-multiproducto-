<?php
// =============================================================================
// FARMAFÉ - Archivo de ejemplo de configuracion (sin secretos)
// Copiar a config.local.php para sobreescribir parametros de entorno local.
// =============================================================================

return [
    'app' => [
        'name' => 'FARMAFÉ - Planificación Logística PLEM',
        'env' => 'development', // 'development' o 'production'
        'debug' => true,
        'url' => 'http://localhost/Proyecto_Grado/Software/public',
        'timezone' => 'America/La_Paz',
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'farmafe_app',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'cookie_name' => 'farmafe_session',
        'lifetime' => 28800, // 8 horas
        'path' => '/',
        'domain' => '',
        'secure' => false, // Cambiar a true si se usa HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ],
    'solver' => [
        'python_bin' => 'C:\\Program Files\\Blender Foundation\\Blender 5.0\\5.0\\python\\bin\\python.exe',
        'script_path' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'solver' . DIRECTORY_SEPARATOR . 'plem_solver.py',
        'timeout_seconds' => 120,
    ],
    'storage' => [
        'imports' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'imports',
        'logs' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs',
        'temp' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'temp',
        'backups' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups',
    ],
];

