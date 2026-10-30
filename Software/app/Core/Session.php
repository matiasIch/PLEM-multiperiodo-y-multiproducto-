<?php

namespace App\Core;

class Session
{
    private static bool $started = false;

    public static function start(array $config = []): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $sessionConfig = $config['session'] ?? [];
        $cookieName = $sessionConfig['cookie_name'] ?? 'farmafe_session';
        $lifetime = (int)($sessionConfig['lifetime'] ?? 28800);
        $path = $sessionConfig['path'] ?? '/';
        $domain = $sessionConfig['domain'] ?? '';
        $secure = !empty($sessionConfig['secure']) || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
        $httpOnly = true;
        $sameSite = $sessionConfig['samesite'] ?? 'Lax';

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_name($cookieName);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite
        ]);

        session_start();
        self::$started = true;

        if (empty($_SESSION['csrf_token'])) {
            self::regenerateCsrfToken();
        }
    }

    public static function regenerateCsrfToken(): string
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    public static function getCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            return self::regenerateCsrfToken();
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function setFlash(string $type, string $message): void
    {
        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }
        $_SESSION['_flash'][$type] = $message;
    }

    public static function getFlashes(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            session_destroy();
            self::$started = false;
        }
    }
}
