<?php
/**
 * Inicia pago de prueba Powertranz HPP/3DS — AM-RAC-PAY-POWERTRANZ-0A/0B.
 * Solo super admin autenticado.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/AdminUserService.php';
require_once __DIR__ . '/../services/PowertranzClient.php';
require_once __DIR__ . '/../services/PowertranzPaymentService.php';
require_once __DIR__ . '/../includes/admin-auth.php';

AdminUserService::ensureSchema();

if (!AdminUserService::isLoggedIn() || !AdminUserService::isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado. Requiere super admin.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = $method === 'POST'
    ? (json_decode((string) file_get_contents('php://input'), true) ?: $_POST)
    : $_GET;

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Parámetros inválidos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!PowertranzClient::isEnabled()) {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'error' => 'Powertranz deshabilitado o sin credenciales. Revise POWERTRANZ_* en config.php.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$client = new PowertranzClient();
$hppWarning = '';
if (!$client->hasHppConfig()) {
    $hppWarning = 'POWERTRANZ_HPP_PAGE_SET / POWERTRANZ_HPP_PAGE_NAME no definidos. Se intentará sin HostedPage.';
}

$amount = isset($input['amount']) ? (float) $input['amount'] : 1.0;
$mode = trim((string) ($input['mode'] ?? 'sale'));
$reservationId = isset($input['reservation_id']) && $input['reservation_id'] !== ''
    ? (int) $input['reservation_id']
    : null;

$service = new PowertranzPaymentService($client);
$result = $service->initTestPayment($amount, $mode, $reservationId);

$payment = $result['payment'] ?? null;
$api = $result['api'] ?? [];
$endpoint = strtolower($mode) === 'auth' ? '/api/spi/auth' : '/api/spi/sale';

http_response_code($result['ok'] ? 200 : 422);
echo json_encode([
    'ok' => (bool) ($result['ok'] ?? false),
    'message' => (string) ($result['message'] ?? ''),
    'hpp_warning' => $hppWarning !== '' ? $hppWarning : null,
    'endpoint' => $endpoint,
    'payment_id' => $payment['payment_id'] ?? null,
    'test_reference' => $payment['test_reference'] ?? null,
    'payment_reference' => $payment['payment_reference'] ?? null,
    'order_identifier' => $payment['order_identifier'] ?? null,
    'transaction_identifier' => $payment['transaction_identifier'] ?? null,
    'status' => $payment['status'] ?? null,
    'http_code' => (int) ($api['http_code'] ?? 0),
    'iso_response_code' => $api['iso_response_code'] ?? ($payment['iso_response_code'] ?? null),
    'response_message' => $api['response_message'] ?? ($payment['response_message'] ?? null),
    'has_redirect_data' => (bool) ($api['has_redirect_data'] ?? ($payment['has_redirect_data'] ?? false)),
    'has_spi_token' => (bool) ($api['has_spi_token'] ?? ($payment['has_spi_token'] ?? false)),
    'frame_url' => (!empty($payment['has_redirect_data']) && !empty($payment['payment_id']))
        ? '/admin/powertranz-payment-frame.php?payment_id=' . (int) $payment['payment_id']
        : ($payment['frame_url'] ?? null),
    'updated_at' => $payment['updated_at'] ?? null,
], JSON_UNESCAPED_UNICODE);
