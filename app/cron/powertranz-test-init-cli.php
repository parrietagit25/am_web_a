<?php
/**
 * Init de prueba Powertranz desde CLI (sin sesión admin).
 * AM-RAC-PAY-POWERTRANZ-0B — solo servidor test / diagnóstico.
 *
 * Uso: php app/cron/powertranz-test-init-cli.php [--amount=1.00]
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Solo CLI.\n");
    exit(1);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/PowertranzClient.php';
require_once __DIR__ . '/../services/PowertranzPaymentService.php';
require_once __DIR__ . '/../services/PowertranzSanitizer.php';

$amount = 1.0;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--amount=')) {
        $amount = max(0.01, (float) substr($arg, 9));
    }
}

if (!PowertranzClient::isEnabled()) {
    fwrite(STDERR, "Powertranz no configurado.\n");
    exit(2);
}

$service = new PowertranzPaymentService();
$result = $service->initTestPayment($amount, 'sale');
$payment = $result['payment'] ?? [];
$api = $result['api'] ?? [];
$diag = $api['diagnostic'] ?? ($payment['init_diagnostic'] ?? null);

$out = [
    'ok' => (bool) ($result['ok'] ?? false),
    'payment_id' => $payment['payment_id'] ?? null,
    'status' => $payment['status'] ?? null,
    'iso_response_code' => $payment['iso_response_code'] ?? ($api['iso_response_code'] ?? null),
    'response_message' => $payment['response_message'] ?? null,
    'has_redirect_data' => !empty($payment['has_redirect_data']),
    'http_code' => (int) ($api['http_code'] ?? 0),
    'diagnostic' => is_array($diag) ? PowertranzSanitizer::sanitizePayload($diag) : null,
];

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit($result['ok'] ? 0 : 1);
