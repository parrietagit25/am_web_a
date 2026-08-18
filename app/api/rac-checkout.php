<?php
/**
 * Inicia checkout RAC: guarda borrador y redirige a pago. No crea RentWorks.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/CaptchaService.php';
require_once __DIR__ . '/../services/PowertranzClient.php';
require_once __DIR__ . '/../services/RacCheckoutStore.php';
require_once __DIR__ . '/../services/RacPublicRateService.php';
require_once __DIR__ . '/../services/RacAddonService.php';
require_once __DIR__ . '/../services/RacBirthDateService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON inválido.']);
    exit;
}

$action = trim((string) ($input['action'] ?? 'start'));
if ($action === 'status') {
    $token = trim((string) ($input['token'] ?? ''));
    $row = RacCheckoutStore::get($token);
    if ($row === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Checkout no encontrado.']);
        exit;
    }
    echo json_encode([
        'success' => true,
        'status' => $row['status'] ?? '',
        'amount' => $row['amount'] ?? 0,
        'confirmation_code' => $row['confirmation_code'] ?? '',
        'redirect' => !empty($row['confirmation_code'])
            ? '/confirmacion.php?code=' . rawurlencode((string) $row['confirmation_code']) . '&token=' . rawurlencode($token)
            : null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!PowertranzClient::isEnabled()) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'La pasarela de pago no está disponible. No se puede confirmar sin pago.']);
    exit;
}

CaptchaService::enforceRacReservation($input);

$firstName = trim((string) ($input['first_name'] ?? ''));
$lastName = trim((string) ($input['last_name'] ?? ''));
$name = trim((string) ($input['customer_name'] ?? trim($firstName . ' ' . $lastName)));
$email = trim((string) ($input['customer_email'] ?? $input['email'] ?? ''));
$phone = trim((string) ($input['customer_phone'] ?? $input['phone'] ?? ''));

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

$birthError = RacBirthDateService::validationError($input['birth_date'] ?? null);
if ($birthError !== null) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $birthError], JSON_UNESCAPED_UNICODE);
    exit;
}

$search = $input['search'] ?? $input['search_snapshot'] ?? [];
$vehicle = $input['vehicle'] ?? $input['vehicle_snapshot'] ?? [];
$extras = is_array($input['extras'] ?? null) ? $input['extras'] : [];
if (empty($vehicle['name']) && empty($vehicle['sippCode'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Datos del vehículo incompletos.']);
    exit;
}

$amount = 0.0;
if (is_array($extras['totals'] ?? null) && isset($extras['totals']['total'])) {
    $amount = round((float) $extras['totals']['total'], 2);
}

$quoteToken = trim((string) ($input['rate_quote_token'] ?? ($vehicle['pricing']['barsQuoteToken'] ?? '')));
if ($quoteToken !== '' && RacPublicRateService::isBarsPricingEnabled()) {
    $publicRateService = new RacPublicRateService();
    $quoteValidation = $publicRateService->validateQuote($quoteToken, array_merge(is_array($search) ? $search : [], [
        'vehicle_code' => $vehicle['sippCode'] ?? '',
        'sippCode' => $vehicle['sippCode'] ?? '',
    ]));
    if (!($quoteValidation['ok'] ?? false)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $quoteValidation['message'] ?? 'Tarifa inválida.']);
        exit;
    }
    $barsQuote = $quoteValidation['quote'] ?? [];
    $rateType = RacPublicRateService::normalizeRateType($input['rate_type'] ?? 'web');
    $baseForTotal = (float) ($barsQuote['final_total_rate'] ?? 0);
    if ($rateType === 'counter') {
        $baseForTotal = round($baseForTotal * RacPublicRateService::counterMarkupFactor(), 2);
    }
    $addonService = new RacAddonService();
    $rentalDays = max(1, (int) ($barsQuote['rental_days'] ?? 1));
    $addonResolved = $addonService->resolveReservationAddons($extras, [
        'sippCode' => $vehicle['sippCode'] ?? '',
        'locationCode' => $search['locationCode'] ?? '',
        'returnLocationCode' => $search['returnLocationCode'] ?? $search['locationCode'] ?? '',
        'rental_days' => $rentalDays,
        'billed_days' => $rentalDays,
        'rental_base' => $baseForTotal,
        'final_total_rate' => $baseForTotal,
    ]);
    if (!empty($addonResolved['ok'])) {
        $extrasAmt = (float) ($addonResolved['totals']['extras'] ?? 0);
        $covAmt = (float) ($addonResolved['totals']['coverage'] ?? 0);
        $mandatoryTotal = (float) ($extras['mandatoryTotal'] ?? ($extras['totals']['mandatory'] ?? 0));
        $subtotal = $baseForTotal + $mandatoryTotal + $covAmt + $extrasAmt;
        $itbms = round($subtotal * 0.07, 2);
        $amount = round($subtotal + $itbms, 2);
        $extras['totals'] = array_merge($extras['totals'] ?? [], [
            'coverage' => $covAmt,
            'extras' => $extrasAmt,
            'itbms' => $itbms,
            'total' => $amount,
        ]);
        $input['extras'] = $extras;
    }
}

if ($amount < 0.50) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'El monto a cobrar no es válido.']);
    exit;
}

$token = RacCheckoutStore::newToken();
RacCheckoutStore::save([
    'token' => $token,
    'status' => 'pending_payment',
    'amount' => $amount,
    'currency' => 'USD',
    'email' => $email,
    'last_name' => $lastName,
    'payload' => $input,
]);

echo json_encode([
    'success' => true,
    'checkout_token' => $token,
    'amount' => $amount,
    'redirect' => '/pago.php?token=' . rawurlencode($token),
    'message' => 'Continúe con el pago para confirmar la reserva.',
], JSON_UNESCAPED_UNICODE);
