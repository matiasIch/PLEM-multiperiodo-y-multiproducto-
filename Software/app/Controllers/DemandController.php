<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

class DemandController
{
    public function pedidos()
    {
        Auth::requireRole(['ADMIN', 'GERENCIA', 'VENTAS']);
        $db = Database::getConnection();

        $stmt = $db->query("
            SELECT p.*, c.codigo_anonimizado, c.tipo_cliente, c.departamento AS cliente_dpto,
                   COUNT(pd.id_detalle_pedido) AS lineas,
                   COALESCE(SUM(pd.cantidad_solicitada), 0) AS total_solicitado,
                   COALESCE(SUM(pd.cantidad_atendida), 0) AS total_atendido
            FROM pedidos p
            JOIN clientes c ON p.id_cliente = c.id_cliente
            LEFT JOIN pedido_detalles pd ON p.id_pedido = pd.id_pedido
            GROUP BY p.id_pedido
            ORDER BY p.id_pedido DESC
            LIMIT 100
        ");
        $pedidos = $stmt->fetchAll();

        View::render('pages/pedidos', [
            'title' => 'Gestión de Pedidos de Clientes',
            'pedidos' => $pedidos
        ]);
    }

    public function demanda()
    {
        Auth::requireRole(['ADMIN', 'GERENCIA', 'VENTAS']);
        $db = Database::getConnection();

        $stmt = $db->query("
            SELECT dp.*, p.codigo AS prod_codigo, p.descripcion AS prod_desc, p.clasificacion_abc
            FROM demanda_periodica dp
            JOIN productos p ON dp.id_producto = p.id_producto
            ORDER BY dp.periodo_secuencial ASC, p.codigo ASC
            LIMIT 150
        ");
        $demandas = $stmt->fetchAll();

        $totDemanda = (int)$db->query("SELECT COALESCE(SUM(cantidad), 0) FROM demanda_periodica")->fetchColumn();

        View::render('pages/demanda', [
            'title' => 'Demanda Periódica por Medicamento',
            'demandas' => $demandas,
            'totalDemanda' => $totDemanda
        ]);
    }
}

