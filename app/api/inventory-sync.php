<?php
/**
 * API: Recibe lote JSON de vehículos → tabla Automarket_Invs_web_temp.
 * Mismo contrato que api_web.php (GoDaddy).
 */
header('Content-Type: text/plain; charset=UTF-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Database.php';
require_once __DIR__ . '/../services/InventorySyncSchema.php';
require_once __DIR__ . '/../services/InventorySyncService.php';

$token = defined('INVENTORY_SYNC_TOKEN') ? INVENTORY_SYNC_TOKEN : '';
$headerToken = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';

if ($token === '' || $headerToken !== $token) {
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
        $msg .= ', ' . $result['errors'] . ' errores.';
    } else {
        $msg .= '.';
    }
    echo $msg;
} catch (Throwable $e) {
    am_log('inventory-sync.php: ' . $e->getMessage(), 'ERROR');
    header('HTTP/1.0 500 Internal Server Error');
    echo 'Error interno en sincronización';
}
