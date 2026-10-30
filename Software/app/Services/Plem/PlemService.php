<?php

namespace App\Services\Plem;

use App\Core\Database;
use App\Core\Audit;
use App\Core\Auth;
use PDO;
use RuntimeException;

class PlemService
{
    private array $config;

    public function __construct()
    {
        $appConfig = require dirname(__DIR__, 3) . '/config/config.php';
        $this->config = $appConfig['solver'];
    }

    public function buildPayloadFromScenario(int $scenarioId, ?array $selectedProductIds = null): array
    {
        $db = Database::getConnection();

        // 1. Obtener escenario
        $stmt = $db->prepare("SELECT * FROM escenarios_planificacion WHERE id_escenario = :id");
        $stmt->execute(['id' => $scenarioId]);
        $escenario = $stmt->fetch();
        if (!$escenario) {
            throw new RuntimeException("El escenario de planificacion #{$scenarioId} no existe.");
        }

        $horizon = (int)($escenario['horizonte_meses'] ?? 6);
        $budgetDef = (float)($escenario['presupuesto_mensual_defecto_bs'] ?? 250000.0);

        // Presupuestos mensuales si existen en la tabla
        $budgets = [];
        $bStmt = $db->prepare("SELECT mes, presupuesto_ordinario_bs FROM presupuestos_periodicos ORDER BY gestion ASC, mes ASC LIMIT :h");
        $bStmt->bindValue(':h', $horizon, PDO::PARAM_INT);
        $bStmt->execute();
        $bRows = $bStmt->fetchAll();
        for ($t = 1; $t <= $horizon; $t++) {
            $budgets[] = isset($bRows[$t - 1]) ? (float)$bRows[$t - 1]['presupuesto_ordinario_bs'] : $budgetDef;
        }

        // 2. Costos de escenario
        $costs = [];
        $cStmt = $db->query("SELECT nombre_parametro, valor FROM costos_escenario");
        while ($row = $cStmt->fetch()) {
            $costs[$row['nombre_parametro']] = (float)$row['valor'];
        }

        // 3. Medicamentos / Productos
        $prodSql = "SELECT id_producto, codigo, descripcion, costo_unitario_promedio_bs, clasificacion_abc FROM productos WHERE activo = 1";
        if (!empty($selectedProductIds)) {
            $inClause = implode(',', array_map('intval', $selectedProductIds));
            $prodSql .= " AND id_producto IN ({$inClause})";
        }
        $prodSql .= " ORDER BY id_producto ASC";
        $pStmt = $db->query($prodSql);
        $products = [];
        $prodIds = [];
        while ($row = $pStmt->fetch()) {
            $products[] = [
                'id' => (int)$row['id_producto'],
                'code' => $row['codigo'],
                'description' => $row['descripcion'],
                'cost_avg' => (float)$row['costo_unitario_promedio_bs'],
                'abc' => $row['clasificacion_abc']
            ];
            $prodIds[] = (int)$row['id_producto'];
        }

        if (empty($products)) {
            throw new RuntimeException("No hay productos activos elegibles para la optimizacion.");
        }

        // 4. Proveedores
        $provStmt = $db->query("SELECT id_proveedor, codigo, razon_social FROM proveedores WHERE activo = 1");
        $suppliers = [];
        while ($row = $provStmt->fetch()) {
            $suppliers[] = [
                'id' => (int)$row['id_proveedor'],
                'code' => $row['codigo'],
                'name' => $row['razon_social']
            ];
        }

        // 5. Pares producto-proveedor validos
        $ppSql = "SELECT id_producto, id_proveedor, costo_unitario_bs, plazo_dias, compra_minima, multiplo_empaque 
                  FROM producto_proveedor WHERE activo = 1";
        if (!empty($prodIds)) {
            $ppSql .= " AND id_producto IN (" . implode(',', $prodIds) . ")";
        }
        $ppStmt = $db->query($ppSql);
        $ppList = [];
        while ($row = $ppStmt->fetch()) {
            $ppList[] = [
                'product_id' => (int)$row['id_producto'],
                'supplier_id' => (int)$row['id_proveedor'],
                'cost_unit' => (float)$row['costo_unitario_bs'],
                'lead_time_days' => (int)$row['plazo_dias'],
                'min_order' => (int)$row['compra_minima'],
                'pack_multiple' => (int)$row['multiplo_empaque']
            ];
        }

        // 6. Lotes iniciales existentes
        $loteSql = "SELECT id_lote, id_producto, codigo_lote, saldo_actual, fecha_vencimiento 
                    FROM lotes WHERE estado_lote = 'DISPONIBLE' AND saldo_actual > 0";
        if (!empty($prodIds)) {
            $loteSql .= " AND id_producto IN (" . implode(',', $prodIds) . ")";
        }
        $lStmt = $db->query($loteSql);
        $batches = [];
        $cutoffDate = $escenario['fecha_corte'] ?? date('Y-m-d');
        $cutoffTime = strtotime($cutoffDate);

        while ($row = $lStmt->fetch()) {
            $vencTime = strtotime($row['fecha_vencimiento']);
            // Calcular en que mes del horizonte vence (1..horizon o mayor)
            $diffMonths = ceil(($vencTime - $cutoffTime) / (30 * 86400));
            $expirePeriod = max(1, (int)$diffMonths);

            $batches[] = [
                'id' => (int)$row['id_lote'],
                'product_id' => (int)$row['id_producto'],
                'code' => $row['codigo_lote'],
                'initial_stock' => (int)$row['saldo_actual'],
                'expire_period' => $expirePeriod
            ];
        }

        // 7. Demanda periodica por producto y mes
        $demSql = "SELECT id_producto, periodo_secuencial, cantidad 
                   FROM demanda_periodica 
                   WHERE periodo_secuencial BETWEEN 1 AND :h";
        if (!empty($prodIds)) {
            $demSql .= " AND id_producto IN (" . implode(',', $prodIds) . ")";
        }
        $demSql .= " ORDER BY id_producto ASC, periodo_secuencial ASC";
        $dStmt = $db->prepare($demSql);
        $dStmt->execute(['h' => $horizon]);
        $demandList = [];
        while ($row = $dStmt->fetch()) {
            $demandList[] = [
                'product_id' => (int)$row['id_producto'],
                'period' => (int)$row['periodo_secuencial'],
                'quantity' => (int)$row['cantidad']
            ];
        }

        // 8. Compras comprometidas (congeladas)
        $commSql = "SELECT ocd.id_producto, oc.id_proveedor, 1 AS period, ocd.cantidad_solicitada 
                    FROM ordenes_compra oc
                    JOIN orden_compra_detalles ocd ON oc.id_orden = ocd.id_orden
                    WHERE oc.es_comprometida = 1 AND oc.estado IN ('EMITIDA', 'APROBADA')";
        if (!empty($prodIds)) {
            $commSql .= " AND ocd.id_producto IN (" . implode(',', $prodIds) . ")";
        }
        $commStmt = $db->query($commSql);
        $committed = [];
        while ($row = $commStmt->fetch()) {
            $committed[] = [
                'product_id' => (int)$row['id_producto'],
                'supplier_id' => (int)$row['id_proveedor'],
                'period' => (int)$row['period'],
                'quantity' => (int)$row['cantidad_solicitada']
            ];
        }

        return [
            'scenario' => [
                'id' => (int)$escenario['id_escenario'],
                'code' => $escenario['codigo_escenario'],
                'name' => $escenario['nombre'],
                'horizon_months' => $horizon,
                'demand_mode' => $escenario['modo_demanda'],
                'policy_shortage' => $escenario['politica_faltante'],
                'budget_per_period' => $budgets,
                'epsilon_tolerance' => (float)($escenario['tolerancia_epsilon'] ?? 0.0001)
            ],
            'costs' => [
                'order_issue_bs' => $costs['costo_emision_orden_bs'] ?? 50.0,
                'holding_annual_pct' => $costs['costo_posesion_anual_pct'] ?? 20.0,
                'shortage_unit_bs' => $costs['costo_faltante_unitario_bs'] ?? 15.0,
                'expiration_penalty_bs' => $costs['costo_baja_vencimiento_bs'] ?? 5.0,
                'dispatch_shipment_bs' => $costs['costo_despacho_envio_bs'] ?? 35.0
            ],
            'products' => $products,
            'suppliers' => $suppliers,
            'product_supplier' => $ppList,
            'initial_batches' => $batches,
            'demand' => $demandList,
            'committed_orders' => $committed
        ];
    }

    public function executeOptimization(int $scenarioId, ?array $selectedProductIds = null): array
    {
        $payload = $this->buildPayloadFromScenario($scenarioId, $selectedProductIds);

        $jsonInput = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $inputHash = hash('sha256', $jsonInput);

        // Guardar payload temporal en storage/temp
        $tempDir = dirname(__DIR__, 3) . '/storage/temp';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $tempInputFile = $tempDir . '/plem_input_' . time() . '_' . substr($inputHash, 0, 8) . '.json';
        file_put_contents($tempInputFile, $jsonInput);

        // Llamar al solver Python
        $pythonBin = $this->config['python_bin'] ?? 'python';
        $scriptPath = $this->config['script_path'];

        if (!file_exists($scriptPath)) {
            unlink($tempInputFile);
            throw new RuntimeException("El script del optimizador PLEM no se encuentra en '{$scriptPath}'.");
        }

        $command = sprintf('"%s" "%s" "%s"', $pythonBin, $scriptPath, $tempInputFile);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $process = proc_open($command, $descriptors, $pipes);
        if (!is_resource($process)) {
            unlink($tempInputFile);
            throw new RuntimeException("No se pudo iniciar el proceso del optimizador Python.");
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        @unlink($tempInputFile);

        if ($exitCode !== 0 && empty($stdout)) {
            throw new RuntimeException("Error en el solver PLEM (Codigo {$exitCode}): " . $stderr);
        }

        $result = json_decode($stdout, true);
        if (!is_array($result)) {
            throw new RuntimeException("El optimizador no devolvio una respuesta JSON valida. Salida cruda: " . substr($stdout, 0, 500) . " - Error: " . $stderr);
        }

        if (empty($result['success']) || $result['status'] !== 'OPTIMAL') {
            return [
                'success' => false,
                'status' => $result['status'] ?? 'ERROR',
                'message' => $result['message'] ?? 'El optimizador no encontro una solucion factible o viable.',
                'raw_result' => $result
            ];
        }

        // Persistir la corrida de forma inmutable
        $executionId = $this->persistExecutionResults($scenarioId, $payload, $result, $inputHash);

        return [
            'success' => true,
            'id_ejecucion' => $executionId,
            'version_plan' => $result['version_plan'] ?? ('V' . date('Ymd.His')),
            'status' => $result['status'],
            'service_level_s1' => $result['service_level_s1'],
            'service_level_s2' => $result['service_level_s2'],
            'total_cost_s2' => $result['total_cost_s2'],
            'cost_breakdown' => $result['cost_breakdown'],
            'summary' => $result['summary'],
            'execution_time_seconds' => $result['execution_time_seconds']
        ];
    }

    private function persistExecutionResults(int $scenarioId, array $payload, array $result, string $inputHash): int
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $userId = Auth::id();

            // Calcular siguiente version inmutable
            $vStmt = $db->prepare("SELECT COUNT(*) AS total FROM ejecuciones_plem WHERE id_escenario = :id");
            $vStmt->execute(['id' => $scenarioId]);
            $count = (int)$vStmt->fetchColumn();
            $versionPlan = sprintf('V%d.%d', date('y'), $count + 1);

            $costs = $result['cost_breakdown'] ?? [];
            $summary = $result['summary'] ?? [];

            // 1. Insertar ejecucion
            $stmt = $db->prepare("
                INSERT INTO ejecuciones_plem (
                    id_escenario, version_plan, fecha_ejecucion, solver_usado, version_solver,
                    tiempo_ejecucion_segundos, estado_solver_etapa1, estado_solver_etapa2,
                    nivel_servicio_optimo_s1, costo_logistico_total_s2, costo_compra_bs,
                    costo_orden_bs, costo_posesion_bs, costo_faltante_bs, costo_vencimiento_bs, costo_despacho_bs,
                    total_demanda_solicitada, total_demanda_atendida, total_demanda_perdida, total_compras_unidades,
                    hash_entradas, snapshot_entradas_json, mensaje_diagnostico, id_usuario
                ) VALUES (
                    :esc_id, :ver, NOW(), :solver, :ver_solver,
                    :dur, :st1, :st2,
                    :s1, :cost, :c_comp,
                    :c_ord, :c_pos, :c_falt, :c_venc, :c_desp,
                    :dem_req, :dem_att, :dem_lost, :purch_u,
                    :hash, :snap, :diag, :user_id
                )
            ");

            $stmt->execute([
                'esc_id' => $scenarioId,
                'ver' => $versionPlan,
                'solver' => $result['solver'] ?? 'PuLP / CBC-HiGHS',
                'ver_solver' => $result['version_solver'] ?? 'PuLP 3.x',
                'dur' => $result['execution_time_seconds'] ?? 0.0,
                'st1' => $result['stage1_status'] ?? 'Optimal',
                'st2' => $result['stage2_status'] ?? 'Optimal',
                's1' => $result['service_level_s1'] ?? 1.0,
                'cost' => $result['total_cost_s2'] ?? 0.0,
                'c_comp' => $costs['compra_bs'] ?? 0.0,
                'c_ord' => $costs['orden_bs'] ?? 0.0,
                'c_pos' => $costs['posesion_bs'] ?? 0.0,
                'c_falt' => $costs['faltante_bs'] ?? 0.0,
                'c_venc' => $costs['vencimiento_bs'] ?? 0.0,
                'c_desp' => $costs['despacho_bs'] ?? 0.0,
                'dem_req' => $summary['total_demand_requested'] ?? 0,
                'dem_att' => $summary['total_demand_fulfilled'] ?? 0,
                'dem_lost' => $summary['total_demand_lost'] ?? 0,
                'purch_u' => $summary['total_purchases_units'] ?? 0,
                'hash' => $inputHash,
                'snap' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'diag' => $result['diagnostics'] ?? 'Optimizacion lexicografica ejecutada exitosamente',
                'user_id' => $userId
            ]);

            $ejecucionId = (int)$db->lastInsertId();

            // 2. Insertar recomendaciones de compras
            $pStmt = $db->prepare("
                INSERT INTO planes_compras (
                    id_ejecucion, periodo_t, periodo_nombre, id_producto, id_proveedor,
                    cantidad_propuesta, costo_unitario_bs, subtotal_bs, lead_time_dias, periodo_llegada_t, estado_decision
                ) VALUES (
                    :ejec_id, :t, :t_nom, :prod_id, :prov_id,
                    :qty, :c_u, :subt, :lt, :t_arr, 'PROPUESTA'
                )
            ");

            foreach ($result['purchases_recommended'] ?? [] as $pr) {
                $pStmt->execute([
                    'ejec_id' => $ejecucionId,
                    't' => (int)$pr['period_t'],
                    't_nom' => $pr['period_name'] ?? ('Mes ' . $pr['period_t']),
                    'prod_id' => (int)$pr['product_id'],
                    'prov_id' => (int)$pr['supplier_id'],
                    'qty' => (int)$pr['quantity'],
                    'c_u' => (float)$pr['cost_unit'],
                    'subt' => (float)$pr['subtotal'],
                    'lt' => (int)$pr['lead_time_days'],
                    't_arr' => (int)$pr['arrival_period']
                ]);
            }

            // 3. Insertar proyeccion de inventario
            $ipStmt = $db->prepare("
                INSERT INTO inventario_proyectado (
                    id_ejecucion, periodo_t, periodo_nombre, id_producto, id_lote,
                    saldo_inicial, recepciones_programadas, demanda_asignada, cantidad_vencida, saldo_final
                ) VALUES (
                    :ejec_id, :t, :t_nom, :prod_id, :lote_id,
                    :ini, :arr, :asig, :exp, :fin
                )
            ");

            foreach ($result['inventory_projection'] ?? [] as $ip) {
                $ipStmt->execute([
                    'ejec_id' => $ejecucionId,
                    't' => (int)$ip['period_t'],
                    't_nom' => $ip['period_name'] ?? ('Mes ' . $ip['period_t']),
                    'prod_id' => (int)$ip['product_id'],
                    'lote_id' => !empty($ip['batch_id']) ? (int)$ip['batch_id'] : null,
                    'ini' => (int)$ip['initial_stock'],
                    'arr' => (int)$ip['incoming'],
                    'asig' => (int)$ip['allocated'],
                    'exp' => (int)$ip['expired'],
                    'fin' => (int)$ip['final_stock']
                ]);
            }

            // 4. Insertar resultados de demanda
            $drStmt = $db->prepare("
                INSERT INTO demanda_resultado_plan (
                    id_ejecucion, periodo_t, periodo_nombre, id_producto,
                    cantidad_solicitada, cantidad_atendida, cantidad_pendiente, cantidad_perdida, porcentaje_atencion
                ) VALUES (
                    :ejec_id, :t, :t_nom, :prod_id,
                    :req, :att, :pend, :lost, :pct
                )
            ");

            foreach ($result['demand_results'] ?? [] as $dr) {
                $drStmt->execute([
                    'ejec_id' => $ejecucionId,
                    't' => (int)$dr['period_t'],
                    't_nom' => $dr['period_name'] ?? ('Mes ' . $dr['period_t']),
                    'prod_id' => (int)$dr['product_id'],
                    'req' => (int)$dr['requested'],
                    'att' => (int)$dr['fulfilled'],
                    'pend' => (int)$dr['pending'],
                    'lost' => (int)$dr['lost'],
                    'pct' => (float)$dr['fulfillment_pct']
                ]);
            }

            // Auditoria
            Audit::log('EJECUTAR_PLEM', 'ejecuciones_plem', (string)$ejecucionId, null, [
                'escenario_id' => $scenarioId,
                'version_plan' => $versionPlan,
                's_opt' => $result['service_level_s1'],
                'costo' => $result['total_cost_s2']
            ], 'Ejecucion de modelo PLEM multiproducto y multiperiodo de 6 meses');

            $db->commit();
            return $ejecucionId;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
