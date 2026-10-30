<?php

// Autoloader PSR-4 simple para App\*
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Cargar helpers globales
require __DIR__ . '/helpers.php';

// Cargar configuracion
$config = require dirname(__DIR__) . '/config/config.php';

// Zona horaria
date_default_timezone_set($config['app']['timezone'] ?? 'America/La_Paz');

// Inicializar componentes nucleo
\App\Core\Database::init($config['database']);
\App\Core\Session::start($config);
\App\Core\View::init(dirname(__DIR__) . '/app/Views');

return $config;
