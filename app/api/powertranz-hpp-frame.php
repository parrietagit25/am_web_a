<?php
/**
 * Sirve RedirectData HPP en origen propio (evita srcdoc + CSP del layout).
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/RacCheckoutStore.php';
require_once __DIR__ . '/../services/PowertranzPaymentService.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
header('X-Frame-Options: SAMEORIGIN');
header("Content-Security-Policy: frame-ancestors 'self'");

$token = trim((string) ($_GET['token'] ?? ''));
$row = RacCheckoutStore::get($token);
if ($row === null) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="es"><body><p>Checkout no encontrado.</p></body></html>';
    exit;
}

$paymentId = (int) ($row['payment_id'] ?? 0);
if ($paymentId <= 0) {
    http_response_code(409);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="es"><body><p>El pago aún no está listo.</p></body></html>';
    exit;
}

$service = new PowertranzPaymentService();
$frame = $service->getPaymentForFrame($paymentId);
$html = is_array($frame) ? (string) ($frame['redirect_html'] ?? '') : '';
if ($html === '') {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="es"><body><p>Formulario HPP no disponible.</p></body></html>';
    exit;
}

$service->markHppOpened($paymentId);
header('Content-Type: text/html; charset=UTF-8');
echo $html;
