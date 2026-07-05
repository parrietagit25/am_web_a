<?php
/**
 * MerchantResponseUrl — retorno 3DS/HPP Powertranz.
 * AM-RAC-PAY-POWERTRANZ-0A/0B
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/PowertranzPaymentService.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, no-cache, must-revalidate');

$rawBody = (string) file_get_contents('php://input');
$parsed = null;

if ($rawBody !== '') {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $parsed = $decoded;
    }
}

if ($parsed === null && ($_POST !== [] || $_GET !== [])) {
    $parsed = array_merge($_GET, $_POST);
}

$requestMeta = [
    'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
    'content_type' => (string) ($_SERVER['CONTENT_TYPE'] ?? ''),
    'get' => $_GET,
    'post' => $_POST,
];

$service = new PowertranzPaymentService();
$result = $service->handleMerchantReturn($rawBody, $parsed, $requestMeta);

$payment = $result['payment'] ?? null;
$paymentId = (int) ($payment['payment_id'] ?? 0);
$status = (string) ($payment['status'] ?? 'error');
$approved = !empty($payment['approved']);

$title = 'Procesando pago';
$message = (string) ($result['message'] ?? 'Procesando respuesta de Powertranz.');
$alertClass = 'secondary';

if ($approved || $status === 'approved') {
    $title = 'Pago aprobado';
    $message = 'La transacción fue aprobada.';
    $alertClass = 'success';
} elseif ($status === 'declined') {
    $title = 'Pago rechazado';
    $message = (string) ($payment['response_message'] ?? 'La transacción fue rechazada.');
    $alertClass = 'danger';
} elseif ($status === 'return_error') {
    $title = 'Retorno inválido';
    $message = (string) ($payment['error_message'] ?? 'MerchantResponseUrl recibido sin payload válido.');
    $alertClass = 'warning';
} elseif ($status === 'complete_error') {
    $title = 'Error al completar pago';
    $message = (string) ($payment['error_message'] ?? 'Error en completePayment.');
    $alertClass = 'warning';
} elseif ($status === 'expired') {
    $title = 'Pago expirado';
    $message = 'El SpiToken expiró. Inicie un pago nuevo.';
    $alertClass = 'warning';
} elseif (!$result['ok']) {
    $title = 'Error procesando pago';
    $alertClass = 'warning';
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{background:#f8f9fc;font-family:system-ui,sans-serif}</style>
</head>
<body class="p-4">
<div class="container" style="max-width:520px">
    <div class="alert alert-<?php echo htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8'); ?> shadow-sm">
        <h1 class="h5 mb-2"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="mb-1"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php if ($paymentId > 0): ?>
            <p class="small mb-0 text-muted">Referencia interna: #<?php echo (int) $paymentId; ?></p>
        <?php endif; ?>
    </div>
</div>
<script>
(function () {
    try {
        if (window.parent && window.parent !== window) {
            window.parent.postMessage({
                type: 'powertranz-result',
                payment_id: <?php echo (int) $paymentId; ?>,
                status: <?php echo json_encode($status, JSON_UNESCAPED_UNICODE); ?>,
                approved: <?php echo $approved ? 'true' : 'false'; ?>
            }, '*');
        }
    } catch (e) {}
})();
</script>
</body>
</html>
