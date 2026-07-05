<?php
/**
 * RedirectData Powertranz sin layout — solo super admin.
 * AM-RAC-PAY-POWERTRANZ-0B diagnostic
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/AdminUserService.php';
require_once __DIR__ . '/../../services/PowertranzPaymentService.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

AdminUserService::ensureSchema();
admin_require_login();

if (!AdminUserService::isSuperAdmin()) {
    http_response_code(403);
    echo 'Acceso denegado.';
    exit;
}

$paymentId = (int) ($_GET['payment_id'] ?? 0);
if ($paymentId <= 0) {
    http_response_code(400);
    echo 'payment_id inválido.';
    exit;
}

$service = new PowertranzPaymentService();
$frame = $service->getPaymentForFrame($paymentId);

if ($frame === null || ($frame['redirect_html'] ?? '') === '') {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="es"><body><p>HPP no disponible para este pago.</p></body></html>';
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: SAMEORIGIN');
header('Content-Security-Policy: frame-ancestors \'self\'');

$service->markHppOpened($paymentId);

echo (string) $frame['redirect_html'];
