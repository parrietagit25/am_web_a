<?php
declare(strict_types=1);

/**
 * AM-ADJ-15 — Tarifas web y preparación neutral de prepago (sin cobros).
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/services/Database.php';
require_once __DIR__ . '/../app/services/RacBarsDatabaseSchema.php';
require_once __DIR__ . '/../app/services/RacPublicRateService.php';
require_once __DIR__ . '/../app/services/AutomarketReservationApiService.php';
require_once __DIR__ . '/../app/services/RacReservationReconcileService.php';

function adj15_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

RacBarsDatabaseSchema::ensure();
$svc = new RacPublicRateService();
$dbPath = __DIR__ . '/../app/storage/database.sqlite';
$dbBackupPath = $dbPath . '.adj15-bak';
adj15_assert(is_file($dbPath) && copy($dbPath, $dbBackupPath), 'backup SQLite');

register_shutdown_function(static function () use ($dbPath, $dbBackupPath): void {
    try {
        if (!is_file($dbBackupPath)) {
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
        copy($dbBackupPath, $dbPath);
        @unlink($dbBackupPath);
    } catch (Throwable $e) {
        // cleanup best-effort
    }
});

// --- Normalización de canal ---
adj15_assert(RacPublicRateService::normalizeRateType('web') === 'web', 'normalize web');
adj15_assert(RacPublicRateService::normalizeRateType('counter') === 'counter', 'normalize counter');
adj15_assert(RacPublicRateService::normalizeRateType('prepaid') === 'web', 'prepaid inventado → web');
adj15_assert(RacPublicRateService::normalizeRateType(['x']) === 'web', 'array → web');
adj15_assert(RacPublicRateService::normalizeRateType('PREPAGO') === 'web', 'prepago inventado → web');

adj15_assert(RacPublicRateService::barsRateCodeForChannel('web') === 'WEB', 'bars code web');
adj15_assert(RacPublicRateService::barsRateCodeForChannel('counter') === 'NONE', 'bars code counter');
adj15_assert(RacPublicRateService::counterMarkupFactor() === 1.07, 'markup 1.07 intacto');

$web = RacPublicRateService::rateChannelDescriptor('web');
$counter = RacPublicRateService::rateChannelDescriptor('counter');
adj15_assert($web['label'] === 'WebExclusivo', 'label web');
adj15_assert($counter['label'] === 'En mostrador', 'label counter');
adj15_assert($web['rate_qualifier'] === 'WEB' && $counter['rate_qualifier'] === 'WEB', 'qualifier WEB');
adj15_assert($web['is_prepaid_rate'] === false && $counter['is_prepaid_rate'] === false, 'no prepaid rate');
adj15_assert($web['prepayment_available'] === false && $counter['prepayment_available'] === false, 'prepago off');
adj15_assert($web['payment_provider_available'] === false, 'provider off');
adj15_assert($web['online_payment_available'] === false, 'online pay off');
adj15_assert(abs((float) $counter['counter_markup'] - 1.07) < 0.0001, 'counter markup meta');

adj15_assert(RacPublicRateService::clientAttemptedPrepaidActivation(['prepaid' => true]) === true, 'detect prepaid true');
adj15_assert(RacPublicRateService::clientAttemptedPrepaidActivation(['payment_mode' => 'prepaid']) === true, 'detect payment_mode');
adj15_assert(RacPublicRateService::clientAttemptedPrepaidActivation(['rate_type' => 'web']) === false, 'rate_type no es prepaid');

// --- Payload público dual rate ---
$fakeRate = [
    'id' => 1,
    'vehicle_code' => 'ECAR',
    'vehicle_name' => 'Economy Test',
    'rental_days' => 3,
    'final_daily_rate' => 40.0,
    'final_total_rate' => 120.0,
    'base_daily_rate' => 40.0,
    'base_total_rate' => 120.0,
    'discount_amount_total' => 0,
    'currency' => 'USD',
    'cache_key' => 'test-cache',
    'snapshot_id' => 1,
    'applied_rules_json' => '[]',
    'pickup_datetime' => '2026-08-10T10:00:00',
    'available' => 1,
];
$payload = $svc->buildPublicRatePayload($fakeRate);
adj15_assert(is_array($payload), 'payload público');
adj15_assert(isset($payload['priceWeb'], $payload['priceCounter'], $payload['priceTotal'], $payload['priceCounterTotal']), 'dual prices');
adj15_assert(abs((float) $payload['priceCounter'] - round(40.0 * 1.07, 2)) < 0.01, 'counter daily markup');
adj15_assert(abs((float) $payload['priceCounterTotal'] - round(120.0 * 1.07, 2)) < 0.01, 'counter total markup');
adj15_assert(($payload['rateCode'] ?? '') === 'WEB', 'rateCode WEB');
adj15_assert(($payload['prepayment_available'] ?? true) === false, 'payload prepago false');
adj15_assert(($payload['pricing']['rateQualifier'] ?? '') === 'WEB', 'pricing qualifier');
adj15_assert(isset($payload['rate_channels']['web'], $payload['rate_channels']['counter']), 'rate_channels');

// --- Preview web vs counter (fixture quote, restore on shutdown) ---
$db = Database::getInstance();
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
$token = 'adj15tok' . bin2hex(random_bytes(12));
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
        ':vehicle_name' => 'Economy',
        ':pickup_location' => 'PTY',
        ':return_location' => 'PTY',
        ':pickup_datetime' => $normalized['pickup_datetime'],
        ':return_datetime' => $normalized['return_datetime'],
        ':rental_days' => 3,
        ':currency' => 'USD',
        ':base_daily_rate' => 40.0,
        ':base_total_rate' => 120.0,
        ':final_daily_rate' => 40.0,
        ':final_total_rate' => 120.0,
        ':applied_rules_json' => '[]',
        ':status' => 'active',
        ':expires_at' => gmdate('Y-m-d H:i:s', time() + 3600),
    ]
);

$extras = [
    'protection' => 'NONE',
    'items' => [],
    'additionalDrivers' => 0,
    'mandatoryTotal' => 0,
    'rental_days' => 3,
];

$prevWeb = $svc->previewTotals($search, 'ECAR', $token, $extras, 'web');
adj15_assert(!empty($prevWeb['ok']), 'preview web ok');
adj15_assert(($prevWeb['rate_type'] ?? '') === 'web', 'preview rate_type web');
adj15_assert(($prevWeb['prepayment_available'] ?? true) === false, 'preview web prepago false');
adj15_assert(abs((float) ($prevWeb['totals']['base'] ?? 0) - 120.0) < 0.01, 'preview web base');

$prevCounter = $svc->previewTotals($search, 'ECAR', $token, $extras, 'counter');
adj15_assert(!empty($prevCounter['ok']), 'preview counter ok');
adj15_assert(($prevCounter['rate_type'] ?? '') === 'counter', 'preview rate_type counter');
adj15_assert(($prevCounter['prepayment_available'] ?? true) === false, 'preview counter prepago false');
adj15_assert(abs((float) ($prevCounter['totals']['base'] ?? 0) - round(120.0 * 1.07, 2)) < 0.01, 'preview counter ×1.07');
adj15_assert(($prevCounter['rate_channel']['bars_rate_code'] ?? '') === 'NONE', 'counter bars code NONE');

// Manipulación: rate_type prepaid se normaliza a web
$prevFake = $svc->previewTotals($search, 'ECAR', $token, $extras, 'prepaid');
adj15_assert(($prevFake['rate_type'] ?? '') === 'web', 'prepaid manipulado → web');
adj15_assert(($prevFake['prepayment_available'] ?? true) === false, 'prepaid manipulado no activa prepago');

// BARS payload mapping
$payloadBars = AutomarketReservationApiService::buildCreatePayload([
    'rate_type' => 'web',
    'customer_name' => 'Test User',
    'customer_email' => 'synthetic@example.test',
    'customer_phone' => '60000000',
    'search' => $search,
    'vehicle' => ['sippCode' => 'ECAR', 'rateCode' => 'WEB', 'vendorRateId' => 'v1'],
    'extras' => ['protection' => 'NONE'],
    'birth_date' => '1990-01-15',
]);
adj15_assert(($payloadBars['rateCode'] ?? '') === 'WEB', 'create payload web WEB');

$payloadCounter = AutomarketReservationApiService::buildCreatePayload([
    'rate_type' => 'counter',
    'customer_name' => 'Test User',
    'customer_email' => 'synthetic@example.test',
    'customer_phone' => '60000000',
    'search' => $search,
    'vehicle' => ['sippCode' => 'ECAR', 'rateCode' => 'WEB', 'vendorRateId' => 'v1'],
    'extras' => ['protection' => 'NONE'],
    'birth_date' => '1990-01-15',
]);
adj15_assert(($payloadCounter['rateCode'] ?? '') === 'NONE', 'create payload counter NONE');

// UI / API sources: sin afirmar pago disponible
$resultsJs = file_get_contents(__DIR__ . '/../app/public/assets/js/rac-results.js');
adj15_assert(str_contains($resultsJs, 'data-prepayment-available="false"'), 'UI marca prepago false');
adj15_assert(!preg_match('/(?i)prepago|paga ahora|pay now|checkout/', $resultsJs), 'UI sin promesas prepago');
adj15_assert(str_contains($resultsJs, 'WebExclusivo') && str_contains($resultsJs, 'En mostrador'), 'labels dual rate');

$previewApi = file_get_contents(__DIR__ . '/../app/api/rac-rate-preview.php');
adj15_assert(str_contains($previewApi, "'prepayment_available' => false"), 'preview API prepago false');

$quoteApi = file_get_contents(__DIR__ . '/../app/api/rac-rate-quote.php');
adj15_assert(str_contains($quoteApi, "'prepayment_available' => false"), 'quote API prepago false');

$disp = file_get_contents(__DIR__ . '/../app/api/disponibilidad.php');
adj15_assert(str_contains($disp, "prepayment_available'] = false"), 'disponibilidad prepago false');

$pago = file_get_contents(__DIR__ . '/../app/api/pago.php');
adj15_assert(str_contains($pago, '503'), 'pago.php sigue 503');

$svcSrc = file_get_contents(__DIR__ . '/../app/services/RacPublicRateService.php');
adj15_assert(!str_contains($svcSrc, 'Powertranz'), 'rate service sin Powertranz');

// Reconcile flags (sin reserva real: solo descriptor)
$ch = RacPublicRateService::rateChannelDescriptor('web');
adj15_assert($ch['online_payment_available'] === false, 'reconcile channel online false');

fwrite(STDOUT, "PASS: AM-ADJ-15 tarifas web y preparación de prepago\n");
exit(0);
