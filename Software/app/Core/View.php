<?php

namespace App\Core;

class View
{
    private static string $viewsPath = '';

    public static function init(string $path): void
    {
        self::$viewsPath = rtrim($path, '/\\');
    }

    public static function render(string $template, array $data = [], string $layout = 'main'): void
    {
        $templateFile = self::$viewsPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $template) . '.php';

        if (!file_exists($templateFile)) {
            http_response_code(500);
            echo "Error: La vista '{$template}' no fue encontrada.";
            return;
        }

        // Extraer variables para la vista
        extract($data);

        // Renderizar contenido interno
        ob_start();
        require $templateFile;
        $content = ob_get_clean();

        if ($layout === 'none' || empty($layout)) {
            echo $content;
            return;
        }

        $layoutFile = self::$viewsPath . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $layout . '.php';
        if (!file_exists($layoutFile)) {
            echo $content;
            return;
        }

        // Variables globales para layout
        $currentUser = Auth::user();
        $flashes = Session::getFlashes();
        $csrfToken = Session::getCsrfToken();
        $pageTitle = $data['title'] ?? 'FARMAFÉ';

        require $layoutFile;
    }

    public static function escape(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function formatBs($amount, int $decimals = 2): string
    {
        if ($amount === null || $amount === '') {
            return '-';
        }
        return 'Bs ' . number_format((float)$amount, $decimals, ',', '.');
    }

    public static function formatNumber($number, int $decimals = 0): string
    {
        if ($number === null || $number === '') {
            return '-';
        }
        return number_format((float)$number, $decimals, ',', '.');
    }

    public static function formatDate(?string $date): string
    {
        if (empty($date)) {
            return '-';
        }
        $time = strtotime($date);
        return $time ? date('d/m/Y', $time) : $date;
    }

    public static function provenanceBadge(string $type): string
    {
        $type = strtoupper(trim($type));
        switch ($type) {
            case 'OBSERVED':
            case 'OBSERVADO':
                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300" title="Dato verificado de fuente real de FARMAFÉ">OBSERVADO</span>';
            case 'DERIVED':
            case 'DERIVADO':
                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-300" title="Calculado a partir de datos observados mediante metodo documentado">DERIVADO</span>';
            case 'SIMULATED':
            case 'SIMULADO':
                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-300" title="Registro generado para pruebas del prototipo">SIMULADO</span>';
            case 'ASSUMPTION':
            case 'SUPUESTO':
                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-900 border border-amber-300" title="Supuesto configurable de escenario por falta de informacion contable">SUPUESTO</span>';
            case 'PENDING_VALIDATION':
            case 'PENDIENTE_VALIDACION':
            default:
                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-yellow-100 text-yellow-900 border border-yellow-400" title="Dato o registro pendiente de confirmacion oficial">PENDIENTE</span>';
        }
    }
}
