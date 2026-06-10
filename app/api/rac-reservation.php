<?php
/**
 * API: Create RAC reservation (BARS + local DB + emails).
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Database.php';
require_once __DIR__ . '/../services/BranchDataService.php';
require_once __DIR__ . '/../services/AutomarketReservationApiService.php';
require_once __DIR__ . '/../services/RacReservationService.php';
require_once __DIR__ . '/../services/RacAlertEmailService.php';
require_once __DIR__ . '/../services/CaptchaService.php';

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

CaptchaService::enforce($input, 'success');

$firstName = trim($input['first_name'] ?? '');
$lastName = trim($input['last_name'] ?? '');
$name = trim($input['customer_name'] ?? trim($firstName . ' ' . $lastName));
$email = trim($input['customer_email'] ?? $input['email'] ?? '');
$emailConfirm = trim($input['email_confirm'] ?? $email);
$phone = trim($input['customer_phone'] ?? $input['phone'] ?? '');
$phonePrefix = trim($input['phone_prefix'] ?? '+507');
$comments = trim($input['customer_comments'] ?? $input['remarks'] ?? '');

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

if ($emailConfirm !== '' && strcasecmp($email, $emailConfirm) !== 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Los correos electrónicos no coinciden.']);
    exit;
}

$search = $input['search'] ?? $input['search_snapshot'] ?? [];
$vehicle = $input['vehicle'] ?? $input['vehicle_snapshot'] ?? [];
$extras = $input['extras'] ?? null;

if (empty($vehicle['name']) && empty($vehicle['sippCode'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Datos del vehículo incompletos.']);
    exit;
}

$pricing = $vehicle['pricing'] ?? [];
$rateType = ($input['rate_type'] ?? 'web') === 'counter' ? 'counter' : 'web';
$coverageCode = trim($input['coverage_code'] ?? ($extras['protection'] ?? ''));
if (strtoupper($coverageCode) === 'NONE') {
    $coverageCode = '';
}
$coverageName = trim($input['coverage_name'] ?? '');
$coverageAmount = $input['coverage_amount'] ?? ($extras['totals']['coverage'] ?? null);
$coverageDeductible = $input['coverage_deductible'] ?? null;
$estimatedTotal = $input['price_total_estimated'] ?? ($extras['totals']['total'] ?? null);
$rentalBase = $input['price_rental_base'] ?? ($extras['totals']['base'] ?? ($pricing['rateBase'] ?? null));
$saf = $input['price_saf'] ?? ($extras['totals']['saf'] ?? ($pricing['saf'] ?? null));
$itbms = $input['price_itbms'] ?? ($extras['totals']['itbms'] ?? null);

$fullPhone = $phone;
if ($phone !== '' && strpos($phone, '+') !== 0 && $phonePrefix !== '') {
    $fullPhone = preg_replace('/\s+/', '', $phonePrefix . $phone);
}

$barsConfirmation = '';
$barsStatus = 'pending';
$barsError = null;

try {
    $api = new AutomarketReservationApiService();
    $barsPayload = AutomarketReservationApiService::buildCreatePayload(array_merge($input, [
        'customer_name' => $name,
        'customer_email' => $email,
        'customer_phone' => $fullPhone,
        'customer_comments' => $comments,
        'search' => $search,
        'vehicle' => $vehicle,
        'extras' => $extras,
    ]));

    $barsResult = $api->createReservation($barsPayload);

    if ($barsResult['ok'] && is_array($barsResult['data'])) {
        $barsConfirmation = strtoupper(trim(
            $barsResult['data']['confirmationNumber']
            ?? $barsResult['data']['confirmation_number']
            ?? $barsResult['data']['reservationCode']
            ?? ''
        ));
        if ($barsConfirmation !== '' && $barsConfirmation !== 'PENDING') {
            $barsStatus = 'confirmed';
        }
    } else {
        $barsError = $barsResult['error'] ?? 'No se pudo crear la reserva en el sistema central.';
        am_log('BARS reservation failed: ' . $barsError, 'ERROR');
    }
} catch (Exception $e) {
    $barsError = $e->getMessage();
    am_log('BARS reservation exception: ' . $barsError, 'ERROR');
}

try {
    $service = new RacReservationService();
    $row = $service->create([
        'status' => $barsStatus,
        'bars_confirmation_code' => $barsConfirmation,
        'customer_name' => $name,
        'customer_email' => $email,
        'customer_phone' => $fullPhone,
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
        'price_total_estimated' => $estimatedTotal !== null ? (float) $estimatedTotal : ($vehicle['priceTotalEstimated'] ?? null),
        'coverage_code' => $coverageCode,
        'coverage_name' => $coverageName,
        'coverage_amount' => $coverageAmount,
        'coverage_deductible' => $coverageDeductible,
        'price_rental_base' => $rentalBase,
        'price_saf' => $saf,
        'price_itbms' => $itbms,
        'equipment' => $extras['items'] ?? ($input['equipment'] ?? []),
        'extras_snapshot' => is_array($extras) ? $extras : [],
        'vehicle_snapshot' => $vehicle,
        'search_snapshot' => $search,
    ]);

    $alert = new RacAlertEmailService();
    $mailAdmin = $alert->notifyNewReservation($row);
    $mailCustomer = $alert->notifyCustomer($row);

    $displayCode = RacReservationService::displayConfirmationCode($row);

    if ($barsError && $barsConfirmation === '') {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'partial' => true,
            'reservation_code' => $row['reservation_code'] ?? '',
            'confirmation_code' => $displayCode,
            'bars_confirmation_code' => $barsConfirmation,
            'reservation_id' => (int) ($row['id'] ?? 0),
            'alert_sent' => $mailAdmin['sent'] ?? false,
            'customer_email_sent' => $mailCustomer['sent'] ?? false,
            'message' => 'Su solicitud fue registrada. Un asesor confirmará los detalles pronto.',
            'last_name' => $lastName,
            'bars_error' => $barsError,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'partial' => false,
        'reservation_code' => $row['reservation_code'] ?? '',
        'confirmation_code' => $displayCode,
        'bars_confirmation_code' => $barsConfirmation,
        'reservation_id' => (int) ($row['id'] ?? 0),
        'alert_sent' => $mailAdmin['sent'] ?? false,
        'customer_email_sent' => $mailCustomer['sent'] ?? false,
        'message' => 'Reserva confirmada correctamente.',
        'last_name' => $lastName,
        'redirect' => '/confirmacion.php?code=' . rawurlencode($displayCode),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    am_log('rac-reservation error: ' . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo registrar la reserva. Intente nuevamente.',
    ]);
}
