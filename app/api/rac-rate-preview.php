<?php
/**
 * API: Preview / recálculo de totales RAC sin crear reserva.
 * AM-ADJ-13 — reutiliza RacPublicRateService + RacAddonService.
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/RacPublicRateService.php';

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

if (isset($input['price_total_estimated']) || isset($input['price_itbms']) || isset($input['price_rental_base'])) {
    // Los precios del cliente no son fuente de verdad; se ignoran y se recalculan en servidor.
}

$vehicleCode = strtoupper(trim((string) ($input['vehicle_code'] ?? $input['sippCode'] ?? '')));
if ($vehicleCode === '' || is_array($input['vehicle_code'] ?? null) || is_array($input['sippCode'] ?? null)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Código de vehículo requerido.']);
    exit;
}

$rateType = RacPublicRateService::normalizeRateType($input['rate_type'] ?? 'web');
// Flags de prepago/pago del cliente se ignoran; el servidor es fuente de verdad.
$quoteToken = trim((string) ($input['quote_token'] ?? $input['rate_quote_token'] ?? ''));
if (is_array($input['quote_token'] ?? null) || is_array($input['rate_quote_token'] ?? null)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Token de tarifa no válido.']);
    exit;
}

$extras = $input['extras'] ?? [];
if ($extras !== null && !is_array($extras)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Formato de extras no válido.']);
    exit;
}
if (!is_array($extras)) {
    $extras = [];
}

$search = [
    'locationCode' => $input['locationCode'] ?? $input['pickup_location'] ?? '',
    'returnLocationCode' => $input['returnLocationCode'] ?? $input['return_location'] ?? '',
    'pickupDate' => $input['pickupDate'] ?? '',
    'pickupTime' => $input['pickupTime'] ?? '10:00',
    'returnDate' => $input['returnDate'] ?? '',
    'returnTime' => $input['returnTime'] ?? '10:00',
    'age' => $input['age'] ?? '25',
    'promoCode' => $input['promoCode'] ?? '',
];

if (trim((string) $search['locationCode']) === '' || trim((string) $search['pickupDate']) === '' || trim((string) $search['returnDate']) === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Datos de búsqueda incompletos.']);
    exit;
}

$service = new RacPublicRateService();
$result = $service->previewTotals($search, $vehicleCode, $quoteToken !== '' ? $quoteToken : null, $extras, $rateType);

if (!($result['ok'] ?? false)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $result['message'] ?? 'No se pudo recalcular la tarifa.',
        'code' => $result['code'] ?? null,
        'reservation_created' => false,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(200);
echo json_encode([
    'success' => true,
    'refreshed' => !empty($result['refreshed']),
    'quote_token' => $result['quote_token'] ?? null,
    'expires_at' => $result['expires_at'] ?? null,
    'currency' => $result['currency'] ?? 'USD',
    'rate_type' => $result['rate_type'] ?? $rateType,
    'rate_channel' => $result['rate_channel'] ?? RacPublicRateService::rateChannelDescriptor($rateType),
    'prepayment_available' => false,
    'payment_provider_available' => false,
    'online_payment_available' => false,
    'rental_days' => $result['rental_days'] ?? null,
    'vehicle' => $result['vehicle'] ?? null,
    'pricing' => $result['pricing'] ?? null,
    'protection' => $result['protection'] ?? null,
    'totals' => $result['totals'] ?? null,
    'reservation_created' => false,
], JSON_UNESCAPED_UNICODE);
