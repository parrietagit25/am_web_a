<?php
/**
 * CLI lab — crea reserva BARS de prueba y postea pago (charge) vía BarsPaymentLabService.
 * Solo para sandbox. No toca checkout público.
 *
 * Uso (en contenedor app):
 *   php /var/www/html/cron/pago-test-rw-sandbox-cli.php
 *   php /var/www/html/cron/pago-test-rw-sandbox-cli.php --dry-pay
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

$dryPay = in_array('--dry-pay', $argv, true);
$last4 = '9896';
$authCode = 'VENTA DIR';
$lastName = 'PayFull';
$firstName = 'Sandbox';

$pickupDate = date('Y-m-d', strtotime('+14 days'));
$returnDate = date('Y-m-d', strtotime('+17 days'));
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
    if (!is_array($v)) {
        continue;
    }
    if (!empty($v['available']) && (float) ($v['total_rate'] ?? 0) > 0) {
        $pick = $v;
        break;
    }
}
if ($pick === null && $vehicles !== []) {
    $pick = $vehicles[0];
}
if ($pick === null) {
    fwrite(STDERR, "Sin vehículos disponibles en tarifas.\n");
    exit(1);
}

$sipp = strtoupper((string) ($pick['vehicle_code'] ?? 'ECAR'));
$totalRate = (float) ($pick['total_rate'] ?? 0);
echo 'Vehículo: ' . $sipp . ' ' . ($pick['vehicle_name'] ?? '') . ' total_rate=' . $totalRate . PHP_EOL;

echo "\n=== 2) Crear reserva (REAL) ===\n";
$client = new BarsReservationClient();
if (!$client->isConfigured()) {
    fwrite(STDERR, "BARS no configurado.\n");
    exit(1);
}

$payload = [
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
    'email' => 'sandbox.paylab@automarket.local',
    'phone' => '+50760004242',
    'countryCode' => 'PA',
    'docType' => 'LIC',
    'docNumber' => 'LAB-PAY-' . date('YmdHis'),
    'birthDate' => '1990-06-15',
    'remarks' => 'LAB Automarket pago-test sandbox — puede cancelarse',
];

$create = $client->createReservation($payload, false);
$conf = (string) ($create['confirmation'] ?? $create['reservation']['confirmationNumber'] ?? '');
echo 'create ok=' . (!empty($create['ok']) ? '1' : '0') . PHP_EOL;
echo 'confirmation=' . ($conf !== '' ? $conf : '(vacío)') . PHP_EOL;
echo 'status=' . ($create['status'] ?? $create['reservation']['status'] ?? '') . PHP_EOL;
if (!empty($create['error'])) {
    echo 'error=' . $create['error'] . PHP_EOL;
}
if (!empty($create['errors'])) {
    echo 'errors=' . implode(' | ', (array) $create['errors']) . PHP_EOL;
}
if ($conf === '') {
    fwrite(STDERR, "No se obtuvo Res #. Abortando pago.\n");
    echo json_encode($create, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(1);
}

// Gustavo/RW: el Amount del depósito debe ser el total del alquiler (EstimatedTotal),
// no solo T&M. Tras crear, releemos la reserva para tomar EstimatedTotalAmount.
echo "\n=== 2b) Lookup para EstimatedTotal ===\n";
$lookupPre = $client->lookupReservation($conf, $lastName, false);
$resPre = is_array($lookupPre['reservation'] ?? null) ? $lookupPre['reservation'] : [];
$est = $resPre['estimatedTotalAmount']
    ?? $resPre['totalAmount']
    ?? ($create['reservation']['estimatedTotalAmount'] ?? null)
    ?? ($create['reservation']['totalAmount'] ?? null)
    ?? null;
$tm = $resPre['rateTotalAmount'] ?? ($create['reservation']['rateTotalAmount'] ?? $totalRate);
echo 'T&M=' . ($tm !== null ? number_format((float) $tm, 2, '.', '') : '?') . PHP_EOL;
echo 'EstimatedTotal=' . ($est !== null ? number_format((float) $est, 2, '.', '') : '?') . PHP_EOL;

$payAmount = is_numeric($est) && (float) $est > 0
    ? round((float) $est, 2)
    : (is_numeric($tm) && (float) $tm > 0 ? round((float) $tm, 2) : 1.0);
echo 'pay_amount (1 solo post, estimado completo)=' . number_format($payAmount, 2, '.', '') . PHP_EOL;

$expire = '1230'; // MMYY — sin Exp RentWorks avisa "expiration date is invalid" y puede ignorar/ parcializar

echo "\n=== 3) Postear pago VehModify (" . ($dryPay ? 'DRY-RUN' : 'SEND') . ") ===\n";
$pay = BarsPaymentLabService::run([
    'action' => $dryPay ? 'preview' : 'send',
    'confirm' => $dryPay ? '' : 'EJECUTAR',
    'dry_run' => $dryPay ? 1 : 0,
    'reservation_code' => $conf,
    'last_name' => $lastName,
    'amount' => $payAmount,
    'card_suffix' => $last4,
    'card_code' => 'VI',
    'card_brand' => 'Visa',
    'txn_type' => 'charge',
    'authorization_code' => $authCode,
    'expire_date' => $expire,
    'mask_style' => 'x12',
    'remark' => 'Automarket sandbox CLI charge full estimated',
]);
echo 'pay ok=' . (!empty($pay['ok']) ? '1' : '0') . PHP_EOL;
echo 'dry_run=' . (!empty($pay['dry_run']) ? '1' : '0') . PHP_EOL;
echo 'http=' . ($pay['http_code'] ?? '') . ' ms=' . ($pay['elapsed_ms'] ?? '') . PHP_EOL;
if (!empty($pay['error'])) {
    echo 'pay error=' . $pay['error'] . PHP_EOL;
}
if (!empty($pay['ota_xml_preview'])) {
    echo "--- ota_xml ---\n" . $pay['ota_xml_preview'] . "\n";
}
if (!empty($pay['response_preview'])) {
    echo "--- response ---\n" . substr((string) $pay['response_preview'], 0, 2500) . "\n";
}

echo "\n=== 4) Lookup ===\n";
$lookup = $client->lookupReservation($conf, $lastName, false);
echo 'lookup ok=' . (!empty($lookup['ok']) ? '1' : '0') . PHP_EOL;
echo 'lookup status=' . ($lookup['status'] ?? $lookup['reservation']['status'] ?? '') . PHP_EOL;
$res = is_array($lookup['reservation'] ?? null) ? $lookup['reservation'] : [];
echo 'lookup total=' . ($res['totalAmount'] ?? $res['estimatedTotalAmount'] ?? '') . PHP_EOL;

echo "\n========== RESULTADO PARA RENTWORKS ==========\n";
echo 'Res #: ' . $conf . PHP_EOL;
echo 'Apellido: ' . $lastName . PHP_EOL;
echo 'Nombre: ' . $firstName . PHP_EOL;
echo 'Pickup: PTY ' . $pickupDate . ' 10:00' . PHP_EOL;
echo 'Return: PTY ' . $returnDate . ' 10:00' . PHP_EOL;
echo 'SIPP: ' . $sipp . PHP_EOL;
echo 'Monto enviado (debe = Total Charges): ' . number_format($payAmount, 2, '.', '') . ' USD' . PHP_EOL;
echo 'Exp: ' . $expire . ' (MMYY)' . PHP_EOL;
echo 'Txn: charge' . PHP_EOL;
echo 'CC last4: ' . $last4 . ' (máscara xxxxxxxxxxxx' . $last4 . ')' . PHP_EOL;
echo 'Auth#: ' . $authCode . PHP_EOL;
echo "En RW Deposit Amount debe ser {$payAmount} (no un parcial). Revisar Balance Due.\n";
echo "==============================================\n";

exit(!empty($create['ok']) && (!empty($pay['ok']) || $dryPay) ? 0 : 1);
