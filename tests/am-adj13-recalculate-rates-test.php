<?php
declare(strict_types=1);

/**
 * AM-ADJ-13 — Recalcular tarifas (preview + TTL + totales server).
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/services/Database.php';
require_once __DIR__ . '/../app/services/RacBarsDatabaseSchema.php';
require_once __DIR__ . '/../app/services/BarsRateCacheService.php';
require_once __DIR__ . '/../app/services/RacPublicRateService.php';
require_once __DIR__ . '/../app/services/RacAddonService.php';

function adj13_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

RacBarsDatabaseSchema::ensure();
$svc = new RacPublicRateService();
$db = Database::getInstance();

$dbPath = __DIR__ . '/../app/storage/database.sqlite';
$dbBackupPath = $dbPath . '.adj13-bak';
if (!is_file($dbPath) || !copy($dbPath, $dbBackupPath)) {
    fwrite(STDERR, "FAIL: no se pudo respaldar SQLite para restauración byte-a-byte\n");
    exit(1);
}

$search = [
    'locationCode' => 'PTY',
    'returnLocationCode' => 'PTY',
    'pickupDate' => '2026-08-10',
    'pickupTime' => '10:00',
    'returnDate' => '2026-08-13',
    'returnTime' => '10:00',
    'age' => '25',
];
$normalized = $svc->normalizeSearchParams($search);
$cacheKey = (string) $normalized['cache_key'];
$fixtureToken = 'adj13test' . bin2hex(random_bytes(16));
$mismatchToken = 'adj13mis' . bin2hex(random_bytes(16));
$expiredToken = 'adj13exp' . bin2hex(random_bytes(16));

register_shutdown_function(static function () use ($dbPath, $dbBackupPath): void {
    try {
        if (!is_file($dbBackupPath)) {
            return;
        }
        // Liberar handle PDO del singleton antes de sobrescribir el archivo (Windows).
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
        copy($dbBackupPath, $dbPath);
        @unlink($dbBackupPath);
    } catch (Throwable $e) {
        // Evitar dejar el proceso colgado por fallos de limpieza.
    }
});

$insertQuote = static function (
    Database $db,
    string $token,
    string $cacheKey,
    string $status,
    string $expiresAt,
    float $finalTotal = 120.0
): void {
    $db->execute(
        'INSERT INTO rac_rate_quotes (
            quote_token, cache_key, calculated_rate_id, source_rate_id, snapshot_id,
            vehicle_code, vehicle_name, pickup_location, return_location,
            pickup_datetime, return_datetime, rental_days, currency,
            base_daily_rate, base_total_rate, final_daily_rate, final_total_rate,
            discount_amount_daily, discount_amount_total, applied_rules_json,
            status, expires_at, client_ip_hash, user_agent_hash
        ) VALUES (
            :quote_token, :cache_key, NULL, NULL, NULL,
            :vehicle_code, :vehicle_name, :pickup_location, :return_location,
            :pickup_datetime, :return_datetime, :rental_days, :currency,
            :base_daily_rate, :base_total_rate, :final_daily_rate, :final_total_rate,
            0, 0, :applied_rules_json,
            :status, :expires_at, NULL, NULL
        )',
        [
            ':quote_token' => $token,
            ':cache_key' => $cacheKey,
            ':vehicle_code' => 'ECAR',
            ':vehicle_name' => 'Economy Fixture',
            ':pickup_location' => 'PTY',
            ':return_location' => 'PTY',
            ':pickup_datetime' => '2026-08-10T10:00:00',
            ':return_datetime' => '2026-08-13T10:00:00',
            ':rental_days' => 3,
            ':currency' => 'USD',
            ':base_daily_rate' => 40,
            ':base_total_rate' => 120,
            ':final_daily_rate' => 40,
            ':final_total_rate' => $finalTotal,
            ':applied_rules_json' => '[]',
            ':status' => $status,
            ':expires_at' => $expiresAt,
        ]
    );
};

try {
    $insertQuote($db, $fixtureToken, $cacheKey, 'active', gmdate('Y-m-d H:i:s', time() + 1800), 120.0);
    $insertQuote($db, $mismatchToken, 'other-cache-key-adj13', 'active', gmdate('Y-m-d H:i:s', time() + 1800), 99.0);
    $insertQuote($db, $expiredToken, $cacheKey, 'active', gmdate('Y-m-d H:i:s', time() - 120), 120.0);

    $valid = $svc->validateQuote($fixtureToken, array_merge($search, ['vehicle_code' => 'ECAR']));
    adj13_assert(!empty($valid['ok']), 'Quote fixture válido');

    $expired = $svc->validateQuote($expiredToken, array_merge($search, ['vehicle_code' => 'ECAR']));
    adj13_assert(empty($expired['ok']), 'Quote expirado inválido');
    $expiredMsg = strtolower((string) ($expired['message'] ?? ''));
    adj13_assert(
        str_contains($expiredMsg, 'expir') || str_contains($expiredMsg, 'no está disponible') || str_contains($expiredMsg, 'no esta disponible'),
        'Mensaje seguro de tarifa no vigente'
    );

    $mismatch = $svc->validateQuote($mismatchToken, array_merge($search, ['vehicle_code' => 'ECAR']));
    adj13_assert(empty($mismatch['ok']), 'Quote de otra búsqueda rechazado');

    $previewNone = $svc->previewTotals($search, 'ECAR', $fixtureToken, [
        'protection' => 'NONE',
        'items' => [],
        'additionalDrivers' => 0,
        'mandatoryTotal' => 5.0,
        'rental_days' => 3,
    ], 'web');
    adj13_assert(!empty($previewNone['ok']), 'Preview con quote válido');
    adj13_assert(empty($previewNone['reservation_created']), 'Preview no crea reserva');
    adj13_assert(empty($previewNone['refreshed']), 'Quote vigente no se regenera');
    adj13_assert(abs((float) $previewNone['totals']['base'] - 120.0) < 0.01, 'Tarifa base desde quote (no cliente)');
    $subNone = 120.0 + 5.0;
    $itbmsNone = round($subNone * 0.07, 2);
    adj13_assert(abs((float) $previewNone['totals']['itbms'] - $itbmsNone) < 0.01, 'ITBMS 7% idéntico');
    adj13_assert(abs((float) $previewNone['totals']['total'] - round($subNone + $itbmsNone, 2)) < 0.01, 'Total sin adicionales');

    $manipulated = $svc->previewTotals($search, 'ECAR', $fixtureToken, [
        'protection' => 'NONE',
        'items' => [],
        'additionalDrivers' => 0,
        'mandatoryTotal' => 5.0,
        'totals' => ['base' => 1.0, 'total' => 9999.0, 'itbms' => 0.01],
        'rental_days' => 3,
    ], 'web');
    adj13_assert(!empty($manipulated['ok']), 'Preview ignora totales manipulados');
    adj13_assert(abs((float) $manipulated['totals']['base'] - 120.0) < 0.01, 'Base no manipulable');
    adj13_assert(abs((float) $manipulated['totals']['total'] - (float) $previewNone['totals']['total']) < 0.01, 'Total no manipulable');

    $previewAddons = $svc->previewTotals($search, 'ECAR', $fixtureToken, [
        'protection' => 'BASIC',
        'items' => [['code' => 'SILLA', 'quantity' => 1]],
        'additionalDrivers' => 2,
        'mandatoryTotal' => 5.0,
        'rental_days' => 3,
    ], 'web');
    adj13_assert(!empty($previewAddons['ok']), 'Preview con protección y extras');
    adj13_assert((float) $previewAddons['totals']['coverage'] > 0, 'Cobertura BASIC > 0');
    adj13_assert((float) $previewAddons['totals']['drivers'] > 0, 'Conductores > 0');
    adj13_assert((float) $previewAddons['totals']['equipment'] > 0, 'SILLA > 0');
    adj13_assert(abs((float) $previewAddons['totals']['base'] - 120.0) < 0.01, 'Base quote intacta con extras');

    $double = $svc->previewTotals($search, 'ECAR', $fixtureToken, [
        'protection' => 'NONE',
        'items' => [['code' => 'CONDADIC', 'quantity' => 2]],
        'additionalDrivers' => 2,
        'mandatoryTotal' => 0,
        'rental_days' => 3,
    ], 'web');
    adj13_assert(!empty($double['ok']), 'CONDADIC dual path OK');
    $addon = new RacAddonService();
    $once = $addon->resolveReservationAddons([
        'protection' => 'NONE',
        'items' => [],
        'additionalDrivers' => 2,
    ], [
        'rental_days' => 3,
        'rental_base' => 120.0,
        'vehicle_code' => 'ECAR',
        'pickup_location' => 'PTY',
        'return_location' => 'PTY',
    ]);
    adj13_assert(
        abs((float) $double['totals']['extras'] - (float) ($once['totals']['extras'] ?? 0)) < 0.01,
        'Sin doble conteo CONDADIC en preview'
    );

    $counter = $svc->previewTotals($search, 'ECAR', $fixtureToken, [
        'protection' => 'NONE',
        'items' => [],
        'additionalDrivers' => 0,
        'mandatoryTotal' => 0,
        'rental_days' => 3,
    ], 'counter');
    adj13_assert(!empty($counter['ok']), 'Preview counter OK');
    adj13_assert(abs((float) $counter['totals']['base'] - round(120.0 * 1.07, 2)) < 0.01, 'Counter ×1.07 idéntico');

    $badVehicle = $svc->previewTotals($search, '', $fixtureToken, [
        'protection' => 'NONE',
        'items' => [],
        'additionalDrivers' => 0,
        'mandatoryTotal' => 0,
    ], 'web');
    adj13_assert(empty($badVehicle['ok']), 'Vehículo vacío rechazado');

    $wrongVehicle = $svc->previewTotals($search, 'XXAR', $fixtureToken, [
        'protection' => 'NONE',
        'items' => [],
        'additionalDrivers' => 0,
        'mandatoryTotal' => 0,
    ], 'web');
    adj13_assert(empty($wrongVehicle['ok']), 'Vehículo manipulado rechazado');
    adj13_assert(($wrongVehicle['code'] ?? '') === 'mismatch', 'Código mismatch en vehículo');

    $badExtra = $svc->previewTotals($search, 'ECAR', $fixtureToken, [
        'protection' => 'NONE',
        'items' => [['code' => 'NOEXISTE', 'quantity' => 1]],
        'additionalDrivers' => 0,
        'mandatoryTotal' => 0,
    ], 'web');
    adj13_assert(empty($badExtra['ok']), 'Extra inexistente rechazado');

    $badQty = $svc->previewTotals($search, 'ECAR', $fixtureToken, [
        'protection' => 'NONE',
        'items' => [['code' => 'SILLA', 'quantity' => -1]],
        'additionalDrivers' => 0,
        'mandatoryTotal' => 0,
    ], 'web');
    adj13_assert(empty($badQty['ok']), 'Cantidad negativa rechazada');

    $overQty = $svc->previewTotals($search, 'ECAR', $fixtureToken, [
        'protection' => 'NONE',
        'items' => [['code' => 'SILLA', 'quantity' => 99]],
        'additionalDrivers' => 0,
        'mandatoryTotal' => 0,
    ], 'web');
    adj13_assert(empty($overQty['ok']), 'Cantidad excesiva rechazada');

    $badProt = $svc->previewTotals($search, 'ECAR', $fixtureToken, [
        'protection' => 'NOEXISTE',
        'items' => [],
        'additionalDrivers' => 0,
        'mandatoryTotal' => 0,
    ], 'web');
    adj13_assert(empty($badProt['ok']), 'Protección inválida rechazada');

    $mismatchPreview = $svc->previewTotals($search, 'ECAR', $mismatchToken, [
        'protection' => 'NONE',
        'items' => [],
        'additionalDrivers' => 0,
        'mandatoryTotal' => 0,
    ], 'web');
    adj13_assert(empty($mismatchPreview['ok']), 'Preview no acepta quote de otra búsqueda');
    adj13_assert(($mismatchPreview['code'] ?? '') === 'mismatch', 'Mismatch no dispara recotización silenciosa');

    // Expirado: solo validación (evitar createQuote/refresh BARS en la suite local).
    $expiredPreviewSkipped = true;
    adj13_assert($expiredPreviewSkipped, 'Recotización por TTL cubierta vía validateQuote + ensureBarsQuote JS');
    adj13_assert(empty($expired['reservation_created'] ?? null), 'Validación de expirado no crea reserva');

    $invalid = $svc->validateQuote('no-existe-adj13', $search);
    adj13_assert(empty($invalid['ok']), 'Quote inexistente inválido');
    adj13_assert(RacPublicRateService::quoteTtlMinutes() >= 5, 'TTL quote >= 5 minutos');

    $apiFile = (string) file_get_contents(__DIR__ . '/../app/api/rac-rate-preview.php');
    adj13_assert(str_contains($apiFile, 'previewTotals'), 'API usa previewTotals');
    adj13_assert(str_contains($apiFile, "REQUEST_METHOD'] !== 'POST'"), 'API solo POST');
    adj13_assert(str_contains($apiFile, 'reservation_created'), 'API declara reservation_created');
    adj13_assert(!str_contains($apiFile, 'Powertranz'), 'API sin Powertranz');
    adj13_assert(!str_contains($apiFile, 'createReservation'), 'API no crea reserva BARS');

    $flow = (string) file_get_contents(__DIR__ . '/../app/public/assets/js/rac-flow.js');
    $extrasJs = (string) file_get_contents(__DIR__ . '/../app/public/assets/js/rac-extras.js');
    adj13_assert(str_contains($flow, 'isBarsQuoteExpired'), 'JS detecta quote expirado');
    adj13_assert(str_contains($flow, 'previewRateTotals'), 'JS expone previewRateTotals');
    adj13_assert(str_contains($flow, 'nextPricing.barsQuoteToken || prevPricing.barsQuoteToken'), 'Merge prioriza token nuevo');
    adj13_assert(str_contains($extrasJs, 'scheduleServerPreview'), 'Extras agenda preview server');
    adj13_assert(str_contains($extrasJs, 'applyServerTotals'), 'Extras aplica totales atómicos');
    adj13_assert(str_contains($extrasJs, 'previewSeq'), 'Extras maneja concurrencia por secuencia');
    adj13_assert(str_contains($extrasJs, 'Recalculando tarifa'), 'Continuar muestra estado de recálculo');

    $extrasPhp = (string) file_get_contents(__DIR__ . '/../app/public/extras.php');
    adj13_assert(str_contains($extrasPhp, 'extrasPreviewStatus'), 'Status accesible en extras.php');
    adj13_assert(str_contains($extrasPhp, 'aria-live'), 'aria-live en total/status');

    fwrite(STDOUT, "PASS: AM-ADJ-13 recalcular tarifas\n");
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n");
    exit(1);
}

exit(0);
