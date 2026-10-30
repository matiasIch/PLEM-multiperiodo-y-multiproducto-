<?php

// Helpers globales para FARMAFÉ

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return \App\Core\View::escape($value);
    }
}

if (!function_exists('url')) {
    function url(string $path = '/'): string
    {
        return \App\Core\Router::url($path);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Core\Session::getCsrfToken();
    }
}

