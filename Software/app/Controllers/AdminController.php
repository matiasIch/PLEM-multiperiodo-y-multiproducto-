<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Audit;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use Exception;

class AdminController
{
    public function parametros()
    {
        Auth::requireRole(['ADMIN', 'GERENCIA']);
        $db = Database::getConnection();

        $stmt = $db->query("SELECT * FROM costos_escenario ORDER BY categoria ASC, id_costo ASC");
        $parametros = $stmt->fetchAll();

        View::render('pages/parametros', [
            'title' => 'Parámetros y Costos de Escenario',
            'parametros' => $parametros
        ]);
    }

    public function guardarParametros()
    {
        Auth::requireRole(['ADMIN']);
        $db = Database::getConnection();

        if (empty($_POST['valores']) || !is_array($_POST['valores'])) {
            Session::setFlash('error', 'No se recibieron datos de parámetros.');
            Router::redirect('/app/parametros');
            return;
        }

        $db->beginTransaction();
        try {
            $upd = $db->prepare("UPDATE costos_escenario SET valor = :val, naturaleza_dato = 'ASSUMPTION' WHERE nombre_parametro = :param");

            foreach ($_POST['valores'] as $param => $val) {
                $numVal = (float)$val;
                $upd->execute(['val' => $numVal, 'param' => $param]);
            }

            Audit::log('MODIFICAR_PARAMETROS', 'costos_escenario', null, null, $_POST['valores'], 'Ajuste de costos y supuestos de escenario PLEM');

            $db->commit();
            Session::setFlash('success', 'Parámetros y costos de escenario actualizados correctamente.');
        } catch (Exception $e) {
            $db->rollBack();
            Session::setFlash('error', 'Error al guardar los parámetros: ' . $e->getMessage());
        }

        Router::redirect('/app/parametros');
    }

    public function usuarios()
    {
        Auth::requireRole(['ADMIN']);
        $db = Database::getConnection();

        $uStmt = $db->query("
            SELECT u.*, r.codigo_rol, r.nombre AS rol_nombre
            FROM usuarios u
            JOIN roles r ON u.id_rol = r.id_rol
            ORDER BY u.id_usuario ASC
        ");
        $usuarios = $uStmt->fetchAll();

        $roles = $db->query("SELECT * FROM roles WHERE activo = 1 ORDER BY id_rol ASC")->fetchAll();

        View::render('pages/usuarios', [
            'title' => 'Gestión de Usuarios y Roles',
            'usuarios' => $usuarios,
            'roles' => $roles
        ]);
    }

    public function crearUsuario()
    {
        Auth::requireRole(['ADMIN']);
        $db = Database::getConnection();

        $username = trim($_POST['username'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $roleId = (int)($_POST['role_id'] ?? 0);
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($fullName) || empty($email) || empty($password) || $roleId <= 0) {
            Session::setFlash('error', 'Todos los campos son obligatorios.');
            Router::redirect('/app/administracion/usuarios');
            return;
        }

        if (strlen($password) < 8) {
            Session::setFlash('error', 'La contraseña debe tener al menos 8 caracteres.');
            Router::redirect('/app/administracion/usuarios');
            return;
        }

        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO usuarios (nombre_usuario, password_hash, nombre_completo, email, id_rol, activo, creado_en)
                VALUES (:u, :p, :name, :email, :r, 1, NOW())
            ");
            $stmt->execute([
                'u' => $username,
                'p' => $hash,
                'name' => $fullName,
                'email' => $email,
                'r' => $roleId
            ]);

            $newId = $db->lastInsertId();
            Audit::log('CREAR_USUARIO', 'usuarios', (string)$newId, null, ['usuario' => $username, 'rol' => $roleId], 'Alta de nuevo usuario del sistema');

            Session::setFlash('success', "Usuario '{$username}' creado exitosamente.");
        } catch (Exception $e) {
            Session::setFlash('error', 'Error al crear usuario: ' . $e->getMessage());
        }

        Router::redirect('/app/administracion/usuarios');
    }

    public function auditoria()
    {
        Auth::requireRole(['ADMIN']);
        $db = Database::getConnection();

        $stmt = $db->query("
            SELECT a.*, u.nombre_usuario, u.nombre_completo
            FROM auditoria_eventos a
            LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario
            ORDER BY a.id_auditoria DESC
            LIMIT 150
        ");
        $eventos = $stmt->fetchAll();

        View::render('pages/auditoria', [
            'title' => 'Registro de Auditoría del Sistema',
            'eventos' => $eventos
        ]);
    }
}

