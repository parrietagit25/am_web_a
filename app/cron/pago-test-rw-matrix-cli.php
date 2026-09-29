<?php
/**
 * Laboratorio: una reserva nueva por variante de pago.
 * No toca 655755, 655757 ni reservas de clientes.
 *
 *   php /var/www/html/cron/pago-test-rw-matrix-cli.php
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

$card = '<PaymentCard CardType="1" CardCode="VI" CardNumber="XXXXXXXXXXXX9896" ExpireDate="1230"/>';

$variants = [
    [
        'name' => 'PayBare',
        'idea' => 'Sin tipo de transaccion y sin codigo de autorizacion',
        'xml' => static fn (string $amt): string => '<RentalPaymentPref Remark="LAB PayBare">' . $card . '<PaymentAmount Amount="' . $amt . '" CurrencyCode="USD"/></RentalPaymentPref>',
    ],
    [
        'name' => 'PayNoAuth',
        'idea' => 'charge sin ApprovalCode; VENTA DIR solo en la nota',
        'xml' => static fn (string $amt): string => '<RentalPaymentPref PaymentTransactionTypeCode="charge" Remark="VENTA DIR">' . $card . '<PaymentAmount Amount="' . $amt . '" CurrencyCode="USD"/></RentalPaymentPref>',
    ],
    [
        'name' => 'PayWord',
        'idea' => 'PaymentTransactionTypeCode=payment y VENTA DIR',
        'xml' => static fn (string $amt): string => '<RentalPaymentPref PaymentTransactionTypeCode="payment" Remark="LAB PayWord">' . $card . '<PaymentAmount Amount="' . $amt . '" CurrencyCode="USD" ApprovalCode="VENTA DIR"/></RentalPaymentPref>',
    ],
    [
        'name' => 'PayPrepay',
        'idea' => 'charge + GuaranteeTypeCode 4 (prepago) y VENTA DIR',
        'xml' => static fn (string $amt): string => '<RentalPaymentPref PaymentTransactionTypeCode="charge" GuaranteeTypeCode="4" Remark="LAB PayPrepay">' . $card . '<PaymentAmount Amount="' . $amt . '" CurrencyCode="USD" ApprovalCode="VENTA DIR"/></RentalPaymentPref>',
    ],
    [
        'name' => 'PayFlag',
        'idea' => 'charge + atributo Paid=true y VENTA DIR',
        'xml' => static fn (string $amt): string => '<RentalPaymentPref PaymentTransactionTypeCode="charge" Paid="true" Remark="LAB PayFlag">' . $card . '<PaymentAmount Amount="' . $amt . '" CurrencyCode="USD" ApprovalCode="VENTA DIR" Paid="true"/></RentalPaymentPref>',
    ],
    [
        'name' => 'PayBoth',
        'idea' => 'Deposito con VENTA DIR mas pago de tarjeta sin codigo de autorizacion',
        'xml' => static fn (string $amt): string => '<RentalPaymentPref PaymentTransactionTypeCode="reserve" Remark="LAB deposit">' . $card . '<PaymentAmount Amount="' . $amt . '" CurrencyCode="USD" ApprovalCode="VENTA DIR"/></RentalPaymentPref>'
            . '<RentalPaymentAmount PaymentType="5" Remark="LAB payment">' . $card . '<PaymentAmount Amount="' . $amt . '" CurrencyCode="USD"/></RentalPaymentAmount>',
    ],
];

$client = new BarsReservationClient();
if (!$client->isConfigured()) {
    fwrite(STDERR, "BARS no configurado.\n");
    exit(1);
}

$results = [];
$i = 0;
foreach ($variants as $variant) {
    $i++;
    $offset = 30 + (($i - 1) * 5);
    $pickupDate = date('Y-m-d', strtotime('+' . $offset . ' days'));
    $returnDate = date('Y-m-d', strtotime('+' . ($offset + 3) . ' days'));
    $name = $variant['name'];
    echo "\n======== {$name} {$pickupDate} ========\n";

    $rates = (new BarsRateClient())->queryRates([
        'pickup_location' => 'PTY',
        'return_location' => 'PTY',
        'pickup_datetime' => $pickupDate . 'T10:00:00',
        'return_datetime' => $returnDate . 'T10:00:00',
        'veh_classes' => BarsRateClient::DEFAULT_VEH_CLASSES,
    ]);
    $pick = null;
    foreach ((array) ($rates['vehicles'] ?? []) as $v) {
        if (is_array($v) && !empty($v['available']) && (float) ($v['total_rate'] ?? 0) > 0) {
            $pick = $v;
            break;
        }
    }
    if (!is_array($pick)) {
        echo "SIN VEHICULO\n";
        $results[] = [$name, '-', $pickupDate, 'sin vehiculo', $variant['idea']];
        continue;
    }
    $sipp = strtoupper((string) ($pick['vehicle_code'] ?? 'ECAR'));

    $create = $client->createReservation([
        'locationCode' => 'PTY',
        'returnLocationCode' => 'PTY',
        'pickupDate' => $pickupDate,
        'pickupTime' => '10:00',
        'returnDate' => $returnDate,
        'returnTime' => '10:00',
        'sippCode' => $sipp,
        'rateCode' => 'WEB',
        'firstName' => 'Sandbox',
        'lastName' => $name,
        'email' => 'sandbox.' . strtolower($name) . '@automarket.local',
        'phone' => '+50760004242',
        'countryCode' => 'PA',
        'docType' => 'LIC',
        'docNumber' => 'LAB-' . strtoupper($name) . '-' . date('YmdHis'),
        'birthDate' => '1990-06-15',
        'remarks' => 'LAB ' . $name . ' — puede cancelarse',
    ], false);
    $conf = (string) ($create['confirmation'] ?? $create['reservation']['confirmationNumber'] ?? '');
    echo 'create=' . ($conf !== '' ? $conf : 'FAIL') . ' sipp=' . $sipp . PHP_EOL;
    if ($conf === '') {
        echo 'error=' . ($create['error'] ?? '') . PHP_EOL;
        $results[] = [$name, '-', $pickupDate, 'no se creo', $variant['idea']];
        continue;
    }

    $lookup = $client->lookupReservation($conf, $name, false);
    $res = is_array($lookup['reservation'] ?? null) ? $lookup['reservation'] : [];
    $est = $res['estimatedTotalAmount'] ?? $res['totalAmount'] ?? null;
    if (!is_numeric($est) || (float) $est <= 0) {
        echo "SIN TOTAL\n";
        $results[] = [$name, $conf, $pickupDate, 'sin total', $variant['idea']];
        continue;
    }
    $amt = number_format((float) $est, 2, '.', '');
    echo 'total=' . $amt . PHP_EOL;

    $pay = BarsPaymentLabService::sendModifyInfo($conf, $variant['xml']($amt), 'am-' . strtolower($name));
    $ok = !empty($pay['rw_success']);
    echo 'rw_success=' . ($ok ? '1' : '0') . ' http=' . ($pay['http_code'] ?? '') . PHP_EOL;
    if (!$ok) {
        $preview = (string) ($pay['response_preview'] ?? $pay['error'] ?? '');
        if (preg_match('/Warning[^<]{0,40}|Error[^<]{0,80}|ShortText=&quot;([^&]+)&quot;|ShortText="([^"]+)"/i', html_entity_decode($preview), $mm)) {
            echo 'nota=' . trim($mm[0]) . PHP_EOL;
        }
    }
    $results[] = [$name, $conf, $pickupDate . ' a ' . $returnDate, $amt . ' ' . ($ok ? 'SOAP ok' : 'SOAP fallo'), $variant['idea']];
    sleep(1);
}

echo "\n========== MATRIZ ==========\n";
foreach ($results as $row) {
    echo implode(' | ', $row) . PHP_EOL;
}
echo "Apellido = nombre de la variante. Nombre: Sandbox. PTY 10:00. Tarjeta 9896.\n";
echo "Buscar linea Payment, casilla Paid marcada, o Balance Due en 0.\n";
echo "655755 y 655757 no se modificaron.\n";
