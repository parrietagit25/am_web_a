<?php
/**
 * Prueba PageSet/PageName HPP (CLI). No imprime secretos ni RedirectData.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Solo CLI.\n");
    exit(1);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/PowertranzClient.php';
require_once __DIR__ . '/../services/PowertranzSanitizer.php';

if (!PowertranzClient::isEnabled()) {
    fwrite(STDERR, "Powertranz no configurado.\n");
    exit(2);
}

$client = new PowertranzClient();
$tries = [
    ['PTZ/Payment', 'Payment'],
    ['PTZ/Default', 'Payment'],
    ['PTZ/Default', 'Default'],
    ['Default', 'Default'],
    ['PTZ/FAC', 'Payment'],
    [null, null],
];

foreach ($tries as [$set, $name]) {
    $txn = sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        random_int(0, 0xffff),
        random_int(0, 0xffff),
        random_int(0, 0xffff),
        random_int(0, 0x0fff) | 0x4000,
        random_int(0, 0x3fff) | 0x8000,
        random_int(0, 0xffff),
        random_int(0, 0xffff),
        random_int(0, 0xffff)
    );
    $extended = [
        'ThreeDSecure' => ['ChallengeWindowSize' => 5, 'ChallengeIndicator' => '01'],
        'MerchantResponseUrl' => 'https://test.automarket.com.pa/api/powertranz-return.php',
    ];
    if ($set !== null && $name !== null) {
        $extended['HostedPage'] = ['PageSet' => $set, 'PageName' => $name];
    }
    $payload = [
        'TransactionIdentifier' => $txn,
        'TotalAmount' => 1.00,
        'TaxAmount' => 0.0,
        'CurrencyCode' => '840',
        'ThreeDSecure' => true,
        'Source' => ['CardholderName' => 'Test Cardholder'],
        'OrderIdentifier' => 'PROBE-' . strtoupper(bin2hex(random_bytes(3))),
        'AddressMatch' => true,
        'BillingAddress' => [
            'FirstName' => 'Test',
            'LastName' => 'Cardholder',
            'Line1' => 'Ciudad de Panama',
            'City' => 'Panama',
            'PostalCode' => '0801',
            'CountryCode' => '591',
            'EmailAddress' => 'test@example.com',
            'PhoneNumber' => '50760000000',
        ],
        'ExtendedData' => $extended,
    ];
    $res = $client->saleHpp($payload);
    $data = is_array($res['data'] ?? null) ? $res['data'] : [];
    $iso = $client->extractIsoCode($data);
    $redirect = $client->extractRedirectData($data);
    $has757 = stripos($redirect, '757') !== false || stripos($redirect, 'Hosted page not found') !== false;
    $label = ($set ?? '(omit)') . ' / ' . ($name ?? '(omit)');
    echo sprintf(
        "%-28s http=%-3s iso=%-4s redirect=%-3s html757=%s err=%s\n",
        $label,
        (string) ($res['http_code'] ?? 0),
        $iso !== '' ? $iso : '-',
        $redirect !== '' ? 'YES' : 'no',
        $has757 ? 'YES' : 'no',
        $res['error'] ?? '-'
    );
}
