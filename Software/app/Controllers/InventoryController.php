<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Audit;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use PDO;

class InventoryController
{
    public function compras()
    {
        Auth::requireRole(['ADMIN', 'GERENCIA', 'ALMACEN']);
        $db = Database::getConnection();

        $stmt = $db->query("
            SELECT oc.*, prv.razon_social AS prov_nombre, prv.codigo AS prov_codigo,
                   COUNT(ocd.id_detalle_compra) AS lineas_totales,
                   COALESCE(SUM(ocd.subtotal_bs), 0) AS monto_total_bs
            FROM ordenes_compra oc
            JOIN proveedores prv ON oc.id_proveedor = prv.id_proveedor
            LEFT JOIN orden_compra_detalles ocd ON oc.id_orden = ocd.id_orden
            GROUP BY oc.id_orden
            ORDER BY oc.id_orden DESC
            LIMIT 100
        ");
        $ordenes = $stmt->fetchAll();

        // Proveedores para modal de nueva orden
        $provs = $db->query("SELECT id_proveedor, codigo, razon_social FROM proveedores WHERE activo = 1 ORDER BY razon_social ASC")->fetchAll();
        $prods = $db->query("SELECT id_producto, codigo, descripcion, costo_unitario_promedio_bs FROM productos WHERE activo = 1 ORDER BY codigo ASC LIMIT 100")->fetchAll();

        View::render('pages/compras', [
            'title' => 'Gestión de Órdenes de Compra',
            'ordenes' => $ordenes,
            'proveedores' => $provs,
            'productos' => $prods
        ]);
    }

    public function toggleCompromiso(int $id)
    {
        Auth::requireRole(['ADMIN', 'GERENCIA']);
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT id_orden, codigo_orden, es_comprometida FROM ordenes_compra WHERE id_orden = :id");
        $stmt->execute(['id' => $id]);
        $oc = $stmt->fetch();
        if (!$oc) {
            Session::setFlash('error', 'Orden de compra no encontrada.');
            Router::redirect('/app/compras');
            return;
        }

        $newVal = (int)$oc['es_comprometida'] === 1 ? 0 : 1;
        $upd = $db->prepare("UPDATE ordenes_compra SET es_comprometida = :val WHERE id_orden = :id");
        $upd->execute(['val' => $newVal, 'id' => $id]);

        Audit::log('CAMBIAR_COMPROMISO_OC', 'ordenes_compra', (string)$id, ['comprometida' => $oc['es_comprometida']], ['comprometida' => $newVal], 'Cambio de estado de compromiso operativo');

        Session::setFlash('success', 'El estado de compromiso de la orden ' . $oc['codigo_orden'] . ' fue actualizado.');
        Router::redirect('/app/compras');
    }

    public function recepciones()
    {
        Auth::requireRole(['ADMIN', 'ALMACEN']);
        $db = Database::getConnection();

        $rStmt = $db->query("
            SELECT r.*, oc.codigo_orden, prv.razon_social AS prov_nombre,
                   COUNT(rd.id_detalle_recepcion) AS items_recibidos,
                   COALESCE(SUM(rd.cantidad_recibida), 0) AS total_unidades
            FROM recepciones r
            LEFT JOIN ordenes_compra oc ON r.id_orden = oc.id_orden
            LEFT JOIN proveedores prv ON oc.id_proveedor = prv.id_proveedor
            LEFT JOIN recepcion_detalles rd ON r.id_recepcion = rd.id_recepcion
            GROUP BY r.id_recepcion
            ORDER BY r.id_recepcion DESC
            LIMIT 100
        ");
        $recepciones = $rStmt->fetchAll();

        View::render('pages/recepciones', [
            'title' => 'Recepciones de Almacén',
            'recepciones' => $recepciones
        ]);
    }

    public function lotes()
    {
        Auth::requireRole(['ADMIN', 'GERENCIA', 'ALMACEN']);
        $db = Database::getConnection();

        $search = trim($_GET['q'] ?? '');
        $estado = trim($_GET['estado'] ?? '');

        $sql = "
            SELECT l.*, p.codigo AS prod_codigo, p.descripcion AS prod_desc, p.clasificacion_abc,
                   DATEDIFF(l.fecha_vencimiento, CURDATE()) AS dias_para_vencer,
                   z.nombre AS zona_nombre
            FROM lotes l
            JOIN productos p ON l.id_producto = p.id_producto
            LEFT JOIN zonas_almacenamiento z ON l.id_zona = z.id_zona
            WHERE 1=1
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (p.codigo LIKE :q1 OR p.descripcion LIKE :q2 OR l.codigo_lote LIKE :q3)";
            $params['q1'] = "%{$search}%";
            $params['q2'] = "%{$search}%";
            $params['q3'] = "%{$search}%";
        }

        if ($estado !== '') {
            $sql .= " AND l.estado_lote = :st";
            $params['st'] = $estado;
        }

        // Orden FEFO: First Expired, First Out
        $sql .= " ORDER BY l.fecha_vencimiento ASC, l.saldo_actual DESC LIMIT 150";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $lotes = $stmt->fetchAll();

        // Resumen
        $totLotes = (int)$db->query("SELECT COUNT(*) FROM lotes")->fetchColumn();
        $totStock = (int)$db->query("SELECT COALESCE(SUM(saldo_actual), 0) FROM lotes WHERE estado_lote = 'DISPONIBLE'")->fetchColumn();

        View::render('pages/lotes', [
            'title' => 'Gestión de Lotes y Política FEFO',
            'lotes' => $lotes,
            'totalLotes' => $totLotes,
            'totalStock' => $totStock,
            'search' => $search,
            'estado' => $estado
        ]);
    }

    public function movimientos()
    {
        Auth::requireRole(['ADMIN', 'ALMACEN', 'GERENCIA']);
        $db = Database::getConnection();

        $stmt = $db->query("
            SELECT m.*, p.codigo AS prod_codigo, p.descripcion AS prod_desc, l.codigo_lote, u.nombre_usuario
            FROM inventario_movimientos m
            JOIN productos p ON m.id_producto = p.id_producto
            LEFT JOIN lotes l ON m.id_lote = l.id_lote
            LEFT JOIN usuarios u ON m.id_usuario = u.id_usuario
            ORDER BY m.id_movimiento DESC
            LIMIT 100
        ");
        $movimientos = $stmt->fetchAll();

        View::render('pages/movimientos', [
            'title' => 'Libro Diario de Movimientos de Inventario',
            'movimientos' => $movimientos
        ]);
    }
}

