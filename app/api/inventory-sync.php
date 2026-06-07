<?php
/**
 * API: Recibe lote JSON de vehículos → tabla Automarket_Invs_web_temp.
 * Si hay suficientes registros, ejecuta el pase a inventario automáticamente.
 */
header('Content-Type: text/plain; charset=UTF-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Database.php';
require_once __DIR__ . '/../services/InventorySyncAuth.php';
require_once __DIR__ . '/../services/InventorySyncSchema.php';
require_once __DIR__ . '/../services/InventorySyncService.php';

if (!InventorySyncAuth::verifyRequest()) {
    header('HTTP/1.0 401 Unauthorized');
    echo 'Token inválido';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 405 Method Not Allowed');
    echo 'Método no permitido';
    exit;
}

$jsonContent = file_get_contents('php://input');
$decoded = json_decode($jsonContent ?: '[]');

if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'No se recibieron datos JSON válidos';
    exit;
}

$items = is_array($decoded) ? $decoded : (is_object($decoded) ? [(array) $decoded] : []);
$normalized = [];
foreach ($items as $item) {
    if (is_object($item)) {
        $normalized[] = (array) $item;
    } elseif (is_array($item)) {
        $normalized[] = $item;
    }
}

if (count($normalized) === 0) {
    echo 'No se recibieron datos JSON válidos';
    exit;
}

try {
    $result = InventorySyncService::ingestBatch($normalized);
    $msg = 'Éxito: Se han procesado ' . $result['processed'] . ' registros';
    if ($result['errors'] > 0) {
        $msg .= ', ' . $result['errors'] . ' errores';
    }
    $msg .= '. Temp: ' . ($result['temp_count'] ?? 0);

    $minVehicles = defined('INVENTORY_SYNC_MIN_VEHICLES') ? (int) INVENTORY_SYNC_MIN_VEHICLES : 50;
    if (($result['temp_count'] ?? 0) >= $minVehicles) {
        $pasar = InventorySyncService::pasarData();
        if ($pasar['ok']) {
            $msg .= '. ' . $pasar['message'];
        } else {
            $msg .= '. Pase omitido: ' . ($pasar['message'] ?? '');
        }
    } else {
        $msg .= '. Pase omitido: se requieren al menos ' . $minVehicles . ' vehículos en temp.';
    }

    echo $msg;
} catch (Throwable $e) {
    am_log('inventory-sync.php: ' . $e->getMessage(), 'ERROR');
    header('HTTP/1.0 500 Internal Server Error');
    echo 'Error interno en sincronización';
}
