<?php

namespace App\Core;

class Audit
{
    public static function log(
        string $accion,
        string $entidad,
        ?string $idEntidad = null,
        $valoresPrevios = null,
        $valoresNuevos = null,
        ?string $motivo = null
    ): void {
        try {
            $db = Database::getConnection();
            $userId = Auth::id();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $prevJson = $valoresPrevios !== null ? json_encode($valoresPrevios, JSON_UNESCAPED_UNICODE) : null;
            $newJson = $valoresNuevos !== null ? json_encode($valoresNuevos, JSON_UNESCAPED_UNICODE) : null;

            $stmt = $db->prepare("
                INSERT INTO auditoria_eventos (id_usuario, accion, entidad, id_entidad, valores_previos, valores_nuevos, motivo, ip_origen)
                VALUES (:user_id, :accion, :entidad, :id_entidad, :prev, :new, :motivo, :ip)
            ");
            $stmt->execute([
                'user_id' => $userId,
                'accion' => strtoupper($accion),
                'entidad' => strtolower($entidad),
                'id_entidad' => $idEntidad,
                'prev' => $prevJson,
                'new' => $newJson,
                'motivo' => $motivo,
                'ip' => $ip
            ]);
        } catch (\Exception $e) {
            error_log("Fallo al registrar auditoria: " . $e->getMessage());
        }
    }
}

