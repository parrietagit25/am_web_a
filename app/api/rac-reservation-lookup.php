<?php
/**
 * API: Lookup RAC reservation (proxy to Automarket backend + local fallback).
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Database.php';
require_once __DIR__ . '/../services/BranchDataService.php';
require_once __DIR__ . '/../services/AutomarketReservationApiService.php';
require_once __DIR__ . '/../services/RacReservationService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$code = strtoupper(trim($_GET['code'] ?? ''));
$lastName = trim($_GET['lastName'] ?? $_GET['last_name'] ?? '');
if ($code === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Indique el número de reserva.']);
    exit;
}
if ($lastName === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Indique su apellido para consultar la reserva.']);
    exit;
}

$api = new AutomarketReservationApiService();
$result = $api->lookupReservation($code, $lastName);

if ($result['ok'] && is_array($result['data'])) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'source' => 'bars',
        'reservation' => $result['data'],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$local = (new RacReservationService())->findByBarsCode($code);
if ($local && !reservationLastNameMatches($local, $lastName)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'El apellido no coincide con la reserva indicada.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($local) {
    $pickupBranch = BranchDataService::findByCode($local['location_code'] ?? '');
    $returnBranch = BranchDataService::findByCode($local['return_location_code'] ?? '');
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'source' => 'local',
        'reservation' => [
            'confirmationNumber' => RacReservationService::displayConfirmationCode($local),
            'status' => ucfirst($local['status'] ?? 'pending'),
            'customerName' => $local['customer_name'] ?? '',
            'customerEmail' => $local['customer_email'] ?? '',
            'vehicleName' => $local['vehicle_name'] ?? '',
            'pickupLocation' => $pickupBranch['name'] ?? ($local['location_code'] ?? ''),
            'returnLocation' => $returnBranch['name'] ?? ($local['return_location_code'] ?? ''),
            'pickupDateTime' => ($local['pickup_date'] ?? '') . 'T' . ($local['pickup_time'] ?? '10:00'),
            'returnDateTime' => ($local['return_date'] ?? '') . 'T' . ($local['return_time'] ?? '10:00'),
            'totalAmount' => (float) ($local['price_total_estimated'] ?? 0),
            'coverageName' => $local['coverage_name'] ?? '',
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(404);
echo json_encode([
    'success' => false,
    'message' => $result['error'] ?? 'No encontramos esta reserva. Revise el número y apellido o contáctenos.',
], JSON_UNESCAPED_UNICODE);

function reservationLastNameMatches(array $row, string $lastName): bool {
    $needle = mb_strtolower(trim($lastName), 'UTF-8');
    if ($needle === '') {
        return false;
    }
    $stored = mb_strtolower(trim($row['customer_name'] ?? ''), 'UTF-8');
    if ($stored === '') {
        return false;
    }
    $parts = preg_split('/\s+/', $stored);
    $storedLast = $parts ? (string) end($parts) : '';
    return $storedLast === $needle || str_contains($stored, $needle);
}
