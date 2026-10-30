<?php

namespace App\Services\Import;

use App\Core\Database;
use App\Core\Audit;
use App\Core\Auth;
use PDO;
use RuntimeException;

class ImportService
{
    private string $storagePath;

    public function __construct()
    {
        $appConfig = require dirname(__DIR__, 3) . '/config/config.php';
        $this->storagePath = $appConfig['storage']['imports'];
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function handleFileUpload(array $file, string $datasetType, string $nature = 'OBSERVED'): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Error al cargar el archivo en el servidor (Codigo: {$file['error']}).");
        }

        $origName = basename($file['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'txt', 'xlsx', 'json'])) {
            throw new RuntimeException("Formato no admitido (.{$ext}). Solo se admiten archivos CSV, XLSX o JSON.");
        }

        $hash = hash_file('sha256', $file['tmp_name']);
        $storedName = 'imp_' . date('Ymd_His') . '_' . substr($hash, 0, 10) . '.' . $ext;
        $destPath = $this->storagePath . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new RuntimeException("No se pudo almacenar el archivo cargado en el directorio de importaciones.");
        }

        $fileSize = filesize($destPath);
        $userId = Auth::id();

        // Extraer hojas si es Excel, o parsear cabeceras si es CSV
        $sheets = [];
        $sampleHeaders = [];
        if ($ext === 'xlsx') {
            $sheetInfo = $this->inspectXlsx($destPath);
            $sheets = $sheetInfo['sheets'] ?? ['Hoja1'];
            $sampleHeaders = $sheetInfo['headers'] ?? [];
        } elseif (in_array($ext, ['csv', 'txt'])) {
            $sheets = ['CSV_DATA'];
            $sampleHeaders = $this->readCsvHeaders($destPath);
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO importaciones (
                nombre_archivo_original, nombre_archivo_almacenado, hash_sha256, tamano_bytes,
                formato, tipo_dataset, hoja, filas_totales, filas_validas, filas_con_error,
                estado, naturaleza_general, id_usuario, creado_en
            ) VALUES (
                :orig, :stored, :hash, :size,
                :fmt, :dst, :sheet, 0, 0, 0,
                'CARGADO', :nature, :user_id, NOW()
            )
        ");

        $stmt->execute([
            'orig' => $origName,
            'stored' => $storedName,
            'hash' => $hash,
            'size' => $fileSize,
            'fmt' => strtoupper($ext),
            'dst' => strtoupper($datasetType),
            'sheet' => $sheets[0] ?? null,
            'nature' => $nature,
            'user_id' => $userId
        ]);

        $importId = (int)$db->lastInsertId();

        Audit::log('CARGAR_ARCHIVO', 'importaciones', (string)$importId, null, [
            'archivo' => $origName,
            'formato' => $ext,
            'dataset' => $datasetType,
            'naturaleza' => $nature
        ], 'Carga de archivo fuente en staging');

        return [
            'id_importacion' => $importId,
            'nombre_original' => $origName,
            'formato' => $ext,
            'hojas' => $sheets,
            'headers' => $sampleHeaders
        ];
    }

    private function readCsvHeaders(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }
        $h = fopen($filePath, 'r');
        if (!$h) {
            return [];
        }
        $line = fgetcsv($h, 4096, ',');
        if (!$line || count($line) <= 1) {
            rewind($h);
            $line = fgetcsv($h, 4096, ';');
        }
        fclose($h);
        return $line ? array_map('trim', $line) : [];
    }

    private function inspectXlsx(string $filePath): array
    {
        $appConfig = require dirname(__DIR__, 3) . '/config/config.php';
        $pythonBin = $appConfig['solver']['python_bin'] ?? 'python';

        $script = <<<PY
import sys, json, openpyxl
try:
    wb = openpyxl.load_workbook(sys.argv[1], read_only=True)
    sheets = wb.sheetnames
    headers = []
    if sheets:
        s = wb[sheets[0]]
        for r in s.iter_rows(max_row=5, values_only=True):
            if any(r):
                headers = [str(c).strip() for c in r if c is not None]
                break
    print(json.dumps({'sheets': sheets, 'headers': headers}))
except Exception as e:
    print(json.dumps({'error': str(e), 'sheets': ['Hoja1'], 'headers': []}))
PY;

        $tempPy = dirname($filePath) . '/inspect_' . time() . '.py';
        file_put_contents($tempPy, $script);

        $cmd = sprintf('"%s" "%s" "%s"', $pythonBin, $tempPy, $filePath);
        $output = shell_exec($cmd);
        @unlink($tempPy);

        $data = json_decode($output, true);
        return is_array($data) ? $data : ['sheets' => ['Hoja1'], 'headers' => []];
    }

    public function processAndStage(int $importId, ?string $sheetName = null): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM importaciones WHERE id_importacion = :id");
        $stmt->execute(['id' => $importId]);
        $imp = $stmt->fetch();
        if (!$imp) {
            throw new RuntimeException("Importacion #{$importId} no encontrada.");
        }

        $filePath = $this->storagePath . DIRECTORY_SEPARATOR . $imp['nombre_archivo_almacenado'];
        if (!file_exists($filePath)) {
            throw new RuntimeException("El archivo almacenado no existe en disco.");
        }

        $datasetType = $imp['tipo_dataset'];
        $ext = strtolower($imp['formato']);

        $rows = [];
        if ($ext === 'xlsx') {
            $rows = $this->extractRowsXlsx($filePath, $sheetName ?? $imp['hoja'] ?? 'Hoja1');
        } else {
            $rows = $this->extractRowsCsv($filePath);
        }

        // Limpiar filas previas de staging si existian
        $delStmt = $db->prepare("DELETE FROM importacion_filas WHERE id_importacion = :id");
        $delStmt->execute(['id' => $importId]);

        $insStmt = $db->prepare("
            INSERT INTO importacion_filas (
                id_importacion, numero_fila, identificador_negocio, datos_crudos_json, estado, mensaje_error
            ) VALUES (
                :imp_id, :num, :ident, :json, :st, :err
            )
        ");

        $total = count($rows);
        $valid = 0;
        $errors = 0;

        foreach ($rows as $idx => $r) {
            $rowNum = $idx + 1;
            $validation = $this->validateRow($datasetType, $r);
            $st = $validation['valid'] ? 'VALIDO' : 'ERROR';
            if ($validation['valid']) {
                $valid++;
            } else {
                $errors++;
            }

            $insStmt->execute([
                'imp_id' => $importId,
                'num' => $rowNum,
                'ident' => $validation['identifier'] ?? null,
                'json' => json_encode($r, JSON_UNESCAPED_UNICODE),
                'st' => $st,
                'err' => $validation['error'] ?? null
            ]);
        }

        // Actualizar cabecera de importacion
        $upd = $db->prepare("
            UPDATE importaciones 
            SET filas_totales = :tot, filas_validas = :val, filas_con_error = :err, estado = 'VALIDADO', hoja = :sheet
            WHERE id_importacion = :id
        ");
        $upd->execute([
            'tot' => $total,
            'val' => $valid,
            'err' => $errors,
            'sheet' => $sheetName ?? $imp['hoja'],
            'id' => $importId
        ]);

        return [
            'total' => $total,
            'valid' => $valid,
            'errors' => $errors
        ];
    }

    private function extractRowsCsv(string $filePath): array
    {
        $rows = [];
        $h = fopen($filePath, 'r');
        if (!$h) {
            return [];
        }

        $delimiter = ',';
        $firstLine = fgets($h);
        if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        }
        rewind($h);

        $headers = fgetcsv($h, 4096, $delimiter);
        if (!$headers) {
            fclose($h);
            return [];
        }
        $headers = array_map(function($h) {
            return strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', $h)));
        }, $headers);

        while (($data = fgetcsv($h, 4096, $delimiter)) !== false) {
            if (empty(array_filter($data))) {
                continue;
            }
            $row = [];
            foreach ($headers as $i => $colName) {
                $row[$colName] = isset($data[$i]) ? trim($data[$i]) : null;
            }
            $rows[] = $row;
        }
        fclose($h);
        return $rows;
    }

    private function extractRowsXlsx(string $filePath, string $sheetName): array
    {
        $appConfig = require dirname(__DIR__, 3) . '/config/config.php';
        $pythonBin = $appConfig['solver']['python_bin'] ?? 'python';

        $script = <<<PY
import sys, json, openpyxl
try:
    wb = openpyxl.load_workbook(sys.argv[1], data_only=True)
    sheet_name = sys.argv[2]
    if sheet_name not in wb.sheetnames:
        sheet_name = wb.sheetnames[0]
    s = wb[sheet_name]
    rows = []
    headers = []
    for r in s.iter_rows(values_only=True):
        if not any(r):
            continue
        if not headers:
            headers = [str(c).strip().lower().replace(' ', '_').replace('.', '') for c in r if c is not None]
        else:
            row_dict = {}
            for idx, val in enumerate(r):
                if idx < len(headers):
                    row_dict[headers[idx]] = str(val).strip() if val is not None else None
            if any(row_dict.values()):
                rows.append(row_dict)
    print(json.dumps(rows, ensure_ascii=False))
except Exception as e:
    print(json.dumps([]))
PY;

        $tempPy = dirname($filePath) . '/extract_' . time() . '.py';
        file_put_contents($tempPy, $script);

        $cmd = sprintf('"%s" "%s" "%s" "%s"', $pythonBin, $tempPy, $filePath, escapeshellarg($sheetName));
        $output = shell_exec($cmd);
        @unlink($tempPy);

        $data = json_decode($output, true);
        return is_array($data) ? $data : [];
    }

    private function validateRow(string $datasetType, array $row): array
    {
        switch ($datasetType) {
            case 'PRODUCTOS':
                $code = $row['codigo'] ?? $row['id_producto'] ?? $row['cod'] ?? null;
                $desc = $row['descripcion'] ?? $row['nombre'] ?? $row['producto'] ?? null;
                if (empty($code)) {
                    return ['valid' => false, 'error' => 'El codigo del medicamento es obligatorio.'];
                }
                if (empty($desc)) {
                    return ['valid' => false, 'error' => 'La descripcion del medicamento es obligatoria.'];
                }
                return ['valid' => true, 'identifier' => $code];

            case 'PROVEEDORES':
                $code = $row['codigo'] ?? $row['id_proveedor'] ?? null;
                $name = $row['razon_social'] ?? $row['nombre'] ?? $row['proveedor'] ?? null;
                if (empty($code) && empty($name)) {
                    return ['valid' => false, 'error' => 'Se requiere codigo o razon social del proveedor.'];
                }
                return ['valid' => true, 'identifier' => $code ?: $name];

            case 'LOTES':
                $prodCode = $row['codigo'] ?? $row['codigo_producto'] ?? null;
                $lote = $row['lote'] ?? $row['codigo_lote'] ?? null;
                $venc = $row['fecha_vencimiento'] ?? $row['vencimiento'] ?? null;
                if (empty($prodCode) || empty($lote)) {
                    return ['valid' => false, 'error' => 'Codigo de producto y lote son obligatorios.'];
                }
                return ['valid' => true, 'identifier' => "{$prodCode}_{$lote}"];

            case 'DEMANDA':
                $prodCode = $row['codigo'] ?? $row['id_producto'] ?? null;
                $cant = $row['cantidad'] ?? $row['demanda'] ?? $row['venta'] ?? null;
                if (empty($prodCode) || $cant === null || !is_numeric($cant)) {
                    return ['valid' => false, 'error' => 'Producto y cantidad numerica requeridos.'];
                }
                return ['valid' => true, 'identifier' => $prodCode];

            default:
                return ['valid' => true, 'identifier' => null];
        }
    }

    public function confirmImport(int $importId): array
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT * FROM importaciones WHERE id_importacion = :id FOR UPDATE");
            $stmt->execute(['id' => $importId]);
            $imp = $stmt->fetch();
            if (!$imp) {
                throw new RuntimeException("Importacion no encontrada.");
            }

            if ($imp['estado'] === 'CONFIRMADO') {
                throw new RuntimeException("Esta importacion ya fue confirmada anteriormente.");
            }

            $filasStmt = $db->prepare("SELECT * FROM importacion_filas WHERE id_importacion = :id AND estado = 'VALIDO'");
            $filasStmt->execute(['id' => $importId]);
            $validRows = $filasStmt->fetchAll();

            $nature = $imp['naturaleza_general'] ?? 'OBSERVED';
            $inserted = 0;
            $updated = 0;

            switch ($imp['tipo_dataset']) {
                case 'PRODUCTOS':
                    $pStmt = $db->prepare("
                        INSERT INTO productos (
                            codigo, descripcion, presentacion, unidad_medida, costo_unitario_promedio_bs,
                            clasificacion_abc, condicion_almacenamiento, cadena_frio, registro_sanitario,
                            estado_registro_sanitario, activo, naturaleza_dato, creado_en
                        ) VALUES (
                            :code, :desc, :pres, :unit, :cost,
                            :abc, :cond, :frio, :reg,
                            :st_reg, 1, :nature, NOW()
                        ) ON DUPLICATE KEY UPDATE
                            descripcion = VALUES(descripcion),
                            presentacion = VALUES(presentacion),
                            costo_unitario_promedio_bs = VALUES(costo_unitario_promedio_bs),
                            clasificacion_abc = VALUES(clasificacion_abc),
                            actualizado_en = NOW()
                    ");

                    foreach ($validRows as $f) {
                        $d = json_decode($f['datos_crudos_json'], true);
                        $code = $d['codigo'] ?? $d['id_producto'] ?? $d['cod'];
                        $desc = $d['descripcion'] ?? $d['nombre'] ?? $d['producto'] ?? 'Sin descripcion';
                        $pres = $d['presentacion'] ?? 'UNIDAD';
                        $unit = $d['unidad'] ?? $d['unidad_medida'] ?? 'UNIDAD';
                        $cost = (float)($d['costo_unitario_promedio_bs'] ?? $d['costo'] ?? $d['precio'] ?? 0.0);
                        $abc = strtoupper($d['clasificacion_abc'] ?? $d['abc'] ?? 'B');
                        if (!in_array($abc, ['A', 'B', 'C'])) {
                            $abc = 'B';
                        }
                        $frio = strtoupper($d['cadena_frio'] ?? 'NO_IDENTIFICADO');
                        if (!in_array($frio, ['SI', 'NO', 'NO_IDENTIFICADO'])) {
                            $frio = 'NO_IDENTIFICADO';
                        }
                        $reg = $d['registro_sanitario'] ?? null;
                        $stReg = (!empty($reg) && strpos($reg, 'PEND') === false) ? 'VIGENTE' : 'PENDIENTE_VALIDACION';

                        $pStmt->execute([
                            'code' => $code,
                            'desc' => $desc,
                            'pres' => $pres,
                            'unit' => $unit,
                            'cost' => $cost,
                            'abc' => $abc,
                            'cond' => $d['condicion_almacenamiento'] ?? 'AMBIENTE',
                            'frio' => $frio,
                            'reg' => $reg,
                            'st_reg' => $stReg,
                            'nature' => $nature
                        ]);
                        $inserted++;
                    }
                    break;

                case 'PROVEEDORES':
                    $provStmt = $db->prepare("
                        INSERT INTO proveedores (codigo, razon_social, tipo, departamento, plazo_base_dias, activo, naturaleza_dato, creado_en)
                        VALUES (:code, :name, :tipo, :dep, :lead, 1, :nature, NOW())
                        ON DUPLICATE KEY UPDATE razon_social = VALUES(razon_social), plazo_base_dias = VALUES(plazo_base_dias)
                    ");
                    foreach ($validRows as $f) {
                        $d = json_decode($f['datos_crudos_json'], true);
                        $code = $d['codigo'] ?? $d['id_proveedor'] ?? ('PRV-' . $f['numero_fila']);
                        $name = $d['razon_social'] ?? $d['nombre'] ?? $code;
                        $provStmt->execute([
                            'code' => $code,
                            'name' => $name,
                            'tipo' => $d['tipo'] ?? 'DROGUERIA',
                            'dep' => $d['departamento'] ?? 'La Paz',
                            'lead' => (int)($d['plazo_base_dias'] ?? $d['plazo'] ?? 7),
                            'nature' => $nature
                        ]);
                        $inserted++;
                    }
                    break;

                default:
                    $inserted = count($validRows);
                    break;
            }

            // Actualizar estado de importacion
            $confStmt = $db->prepare("UPDATE importaciones SET estado = 'CONFIRMADO', confirmado_en = NOW() WHERE id_importacion = :id");
            $confStmt->execute(['id' => $importId]);

            $confFilas = $db->prepare("UPDATE importacion_filas SET estado = 'CONFIRMADO' WHERE id_importacion = :id AND estado = 'VALIDO'");
            $confFilas->execute(['id' => $importId]);

            Audit::log('CONFIRMAR_IMPORTACION', 'importaciones', (string)$importId, null, [
                'filas_confirmadas' => $inserted,
                'dataset' => $imp['tipo_dataset']
            ], 'Incorporacion definitiva de datos desde staging a tablas maestras');

            $db->commit();
            return [
                'success' => true,
                'inserted' => $inserted,
                'updated' => $updated
            ];
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}

