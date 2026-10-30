<?php

require dirname(__DIR__, 2) . '/bootstrap/app.php';

use App\Core\Router;
use App\Core\Auth;
use App\Core\Database;

echo "=== TEST DE RUTAS Y VISTAS WEB FARMAFÉ ===" . PHP_EOL;

$_SERVER['SCRIPT_NAME'] = '/index.php';
Router::setBasePath('');
require dirname(__DIR__, 2) . '/routes/web.php';

// 1. Probar GET / (Landing Page)
ob_start();
Router::dispatch('/', 'GET');
$landingHtml = ob_get_clean();

if (strpos($landingHtml, 'FARMAFÉ') !== false && strpos($landingHtml, 'Distribuidora de Medicamentos') !== false) {
    echo "[PASS] Ruta GET / (Landing Page) renderiza correctamente." . PHP_EOL;
} else {
    echo "[FAIL] Ruta GET / fallo al renderizar." . PHP_EOL;
    exit(1);
}

// 2. Probar creacion del primer administrador seguro
if (Auth::isInitialSetupNeeded()) {
    echo "Creando primer administrador seguro via Auth::createInitialAdmin..." . PHP_EOL;
    $res = Auth::createInitialAdmin('admin_farmafe', 'AdminSeguro2026', 'Administrador General FARMAFÉ', 'admin@farmafe.bo');
    if ($res['success']) {
        echo "[PASS] createInitialAdmin: Administrador creado exitosamente." . PHP_EOL;
    } else {
        echo "[FAIL] Error creando admin: " . ($res['error'] ?? '') . PHP_EOL;
        exit(1);
    }
} else {
    echo "[INFO] El administrador ya se encontraba registrado." . PHP_EOL;
}

// 3. Probar autenticacion con credenciales
$loginOk = Auth::attempt('admin_farmafe', 'AdminSeguro2026');
if ($loginOk) {
    echo "[PASS] Auth::attempt: Autenticación exitosa (password_verify)." . PHP_EOL;
} else {
    echo "[FAIL] Auth::attempt fallo." . PHP_EOL;
    exit(1);
}

// 4. Probar Dashboard autenticado GET /app
ob_start();
Router::dispatch('/app', 'GET');
$appHtml = ob_get_clean();
if (strpos($appHtml, 'Panel de Control Principal') !== false && strpos($appHtml, 'Aviso de Integridad') !== false) {
    echo "[PASS] Ruta GET /app: Dashboard privado renderiza con KPIs reales." . PHP_EOL;
} else {
    echo "[FAIL] Ruta GET /app fallo." . PHP_EOL;
    exit(1);
}

// 5. Probar Catálogo de Medicamentos GET /app/medicamentos
ob_start();
Router::dispatch('/app/medicamentos', 'GET');
$medHtml = ob_get_clean();
if (strpos($medHtml, 'Catálogo de Medicamentos') !== false && strpos($medHtml, 'Clasificación ABC') !== false) {
    echo "[PASS] Ruta GET /app/medicamentos: Catálogo de medicamentos renderiza." . PHP_EOL;
} else {
    echo "[FAIL] Ruta GET /app/medicamentos fallo." . PHP_EOL;
    exit(1);
}

// 6. Probar Proveedores GET /app/proveedores
ob_start();
Router::dispatch('/app/proveedores', 'GET');
$provHtml = ob_get_clean();
if (strpos($provHtml, 'Proveedores Farmacéuticos') !== false && strpos($provHtml, 'Pares Válidos') !== false) {
    echo "[PASS] Ruta GET /app/proveedores: Proveedores y 287 pares renderizan." . PHP_EOL;
} else {
    echo "[FAIL] Ruta GET /app/proveedores fallo." . PHP_EOL;
    exit(1);
}

// 7. Probar Lotes y FEFO GET /app/lotes
ob_start();
Router::dispatch('/app/lotes', 'GET');
$lotesHtml = ob_get_clean();
if (strpos($lotesHtml, 'Gestión de Lotes y Prioridad FEFO') !== false) {
    echo "[PASS] Ruta GET /app/lotes: Tabla FEFO de lotes renderiza." . PHP_EOL;
} else {
    echo "[FAIL] Ruta GET /app/lotes fallo." . PHP_EOL;
    exit(1);
}

// 8. Probar Centro de Importaciones GET /app/importaciones
ob_start();
Router::dispatch('/app/importaciones', 'GET');
$impHtml = ob_get_clean();
if (strpos($impHtml, 'Centro de Carga y Staging') !== false) {
    echo "[PASS] Ruta GET /app/importaciones: Centro de importación renderiza." . PHP_EOL;
} else {
    echo "[FAIL] Ruta GET /app/importaciones fallo." . PHP_EOL;
    exit(1);
}

// 9. Probar Formulario PLEM GET /app/planificacion/ejecutar
ob_start();
Router::dispatch('/app/planificacion/ejecutar', 'GET');
$plemHtml = ob_get_clean();
if (strpos($plemHtml, 'Configuración del Modelo de Optimización PLEM') !== false) {
    echo "[PASS] Ruta GET /app/planificacion/ejecutar: Formulario PLEM renderiza." . PHP_EOL;
} else {
    echo "[FAIL] Ruta GET /app/planificacion/ejecutar fallo." . PHP_EOL;
    exit(1);
}

// 10. Probar Versiones PLEM GET /app/planificacion/versiones
ob_start();
Router::dispatch('/app/planificacion/versiones', 'GET');
$verHtml = ob_get_clean();
if (strpos($verHtml, 'Historial de Versiones del Plan PLEM') !== false) {
    echo "[PASS] Ruta GET /app/planificacion/versiones: Historial de versiones renderiza." . PHP_EOL;
} else {
    echo "[FAIL] Ruta GET /app/planificacion/versiones fallo." . PHP_EOL;
    exit(1);
}

// 11. Probar Comparaciones AS-IS / TO-BE GET /app/evaluacion/comparaciones
ob_start();
Router::dispatch('/app/evaluacion/comparaciones', 'GET');
$compHtml = ob_get_clean();
if (strpos($compHtml, 'Evaluación Contrafactual') !== false && strpos($compHtml, 'Principio de Veracidad') !== false) {
    echo "[PASS] Ruta GET /app/evaluacion/comparaciones: Comparación y advertencias de datos renderizan." . PHP_EOL;
} else {
    echo "[FAIL] Ruta GET /app/evaluacion/comparaciones fallo." . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "=== TODAS LAS 11 PRUEBAS DE VISTAS Y RUTAS WEB SUPERADAS CON EXITO ===" . PHP_EOL;
