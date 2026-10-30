<?php
// =============================================================================
// FARMAFÉ - Configuracion unificada del sistema
// Carga la configuracion base y aplica config.local.php si existe
// =============================================================================

$baseConfig = require __DIR__ . '/config.example.php';
$localConfigPath = __DIR__ . '/config.local.php';

if (file_exists($localConfigPath)) {
    $localConfig = require $localConfigPath;
    if (is_array($localConfig)) {
        $baseConfig = array_replace_recursive($baseConfig, $localConfig);
    }
}

return $baseConfig;

