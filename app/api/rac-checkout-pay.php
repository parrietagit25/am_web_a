<?php
/**
 * Inicia HPP PowerTranz para un checkout RAC pendiente.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/RacCheckoutStore.php';
require_once __DIR__ . '/../services/PowertranzClient.php';
require_once __DIR__ . '/../services/PowertranzPaymentService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}
$token = trim((string) ($input['token'] ?? ''));
$row = RacCheckoutStore::get($token);
if ($row === null) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Checkout no encontrado.']);
    exit;
}

$status = (string) ($row['status'] ?? '');
if (in_array($status, ['fulfilled', 'paid'], true) && !empty($row['confirmation_code'])) {
    echo json_encode([
        'success' => true,
        'already_paid' => true,
        'redirect' => '/confirmacion.php?code=' . rawurlencode((string) $row['confirmation_code']) . '&token=' . rawurlencode($token),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!PowertranzClient::isEnabled()) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Pasarela no configurada.']);
    exit;
}

$payload = is_array($row['payload'] ?? null) ? $row['payload'] : [];
$customer = [
    'first_name' => (string) ($payload['first_name'] ?? ''),
    'last_name' => (string) ($payload['last_name'] ?? ''),
    'email' => (string) ($payload['customer_email'] ?? $payload['email'] ?? $row['email'] ?? ''),
    'phone' => (string) ($payload['customer_phone'] ?? $payload['phone'] ?? ''),
];

$service = new PowertranzPaymentService();
$init = $service->initCheckoutSale((float) ($row['amount'] ?? 0), $token, $customer);
if (empty($init['ok'])) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => $init['message'] ?? 'No se pudo iniciar el pago.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$payment = is_array($init['payment'] ?? null) ? $init['payment'] : [];
RacCheckoutStore::update($token, [
    'payment_id' => $payment['payment_id'] ?? null,
    'status' => 'payment_started',
]);

echo json_encode([
    'success' => true,
    'payment_id' => $payment['payment_id'] ?? 0,
    'frame_url' => '/api/powertranz-hpp-frame.php?token=' . rawurlencode($token),
    'redirect_html' => $payment['redirect_html'] ?? null,
    'amount' => $row['amount'] ?? 0,
], JSON_UNESCAPED_UNICODE);
