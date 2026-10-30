<?php

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ImportController;
use App\Controllers\MasterController;
use App\Controllers\InventoryController;
use App\Controllers\DemandController;
use App\Controllers\PlanningController;
use App\Controllers\EvaluationController;
use App\Controllers\AdminController;

// 1. Rutas Publicas
Router::get('/', [AuthController::class, 'showLanding']);
Router::get('/login', [AuthController::class, 'showLogin']);
Router::post('/login', [AuthController::class, 'login']);
Router::post('/logout', [AuthController::class, 'logout']);

// Asistente Inicial de Configuracion
Router::get('/setup', [AuthController::class, 'showSetup']);
Router::post('/setup', [AuthController::class, 'setup']);

// 2. Dashboard
Router::get('/app', [DashboardController::class, 'index']);

// 3. Centro de Importacion
Router::get('/app/importaciones', [ImportController::class, 'index']);
Router::post('/app/importaciones/upload', [ImportController::class, 'upload']);
Router::get('/app/importaciones/preview/{id}', [ImportController::class, 'preview']);
Router::post('/app/importaciones/confirm/{id}', [ImportController::class, 'confirm']);

// 4. Maestros
Router::get('/app/medicamentos', [MasterController::class, 'medicamentos']);
Router::get('/app/proveedores', [MasterController::class, 'proveedores']);
Router::get('/app/clientes', [MasterController::class, 'clientes']);
Router::get('/app/ubicaciones', [MasterController::class, 'ubicaciones']);

// 5. Abastecimiento e Inventarios
Router::get('/app/compras', [InventoryController::class, 'compras']);
Router::post('/app/compras/compromiso/{id}', [InventoryController::class, 'toggleCompromiso']);
Router::get('/app/recepciones', [InventoryController::class, 'recepciones']);
Router::get('/app/lotes', [InventoryController::class, 'lotes']);
Router::get('/app/inventario/movimientos', [InventoryController::class, 'movimientos']);

// 6. Demanda y Pedidos
Router::get('/app/pedidos', [DemandController::class, 'pedidos']);
Router::get('/app/demanda', [DemandController::class, 'demanda']);

// 7. Optimizacion PLEM y Planificacion
Router::get('/app/planificacion/ejecutar', [PlanningController::class, 'ejecutar']);
Router::post('/app/planificacion/ejecutar', [PlanningController::class, 'procesarEjecucion']);
Router::get('/app/planificacion/ejecuciones/{id}', [PlanningController::class, 'resultado']);
Router::get('/app/planificacion/versiones', [PlanningController::class, 'versiones']);
Router::get('/app/planificacion/exportar/{id}', [PlanningController::class, 'exportarCsv']);

// 8. Evaluacion y Comparaciones
Router::get('/app/evaluacion/comparaciones', [EvaluationController::class, 'comparaciones']);

// 9. Administracion y Auditoria
Router::get('/app/parametros', [AdminController::class, 'parametros']);
Router::post('/app/parametros/guardar', [AdminController::class, 'guardarParametros']);
Router::get('/app/administracion/usuarios', [AdminController::class, 'usuarios']);
Router::post('/app/administracion/usuarios/crear', [AdminController::class, 'crearUsuario']);
Router::get('/app/administracion/auditoria', [AdminController::class, 'auditoria']);

