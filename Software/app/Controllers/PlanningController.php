<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Audit;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Services\Plem\PlemService;
use App\Services\Reports\ExportService;
use Exception;

class PlanningController
{
    private PlemService $plemService;

    public function __construct()
    {
        $this->plemService = new PlemService();
    }

    public function ejecutar()
    {
        Auth::requireRole(['ADMIN', 'GERENCIA']);
        $db = Database::getConnection();

        // Obtener escenarios disponibles
        $escStmt = $db->query("SELECT * FROM escenarios_planificacion WHERE estado = 'ACTIVO' ORDER BY id_escenario DESC");
        $escenarios = $escStmt->fetchAll();

        // Medicamentos activos
        $prods = $db->query("SELECT id_producto, codigo, descripcion, clasificacion_abc FROM productos WHERE activo = 1 ORDER BY clasificacion_abc ASC, codigo ASC")->fetchAll();

        // Parametros de costos
        $costs = $db->query("SELECT * FROM costos_escenario ORDER BY id_costo ASC")->fetchAll();

        View::render('pages/planificacion_ejecutar', [
            'title' => 'Configurar y Ejecutar Modelo PLEM (6 Meses)',
            'escenarios' => $escenarios,
            'productos' => $prods,
            'costos' => $costs
        ]);
    }

    public function procesarEjecucion()
    {
        Auth::requireRole(['ADMIN', 'GERENCIA']);

        $scenarioId = (int)($_POST['scenario_id'] ?? 0);
        $prodMode = $_POST['prod_mode'] ?? 'ALL';
        $selectedIds = null;

        if ($prodMode === 'CUSTOM' && !empty($_POST['selected_products'])) {
            $selectedIds = array_map('intval', $_POST['selected_products']);
        } elseif ($prodMode === 'CLASS_A') {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT id_producto FROM productos WHERE clasificacion_abc = 'A' AND activo = 1");
            $selectedIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }

        if ($scenarioId <= 0) {
            // Si no hay escenario seleccionado, crear uno base
            $scenarioId = $this->createDefaultScenario();
        }

        try {
            $result = $this->plemService->executeOptimization($scenarioId, $selectedIds);

            if ($result['success']) {
                Session::setFlash('success', "Optimización PLEM ejecutada exitosamente. Plan {$result['version_plan']} generado con Nivel de Servicio S* = " . number_format($result['service_level_s1'] * 100, 2) . "% y Costo Logístico = " . \App\Core\View::formatBs($result['total_cost_s2']) . ".");
                Router::redirect('/app/planificacion/ejecuciones/' . $result['id_ejecucion']);
                return;
            } else {
                Session::setFlash('error', "El solver reportó estado: " . ($result['status'] ?? 'ERROR') . ". Mensaje: " . ($result['message'] ?? 'Infactibilidad matemática o parámetros incompatibles.'));
                Router::redirect('/app/planificacion/ejecutar');
                return;
            }
        } catch (Exception $e) {
            Session::setFlash('error', "Error al ejecutar el optimizador PLEM: " . $e->getMessage());
            Router::redirect('/app/planificacion/ejecutar');
        }
    }

    private function createDefaultScenario(): int
    {
        $db = Database::getConnection();
        $code = 'ESC-' . date('Ym') . '-DEF';
        $name = 'Escenario Base 6 Meses (' . date('M Y') . ')';

        $stmt = $db->prepare("
            INSERT INTO escenarios_planificacion (
                codigo_escenario, nombre, descripcion, fecha_corte,
                periodo_inicio_gestion, periodo_inicio_mes, horizonte_meses,
                modo_demanda, politica_faltante, presupuesto_mensual_defecto_bs,
                tolerancia_epsilon, estado, id_usuario, naturaleza_dato, creado_en
            ) VALUES (
                :code, :name, 'Escenario de referencia generado automaticamente para horizonte de seis meses',
                CURDATE(), YEAR(CURDATE()), MONTH(CURDATE()), 6,
                'AGREGADO', 'BACKORDER', 250000.00,
                0.000100, 'ACTIVO', :user_id, 'ASSUMPTION', NOW()
            )
        ");

        $stmt->execute([
            'code' => $code,
            'name' => $name,
            'user_id' => Auth::id()
        ]);

        return (int)$db->lastInsertId();
    }

    public function resultado(int $id)
    {
        Auth::requireRole(['ADMIN', 'GERENCIA', 'VENTAS', 'ALMACEN']);
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT ep.*, esc.nombre AS escenario_nombre, esc.horizonte_meses, esc.modo_demanda, esc.politica_faltante,
                   u.nombre_completo AS autor_nombre
            FROM ejecuciones_plem ep
            JOIN escenarios_planificacion esc ON ep.id_escenario = esc.id_escenario
            LEFT JOIN usuarios u ON ep.id_usuario = u.id_usuario
            WHERE ep.id_ejecucion = :id
        ");
        $stmt->execute(['id' => $id]);
        $ejecucion = $stmt->fetch();

        if (!$ejecucion) {
            Session::setFlash('error', 'La ejecución del plan solicitada no existe.');
            Router::redirect('/app/planificacion/versiones');
            return;
        }

        // Compras recomendadas
        $pcStmt = $db->prepare("
            SELECT pc.*, p.codigo AS prod_codigo, p.descripcion AS prod_desc, prv.razon_social AS prov_nombre
            FROM planes_compras pc
            JOIN productos p ON pc.id_producto = p.id_producto
            JOIN proveedores prv ON pc.id_proveedor = prv.id_proveedor
            WHERE pc.id_ejecucion = :id
            ORDER BY pc.periodo_t ASC, p.codigo ASC
        ");
        $pcStmt->execute(['id' => $id]);
        $compras = $pcStmt->fetchAll();

        // Demanda
        $drStmt = $db->prepare("
            SELECT dr.*, p.codigo AS prod_codigo, p.descripcion AS prod_desc
            FROM demanda_resultado_plan dr
            JOIN productos p ON dr.id_producto = p.id_producto
            WHERE dr.id_ejecucion = :id
            ORDER BY dr.periodo_t ASC, p.codigo ASC
        ");
        $drStmt->execute(['id' => $id]);
        $demandas = $drStmt->fetchAll();

        // Proyeccion de inventario
        $ipStmt = $db->prepare("
            SELECT ip.*, p.codigo AS prod_codigo, p.descripcion AS prod_desc, l.codigo_lote
            FROM inventario_proyectado ip
            JOIN productos p ON ip.id_producto = p.id_producto
            LEFT JOIN lotes l ON ip.id_lote = l.id_lote
            WHERE ip.id_ejecucion = :id
            ORDER BY ip.periodo_t ASC, p.codigo ASC
        ");
        $ipStmt->execute(['id' => $id]);
        $inventario = $ipStmt->fetchAll();

        View::render('pages/planificacion_resultado', [
            'title' => 'Resultados del Plan PLEM - ' . $ejecucion['version_plan'],
            'ejecucion' => $ejecucion,
            'compras' => $compras,
            'demandas' => $demandas,
            'inventario' => $inventario
        ]);
    }

    public function versiones()
    {
        Auth::requireRole(['ADMIN', 'GERENCIA', 'VENTAS', 'ALMACEN']);
        $db = Database::getConnection();

        $stmt = $db->query("
            SELECT ep.*, esc.nombre AS escenario_nombre, esc.horizonte_meses, u.nombre_usuario
            FROM ejecuciones_plem ep
            JOIN escenarios_planificacion esc ON ep.id_escenario = esc.id_escenario
            LEFT JOIN usuarios u ON ep.id_usuario = u.id_usuario
            ORDER BY ep.id_ejecucion DESC
        ");
        $versiones = $stmt->fetchAll();

        View::render('pages/planificacion_versiones', [
            'title' => 'Versiones del Plan e Historial de Ejecuciones',
            'versiones' => $versiones
        ]);
    }

    public function exportarCsv(int $id)
    {
        Auth::requireRole(['ADMIN', 'GERENCIA', 'VENTAS', 'ALMACEN']);
        $exportService = new ExportService();
        $exportService->exportExecutionToCsv($id);
    }
}

