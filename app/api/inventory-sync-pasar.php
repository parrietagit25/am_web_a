<?php
/**
 * API: Pasa datos de Automarket_Invs_web_temp → Automarket_Invs_web.
 * Mismo contrato que api_web_pasar_data.php (GoDaddy).
 */
header('Content-Type: text/plain; charset=UTF-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Database.php';
require_once __DIR__ . '/../services/InventorySyncSchema.php';
require_once __DIR__ . '/../services/InventorySyncService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('HTTP/1.0 405 Method Not Allowed');
    echo 'Método no permitido';
    exit;
}

try {
    $result = InventorySyncService::pasarData();
    if ($result['ok']) {
        echo 'El pase se ha completado, todos los registros se han actualizado';
    } else {
        echo $result['message'];
    }
} catch (Throwable $e) {
    am_log('inventory-sync-pasar.php: ' . $e->getMessage(), 'ERROR');
    header('HTTP/1.0 500 Internal Server Error');
    echo 'Ocurrio un error en el pase';
}
