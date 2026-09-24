<?php
/**
 * Laboratorio aislado: postear pago/Auth a RentWorks vía OTA_VehModify.
 * NO se usa en checkout público ni en RacCheckoutFulfillment.
 */
declare(strict_types=1);

require_once __DIR__ . '/BarsApiLabService.php';
require_once __DIR__ . '/BarsReservationClient.php';
require_once __DIR__ . '/Database.php';

class BarsPaymentLabService
{
    public const TXN_CHARGE = 'charge';
    public const TXN_RESERVE = 'reserve';

    /**
     * Prefijos de tarjeta enmascarada (solo últimos 4 reales; el resto es placeholder).
     * RentWorks en UI muestra formas tipo 477176XXXXXXXX1564 — sin BIN usamos máscaras neutras.
     */
    public const MASK_X12 = 'xxxxxxxxxxxx';
    public const MASK_X8 = 'xxxxxxxx';
    public const MASK_ZEROS = '000000xxxxxx';

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function run(array $input): array
    {
        $action = strtolower(trim((string) ($input['action'] ?? 'preview')));
        if ($action === 'lookup') {
            return self::lookup($input);
        }
        if ($action === 'prefill_payment') {
            return self::prefillFromPowertranz($input);
        }
        if (!in_array($action, ['preview', 'send'], true)) {
            return ['ok' => false, 'error' => 'action inválida. Use preview | send | lookup | prefill_payment.'];
        }

        $built = self::buildPaymentPayload($input);
        if (empty($built['ok'])) {
            return $built;
        }

        /** @var array<string, mixed> $payload */
        $payload = $built['payload'];
        $otaXml = self::buildVehModifyPaymentXml($payload);

        $dryRun = $action === 'preview' || !empty($input['dry_run']);
        $confirm = strtoupper(trim((string) ($input['confirm'] ?? '')));
        if ($action === 'send' && !$dryRun && $confirm !== 'EJECUTAR') {
            return [
                'ok' => false,
                'error' => 'Para enviar a RentWorks escribe confirm=EJECUTAR. Por defecto solo preview (dry-run).',
                'ota_xml_preview' => self::redactSecrets($otaXml),
                'payload' => self::publicPayload($payload),
            ];
        }

        $params = [
            'ota_xml' => $otaXml,
            'dry_run' => $dryRun ? 1 : 0,
            'confirm' => $dryRun ? '' : 'EJECUTAR',
        ];

        $result = BarsApiLabService::run('bars_otavehmodify', 'soap', $params);
        $result['lab'] = 'pago-test-rentworks';
        $result['action'] = $action;
        $result['payload'] = self::publicPayload($payload);
        $result['ota_xml_preview'] = self::redactSecrets($otaXml);
        $result['hint'] = $dryRun
            ? 'Dry-run: no se envió a RentWorks. Revisa el XML y luego usa action=send + confirm=EJECUTAR.'
            : 'SOAP VehModify enviado. Verifica en RentWorks Charges/Payments (Paid vs Auth) y Balance Due.';

        return $result;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function lookup(array $input): array
    {
        $code = strtoupper(trim((string) ($input['reservation_code'] ?? $input['res_number'] ?? '')));
        $lastName = trim((string) ($input['last_name'] ?? ''));
        if ($code === '') {
            return ['ok' => false, 'error' => 'reservation_code / res_number requerido.'];
        }

        $client = new BarsReservationClient();
        if (!$client->isConfigured()) {
            return ['ok' => false, 'error' => 'BARS no configurado (BARS_RW_*).'];
        }

        $result = $client->lookupReservation($code, $lastName, false);
        $result['lab'] = 'pago-test-rentworks';
        $result['action'] = 'lookup';

        return $result;
    }

    /**
     * Lee un pago PowerTranz (tabla lab/prod compartida) y expone campos útiles para el form.
     * No modifica el pago ni el checkout.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function prefillFromPowertranz(array $input): array
    {
        $paymentId = isset($input['payment_id']) ? (int) $input['payment_id'] : 0;
        $db = Database::getInstance();
        if ($paymentId > 0) {
            $row = $db->selectOne('SELECT * FROM rac_powertranz_payments WHERE id = :id LIMIT 1', [':id' => $paymentId]);
        } else {
            $row = $db->selectOne(
                "SELECT * FROM rac_powertranz_payments WHERE approved = 1 OR status = 'approved' ORDER BY id DESC LIMIT 1"
            );
            if (!is_array($row)) {
                $row = $db->selectOne('SELECT * FROM rac_powertranz_payments ORDER BY id DESC LIMIT 1');
            }
        }

        if (!is_array($row)) {
            return ['ok' => false, 'error' => 'No hay pagos PowerTranz en BD.'];
        }

        $suffix = self::extractCardSuffixFromRow($row);
        $brand = trim((string) ($row['card_brand'] ?? ''));
        if ($brand === '') {
            $brand = self::extractFromJson($row, ['CardBrand', 'cardBrand']) ?? '';
        }

        return [
            'ok' => true,
            'lab' => 'pago-test-rentworks',
            'action' => 'prefill_payment',
            'payment' => [
                'payment_id' => (int) ($row['id'] ?? 0),
                'status' => (string) ($row['status'] ?? ''),
                'approved' => !empty($row['approved']),
                'amount' => (float) ($row['amount'] ?? 0),
                'currency' => (string) ($row['currency_code'] ?? $row['currency'] ?? 'USD'),
                'authorization_code' => (string) ($row['authorization_code'] ?? ''),
                'rrn' => (string) ($row['rrn'] ?? ''),
                'card_brand' => $brand,
                'card_suffix' => $suffix,
                'card_code' => self::mapBrandToCardCode($brand),
                'mode' => (string) ($row['mode'] ?? 'sale'),
                'iso_response_code' => (string) ($row['iso_response_code'] ?? ''),
                'test_reference' => (string) ($row['test_reference'] ?? $row['payment_reference'] ?? ''),
                'suggested_txn_type' => strtolower((string) ($row['mode'] ?? 'sale')) === 'auth'
                    ? self::TXN_RESERVE
                    : self::TXN_CHARGE,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool,error?:string,payload?:array<string,mixed>}
     */
    private static function buildPaymentPayload(array $input): array
    {
        $res = strtoupper(trim((string) ($input['reservation_code'] ?? $input['res_number'] ?? '')));
        if ($res === '' || !preg_match('/^[A-Z0-9\-]{3,32}$/', $res)) {
            return ['ok' => false, 'error' => 'reservation_code inválido (Res # de RentWorks).'];
        }

        $amount = isset($input['amount']) ? (float) $input['amount'] : 0.0;
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'amount debe ser > 0.'];
        }

        $suffix = preg_replace('/\D+/', '', (string) ($input['card_suffix'] ?? $input['last4'] ?? '')) ?? '';
        if (strlen($suffix) !== 4) {
            return ['ok' => false, 'error' => 'card_suffix / last4 debe ser exactamente 4 dígitos.'];
        }

        $txn = strtolower(trim((string) ($input['txn_type'] ?? self::TXN_CHARGE)));
        if (!in_array($txn, [self::TXN_CHARGE, self::TXN_RESERVE], true)) {
            return ['ok' => false, 'error' => 'txn_type debe ser charge (Paid) o reserve (Auth).'];
        }

        $brand = trim((string) ($input['card_brand'] ?? ''));
        $cardCode = strtoupper(trim((string) ($input['card_code'] ?? '')));
        if ($cardCode === '') {
            $cardCode = self::mapBrandToCardCode($brand);
        }
        if ($cardCode === '') {
            $cardCode = 'VI';
        }

        $maskStyle = strtolower(trim((string) ($input['mask_style'] ?? 'x12')));
        $masked = self::buildMaskedCardNumber($suffix, $maskStyle);

        $auth = trim((string) ($input['authorization_code'] ?? $input['auth_code'] ?? ''));
        $currency = strtoupper(trim((string) ($input['currency'] ?? 'USD')));
        if ($currency === '') {
            $currency = 'USD';
        }

        $expire = preg_replace('/\D+/', '', (string) ($input['expire_date'] ?? '')) ?? '';
        if ($expire !== '' && strlen($expire) !== 4) {
            return ['ok' => false, 'error' => 'expire_date debe ser MMYY (4 dígitos) o vacío.'];
        }

        $guaranteeType = trim((string) ($input['guarantee_type_code'] ?? ''));
        if ($guaranteeType === '' && $txn === self::TXN_RESERVE) {
            $guaranteeType = '8'; // sample OTA: obtain authorization
        }

        return [
            'ok' => true,
            'payload' => [
                'reservation_code' => $res,
                'last_name' => trim((string) ($input['last_name'] ?? '')),
                'amount' => round($amount, 2),
                'currency' => $currency,
                'txn_type' => $txn,
                'card_suffix' => $suffix,
                'card_brand' => $brand,
                'card_code' => $cardCode,
                'card_number_masked' => $masked,
                'mask_style' => $maskStyle,
                'authorization_code' => $auth,
                'expire_date' => $expire,
                'guarantee_type_code' => $guaranteeType,
                'payment_id' => isset($input['payment_id']) ? (int) $input['payment_id'] : null,
                'remark' => trim((string) ($input['remark'] ?? 'Automarket pago-test lab')),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $p
     */
    public static function buildVehModifyPaymentXml(array $p): string
    {
        $ns = 'http://www.opentravel.org/OTA/2003/05';
        $echo = 'am-paylab-' . date('YmdHis');
        $pos = self::otaPosFragment();
        $res = htmlspecialchars((string) $p['reservation_code'], ENT_QUOTES | ENT_XML1, 'UTF-8');
        $amount = number_format((float) $p['amount'], 2, '.', '');
        $currency = htmlspecialchars((string) $p['currency'], ENT_QUOTES | ENT_XML1, 'UTF-8');
        $cardCode = htmlspecialchars((string) $p['card_code'], ENT_QUOTES | ENT_XML1, 'UTF-8');
        $cardNumber = htmlspecialchars((string) $p['card_number_masked'], ENT_QUOTES | ENT_XML1, 'UTF-8');
        $txn = htmlspecialchars((string) $p['txn_type'], ENT_QUOTES | ENT_XML1, 'UTF-8');
        $auth = trim((string) ($p['authorization_code'] ?? ''));
        $expire = trim((string) ($p['expire_date'] ?? ''));
        $guarantee = trim((string) ($p['guarantee_type_code'] ?? ''));
        $remark = trim((string) ($p['remark'] ?? ''));

        $prefAttrs = 'PaymentTransactionTypeCode="' . $txn . '"';
        if ($guarantee !== '') {
            $prefAttrs .= ' GuaranteeTypeCode="' . htmlspecialchars($guarantee, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"';
        }
        if ($remark !== '') {
            $prefAttrs .= ' Remark="' . htmlspecialchars($remark, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"';
        }

        $cardAttrs = 'CardType="1" CardCode="' . $cardCode . '" CardNumber="' . $cardNumber . '"';
        if ($expire !== '') {
            $cardAttrs .= ' ExpireDate="' . htmlspecialchars($expire, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"';
        }

        $paymentAmountAttrs = 'Amount="' . $amount . '" CurrencyCode="' . $currency . '"';
        if ($auth !== '') {
            $paymentAmountAttrs .= ' ApprovalCode="' . htmlspecialchars($auth, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"';
        }

        // OTA_VehModify: UniqueID + VehModifyRQInfo/RentalPaymentPref (PaymentCard + PaymentAmount).
        return '<OTA_VehModifyRQ xmlns="' . $ns . '" Version="3.000" EchoToken="' . htmlspecialchars($echo, ENT_QUOTES | ENT_XML1, 'UTF-8') . '" Target="Production">'
            . $pos
            . '<UniqueID Type="14" ID="' . $res . '"/>'
            . '<VehModifyRQCore ModifyType="Commit" Status="Available"/>'
            . '<VehModifyRQInfo>'
            . '<RentalPaymentPref ' . $prefAttrs . '>'
            . '<PaymentCard ' . $cardAttrs . '/>'
            . '<PaymentAmount ' . $paymentAmountAttrs . '/>'
            . '</RentalPaymentPref>'
            . '</VehModifyRQInfo>'
            . '</OTA_VehModifyRQ>';
    }

    public static function mapBrandToCardCode(string $brand): string
    {
        $b = strtoupper(trim($brand));
        if ($b === '') {
            return '';
        }
        if (str_contains($b, 'VISA') || $b === 'VI' || $b === 'VC') {
            return 'VI';
        }
        if (str_contains($b, 'MASTER') || $b === 'MC' || $b === 'CA') {
            return 'MC';
        }
        if (str_contains($b, 'AMEX') || str_contains($b, 'AMERICAN') || $b === 'AX') {
            return 'AX';
        }
        if (str_contains($b, 'DISC') || $b === 'DS') {
            return 'DS';
        }

        return strlen($b) <= 2 ? $b : '';
    }

    public static function buildMaskedCardNumber(string $last4, string $style): string
    {
        $last4 = substr(preg_replace('/\D+/', '', $last4) ?? '', -4);
        $prefix = match ($style) {
            'x8' => self::MASK_X8,
            'zeros' => self::MASK_ZEROS,
            default => self::MASK_X12,
        };

        return strtoupper($prefix . $last4);
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function extractCardSuffixFromRow(array $row): string
    {
        foreach (['card_suffix', 'CardSuffix'] as $col) {
            if (!empty($row[$col]) && preg_match('/^\d{4}$/', (string) $row[$col])) {
                return (string) $row[$col];
            }
        }

        $fromJson = self::extractFromJson($row, ['CardSuffix', 'cardSuffix', 'CardLastFour', 'LastFour']);
        if ($fromJson !== null && preg_match('/^\d{4}$/', $fromJson)) {
            return $fromJson;
        }

        return '';
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string> $keys
     */
    private static function extractFromJson(array $row, array $keys): ?string
    {
        foreach (['complete_response_json', 'payment_response_json_sanitized', 'response_payload_json', 'auth_response_json_sanitized'] as $col) {
            $raw = (string) ($row[$col] ?? '');
            if ($raw === '') {
                continue;
            }
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                continue;
            }
            foreach ($keys as $key) {
                if (isset($data[$key]) && (is_string($data[$key]) || is_numeric($data[$key]))) {
                    $v = trim((string) $data[$key]);
                    if ($v !== '') {
                        return $v;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private static function publicPayload(array $payload): array
    {
        return $payload;
    }

    private static function otaPosFragment(): string
    {
        $reqId = defined('BARS_RW_REQUESTOR_ID') && trim((string) BARS_RW_REQUESTOR_ID) !== ''
            ? trim((string) BARS_RW_REQUESTOR_ID)
            : 'website';
        $msgPass = defined('BARS_RW_MESSAGE_PASSWORD') ? trim((string) BARS_RW_MESSAGE_PASSWORD) : '';

        return '<POS><Source><RequestorID ID="'
            . htmlspecialchars($reqId, ENT_QUOTES | ENT_XML1, 'UTF-8')
            . '" MessagePassword="'
            . htmlspecialchars($msgPass, ENT_QUOTES | ENT_XML1, 'UTF-8')
            . '"/></Source></POS>';
    }

    private static function redactSecrets(string $xml): string
    {
        return (string) preg_replace('/MessagePassword="[^"]*"/i', 'MessagePassword="***"', $xml);
    }
}
