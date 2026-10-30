<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Services\Import\ImportService;
use Exception;

class ImportController
{
    private ImportService $importService;

    public function __construct()
    {
        $this->importService = new ImportService();
    }

    public function index()
    {
        Auth::requireRole(['ADMIN', 'GERENCIA']);
        $db = Database::getConnection();

        $stmt = $db->query("
            SELECT i.*, u.nombre_usuario
            FROM importaciones i
            LEFT JOIN usuarios u ON i.id_usuario = u.id_usuario
            ORDER BY i.id_importacion DESC
        ");
        $importaciones = $stmt->fetchAll();

        View::render('pages/importaciones', [
            'title' => 'Centro de Importación y Staging',
            'importaciones' => $importaciones
        ]);
    }

    public function upload()
    {
        Auth::requireRole(['ADMIN']);

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Session::setFlash('error', 'Debe seleccionar un archivo válido (CSV o XLSX).');
            Router::redirect('/app/importaciones');
            return;
        }

        $datasetType = $_POST['dataset_type'] ?? 'PRODUCTOS';
        $nature = $_POST['nature'] ?? 'OBSERVED';
        $sheetName = !empty($_POST['sheet_name']) ? trim($_POST['sheet_name']) : null;

        try {
            $uploadInfo = $this->importService->handleFileUpload($_FILES['file'], $datasetType, $nature);
            $stageInfo = $this->importService->processAndStage($uploadInfo['id_importacion'], $sheetName);

            Session::setFlash('success', "Archivo '{$uploadInfo['nombre_original']}' cargado a staging con {$stageInfo['total']} filas ({$stageInfo['valid']} válidas, {$stageInfo['errors']} errores). Revise la previsualización antes de confirmar.");
            Router::redirect('/app/importaciones/preview/' . $uploadInfo['id_importacion']);
        } catch (Exception $e) {
            Session::setFlash('error', 'Error en la importación: ' . $e->getMessage());
            Router::redirect('/app/importaciones');
        }
    }

    public function preview(int $id)
    {
        Auth::requireRole(['ADMIN', 'GERENCIA']);
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM importaciones WHERE id_importacion = :id");
        $stmt->execute(['id' => $id]);
        $importacion = $stmt->fetch();
        if (!$importacion) {
            Session::setFlash('error', 'Importación no encontrada.');
            Router::redirect('/app/importaciones');
            return;
        }

        // Obtener primeras 100 filas de staging
        $fStmt = $db->prepare("SELECT * FROM importacion_filas WHERE id_importacion = :id ORDER BY numero_fila ASC LIMIT 100");
        $fStmt->execute(['id' => $id]);
        $filas = $fStmt->fetchAll();

        View::render('pages/importaciones_preview', [
            'title' => 'Previsualización y Validación de Importación #' . $id,
            'importacion' => $importacion,
            'filas' => $filas
        ]);
    }

    public function confirm(int $id)
    {
        Auth::requireRole(['ADMIN']);

        try {
            $res = $this->importService->confirmImport($id);
            Session::setFlash('success', "Importación confirmada e incorporada exitosamente: {$res['inserted']} registros guardados en tablas maestras.");
            Router::redirect('/app/importaciones');
        } catch (Exception $e) {
            Session::setFlash('error', 'Error al confirmar la importación: ' . $e->getMessage());
            Router::redirect('/app/importaciones/preview/' . $id);
        }
    }
}

