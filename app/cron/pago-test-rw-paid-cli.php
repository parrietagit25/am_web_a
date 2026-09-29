<?php
/**
 * CLI lab — reserva nueva y un pago de tarjeta (PaymentType 5), no un depósito Auth.
 * No toca reservas existentes ni el checkout público.
 *
 *   php /var/www/html/cron/pago-test-rw-paid-cli.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Solo CLI.\n");
    exit(1);
}

$appDir = dirname(__DIR__);
require_once $appDir . '/config/config.php';
require_once $appDir . '/services/BarsRateClient.php';
require_once $appDir . '/services/BarsReservationClient.php';
require_once $appDir . '/services/BarsPaymentLabService.php';

$last4 = '9896';
$authCode = 'VENTA DIR';
$lastName = 'PayLine';
$firstName = 'Sandbox';

$pickupDate = date('Y-m-d', strtotime('+21 days'));
$returnDate = date('Y-m-d', strtotime('+24 days'));
$pickupDt = $pickupDate . 'T10:00:00';
$returnDt = $returnDate . 'T10:00:00';

echo "=== 1) Tarifas BARS PTY ===\n";
$rates = (new BarsRateClient())->queryRates([
    'pickup_location' => 'PTY',
    'return_location' => 'PTY',
    'pickup_datetime' => $pickupDt,
    'return_datetime' => $returnDt,
    'veh_classes' => BarsRateClient::DEFAULT_VEH_CLASSES,
]);
if (empty($rates['ok'])) {
    fwrite(STDERR, 'Tarifas FAIL: ' . ($rates['error'] ?? 'unknown') . PHP_EOL);
    exit(1);
}

$vehicles = is_array($rates['vehicles'] ?? null) ? $rates['vehicles'] : [];
$pick = null;
foreach ($vehicles as $v) {
    if (is_array($v) && !empty($v['available']) && (float) ($v['total_rate'] ?? 0) > 0) {
        $pick = $v;
        break;
    }
}
if ($pick === null && $vehicles !== []) {
    $pick = $vehicles[0];
}
if (!is_array($pick)) {
    fwrite(STDERR, "Sin vehículos disponibles.\n");
    exit(1);
}

$sipp = strtoupper((string) ($pick['vehicle_code'] ?? 'ECAR'));
echo 'Vehículo: ' . $sipp . ' total_rate=' . ($pick['total_rate'] ?? '') . PHP_EOL;

echo "\n=== 2) Crear reserva (REAL, nueva) ===\n";
$client = new BarsReservationClient();
if (!$client->isConfigured()) {
    fwrite(STDERR, "BARS no configurado.\n");
    exit(1);
}

$create = $client->createReservation([
    'locationCode' => 'PTY',
    'returnLocationCode' => 'PTY',
    'pickupDate' => $pickupDate,
    'pickupTime' => '10:00',
    'returnDate' => $returnDate,
    'returnTime' => '10:00',
    'sippCode' => $sipp,
    'rateCode' => 'WEB',
    'firstName' => $firstName,
    'lastName' => $lastName,
    'email' => 'sandbox.payline@automarket.local',
    'phone' => '+50760004242',
    'countryCode' => 'PA',
    'docType' => 'LIC',
    'docNumber' => 'LAB-PAYLINE-' . date('YmdHis'),
    'birthDate' => '1990-06-15',
    'remarks' => 'LAB Automarket pago PaymentType 5 — puede cancelarse',
], false);

$conf = (string) ($create['confirmation'] ?? $create['reservation']['confirmationNumber'] ?? '');
echo 'create ok=' . (!empty($create['ok']) ? '1' : '0') . ' confirmation=' . ($conf !== '' ? $conf : '(vacío)') . PHP_EOL;
if (!empty($create['error'])) {
    echo 'error=' . $create['error'] . PHP_EOL;
}
if ($conf === '') {
    fwrite(STDERR, "No se obtuvo Res #. No se postea pago.\n");
    exit(1);
}

echo "\n=== 2b) EstimatedTotal ===\n";
$lookupPre = $client->lookupReservation($conf, $lastName, false);
$resPre = is_array($lookupPre['reservation'] ?? null) ? $lookupPre['reservation'] : [];
$est = $resPre['estimatedTotalAmount'] ?? $resPre['totalAmount'] ?? null;
$tm = $resPre['rateTotalAmount'] ?? null;
echo 'T&M=' . ($tm !== null ? number_format((float) $tm, 2, '.', '') : '?') . PHP_EOL;
echo 'EstimatedTotal=' . ($est !== null ? number_format((float) $est, 2, '.', '') : '?') . PHP_EOL;
$payAmount = is_numeric($est) && (float) $est > 0 ? round((float) $est, 2) : 0.0;
if ($payAmount <= 0) {
    fwrite(STDERR, "Sin EstimatedTotal. No se postea pago.\n");
    exit(1);
}

echo "\n=== 3) Postear RentalPaymentAmount PaymentType=5 (pago, no depósito) ===\n";
$pay = BarsPaymentLabService::sendCreditCardPaymentLine([
    'confirm' => 'EJECUTAR',
    'reservation_code' => $conf,
    'amount' => $payAmount,
    'card_suffix' => $last4,
    'card_code' => 'VI',
    'expire_date' => '1230',
    'authorization_code' => $authCode,
]);
echo 'pay ok=' . (!empty($pay['ok']) ? '1' : '0') . ' http=' . ($pay['http_code'] ?? '') . PHP_EOL;
if (!empty($pay['error'])) {
    echo 'pay error=' . $pay['error'] . PHP_EOL;
}
if (!empty($pay['ota_xml_preview'])) {
    echo "--- ota_xml ---\n" . $pay['ota_xml_preview'] . "\n";
}
if (!empty($pay['response_preview'])) {
    echo "--- response ---\n" . substr((string) $pay['response_preview'], 0, 2500) . "\n";
}

echo "\n========== RESULTADO PARA RENTWORKS ==========\n";
echo 'Res #: ' . $conf . PHP_EOL;
echo 'Apellido: ' . $lastName . PHP_EOL;
echo 'Nombre: ' . $firstName . PHP_EOL;
echo 'Pickup: PTY ' . $pickupDate . ' 10:00' . PHP_EOL;
echo 'Return: PTY ' . $returnDate . ' 10:00' . PHP_EOL;
echo 'SIPP: ' . $sipp . PHP_EOL;
echo 'Monto: ' . number_format($payAmount, 2, '.', '') . ' USD' . PHP_EOL;
echo 'Forma: RentalPaymentAmount PaymentType=5 (tarjeta)' . PHP_EOL;
echo 'CC last4: ' . $last4 . PHP_EOL;
echo 'Auth#: ' . $authCode . PHP_EOL;
echo "Revisar si hay línea Payment, casilla Paid y Balance Due en 0.\n";
echo "La reserva 655755 no se modificó.\n";
echo "==============================================\n";

exit(!empty($create['ok']) && !empty($pay['ok']) ? 0 : 1);
