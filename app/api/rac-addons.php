<?php
/**
 * API pública: protecciones y extras RAC desde BD local.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Database.php';
require_once __DIR__ . '/../services/RacAddonService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$vehicleCode = strtoupper(trim((string) ($_GET['vehicle_code'] ?? $_GET['sipp'] ?? '')));
$vehicleName = trim((string) ($_GET['vehicle_name'] ?? $_GET['category'] ?? ''));
$pickup = strtoupper(trim((string) ($_GET['pickup_location'] ?? $_GET['location'] ?? '')));
$ret = strtoupper(trim((string) ($_GET['return_location'] ?? '')));
$rentalDays = max(1, (int) ($_GET['rental_days'] ?? $_GET['days'] ?? 1));
$rentalBase = (float) ($_GET['rental_base'] ?? 0);

$context = [
    'vehicle_code' => $vehicleCode,
    'sippCode' => $vehicleCode,
    'vehicle_name' => $vehicleName,
    'vehicle_category' => $vehicleName,
    'pickup_location' => $pickup,
    'locationCode' => $pickup,
    'return_location' => $ret !== '' ? $ret : $pickup,
    'returnLocationCode' => $ret !== '' ? $ret : $pickup,
    'rental_days' => $rentalDays,
    'billed_days' => $rentalDays,
    'rental_base' => $rentalBase,
];

try {
    $service = new RacAddonService();
    $protections = $service->getPublicProtections($context);
    $extras = $service->getPublicExtras($context);

    echo json_encode([
        'success' => true,
        'ok' => true,
        'protections' => $protections,
        'extras' => $extras,
        'source' => 'db',
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    am_log('rac-addons error: ' . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudieron cargar protecciones y extras.']);
}
