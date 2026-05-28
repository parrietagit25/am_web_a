<?php
/**
 * API: Create RAC reservation (DB + alert emails).
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Database.php';
require_once __DIR__ . '/../services/BranchDataService.php';
require_once __DIR__ . '/../services/RacReservationService.php';
require_once __DIR__ . '/../services/RacAlertEmailService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON inválido.']);
    exit;
}

$name = trim($input['customer_name'] ?? $input['name'] ?? '');
$email = trim($input['customer_email'] ?? $input['email'] ?? '');
$phone = trim($input['customer_phone'] ?? $input['phone'] ?? '');
$comments = trim($input['customer_comments'] ?? $input['comments'] ?? '');

if ($name === '' || $email === '' || $phone === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Nombre, correo y teléfono son obligatorios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Correo electrónico no válido.']);
    exit;
}

$search = $input['search'] ?? $input['search_snapshot'] ?? [];
$vehicle = $input['vehicle'] ?? $input['vehicle_snapshot'] ?? [];

if (empty($vehicle['name']) && empty($vehicle['sippCode'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Datos del vehículo incompletos.']);
    exit;
}

$pricing = $vehicle['pricing'] ?? [];
$rateType = ($input['rate_type'] ?? 'web') === 'counter' ? 'counter' : 'web';

try {
    $service = new RacReservationService();
    $row = $service->create([
        'customer_name' => $name,
        'customer_email' => $email,
        'customer_phone' => $phone,
        'customer_comments' => $comments,
        'location_code' => $search['locationCode'] ?? '',
        'return_location_code' => $search['returnLocationCode'] ?? $search['locationCode'] ?? '',
        'pickup_date' => $search['pickupDate'] ?? '',
        'pickup_time' => $search['pickupTime'] ?? '10:00',
        'return_date' => $search['returnDate'] ?? '',
        'return_time' => $search['returnTime'] ?? '10:00',
        'driver_age' => (string) ($search['age'] ?? '25'),
        'promo_code' => $search['promoCode'] ?? '',
        'sipp_code' => $vehicle['sippCode'] ?? '',
        'vehicle_name' => $vehicle['name'] ?? 'Vehículo',
        'vehicle_category' => $vehicle['category'] ?? '',
        'vendor_rate_id' => $vehicle['vendorRateId'] ?? '',
        'quote_token' => $pricing['quoteToken'] ?? $vehicle['vendorRateId'] ?? '',
        'rate_type' => $rateType,
        'price_web' => $vehicle['priceWeb'] ?? null,
        'price_counter' => $vehicle['priceCounter'] ?? null,
        'price_total' => $vehicle['priceTotal'] ?? null,
        'price_total_estimated' => isset($input['price_total_estimated'])
            ? (float) $input['price_total_estimated']
            : ($vehicle['priceTotalEstimated'] ?? null),
        'coverage_code' => trim($input['coverage_code'] ?? ''),
        'equipment' => $input['equipment'] ?? [],
        'vehicle_snapshot' => $vehicle,
        'search_snapshot' => $search,
    ]);

    $alert = new RacAlertEmailService();
    $mailResult = $alert->notifyNewReservation($row);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'reservation_code' => $row['reservation_code'] ?? '',
        'reservation_id' => (int) ($row['id'] ?? 0),
        'alert_sent' => $mailResult['sent'] ?? false,
        'message' => 'Reserva registrada correctamente.',
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    am_log('rac-reservation error: ' . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo registrar la reserva. Intente nuevamente.',
    ]);
}
