<?php
/**
 * API: Reconciliación previa al pago (AM-ADJ-14).
 * No crea cobros ni modifica reservas BARS.
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/RacReservationReconcileService.php';

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

if (isset($input['monto']) || isset($input['amount']) || isset($input['price_total_estimated']) || isset($input['total'])) {
    // Montos del cliente se ignoran; la fuente de verdad es el servidor.
}

$code = $input['code'] ?? $input['confirmation_number'] ?? $input['reserva_id'] ?? '';
$lastName = $input['lastName'] ?? $input['last_name'] ?? '';

if (is_array($code) || is_array($lastName)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'result' => RacReservationReconcileService::RESULT_NOT_FOUND,
        'message' => RacReservationReconcileService::GENERIC_NOT_FOUND,
        'payment_available' => false,
        'payment_created' => false,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$service = new RacReservationReconcileService();
$result = $service->reconcile((string) $code, (string) $lastName);

$http = !empty($result['ok']) ? 200 : (
    ($result['result'] ?? '') === RacReservationReconcileService::RESULT_NOT_FOUND ? 404 : 422
);

http_response_code($http);
echo json_encode([
    'success' => !empty($result['ok']),
    'result' => $result['result'] ?? null,
    'message' => $result['message'] ?? '',
    'payment_available' => false,
    'provider_available' => false,
    'reservation_modified' => false,
    'payment_created' => false,
    'reservation' => $result['reservation'] ?? null,
    'amount_due' => $result['amount_due'] ?? null,
    'amount_stored' => $result['amount_stored'] ?? null,
    'amount_recalculated' => $result['amount_recalculated'] ?? null,
    'currency' => $result['currency'] ?? null,
    'totals' => $result['totals'] ?? null,
    'quote' => $result['quote'] ?? null,
], JSON_UNESCAPED_UNICODE);
