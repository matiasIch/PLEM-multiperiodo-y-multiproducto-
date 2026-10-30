<?php

namespace App\Services\Reports;

use App\Core\Database;
use RuntimeException;

class ExportService
{
    public function exportExecutionToCsv(int $executionId): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT ep.*, esc.nombre AS escenario_nombre, esc.horizonte_meses, esc.politica_faltante
            FROM ejecuciones_plem ep
            JOIN escenarios_planificacion esc ON ep.id_escenario = esc.id_escenario
            WHERE ep.id_ejecucion = :id
        ");
        $stmt->execute(['id' => $executionId]);
        $ejec = $stmt->fetch();
        if (!$ejec) {
            throw new RuntimeException("Ejecucion PLEM no encontrada.");
        }

        $filename = 'plan_farmafe_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $ejec['version_plan']) . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM para apertura correcta en Excel
        fputs($out, "\xEF\xBB\xBF");

        // Metadatos
        fputcsv($out, ['FARMAFÉ - Planificación Logística PLEM (Horizonte Móvil 6 Meses)']);
        fputcsv($out, ['Versión del Plan', $ejec['version_plan']]);
        fputcsv($out, ['Fecha de Ejecución', $ejec['fecha_ejecucion']]);
        fputcsv($out, ['Escenario', $ejec['escenario_nombre']]);
        fputcsv($out, ['Política de Faltantes', $ejec['politica_faltante']]);
        fputcsv($out, ['Nivel de Servicio S* (Etapa 1)', number_format($ejec['nivel_servicio_optimo_s1'] * 100, 2) . '%']);
        fputcsv($out, ['Costo Logístico Total (Etapa 2)', 'Bs ' . number_format($ejec['costo_logistico_total_s2'], 2, ',', '.')]);
        fputcsv($out, ['Hash de Entradas (SHA-256)', $ejec['hash_entradas']]);
        fputcsv($out, ['Solver / Motor', $ejec['solver_usado']]);
        fputcsv($out, []);

        // 1. Compras recomendadas
        fputcsv($out, ['--- COMPRAS RECOMENDADAS POR PERIODO Y PROVEEDOR ---']);
        fputcsv($out, ['Periodo', 'Código Medicamento', 'Descripción', 'Proveedor', 'Cantidad Propuesta', 'Costo Unitario (Bs)', 'Subtotal (Bs)', 'Lead Time (Días)', 'Periodo Llegada', 'Naturaleza']);

        $pStmt = $db->prepare("
            SELECT pc.*, p.codigo AS prod_codigo, p.descripcion AS prod_desc, prv.razon_social AS prov_nombre
            FROM planes_compras pc
            JOIN productos p ON pc.id_producto = p.id_producto
            JOIN proveedores prv ON pc.id_proveedor = prv.id_proveedor
            WHERE pc.id_ejecucion = :id
            ORDER BY pc.periodo_t ASC, p.codigo ASC
        ");
        $pStmt->execute(['id' => $executionId]);
        while ($row = $pStmt->fetch()) {
            fputcsv($out, [
                $row['periodo_nombre'],
                $row['prod_codigo'],
                $row['prod_desc'],
                $row['prov_nombre'],
                $row['cantidad_propuesta'],
                number_format($row['costo_unitario_bs'], 4, ',', '.'),
                number_format($row['subtotal_bs'], 2, ',', '.'),
                $row['lead_time_dias'],
                'Mes ' . $row['periodo_llegada_t'],
                'DERIVED'
            ]);
        }

        fputcsv($out, []);

        // 2. Proyección de Inventario
        fputcsv($out, ['--- PROYECCIÓN DE INVENTARIO Y LOTES ---']);
        fputcsv($out, ['Periodo', 'Código Medicamento', 'Descripción', 'Lote', 'Saldo Inicial', 'Entradas Compras', 'Asignación Demanda', 'Bajas Vencimiento', 'Saldo Final', 'Naturaleza']);

        $ipStmt = $db->prepare("
            SELECT ip.*, p.codigo AS prod_codigo, p.descripcion AS prod_desc, l.codigo_lote
            FROM inventario_proyectado ip
            JOIN productos p ON ip.id_producto = p.id_producto
            LEFT JOIN lotes l ON ip.id_lote = l.id_lote
            WHERE ip.id_ejecucion = :id
            ORDER BY ip.periodo_t ASC, p.codigo ASC
        ");
        $ipStmt->execute(['id' => $executionId]);
        while ($row = $ipStmt->fetch()) {
            fputcsv($out, [
                $row['periodo_nombre'],
                $row['prod_codigo'],
                $row['prod_desc'],
                $row['codigo_lote'] ?? 'Proyección Compras',
                $row['saldo_inicial'],
                $row['recepciones_programadas'],
                $row['demanda_asignada'],
                $row['cantidad_vencida'],
                $row['saldo_final'],
                'DERIVED'
            ]);
        }

        fputcsv($out, []);

        // 3. Atención de Demanda
        fputcsv($out, ['--- ATENCIÓN DE DEMANDA POR PERIODO ---']);
        fputcsv($out, ['Periodo', 'Código Medicamento', 'Descripción', 'Demanda Solicitada', 'Atendida', 'Pendiente (Backorder)', 'Perdida', 'Cumplimiento (%)', 'Naturaleza']);

        $drStmt = $db->prepare("
            SELECT dr.*, p.codigo AS prod_codigo, p.descripcion AS prod_desc
            FROM demanda_resultado_plan dr
            JOIN productos p ON dr.id_producto = p.id_producto
            WHERE dr.id_ejecucion = :id
            ORDER BY dr.periodo_t ASC, p.codigo ASC
        ");
        $drStmt->execute(['id' => $executionId]);
        while ($row = $drStmt->fetch()) {
            fputcsv($out, [
                $row['periodo_nombre'],
                $row['prod_codigo'],
                $row['prod_desc'],
                $row['cantidad_solicitada'],
                $row['cantidad_atendida'],
                $row['cantidad_pendiente'],
                $row['cantidad_perdida'],
                number_format($row['porcentaje_atencion'], 2, ',', '.') . '%',
                'DERIVED'
            ]);
        }

        fclose($out);
        exit;
    }
}

