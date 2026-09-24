<?php
/**
 * API lab — postear pago a RentWorks desde admin/pago-test.php.
 * Solo super admin. No toca checkout público ni RacCheckoutFulfillment.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/AdminUserService.php';
require_once __DIR__ . '/../services/BarsPaymentLabService.php';
require_once __DIR__ . '/../includes/admin-auth.php';

AdminUserService::ensureSchema();

if (!AdminUserService::isLoggedIn() || !AdminUserService::isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado. Requiere super admin.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method === 'GET') {
    $action = trim((string) ($_GET['action'] ?? 'prefill_payment'));
    $result = BarsPaymentLabService::run([
        'action' => $action,
        'payment_id' => isset($_GET['payment_id']) ? (int) $_GET['payment_id'] : 0,
        'reservation_code' => (string) ($_GET['reservation_code'] ?? $_GET['res_number'] ?? ''),
        'last_name' => (string) ($_GET['last_name'] ?? ''),
    ]);
    if (empty($result['ok'])) {
        http_response_code(422);
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$token = (string) ($input['admin_csrf_token'] ?? '');
if (!admin_verify_csrf($token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'CSRF inválido. Recarga la página.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = BarsPaymentLabService::run($input);
if (empty($result['ok'])) {
    http_response_code(422);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
