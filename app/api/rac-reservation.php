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
require_once __DIR__ . '/../services/RacPublicRateService.php';
require_once __DIR__ . '/../services/RacAddonService.php';

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

CaptchaService::enforceRacReservation($input);

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
$quoteToken = trim((string) ($input['rate_quote_token'] ?? $pricing['barsQuoteToken'] ?? $input['quote_token'] ?? ''));
$barsQuote = null;
$rateSource = 'legacy';
$publicRateService = null;

if (($pricing['rateSource'] ?? '') === 'bars_cache' && $quoteToken === '' && RacPublicRateService::isBarsPricingEnabled()) {
    $publicRateService = new RacPublicRateService();
    $autoQuote = $publicRateService->createQuote($search, (string) ($vehicle['sippCode'] ?? ''));
    if ($autoQuote['ok'] ?? false) {
        $quoteToken = (string) ($autoQuote['quote_token'] ?? '');
    } else {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => $autoQuote['message'] ?? 'Tarifa no bloqueada. Vuelva a seleccionar el vehículo.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (($pricing['rateSource'] ?? '') === 'bars_cache' && $quoteToken === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Tarifa no bloqueada. Vuelva a seleccionar el vehículo.']);
    exit;
}

if ($quoteToken !== '' && RacPublicRateService::isBarsPricingEnabled()) {
    $publicRateService = new RacPublicRateService();
    $quoteValidation = $publicRateService->validateQuote($quoteToken, array_merge($search, [
        'vehicle_code' => $vehicle['sippCode'] ?? '',
        'sippCode' => $vehicle['sippCode'] ?? '',
    ]));
    if (!($quoteValidation['ok'] ?? false)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $quoteValidation['message'] ?? 'Tarifa inválida.']);
        exit;
    }
    $barsQuote = $quoteValidation['quote'];
    $rateSource = 'bars_cache';
}

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
$reservationAddonItems = [];

if (is_array($extras)) {
    $addonService = new RacAddonService();
    $adminProtections = $addonService->getAdminProtections();
    $adminExtras = $addonService->getAdminExtras();
    $hasDbCatalog = $adminProtections !== [] || $adminExtras !== [];

    if ($hasDbCatalog) {
        $rentalDaysForAddons = max(1, (int) ($barsQuote['rental_days'] ?? $vehicle['rentalDays'] ?? 1));
        if ($rentalDaysForAddons <= 1 && !empty($search['pickupDate']) && !empty($search['returnDate'])) {
            $d1 = new DateTime($search['pickupDate']);
            $d2 = new DateTime($search['returnDate']);
            $rentalDaysForAddons = max(1, (int) $d1->diff($d2)->days);
        }
        $addonContext = [
            'vehicle_code' => $vehicle['sippCode'] ?? '',
            'sippCode' => $vehicle['sippCode'] ?? '',
            'vehicle_name' => $vehicle['category'] ?? $vehicle['name'] ?? '',
            'vehicle_category' => $vehicle['category'] ?? '',
            'pickup_location' => $search['locationCode'] ?? '',
            'locationCode' => $search['locationCode'] ?? '',
            'return_location' => $search['returnLocationCode'] ?? $search['locationCode'] ?? '',
            'returnLocationCode' => $search['returnLocationCode'] ?? $search['locationCode'] ?? '',
            'rental_days' => $rentalDaysForAddons,
            'billed_days' => $rentalDaysForAddons,
            'rental_base' => is_array($barsQuote) ? (float) ($barsQuote['final_total_rate'] ?? 0) : (float) ($rentalBase ?? 0),
            'final_total_rate' => is_array($barsQuote) ? (float) ($barsQuote['final_total_rate'] ?? 0) : (float) ($rentalBase ?? 0),
        ];
        $addonResolved = $addonService->resolveReservationAddons($extras, $addonContext);
        if (!($addonResolved['ok'] ?? false)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $addonResolved['message'] ?? 'Extras o protección no válidos.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $protection = $addonResolved['protection'] ?? [];
        if (($protection['code'] ?? '') !== '') {
            $coverageCode = (string) $protection['code'];
            $coverageName = (string) ($protection['name'] ?? $coverageCode);
            $coverageAmount = (float) ($protection['amount'] ?? 0);
        } else {
            $coverageCode = '';
            $coverageName = 'Sin protección adicional';
            $coverageAmount = 0.0;
        }
        $reservationAddonItems = $addonResolved['items'] ?? [];
        $extrasEquip = [];
        foreach ($addonResolved['extras'] ?? [] as $exItem) {
            $extrasEquip[] = [
                'code' => $exItem['item_code'] ?? '',
                'description' => $exItem['item_name'] ?? '',
                'quantity' => (int) ($exItem['quantity'] ?? 1),
                'unit_price' => (float) ($exItem['unit_price'] ?? 0),
                'total_price' => (float) ($exItem['total_price'] ?? 0),
            ];
        }
        if ($extrasEquip !== []) {
            $extras['items'] = $extrasEquip;
        }
        $extrasAmt = (float) ($addonResolved['totals']['extras'] ?? 0);
        $covAmt = (float) ($addonResolved['totals']['coverage'] ?? 0);
        $mandatoryTotal = (float) ($extras['mandatoryTotal'] ?? ($extras['totals']['mandatory'] ?? 0));
        $baseForTotal = is_array($barsQuote) ? (float) ($barsQuote['final_total_rate'] ?? 0) : (float) ($rentalBase ?? 0);
        if ($rateType === 'counter' && is_array($barsQuote)) {
            $baseForTotal = round($baseForTotal * 1.07, 2);
        }
        $subtotal = $baseForTotal + $mandatoryTotal + $covAmt + $extrasAmt;
        $itbms = round($subtotal * 0.07, 2);
        $estimatedTotal = round($subtotal + $itbms, 2);
        if (is_array($extras)) {
            $extras['totals'] = array_merge($extras['totals'] ?? [], [
                'coverage' => $covAmt,
                'extras' => $extrasAmt,
                'itbms' => $itbms,
                'total' => $estimatedTotal,
            ]);
        }
    }
}

if (is_array($barsQuote)) {
    $quoteToken = (string) ($barsQuote['quote_token'] ?? $quoteToken);
    $finalDaily = (float) ($barsQuote['final_daily_rate'] ?? 0);
    $finalTotal = (float) ($barsQuote['final_total_rate'] ?? 0);
    if ($rateType === 'counter') {
        $finalDaily = round($finalDaily * 1.07, 2);
        $finalTotal = round($finalTotal * 1.07, 2);
    }
    $rentalBase = $finalTotal;
    $vehicle['priceWeb'] = $finalDaily;
    $vehicle['priceTotal'] = $finalTotal;
    $vehicle['priceCounter'] = $finalDaily;
    $vehicle['priceCounterTotal'] = $finalTotal;
    $pricing['rateBase'] = $finalTotal;
    $pricing['finalDailyRate'] = $finalDaily;
    $pricing['finalTotalRate'] = $finalTotal;
    $vehicle['pricing'] = $pricing;
}

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
        'quote_token' => $quoteToken !== '' ? $quoteToken : ($pricing['quoteToken'] ?? $vehicle['vendorRateId'] ?? ''),
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
        'bars_cache_key' => is_array($barsQuote) ? ($barsQuote['cache_key'] ?? null) : null,
        'bars_snapshot_id' => is_array($barsQuote) ? ($barsQuote['snapshot_id'] ?? null) : null,
        'calculated_rate_id' => is_array($barsQuote) ? ($barsQuote['calculated_rate_id'] ?? null) : null,
        'vehicle_code' => is_array($barsQuote) ? ($barsQuote['vehicle_code'] ?? null) : ($vehicle['sippCode'] ?? null),
        'rental_days' => is_array($barsQuote) ? ($barsQuote['rental_days'] ?? null) : ($vehicle['rentalDays'] ?? null),
        'currency' => is_array($barsQuote) ? ($barsQuote['currency'] ?? 'USD') : ($vehicle['currency'] ?? 'USD'),
        'base_daily_rate' => is_array($barsQuote) ? ($barsQuote['base_daily_rate'] ?? null) : null,
        'base_total_rate' => is_array($barsQuote) ? ($barsQuote['base_total_rate'] ?? null) : null,
        'final_daily_rate' => is_array($barsQuote) ? ($barsQuote['final_daily_rate'] ?? null) : null,
        'final_total_rate' => is_array($barsQuote) ? ($barsQuote['final_total_rate'] ?? null) : null,
        'discount_amount_total' => is_array($barsQuote) ? ($barsQuote['discount_amount_total'] ?? null) : null,
        'applied_rules_json' => is_array($barsQuote) ? ($barsQuote['applied_rules_json'] ?? []) : null,
        'rate_source' => $rateSource,
        'rate_locked_at' => is_array($barsQuote) ? date('Y-m-d H:i:s') : null,
    ]);

    if (is_array($barsQuote) && $quoteToken !== '') {
        if ($publicRateService === null) {
            $publicRateService = new RacPublicRateService();
        }
        $publicRateService->markQuoteUsed($quoteToken, (int) ($row['id'] ?? 0));
    }

    if ($reservationAddonItems !== []) {
        (new RacAddonService())->saveReservationItems((int) ($row['id'] ?? 0), $reservationAddonItems);
    }

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
