<?php
declare(strict_types=1);

/**
 * AM-ADJ-16 — RentWorks es la fuente de cobro; admin solo registra.
 * ChargeID (opción B) en create SOAP. PromoDesc no va como RateQualifier.
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/services/BarsChargeIds.php';
require_once __DIR__ . '/../app/services/BarsReservationClient.php';
require_once __DIR__ . '/../app/services/AutomarketReservationApiService.php';

function adj16_assert(bool $ok, string $msg): void
{
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

adj16_assert(BarsChargeIds::resolve('BASIC') === '32652', 'BASIC → VendorChargeID 32652');
adj16_assert(BarsChargeIds::resolve('SILLA') === '10575', 'SILLA → 10575');
adj16_assert(BarsChargeIds::resolve('CONDADIC') === '1003', 'CONDADIC → 1003');
adj16_assert(BarsChargeIds::resolve('CONDADIC') === '1003', 'Alias CONDADIC → 1003');
adj16_assert(BarsChargeIds::resolve('UD') === '20837', 'UD → 20837');
adj16_assert(BarsChargeIds::resolve('NONE') === null, 'NONE no genera ChargeID');
adj16_assert(BarsChargeIds::resolve('NOEXISTE') === null, 'Código desconocido no se inventa');

$charges = BarsChargeIds::fromCheckoutExtras(
    [
        'protection' => 'BASIC',
        'items' => [['code' => 'SILLA', 'quantity' => 1]],
        'additionalDrivers' => 1,
    ],
    ['age' => 23]
);
$ids = array_column($charges, 'chargeId');
$codes = array_column($charges, 'code');
adj16_assert(in_array('32652', $ids, true), 'Checkout incluye ChargeID BASIC');
adj16_assert(in_array('10575', $ids, true), 'Checkout incluye ChargeID SILLA');
adj16_assert(in_array('1003', $ids, true), 'Checkout incluye ChargeID conductor extra');
adj16_assert(in_array('20837', $ids, true), 'Edad < 25 incluye ChargeID UD');
adj16_assert(!in_array('NOEXISTE', $codes, true), 'No se empujan códigos inventados');

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
        'age' => 23,
        'promoCode' => 'CONANREH2026',
    ],
    'vehicle' => ['sippCode' => 'ECAR', 'rates' => [['rateCode' => 'WEB', 'vendorRateId' => 'VR1']]],
    'extras' => [
        'protection' => 'BASIC',
        'items' => [['code' => 'SILLA', 'quantity' => 1]],
        'additionalDrivers' => 1,
    ],
]);

adj16_assert(($payload['coverageCode'] ?? null) === 'BASIC', 'coverageCode BASIC se conserva (compat)');
adj16_assert(!empty($payload['bars_passthrough']), 'bars_passthrough activo');
adj16_assert(is_array($payload['vehicle_charges'] ?? null) && $payload['vehicle_charges'] !== [], 'vehicle_charges en payload');
$payloadIds = array_column($payload['vehicle_charges'], 'chargeId');
adj16_assert(in_array('32652', $payloadIds, true), 'Payload ChargeID BASIC');
adj16_assert(in_array('10575', $payloadIds, true), 'Payload ChargeID SILLA');
adj16_assert(($payload['rateCode'] ?? '') === 'WEB', 'RateQualifier sigue WEB, no el promo');
adj16_assert(strtoupper((string) ($payload['promoCode'] ?? '')) === 'CONANREH2026', 'Promo viaja aparte');

$client = new BarsReservationClient();
$xml = $client->buildCreateOtaXml($payload);
adj16_assert(str_contains($xml, 'ChargeID="32652"'), 'SOAP emite ChargeID BASIC');
adj16_assert(str_contains($xml, 'ChargeID="10575"'), 'SOAP emite ChargeID SILLA');
adj16_assert(str_contains($xml, 'ChargeID="1003"'), 'SOAP emite ChargeID CONDADIC');
adj16_assert(str_contains($xml, '<VehicleCharges>'), 'Bloque VehicleCharges presente');
adj16_assert(!str_contains($xml, 'CoveragePref'), 'Sin CoveragePref cuando hay ChargeID');
adj16_assert(!str_contains($xml, 'SpecialEquipPref'), 'Sin SpecialEquipPref cuando hay ChargeID');
adj16_assert(str_contains($xml, 'RateQualifier="WEB"'), 'Tarifa WEB en SOAP');
adj16_assert(!str_contains($xml, 'RateQualifier="CONANREH2026"'), 'Promo no se usa como tarifa');
adj16_assert(str_contains($xml, 'CONANREH2026'), 'PromoDesc / texto promo en SOAP');

$tab = (string) file_get_contents(__DIR__ . '/../app/includes/admin-rac-tab.php');
adj16_assert($tab !== '', 'admin-rac-tab.php legible');
adj16_assert(str_contains($tab, 'RentWorks'), 'Admin explica que RentWorks es la fuente');
adj16_assert(str_contains($tab, 'bars_confirmation_code') || str_contains($tab, 'displayConfirmationCode'), 'Muestra código RentWorks');
adj16_assert(!str_contains($tab, 'update_rac_reservation_status'), 'Admin ya no cambia estado operativo de la reserva');

$addonsView = (string) file_get_contents(__DIR__ . '/../app/public/admin/rac-addons-view.php');
adj16_assert(str_contains($addonsView, 'RentWorks') || str_contains($addonsView, 'ChargeID'), 'Aviso en protecciones/extras admin');

fwrite(STDOUT, "PASS: AM-ADJ-16 registro RentWorks + ChargeID\n");
exit(0);
