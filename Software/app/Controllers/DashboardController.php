<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

class DashboardController
{
    public function index()
    {
        Auth::requireRole(['ADMIN', 'GERENCIA', 'VENTAS', 'ALMACEN']);

        $db = Database::getConnection();

        // 1. Conteo de Medicamentos y desglose ABC
        $prodStmt = $db->query("
            SELECT COUNT(*) AS total,
                   SUM(CASE WHEN clasificacion_abc = 'A' THEN 1 ELSE 0 END) AS total_a,
                   SUM(CASE WHEN clasificacion_abc = 'B' THEN 1 ELSE 0 END) AS total_b,
                   SUM(CASE WHEN clasificacion_abc = 'C' THEN 1 ELSE 0 END) AS total_c,
                   SUM(CASE WHEN estado_registro_sanitario = 'PENDIENTE_VALIDACION' THEN 1 ELSE 0 END) AS total_reg_pend,
                   SUM(CASE WHEN cadena_frio = 'NO_IDENTIFICADO' THEN 1 ELSE 0 END) AS total_frio_pend
            FROM productos WHERE activo = 1
        ");
        $prodKpi = $prodStmt->fetch();

        // 2. Conteo de proveedores y pares validos
        $provStmt = $db->query("SELECT COUNT(*) FROM proveedores WHERE activo = 1");
        $totalProveedores = (int)$provStmt->fetchColumn();

        $ppStmt = $db->query("SELECT COUNT(*) FROM producto_proveedor WHERE activo = 1");
        $totalParesPP = (int)$ppStmt->fetchColumn();

        // 3. Stock e Inventarios por lote
        $stockStmt = $db->query("
            SELECT COUNT(*) AS total_lotes,
                   COALESCE(SUM(saldo_actual), 0) AS total_unidades,
                   SUM(CASE WHEN fecha_vencimiento <= CURDATE() THEN 1 ELSE 0 END) AS lotes_vencidos,
                   SUM(CASE WHEN fecha_vencimiento > CURDATE() AND DATEDIFF(fecha_vencimiento, CURDATE()) <= 365 THEN 1 ELSE 0 END) AS lotes_corto_venc
            FROM lotes WHERE estado_lote = 'DISPONIBLE'
        ");
        $stockKpi = $stockStmt->fetch();

        // 4. Ultima ejecucion PLEM
        $plemStmt = $db->query("
            SELECT ep.*, ep_esc.nombre AS escenario_nombre, ep_esc.horizonte_meses
            FROM ejecuciones_plem ep
            JOIN escenarios_planificacion ep_esc ON ep.id_escenario = ep_esc.id_escenario
            ORDER BY ep.id_ejecucion DESC LIMIT 1
        ");
        $lastPlem = $plemStmt->fetch();

        // 5. Ultimas importaciones en staging
        $impStmt = $db->query("
            SELECT id_importacion, nombre_archivo_original, tipo_dataset, filas_totales, filas_validas, filas_con_error, estado, naturaleza_general, creado_en
            FROM importaciones ORDER BY id_importacion DESC LIMIT 5
        ");
        $recentImports = $impStmt->fetchAll();

        // 6. Ordenes de compra comprometidas / abiertas
        $ocStmt = $db->query("
            SELECT COUNT(*) AS total_ordenes,
                   SUM(CASE WHEN es_comprometida = 1 THEN 1 ELSE 0 END) AS comprometidas,
                   SUM(CASE WHEN estado = 'PROPUESTA' THEN 1 ELSE 0 END) AS propuestas
            FROM ordenes_compra
        ");
        $ocKpi = $ocStmt->fetch();

        View::render('pages/dashboard', [
            'title' => 'Panel de Control Principal',
            'prodKpi' => $prodKpi,
            'totalProveedores' => $totalProveedores,
            'totalParesPP' => $totalParesPP,
            'stockKpi' => $stockKpi,
            'lastPlem' => $lastPlem,
            'recentImports' => $recentImports,
            'ocKpi' => $ocKpi
        ]);
    }
}

