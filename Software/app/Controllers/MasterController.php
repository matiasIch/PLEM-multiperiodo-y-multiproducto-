<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

class MasterController
{
    public function medicamentos()
    {
        Auth::requireRole(['ADMIN', 'GERENCIA', 'VENTAS', 'ALMACEN']);
        $db = Database::getConnection();

        $search = trim($_GET['q'] ?? '');
        $abc = trim($_GET['abc'] ?? '');

        $sql = "SELECT * FROM productos WHERE activo = 1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (codigo LIKE :q1 OR descripcion LIKE :q2)";
            $params['q1'] = "%{$search}%";
            $params['q2'] = "%{$search}%";
        }

        if ($abc !== '' && in_array($abc, ['A', 'B', 'C'])) {
            $sql .= " AND clasificacion_abc = :abc";
            $params['abc'] = $abc;
        }

        $sql .= " ORDER BY codigo ASC LIMIT 100";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $productos = $stmt->fetchAll();

        // Conteo total
        $cntStmt = $db->query("SELECT COUNT(*) FROM productos WHERE activo = 1");
        $totalProductos = (int)$cntStmt->fetchColumn();

        View::render('pages/medicamentos', [
            'title' => 'Catálogo de Medicamentos',
            'productos' => $productos,
            'totalProductos' => $totalProductos,
            'search' => $search,
            'abc' => $abc
        ]);
    }

    public function proveedores()
    {
        Auth::requireRole(['ADMIN', 'GERENCIA', 'ALMACEN']);
        $db = Database::getConnection();

        $provStmt = $db->query("SELECT * FROM proveedores WHERE activo = 1 ORDER BY codigo ASC");
        $proveedores = $provStmt->fetchAll();

        // Pares producto-proveedor
        $ppStmt = $db->query("
            SELECT pp.*, p.codigo AS prod_codigo, p.descripcion AS prod_desc, prv.razon_social AS prov_nombre
            FROM producto_proveedor pp
            JOIN productos p ON pp.id_producto = p.id_producto
            JOIN proveedores prv ON pp.id_proveedor = prv.id_proveedor
            WHERE pp.activo = 1
            ORDER BY prv.codigo ASC, p.codigo ASC
            LIMIT 100
        ");
        $pares = $ppStmt->fetchAll();

        $cntPP = (int)$db->query("SELECT COUNT(*) FROM producto_proveedor WHERE activo = 1")->fetchColumn();

        View::render('pages/proveedores', [
            'title' => 'Proveedores y Oferta Comercial',
            'proveedores' => $proveedores,
            'pares' => $pares,
            'totalPares' => $cntPP
        ]);
    }

    public function clientes()
    {
        Auth::requireRole(['ADMIN', 'GERENCIA', 'VENTAS']);
        $db = Database::getConnection();

        $cStmt = $db->query("SELECT * FROM clientes WHERE activo = 1 ORDER BY codigo_anonimizado ASC LIMIT 100");
        $clientes = $cStmt->fetchAll();
        $total = (int)$db->query("SELECT COUNT(*) FROM clientes WHERE activo = 1")->fetchColumn();

        View::render('pages/clientes', [
            'title' => 'Clientes (Códigos Anonimizados)',
            'clientes' => $clientes,
            'totalClientes' => $total
        ]);
    }

    public function ubicaciones()
    {
        Auth::requireRole(['ADMIN', 'GERENCIA', 'ALMACEN']);
        $db = Database::getConnection();

        $uStmt = $db->query("SELECT * FROM zonas_almacenamiento WHERE activo = 1 ORDER BY codigo ASC");
        $zonas = $uStmt->fetchAll();

        View::render('pages/ubicaciones', [
            'title' => 'Zonas y Capacidad de Almacén',
            'zonas' => $zonas
        ]);
    }
}
