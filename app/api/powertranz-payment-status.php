<?php
/**
 * Estado de pago Powertranz (sin datos sensibles) — AM-RAC-PAY-POWERTRANZ-0A/0B.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/AdminUserService.php';
require_once __DIR__ . '/../services/PowertranzPaymentService.php';
require_once __DIR__ . '/../includes/admin-auth.php';

AdminUserService::ensureSchema();

if (!AdminUserService::isLoggedIn() || !AdminUserService::isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$paymentId = (int) ($_GET['payment_id'] ?? $_POST['payment_id'] ?? 0);
$reference = trim((string) ($_GET['test_reference'] ?? $_GET['reference'] ?? $_POST['test_reference'] ?? $_POST['reference'] ?? ''));

$service = new PowertranzPaymentService();
$payment = null;

if ($paymentId > 0) {
    $payment = $service->getPublicPayment($paymentId);
} elseif ($reference !== '') {
    $payment = $service->getPublicPaymentByReference($reference);
} else {
    $payment = $service->getLastTestPayment();
}

if ($payment === null) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Pago no encontrado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode($payment, JSON_UNESCAPED_UNICODE);
