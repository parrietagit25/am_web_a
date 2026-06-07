<?php
/**
 * API: estado rápido del sync de inventario (diagnóstico).
 */
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Database.php';
require_once __DIR__ . '/../services/InventorySyncSchema.php';
require_once __DIR__ . '/../services/InventorySyncService.php';

try {
    InventorySyncSchema::ensure();
    $min = defined('INVENTORY_SYNC_MIN_VEHICLES') ? (int) INVENTORY_SYNC_MIN_VEHICLES : 50;
    echo json_encode([
        'ok' => true,
        'temp_count' => InventorySyncService::tempCount(),
        'main_disponible_count' => InventorySyncService::mainCount(),
        'min_required_for_pasar' => $min,
        'driver' => Database::getInstance()->getDriverName(),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
