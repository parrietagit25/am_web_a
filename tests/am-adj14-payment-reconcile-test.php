<?php
declare(strict_types=1);

/**
 * AM-ADJ-14 — Reconciliación previa al pago (sin cobro).
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/services/Database.php';
require_once __DIR__ . '/../app/services/RacReservationService.php';
require_once __DIR__ . '/../app/services/RacReservationReconcileService.php';

function adj14_assert(bool $ok, string $msg): void
{
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

$dbPath = __DIR__ . '/../app/storage/database.sqlite';
$bak = $dbPath . '.adj14-bak';
adj14_assert(is_file($dbPath) && copy($dbPath, $bak), 'Backup SQLite para restauración');

register_shutdown_function(static function () use ($dbPath, $bak): void {
    try {
        if (!is_file($bak)) {
            return;
        }
        $ref = new ReflectionClass('Database');
        if ($ref->hasProperty('instance')) {
            $prop = $ref->getProperty('instance');
            $prop->setAccessible(true);
            $inst = $prop->getValue();
            if (is_object($inst) && $ref->hasProperty('pdo')) {
                $pdoProp = $ref->getProperty('pdo');
                $pdoProp->setAccessible(true);
                $pdoProp->setValue($inst, null);
            }
            $prop->setValue(null);
        }
        copy($bak, $dbPath);
        @unlink($bak);
    } catch (Throwable $e) {
        // ignore
    }
});

$svc = new RacReservationReconcileService();
$db = Database::getInstance();

// Fixture técnico sintético (sin PII real)
$code = 'AM-ADJ14-' . strtoupper(bin2hex(random_bytes(3)));
$token = 'adj14tok' . bin2hex(random_bytes(12));
$db->execute(
    "INSERT INTO rac_reservations (
        reservation_code, status, customer_name, customer_email, customer_phone,
        location_code, return_location_code, pickup_date, pickup_time, return_date, return_time,
        driver_age, sipp_code, vehicle_name, vehicle_category, quote_token, rate_type,
        price_total_estimated, price_rental_base, price_itbms, coverage_code, coverage_name,
        extras_snapshot_json, currency, rental_days, vehicle_code, bars_confirmation_code
    ) VALUES (
        :code, 'confirmed', 'Ana Prueba', 'a***@example.test', '60000000',
        'PTY', 'PTY', '2026-08-10', '10:00', '2026-08-13', '10:00',
        '25', 'ECAR', 'Economy Test', 'Economy', :token, 'web',
        133.75, 120.00, 8.75, 'NONE', 'Sin protección adicional',
        :extras, 'USD', 3, 'ECAR', :bars
    )",
    [
        ':code' => $code,
        ':token' => $token,
        ':bars' => $code,
        ':extras' => json_encode([
            'protection' => 'NONE',
            'items' => [],
            'additionalDrivers' => 0,
            'mandatoryTotal' => 5,
            'totals' => ['mandatory' => 5],
        ], JSON_UNESCAPED_UNICODE),
    ]
);

$missing = $svc->reconcile('NO-EXISTE-ADJ14', 'Prueba');
adj14_assert(empty($missing['ok']), 'Reserva inexistente');
adj14_assert(($missing['result'] ?? '') === RacReservationReconcileService::RESULT_NOT_FOUND, 'result not_found');
adj14_assert(($missing['message'] ?? '') === RacReservationReconcileService::GENERIC_NOT_FOUND, 'mensaje genérico');
adj14_assert(empty($missing['payment_created']), 'no crea pago');

$wrong = $svc->reconcile($code, 'OtroApellido');
adj14_assert(empty($wrong['ok']), 'Apellido incorrecto');
adj14_assert(($wrong['result'] ?? '') === RacReservationReconcileService::RESULT_NOT_FOUND, 'apellido = not_found genérico');
adj14_assert(($wrong['message'] ?? '') === ($missing['message'] ?? ''), 'mismo mensaje anti-enumeración');

$html = $svc->reconcile('<script>x</script>', 'Prueba');
adj14_assert(empty($html['ok']), 'HTML rechazado');

$arrayLike = $svc->reconcile($code, '');
adj14_assert(empty($arrayLike['ok']), 'Apellido vacío rechazado');

$ok = $svc->reconcile($code, 'Prueba');
adj14_assert(!empty($ok['ok']), 'Reconciliación válida');
adj14_assert(($ok['payment_available'] ?? true) === false, 'pago no disponible');
adj14_assert(($ok['provider_available'] ?? true) === false, 'proveedor no disponible');
adj14_assert(($ok['payment_created'] ?? true) === false, 'no crea pago');
adj14_assert(($ok['reservation_modified'] ?? true) === false, 'no modifica reserva');
adj14_assert(abs((float) ($ok['amount_stored'] ?? 0) - 133.75) < 0.01, 'monto almacenado desde servidor');
adj14_assert(str_contains((string) ($ok['reservation']['customer_email_masked'] ?? ''), '***@'), 'email enmascarado');
adj14_assert(!str_contains(json_encode($ok), $token), 'token completo no expuesto');
adj14_assert(str_starts_with((string) ($ok['quote']['token_prefix'] ?? ''), 'adj14tok') || ($ok['quote']['token_prefix'] ?? '') === substr($token, 0, 8), 'solo prefijo token');

// Cancelada
$db->execute("UPDATE rac_reservations SET status = 'cancelled' WHERE reservation_code = :c", [':c' => $code]);
$canc = $svc->reconcile($code, 'Prueba');
adj14_assert(empty($canc['ok']), 'Cancelada no elegible');
adj14_assert(($canc['result'] ?? '') === RacReservationReconcileService::RESULT_STATUS_NOT_ALLOWED, 'status_not_allowed');
$db->execute("UPDATE rac_reservations SET status = 'confirmed' WHERE reservation_code = :c", [':c' => $code]);

// Total manipulado en input no aplica (API lo ignora) — servicio no acepta monto cliente
$manip = $svc->reconcile($code, 'Prueba');
adj14_assert(abs((float) $manip['amount_stored'] - 133.75) < 0.01, 'monto no manipulable vía reconcile');

// pago.php no simula cobro
$pago = file_get_contents(__DIR__ . '/../app/api/pago.php');
adj14_assert(str_contains((string) $pago, '503'), 'pago.php responde no disponible');
adj14_assert(!str_contains((string) $pago, 'homepage') || !str_contains((string) $pago, "payments'][]"), 'pago.php no escribe payments');
adj14_assert(!str_contains((string) $pago, 'Confirmación de Pago'), 'sin correo de pago');

$seguro = file_get_contents(__DIR__ . '/../app/public/pago-seguro.php');
adj14_assert(!str_contains((string) $seguro, 'numero_tarjeta'), 'sin campo tarjeta');
adj14_assert(!str_contains((string) $seguro, 'cvv'), 'sin CVV');
adj14_assert(str_contains((string) $seguro, 'rac-reservation-reconcile.php'), 'usa reconcile API');
adj14_assert(str_contains((string) $seguro, 'Pago en línea no disponible'), 'CTA deshabilitado');

$lookup = file_get_contents(__DIR__ . '/../app/api/rac-reservation-lookup.php');
adj14_assert(str_contains((string) $lookup, 'Anti-enumeración') || str_contains((string) $lookup, '404'), 'lookup anti-enumeración');

$mi = file_get_contents(__DIR__ . '/../app/public/mi-reserva.php');
adj14_assert(str_contains((string) $mi, 'pago-seguro.php'), 'CTA verificar monto en mi-reserva');

// No Powertranz en archivos nuevos
$rec = file_get_contents(__DIR__ . '/../app/services/RacReservationReconcileService.php');
adj14_assert(!str_contains((string) $rec, 'Powertranz'), 'servicio sin Powertranz');
adj14_assert(!str_contains((string) $rec, 'payment_status'), 'sin inventar payment_status persistido');

fwrite(STDOUT, "PASS: AM-ADJ-14 reconciliación previa al pago\n");
exit(0);
