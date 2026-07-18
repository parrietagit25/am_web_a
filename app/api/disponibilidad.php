<?php
/**
 * API Endpoint: Vehicle Availability (BARS cache + partner fallback).
 * AM-RAC-BARS-RAC-3A
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/BranchDataService.php';
require_once __DIR__ . '/../services/AutomarketApiService.php';
require_once __DIR__ . '/../services/RacPublicRateService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Solo se acepta POST.',
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Cuerpo de solicitud inválido o JSON mal formado.',
    ]);
    exit;
}

$pickupLocation = strtoupper(trim($input['locationCode'] ?? ''));
$returnLocation = strtoupper(trim($input['returnLocationCode'] ?? ''));
$pickupDate = trim($input['pickupDate'] ?? '');
$pickupTime = trim($input['pickupTime'] ?? '10:00');
$returnDate = trim($input['returnDate'] ?? '');
$returnTime = trim($input['returnTime'] ?? '10:00');
$age = trim((string) ($input['age'] ?? '25'));
$promoCode = trim($input['promoCode'] ?? '');

if ($returnLocation === '') {
    $returnLocation = $pickupLocation;
}

if ($pickupLocation === '' || $pickupDate === '' || $returnDate === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Faltan campos obligatorios: locationCode, pickupDate, returnDate.',
    ]);
    exit;
}

if (!in_array($age, ['23', '25'], true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Edad no válida. Solo se admiten 23-24 años o 25+ años en línea.',
    ]);
    exit;
}

if ($pickupDate >= $returnDate) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'La fecha de devolución debe ser posterior al retiro.',
    ]);
    exit;
}

if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $pickupTime)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Hora de retiro inválida. Use formato HH:MM (24h).',
    ]);
    exit;
}

if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $returnTime)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Hora de devolución inválida. Use formato HH:MM (24h).',
    ]);
    exit;
}

$result = null;

if (RacPublicRateService::isBarsPricingEnabled()) {
    $publicRateService = new RacPublicRateService();
    $result = $publicRateService->getPublicRates($input);
}

if ($result === null || !($result['success'] ?? false)) {
    $apiService = new AutomarketApiService();
    if ($apiService->isConfigured()) {
        $partnerResult = $apiService->getAvailability([
            'locationCode' => $pickupLocation,
            'returnLocationCode' => $returnLocation,
            'pickupDate' => $pickupDate,
            'pickupTime' => $pickupTime,
            'returnDate' => $returnDate,
            'returnTime' => $returnTime,
            'age' => $age,
            'promoCode' => $promoCode,
        ]);
        if ($partnerResult['success'] ?? false) {
            $result = $partnerResult;
        } elseif ($result === null) {
            $result = $partnerResult;
        }
    } elseif ($result === null) {
        $result = [
            'success' => false,
            'message' => 'No pudimos consultar disponibilidad para esos datos. Intenta con otra fecha o comunícate con nosotros.',
            'vehicles' => [],
        ];
    }
}

$branch = BranchDataService::findByCode($returnLocation);
if ($branch && !empty($branch['note']) && empty($result['vehicles']) && empty($result['miss'])) {
    $result['branchNote'] = $branch['note'];
}

// AM-ADJ-15: prepago/pago online no disponibles (aunque el fallback partner no lo declare).
$result['prepayment_available'] = false;
$result['payment_provider_available'] = false;
$result['online_payment_available'] = false;
if (empty($result['rate_channels']) || !is_array($result['rate_channels'] ?? null)) {
    $result['rate_channels'] = RacPublicRateService::allRateChannelDescriptors();
}
if (empty($result['rateCodes']) || !is_array($result['rateCodes'])) {
    $result['rateCodes'] = ['WEB'];
}
if (empty($result['rate_qualifier'])) {
    $result['rate_qualifier'] = RacPublicRateService::BARS_RATE_QUALIFIER;
}

http_response_code($result['success'] ? 200 : 502);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
