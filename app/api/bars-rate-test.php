<?php
/**
 * Endpoint interno de prueba BARS/RW Web — AM-RAC-BARS-TEST-0F.
 * URL pública: /api/bars-rate-test.php
 * No conectado al flujo de reservas ni al frontend RAC.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/BarsRateClient.php';

/**
 * Restringe el endpoint a entornos internos (localhost / test).
 */
function bars_rate_test_host_allowed(): bool
{
    $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
    if ($host === '') {
        return false;
    }
    $host = preg_replace('/:\d+$/', '', $host) ?? $host;

    return in_array($host, ['test.automarket.com.pa', 'localhost', '127.0.0.1'], true);
}

/**
 * @param list<array<string, mixed>> $vehicles
 */
function bars_rate_count_available(array $vehicles): int
{
    $count = 0;
    foreach ($vehicles as $vehicle) {
        if (!empty($vehicle['available'])) {
            $count++;
        }
    }

    return $count;
}

if (!bars_rate_test_host_allowed()) {
    http_response_code(404);
    echo json_encode([
        'ok' => false,
        'error' => 'Not found',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'error' => 'Método no permitido. Use GET o POST.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = $method === 'POST'
    ? (json_decode((string) file_get_contents('php://input'), true) ?: $_POST)
    : $_GET;

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Parámetros inválidos.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$pickupLocation = strtoupper(trim((string) ($input['pickup_location'] ?? 'PTY')));
$returnLocation = strtoupper(trim((string) ($input['return_location'] ?? 'PTY')));
$pickupDatetime = trim((string) ($input['pickup_datetime'] ?? '2026-07-15T10:00:00'));
$returnDatetime = trim((string) ($input['return_datetime'] ?? '2026-07-18T10:00:00'));
$debugMode = filter_var($input['debug'] ?? false, FILTER_VALIDATE_BOOLEAN);

$client = new BarsRateClient();
$result = $client->queryRates([
    'pickup_location' => $pickupLocation,
    'return_location' => $returnLocation,
    'pickup_datetime' => $pickupDatetime,
    'return_datetime' => $returnDatetime,
    'veh_classes' => BarsRateClient::DEFAULT_VEH_CLASSES,
    'debug' => $debugMode,
]);

$vehicles = is_array($result['vehicles'] ?? null) ? $result['vehicles'] : [];
$warnings = is_array($result['warnings'] ?? null) ? $result['warnings'] : [];
$catalogWarnings = BarsRateClient::extractCatalogWarnings($warnings);
$debug = is_array($result['debug'] ?? null) ? $result['debug'] : [
    'http_code' => 0,
    'has_pc_message' => false,
    'pc_message_length' => 0,
];

$availableCount = bars_rate_count_available($vehicles);
$totalCount = count($vehicles);

$response = [
    'ok' => (bool) ($result['ok'] ?? false),
    'auth_ok' => (bool) ($result['auth_ok'] ?? false),
    'success' => (bool) ($result['success'] ?? false),
    'source' => 'BARS_RW_WEB',
    'request' => [
        'pickup_location' => $pickupLocation,
        'return_location' => $returnLocation,
        'pickup_datetime' => $pickupDatetime,
        'return_datetime' => $returnDatetime,
        'rate_qualifier' => 'WEB',
    ],
    'count' => $totalCount,
    'available_count' => $availableCount,
    'unavailable_count' => $totalCount - $availableCount,
    'vehicles' => $vehicles,
    'warnings' => $warnings,
    'catalog_warnings' => $catalogWarnings,
    'catalog_warnings_note' => $catalogWarnings !== []
        ? 'Warnings Code=256: clases de catálogo no reconocidas por BARS; no bloquean autenticación ni tarifas válidas.'
        : null,
    'debug' => $debug,
];

if (!empty($result['error'])) {
    $response['error'] = (string) $result['error'];
}

if (!$response['ok']) {
    http_response_code($debug['http_code'] > 0 && $debug['http_code'] !== 200 ? 502 : 500);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
