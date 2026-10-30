<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

class EvaluationController
{
    public function comparaciones()
    {
        Auth::requireRole(['ADMIN', 'GERENCIA']);
        $db = Database::getConnection();

        $stmt = $db->query("
            SELECT ep.*, esc.nombre AS escenario_nombre, esc.horizonte_meses
            FROM ejecuciones_plem ep
            JOIN escenarios_planificacion esc ON ep.id_escenario = esc.id_escenario
            ORDER BY ep.id_ejecucion DESC
        ");
        $planes = $stmt->fetchAll();

        // Comparaciones guardadas
        $cStmt = $db->query("
            SELECT cp.*, b.version_plan AS version_base, ev.version_plan AS version_eval, u.nombre_usuario
            FROM comparaciones_planes cp
            JOIN ejecuciones_plem b ON cp.id_ejecucion_base = b.id_ejecucion
            JOIN ejecuciones_plem ev ON cp.id_ejecucion_evaluada = ev.id_ejecucion
            LEFT JOIN usuarios u ON cp.id_usuario = u.id_usuario
            ORDER BY cp.id_comparacion DESC
        ");
        $comparaciones = $cStmt->fetchAll();

        View::render('pages/comparaciones', [
            'title' => 'Evaluación Retrospectiva y Comparación AS-IS / TO-BE',
            'planes' => $planes,
            'comparaciones' => $comparaciones
        ]);
    }
}

