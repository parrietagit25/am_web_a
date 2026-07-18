<?php
/**
 * API: Crear quote server-side para tarifa RAC (BARS cache).
 * AM-RAC-BARS-RAC-3A
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

if (!RacPublicRateService::isBarsPricingEnabled()) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Tarifas en línea no disponibles temporalmente.']);
    exit;
}

$vehicleCode = strtoupper(trim((string) ($input['vehicle_code'] ?? $input['sippCode'] ?? '')));
if ($vehicleCode === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Código de vehículo requerido.']);
    exit;
}

$service = new RacPublicRateService();
$result = $service->createQuote($input, $vehicleCode);

if (!($result['ok'] ?? false)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $result['message'] ?? 'No se pudo bloquear la tarifa.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(200);
echo json_encode([
    'success' => true,
    'quote_token' => $result['quote_token'],
    'expires_at' => $result['expires_at'],
    'vehicle' => $result['vehicle'],
    'pricing' => $result['pricing'],
    'rate_qualifier' => $result['rate_qualifier'] ?? RacPublicRateService::BARS_RATE_QUALIFIER,
    'rate_channels' => $result['rate_channels'] ?? RacPublicRateService::allRateChannelDescriptors(),
    'prepayment_available' => false,
    'payment_provider_available' => false,
    'online_payment_available' => false,
], JSON_UNESCAPED_UNICODE);
