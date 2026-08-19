<?php
declare(strict_types=1);

/**
 * AM-ADJ-18 — Paso 5 pago PowerTranz, paso 6 confirmación. Sin pago no hay reserva BARS.
 */

function adj18_assert(bool $ok, string $msg): void
{
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

$stepper = (string) file_get_contents(__DIR__ . '/../app/includes/rac-stepper.php');
adj18_assert(str_contains($stepper, "'Pago'"), 'Stepper incluye paso Pago');
adj18_assert(str_contains($stepper, 'min(6'), 'Stepper admite 6 pasos');
adj18_assert(substr_count($stepper, 'label') >= 6, 'Hay 6 etiquetas de paso');

$pago = (string) file_get_contents(__DIR__ . '/../app/public/pago.php');
adj18_assert(str_contains($pago, '$racStep = 5'), 'pago.php es paso 5');
adj18_assert(str_contains($pago, 'iframe') || str_contains($pago, 'RedirectData'), 'pago.php embebido HPP');

$confirm = (string) file_get_contents(__DIR__ . '/../app/public/confirmacion.php');
adj18_assert(str_contains($confirm, '$racStep = 6'), 'confirmacion.php es paso 6');

$reserveJs = (string) file_get_contents(__DIR__ . '/../app/public/reservar.php');
adj18_assert(str_contains($reserveJs, '/api/rac-checkout.php'), 'Reservar inicia checkout de pago, no BARS directo');
adj18_assert(!str_contains($reserveJs, "fetch('/api/rac-reservation.php'"), 'Reservar ya no crea en RentWorks antes de pagar');

$resApi = (string) file_get_contents(__DIR__ . '/../app/api/rac-reservation.php');
adj18_assert(str_contains($resApi, 'checkout_fulfill') || str_contains($resApi, 'RacCheckoutStore'), 'Create BARS exige fulfill de pago');

$flow = (string) file_get_contents(__DIR__ . '/../app/public/assets/js/rac-flow.js');
adj18_assert(str_contains($flow, '/pago.php'), 'Flujo conoce paso pago');

$captchaJs = (string) file_get_contents(__DIR__ . '/../app/public/assets/js/captcha.js');
adj18_assert(str_contains($captchaJs, '/api/rac-checkout.php'), 'Captcha inyecta token en checkout');

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/services/PowertranzClient.php';
adj18_assert(PowertranzClient::hppPageSet() === 'PTZ/Payment', 'API PageSet PTZ/Payment (portal Payment)');
adj18_assert(PowertranzClient::hppPageName() === 'Payment', 'HPP PageName Payment');

require_once __DIR__ . '/../app/services/RacCheckoutStore.php';
$token = 'chk_' . bin2hex(random_bytes(8));
$saved = RacCheckoutStore::save([
    'token' => $token,
    'status' => 'pending_payment',
    'amount' => 104.29,
    'payload' => ['first_name' => 'Ana'],
]);
adj18_assert(!empty($saved['token']), 'Store guarda checkout');
$loaded = RacCheckoutStore::get($token);
adj18_assert((float) ($loaded['amount'] ?? 0) === 104.29, 'Store recupera monto');
RacCheckoutStore::update($token, ['status' => 'paid']);
$paid = RacCheckoutStore::get($token);
adj18_assert(($paid['status'] ?? '') === 'paid', 'Store marca pagado');
RacCheckoutStore::delete($token);

fwrite(STDOUT, "PASS: AM-ADJ-18 pago paso 5 / confirmación paso 6\n");
exit(0);
