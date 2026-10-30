<?php

namespace App\Core;

class Router
{
    private static array $routes = [];
    private static string $basePath = '';

    public static function setBasePath(string $basePath): void
    {
        self::$basePath = rtrim($basePath, '/');
    }

    public static function getBasePath(): string
    {
        return self::$basePath;
    }

    public static function get(string $path, array $handler, array $middleware = []): void
    {
        self::add('GET', $path, $handler, $middleware);
    }

    public static function post(string $path, array $handler, array $middleware = []): void
    {
        self::add('POST', $path, $handler, $middleware);
    }

    private static function add(string $method, string $path, array $handler, array $middleware): void
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        self::$routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public static function dispatch(string $uri, string $method): void
    {
        // Limpiar URI y eliminar base path
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        if (self::$basePath !== '' && strpos($path, self::$basePath) === 0) {
            $path = substr($path, strlen(self::$basePath));
        }

        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        $method = strtoupper($method);

        // Validacion CSRF para peticiones POST (salvo instalador si no hay sesion iniciada)
        if ($method === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!Session::validateCsrfToken($token)) {
                http_response_code(419);
                View::render('errors/419', [
                    'title' => 'Sesion o formulario expirado',
                    'message' => 'El token de seguridad (CSRF) no es valido o expiro. Por favor recargue la pagina e intente nuevamente.'
                ]);
                return;
            }
        }

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                $params = [];
                foreach ($matches as $key => $val) {
                    if (is_string($key)) {
                        $params[$key] = $val;
                    }
                }

                // Ejecutar middlewares si existen
                foreach ($route['middleware'] as $mw) {
                    if (is_callable($mw)) {
                        $result = call_user_func($mw);
                        if ($result === false) {
                            return;
                        }
                    }
                }

                [$controllerClass, $action] = $route['handler'];
                $controller = new $controllerClass();
                call_user_func_array([$controller, $action], $params);
                return;
            }
        }

        // Ruta no encontrada
        http_response_code(404);
        View::render('errors/404', [
            'title' => 'Pagina no encontrada',
            'message' => 'La ruta solicitada no existe o no se encuentra disponible.'
        ]);
    }

    public static function url(string $path = '/'): string
    {
        $cleanPath = '/' . ltrim($path, '/');
        return self::$basePath . $cleanPath;
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . self::url($path));
        exit;
    }
}

