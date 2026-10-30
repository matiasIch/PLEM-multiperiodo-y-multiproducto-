<?php

require dirname(__DIR__, 2) . '/bootstrap/app.php';

use App\Services\Plem\PlemService;
use App\Core\Database;

echo "=== TEST DE INTEGRACION PLEM EN FARMAFÉ ===" . PHP_EOL;

try {
    $service = new PlemService();
    echo "Construyendo payload desde el Escenario #1 (6 Meses)..." . PHP_EOL;
    $payload = $service->buildPayloadFromScenario(1);

    echo " - Productos incluidos: " . count($payload['products']) . PHP_EOL;
    echo " - Proveedores: " . count($payload['suppliers']) . PHP_EOL;
    echo " - Pares producto-proveedor: " . count($payload['product_supplier']) . PHP_EOL;
    echo " - Lotes activos: " . count($payload['initial_batches']) . PHP_EOL;
    echo " - Registros de demanda: " . count($payload['demand']) . PHP_EOL;

    echo "Invocando optimizador lexicografico (Etapa 1 + Etapa 2)..." . PHP_EOL;
    $res = $service->executeOptimization(1);

    if ($res['success']) {
        echo "[EXITO] Optimizacion completada exitosamente!" . PHP_EOL;
        echo " - Plan Version: " . $res['version_plan'] . PHP_EOL;
        echo " - Nivel de Servicio S* (Etapa 1): " . number_format($res['service_level_s1'] * 100, 2) . "%" . PHP_EOL;
        echo " - Costo Logistico Total (Etapa 2): Bs " . number_format($res['total_cost_s2'], 2, ',', '.') . PHP_EOL;
        echo " - Tiempo de computo: " . $res['execution_time_seconds'] . "s" . PHP_EOL;
        echo " - Total compras recomendadas: " . $res['summary']['total_purchases_units'] . " unidades" . PHP_EOL;

        // Verificar en BD
        $db = Database::getConnection();
        $comprasCount = (int)$db->query("SELECT COUNT(*) FROM planes_compras WHERE id_ejecucion = " . (int)$res['id_ejecucion'])->fetchColumn();
        $invCount = (int)$db->query("SELECT COUNT(*) FROM inventario_proyectado WHERE id_ejecucion = " . (int)$res['id_ejecucion'])->fetchColumn();
        $demCount = (int)$db->query("SELECT COUNT(*) FROM demanda_resultado_plan WHERE id_ejecucion = " . (int)$res['id_ejecucion'])->fetchColumn();

        echo " - Filas de compras persistidas: " . $comprasCount . PHP_EOL;
        echo " - Filas de inventario proyectado: " . $invCount . PHP_EOL;
        echo " - Filas de atencion de demanda: " . $demCount . PHP_EOL;

        if ($comprasCount >= 0 && $invCount > 0 && $demCount > 0) {
            echo "[PASS] Prueba de integracion superada con exito." . PHP_EOL;
            exit(0);
        } else {
            echo "[FAIL] Inconsistencia en filas persistidas." . PHP_EOL;
            exit(1);
        }
    } else {
        echo "[FAIL] Solver no alcanzo estado optimo: " . ($res['message'] ?? $res['status']) . PHP_EOL;
        exit(1);
    }
} catch (\Throwable $e) {
    echo "[ERROR EXCEPCION] " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}

