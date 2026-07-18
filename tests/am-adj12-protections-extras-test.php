<?php
declare(strict_types=1);

/**
 * AM-ADJ-12 — Protecciones y extras (suite específica).
 * No inventa productos; valida catálogo existente, cantidades, doble conteo y payload.
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/services/Database.php';
require_once __DIR__ . '/../app/services/RacAddonService.php';
require_once __DIR__ . '/../app/services/AutomarketReservationApiService.php';

function adj12_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$svc = new RacAddonService();
$ctx = [
    'rental_days' => 3,
    'billed_days' => 3,
    'rental_base' => 100.0,
    'vehicle_code' => 'ECAR',
    'vehicle_name' => 'Economy',
    'pickup_location' => 'PTY',
    'return_location' => 'PTY',
];

$publicExtras = $svc->getPublicExtras($ctx);
$publicProtections = $svc->getPublicProtections($ctx);

$silla = null;
$condadic = null;
foreach ($publicExtras as $ex) {
    $code = strtoupper((string) ($ex['code'] ?? ''));
    if ($code === 'SILLA') {
        $silla = $ex;
    }
    if ($code === 'CONDADIC') {
        $condadic = $ex;
    }
}

adj12_assert($silla !== null, 'SILLA existe en catálogo público (no inventado)');
adj12_assert($condadic !== null, 'CONDADIC existe en catálogo público');
adj12_assert(
    (string) ($silla['description'] ?? '') !== (string) ($silla['name'] ?? '')
    || (string) ($silla['description'] ?? '') !== '',
    'description pública de SILLA no se fuerza al name vacío'
);
adj12_assert(
    stripos((string) ($silla['description'] ?? ''), 'Silla') !== false
    || stripos((string) ($silla['description'] ?? ''), 'disponibilidad') !== false
    || (string) ($silla['description'] ?? '') !== (string) ($silla['name'] ?? ''),
    'description de SILLA proviene del campo descriptivo'
);
adj12_assert((int) ($silla['maxQuantity'] ?? 0) === 2, 'SILLA maxQuantity=2 desde BD');
adj12_assert((int) ($condadic['maxQuantity'] ?? 0) === 3, 'CONDADIC maxQuantity=3 desde BD');

$invented = ['AMAS', 'PPASS', 'DELIVERY'];
foreach ($publicExtras as $ex) {
    $code = strtoupper((string) ($ex['code'] ?? ''));
    adj12_assert(!in_array($code, $invented, true), 'No se inventan extras AMAS/PPASS/DELIVERY en API pública');
}

$noneOk = $svc->resolveReservationAddons(['protection' => 'NONE', 'items' => [], 'additionalDrivers' => 0], $ctx);
adj12_assert(!empty($noneOk['ok']), 'Sin protección es válido');
adj12_assert((float) ($noneOk['totals']['coverage'] ?? -1) === 0.0, 'Cobertura 0 sin protección');
adj12_assert((float) ($noneOk['totals']['extras'] ?? -1) === 0.0, 'Extras 0 sin selección');
$totalSin = (float) ($noneOk['totals']['coverage'] ?? 0) + (float) ($noneOk['totals']['extras'] ?? 0);

$basic = $svc->resolveReservationAddons(['protection' => 'BASIC', 'items' => [], 'additionalDrivers' => 0], $ctx);
adj12_assert(!empty($basic['ok']), 'Protección BASIC válida');
adj12_assert((string) ($basic['protection']['code'] ?? '') === 'BASIC', 'Código BASIC conservado');
$basicAmt = (float) ($basic['totals']['coverage'] ?? 0);
adj12_assert($basicAmt > 0, 'BASIC tiene precio calculado en servidor');

$fakeProt = $svc->resolveReservationAddons(['protection' => 'NOEXISTE', 'items' => [], 'additionalDrivers' => 0], $ctx);
adj12_assert(empty($fakeProt['ok']), 'Protección inexistente rechazada');

$fakeExtra = $svc->resolveReservationAddons([
    'protection' => 'NONE',
    'items' => [['code' => 'FAKEEXTRA', 'quantity' => 1]],
    'additionalDrivers' => 0,
], $ctx);
adj12_assert(empty($fakeExtra['ok']), 'Extra inexistente rechazado');

$protAsExtra = $svc->resolveReservationAddons([
    'protection' => 'NONE',
    'items' => [['code' => 'BASIC', 'quantity' => 1]],
    'additionalDrivers' => 0,
], $ctx);
adj12_assert(empty($protAsExtra['ok']), 'Código de protección no vale como extra');

$neg = $svc->resolveReservationAddons([
    'protection' => 'NONE',
    'items' => [['code' => 'SILLA', 'quantity' => -1]],
    'additionalDrivers' => 0,
], $ctx);
adj12_assert(empty($neg['ok']), 'Cantidad negativa rechazada');

$over = $svc->resolveReservationAddons([
    'protection' => 'NONE',
    'items' => [['code' => 'SILLA', 'quantity' => 99]],
    'additionalDrivers' => 0,
], $ctx);
adj12_assert(empty($over['ok']), 'Cantidad excesiva rechazada según max_quantity');

$decimal = $svc->resolveReservationAddons([
    'protection' => 'NONE',
    'items' => [['code' => 'SILLA', 'quantity' => 1.5]],
    'additionalDrivers' => 0,
], $ctx);
adj12_assert(empty($decimal['ok']), 'Cantidad decimal rechazada');

$arrayQty = $svc->resolveReservationAddons([
    'protection' => 'NONE',
    'items' => [['code' => 'SILLA', 'quantity' => [1]]],
    'additionalDrivers' => 0,
], $ctx);
adj12_assert(empty($arrayQty['ok']), 'Cantidad como array rechazada');

$htmlItem = $svc->resolveReservationAddons([
    'protection' => 'NONE',
    'items' => [['code' => '<script>SILLA</script>', 'quantity' => 1]],
    'additionalDrivers' => 0,
], $ctx);
adj12_assert(empty($htmlItem['ok']), 'Código HTML rechazado');

$unexpected = $svc->resolveReservationAddons([
    'protection' => 'NONE',
    'items' => ['SILLA'],
    'additionalDrivers' => 0,
], $ctx);
adj12_assert(empty($unexpected['ok']), 'Item escalar inesperado rechazado');

$silla1 = $svc->resolveReservationAddons([
    'protection' => 'NONE',
    'items' => [['code' => 'SILLA', 'quantity' => 1, 'unit_price' => 9999, 'name' => 'Hack']],
    'additionalDrivers' => 0,
], $ctx);
adj12_assert(!empty($silla1['ok']), 'SILLA qty 1 válida');
adj12_assert((string) ($silla1['extras'][0]['item_name'] ?? '') !== 'Hack', 'Nombre cliente no se usa como verdad');
$unitFromDb = (float) ($silla1['extras'][0]['unit_price'] ?? 0);
adj12_assert($unitFromDb !== 9999.0, 'Precio manipulado en cliente se ignora');
adj12_assert(abs($unitFromDb - 15.0) < 0.001, 'Precio SILLA desde BD (15)');

$silla2 = $svc->resolveReservationAddons([
    'protection' => 'NONE',
    'items' => [['code' => 'SILLA', 'quantity' => 2]],
    'additionalDrivers' => 0,
], $ctx);
adj12_assert(!empty($silla2['ok']), 'SILLA qty 2 (máximo) válida');
adj12_assert((int) ($silla2['extras'][0]['quantity'] ?? 0) === 2, 'Cantidad 2 persistida');
adj12_assert(
    abs((float) ($silla2['totals']['extras'] ?? 0) - 2 * $unitFromDb) < 0.01,
    'Subtotal extras = precio BD × cantidad'
);

$dup = $svc->resolveReservationAddons([
    'protection' => 'NONE',
    'items' => [
        ['code' => 'SILLA', 'quantity' => 1],
        ['code' => 'SILLA', 'quantity' => 1],
    ],
    'additionalDrivers' => 0,
], $ctx);
adj12_assert(!empty($dup['ok']), 'Duplicados en items se deduplican');
adj12_assert(count($dup['extras'] ?? []) === 1, 'Un solo ítem SILLA tras dedupe');

$driversOnly = $svc->resolveReservationAddons([
    'protection' => 'NONE',
    'items' => [],
    'additionalDrivers' => 2,
], $ctx);
adj12_assert(!empty($driversOnly['ok']), 'Conductores adicionales válidos');
$driverTotal = (float) ($driversOnly['totals']['extras'] ?? 0);

$doublePath = $svc->resolveReservationAddons([
    'protection' => 'NONE',
    'items' => [['code' => 'CONDADIC', 'quantity' => 2]],
    'additionalDrivers' => 2,
], $ctx);
adj12_assert(!empty($doublePath['ok']), 'CONDADIC en items+additionalDrivers no falla');
adj12_assert(
    abs((float) ($doublePath['totals']['extras'] ?? 0) - $driverTotal) < 0.01,
    'Sin doble conteo CONDADIC (items + additionalDrivers)'
);
adj12_assert(count($doublePath['extras'] ?? []) === 1, 'Un solo renglón CONDADIC');

$overDrivers = $svc->resolveReservationAddons([
    'protection' => 'NONE',
    'items' => [],
    'additionalDrivers' => 99,
], $ctx);
adj12_assert(!empty($overDrivers['ok']), 'Conductores excesivos se acotan al máximo BD');
adj12_assert((int) ($overDrivers['extras'][0]['quantity'] ?? 0) === 3, 'Máximo CONDADIC=3 desde BD');

$combo = $svc->resolveReservationAddons([
    'protection' => 'BASIC',
    'items' => [['code' => 'SILLA', 'quantity' => 1]],
    'additionalDrivers' => 1,
], $ctx);
adj12_assert(!empty($combo['ok']), 'Combinación protección+extras válida');
adj12_assert(abs((float) ($combo['totals']['coverage'] ?? 0) - $basicAmt) < 0.01, 'Cobertura idéntica a BASIC sola');
adj12_assert((float) ($combo['totals']['extras'] ?? 0) > 0, 'Extras > 0 en combinación');

$legacyEmpty = $svc->resolveReservationAddons([], $ctx);
adj12_assert(!empty($legacyEmpty['ok']), 'Sesión legacy sin extras sigue válida');
adj12_assert(abs($totalSin - ((float) ($legacyEmpty['totals']['coverage'] ?? 0) + (float) ($legacyEmpty['totals']['extras'] ?? 0))) < 0.01, 'Total sin selecciones estable');

$payload = AutomarketReservationApiService::buildCreatePayload([
    'first_name' => 'Ana',
    'last_name' => 'Prueba',
    'email' => 'ana@example.com',
    'phone' => '60000000',
    'phone_prefix' => '+507',
    'doc_type' => 'LIC',
    'doc_number' => '8-123',
    'country_code' => 'PA',
    'birth_date' => '1990-06-15',
    'search' => [
        'locationCode' => 'PTY',
        'returnLocationCode' => 'PTY',
        'pickupDate' => '2026-08-01',
        'pickupTime' => '10:00',
        'returnDate' => '2026-08-04',
        'returnTime' => '10:00',
    ],
    'vehicle' => ['sippCode' => 'ECAR', 'rates' => [['rateCode' => 'WEB', 'vendorRateId' => 'VR1']]],
    'extras' => [
        'protection' => 'BASIC',
        'items' => [['code' => 'SILLA', 'quantity' => 1]],
        'additionalDrivers' => 1,
    ],
]);

adj12_assert(($payload['coverageCode'] ?? null) === 'BASIC', 'Payload BARS coverageCode=BASIC existente');
adj12_assert(isset($payload['extras']), 'Payload conserva extras existentes');
adj12_assert(!array_key_exists('fakeProtectionField', $payload), 'Sin campos inventados en payload');
adj12_assert(($payload['locationCode'] ?? '') === 'PTY', 'Estación PTY sin alterar');

$jsPath = __DIR__ . '/../app/public/assets/js/rac-extras.js';
$js = file_get_contents($jsPath);
adj12_assert($js !== false && $js !== '', 'rac-extras.js legible');
adj12_assert(str_contains($js, 'selectedQty'), 'UI persiste cantidades por código');
adj12_assert(str_contains($js, 'data-equip-delta'), 'UI cantidad para extras con maxQuantity>1');
adj12_assert(str_contains($js, 'equipmentMaxQuantity'), 'UI respeta maxQuantity del catálogo');
adj12_assert(!str_contains($js, 'Más popular'), 'Sin badge comercial inventado Más popular');
adj12_assert(!str_contains($js, 'Sin preocupaciones'), 'Sin badge comercial inventado Sin preocupaciones');
adj12_assert(str_contains($js, 'protectionTitle'), 'Nombres de protección priorizan fuente DB');

fwrite(STDOUT, "PASS: AM-ADJ-12 protecciones y extras\n");
exit(0);
