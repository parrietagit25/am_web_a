<?php
/**
 * Vista iframe RedirectData Powertranz — solo admin.
 * AM-RAC-PAY-POWERTRANZ-0A
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
$payment = $service->getPublicPayment($paymentId);

if ($frame === null || ($frame['redirect_html'] ?? '') === '') {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    $ref = $paymentId;
    $status = is_array($payment) ? (string) ($payment['status'] ?? '') : '';
    $errMsg = is_array($payment) ? (string) ($payment['error_message'] ?? '') : '';
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HPP no disponible</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container" style="max-width:520px">
    <div class="alert alert-warning shadow-sm">
        <h1 class="h5 mb-2">HPP no disponible</h1>
        <p class="mb-1">Este intento no tiene HPP disponible porque el init falló<?php echo $status === 'complete_error' ? ' o el completado devolvió error' : ''; ?>.</p>
        <?php if ($errMsg !== ''): ?>
            <p class="small mb-1 text-muted"><?php echo htmlspecialchars($errMsg, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <p class="small mb-0 text-muted">Referencia interna: #<?php echo (int) $ref; ?></p>
        <p class="small mb-0 mt-2">No se intentará completar el pago desde esta vista.</p>
    </div>
</div>
</body>
</html>
    <?php
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: SAMEORIGIN');
header('Content-Security-Policy: frame-ancestors \'self\'');

echo (string) $frame['redirect_html'];
