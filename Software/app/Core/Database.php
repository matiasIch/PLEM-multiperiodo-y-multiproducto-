<?php

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            if (empty(self::$config)) {
                $appConfig = require dirname(__DIR__, 2) . '/config/config.php';
                self::$config = $appConfig['database'];
            }

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                self::$config['host'] ?? '127.0.0.1',
                self::$config['port'] ?? 3306,
                self::$config['database'] ?? 'farmafe_app',
                self::$config['charset'] ?? 'utf8mb4'
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, self::$config['username'] ?? 'root', self::$config['password'] ?? '', $options);
            } catch (PDOException $e) {
                // Log safe error without credentials
                error_log("Database connection error: " . $e->getMessage());
                throw new RuntimeException("No se pudo conectar a la base de datos farmafe_app. Verifique la configuracion local de XAMPP.");
            }
        }

        return self::$instance;
    }

    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    public static function rollBack(): bool
    {
        if (self::getConnection()->inTransaction()) {
            return self::getConnection()->rollBack();
        }
        return false;
    }
}

