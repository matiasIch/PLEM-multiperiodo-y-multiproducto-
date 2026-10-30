<?php

namespace App\Core;

use PDO;

class Auth
{
    private static ?array $currentUser = null;

    public static function check(): bool
    {
        return Session::has('user_id');
    }

    public static function id(): ?int
    {
        return Session::get('user_id');
    }

    public static function user(): ?array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        if (!self::check()) {
            return null;
        }

        $userId = self::id();
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.id_usuario, u.nombre_usuario, u.nombre_completo, u.email, u.id_rol, u.activo,
                   r.codigo_rol, r.nombre AS rol_nombre
            FROM usuarios u
            JOIN roles r ON u.id_rol = r.id_rol
            WHERE u.id_usuario = :id AND u.activo = 1
            LIMIT 1
        ");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            self::logout();
            return null;
        }

        self::$currentUser = $user;
        return self::$currentUser;
    }

    public static function attempt(string $username, string $password): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.id_usuario, u.nombre_usuario, u.password_hash, u.nombre_completo, u.email, u.id_rol, u.activo,
                   r.codigo_rol, r.nombre AS rol_nombre
            FROM usuarios u
            JOIN roles r ON u.id_rol = r.id_rol
            WHERE u.nombre_usuario = :username
            LIMIT 1
        ");
        $stmt->execute(['username' => trim($username)]);
        $user = $stmt->fetch();

        if (!$user) {
            return false;
        }

        if ((int)$user['activo'] !== 1) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Regenerar ID de sesion para prevenir fijacion de sesion
        Session::regenerate();
        Session::set('user_id', (int)$user['id_usuario']);
        Session::set('user_role', $user['codigo_rol']);
        Session::set('user_name', $user['nombre_completo']);

        // Actualizar ultimo acceso
        $updateStmt = $db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = :id");
        $updateStmt->execute(['id' => $user['id_usuario']]);

        // Registrar auditoria
        Audit::log('LOGIN', 'usuarios', (string)$user['id_usuario'], null, null, 'Inicio de sesion exitoso');

        self::$currentUser = $user;
        return true;
    }

    public static function logout(): void
    {
        $userId = self::id();
        if ($userId) {
            Audit::log('LOGOUT', 'usuarios', (string)$userId, null, null, 'Cierre de sesion voluntario');
        }
        self::$currentUser = null;
        Session::destroy();
    }

    public static function hasRole($roles): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        $userRole = $user['codigo_rol'];
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return in_array($userRole, $roles, true);
    }

    public static function requireRole($roles): void
    {
        if (!self::check()) {
            Session::setFlash('error', 'Debe iniciar sesion para acceder a este recurso.');
            header('Location: ' . Router::url('/login'));
            exit;
        }

        if (!self::hasRole($roles)) {
            http_response_code(403);
            View::render('errors/403', [
                'title' => 'Acceso denegado',
                'message' => 'Su rol actual no cuenta con autorizacion para acceder a este modulo.'
            ]);
            exit;
        }
    }

    public static function isInitialSetupNeeded(): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT COUNT(*) AS total FROM usuarios");
            $row = $stmt->fetch();
            return ((int)($row['total'] ?? 0)) === 0;
        } catch (\Exception $e) {
            return true;
        }
    }

    public static function createInitialAdmin(string $username, string $password, string $fullName, string $email): array
    {
        if (!self::isInitialSetupNeeded()) {
            return ['success' => false, 'error' => 'El sistema ya cuenta con un usuario administrador registrado.'];
        }

        if (strlen($username) < 4) {
            return ['success' => false, 'error' => 'El nombre de usuario debe contener al menos 4 caracteres.'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'La contrasena debe tener al menos 8 caracteres para garantizar la seguridad.'];
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            // Obtener rol ADMIN
            $roleStmt = $db->query("SELECT id_rol FROM roles WHERE codigo_rol = 'ADMIN' LIMIT 1");
            $role = $roleStmt->fetch();
            $roleId = $role ? (int)$role['id_rol'] : 1;

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("
                INSERT INTO usuarios (nombre_usuario, password_hash, nombre_completo, email, id_rol, activo, creado_en)
                VALUES (:user, :hash, :name, :email, :rol, 1, NOW())
            ");
            $stmt->execute([
                'user' => trim($username),
                'hash' => $hash,
                'name' => trim($fullName),
                'email' => trim($email),
                'rol' => $roleId
            ]);

            $adminId = $db->lastInsertId();

            // Marcar instalacion completada
            $cfgStmt = $db->prepare("UPDATE configuracion_sistema SET valor = '1' WHERE clave = 'sistema_instalado'");
            $cfgStmt->execute();

            // Auditoria
            Audit::log('SETUP_ADMIN', 'usuarios', (string)$adminId, null, [
                'usuario' => $username,
                'nombre' => $fullName,
                'email' => $email
            ], 'Creacion inicial de cuenta administrador en primer uso');

            $db->commit();
            return ['success' => true, 'id' => $adminId];
        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'error' => 'Error al registrar el administrador: ' . $e->getMessage()];
        }
    }
}

