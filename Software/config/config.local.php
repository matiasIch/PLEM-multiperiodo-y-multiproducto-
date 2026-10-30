<?php
// =============================================================================
// FARMAFÉ - Configuración local para XAMPP en puerto 8012
// Este archivo sobrescribe config.example.php y NO debe subirse a Git.
// =============================================================================

return [
    'app' => [
        'url'   => 'http://localhost:8012/farmafe',
        'debug' => true,
        'env'   => 'development',
    ],
    'database' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'farmafe_app',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
    'solver' => [
        'python_bin'     => 'C:\\Program Files\\Blender Foundation\\Blender 5.0\\5.0\\python\\bin\\python.exe',
        'script_path'    => 'C:\\Users\\paola\\Desktop\\Proyecto_Grado\\Software\\solver\\plem_solver.py',
        'timeout_seconds'=> 120,
    ],
];

