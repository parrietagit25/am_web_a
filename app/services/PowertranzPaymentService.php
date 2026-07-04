<?php
/**
 * Persistencia y orquestación de pagos Powertranz aislados.
 * AM-RAC-PAY-POWERTRANZ-0A
 */
declare(strict_types=1);

require_once __DIR__ . '/PowertranzDatabaseSchema.php';
require_once __DIR__ . '/PowertranzClient.php';
require_once __DIR__ . '/PowertranzSanitizer.php';

class PowertranzPaymentService
{
    private PowertranzClient $client;

    public function __construct(?PowertranzClient $client = null)
    {
        PowertranzDatabaseSchema::ensure();
        $this->client = $client ?? new PowertranzClient();
    }

    public function getClient(): PowertranzClient
    {
        return $this->client;
    }

    public static function merchantResponseUrl(): string
    {
        if (defined('POWERTRANZ_MERCHANT_RESPONSE_URL') && trim((string) POWERTRANZ_MERCHANT_RESPONSE_URL) !== '') {
            return rtrim((string) POWERTRANZ_MERCHANT_RESPONSE_URL, '/');
        }
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '') {
            $host = 'test.automarket.com.pa';
        }
        $scheme = 'https';
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $scheme = 'https';
        } elseif (!empty($_SERVER['REQUEST_SCHEME'])) {
            $scheme = (string) $_SERVER['REQUEST_SCHEME'];
        }

        return $scheme . '://' . $host . '/api/powertranz-return.php';
    }

    /**
     * @return array{ok: bool, message?: string, payment?: array<string, mixed>, api?: array<string, mixed>}
     */
    public function initTestPayment(float $amount, string $mode = 'sale', ?int $reservationId = null): array
    {
        if (!$this->client->isConfigured()) {
            return ['ok' => false, 'message' => 'Powertranz no está configurado en config.php.'];
        }

        $amount = round(max(0.01, $amount), 2);
        $mode = strtolower(trim($mode)) === 'auth' ? 'auth' : 'sale';
        $currency = PowertranzClient::currencyCode();

        $transactionId = $this->generateUuid();
        $orderId = $this->generateOrderIdentifier();
        $testRef = 'AM-RAC-PTZ-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        $payload = $this->buildHppPayload($transactionId, $orderId, $amount, $currency);
        if (isset($payload['error'])) {
            return ['ok' => false, 'message' => (string) $payload['error']];
        }

        $requestJson = json_encode($this->client->sanitizePayload($payload), JSON_UNESCAPED_UNICODE);
        $paymentId = $this->insertPayment([
            'reservation_id' => $reservationId,
            'test_reference' => $testRef,
            'payment_reference' => $testRef,
            'transaction_identifier' => $transactionId,
            'order_identifier' => $orderId,
            'amount' => $amount,
            'tax_amount' => 0,
            'currency' => $currency,
            'currency_code' => $currency,
            'mode' => $mode,
            'environment' => $this->client->getEnvironment(),
            'status' => 'created',
            'request_payload_json' => $requestJson,
            'request_json_sanitized' => $requestJson,
        ]);

        $api = $mode === 'auth'
            ? $this->client->authHpp($payload)
            : $this->client->saleHpp($payload);

        return $this->applyAuthResponse($paymentId, $api);
    }

    /**
     * @return array{ok: bool, message?: string, payment?: array<string, mixed>}
     */
    public function handleMerchantReturn(string $rawBody, ?array $parsed = null): array
    {
        $data = $parsed ?? json_decode($rawBody, true);
        if (!is_array($data)) {
            return ['ok' => false, 'message' => 'Callback Powertranz inválido.'];
        }

        $payment = $this->findPaymentFromCallback($data);
        if ($payment === null) {
            return ['ok' => false, 'message' => 'No se encontró el pago asociado al callback.'];
        }

        $spiToken = $this->client->extractSpiToken($data);
        if ($spiToken === '') {
            $spiToken = trim((string) ($payment['spi_token_vault'] ?? ''));
        }

        $this->updatePayment((int) $payment['id'], [
            'status' => 'returned_from_3ds',
            'merchant_response_json_sanitized' => json_encode($this->client->sanitizeResponse($data), JSON_UNESCAPED_UNICODE),
        ]);

        if ($spiToken === '') {
            $this->updatePayment((int) $payment['id'], [
                'status' => 'error',
                'response_message' => 'Callback sin SpiToken.',
                'error_message' => 'Callback sin SpiToken.',
            ]);

            return ['ok' => false, 'message' => 'Callback sin SpiToken.', 'payment' => $this->getPublicPayment((int) $payment['id'])];
        }

        $completePayload = json_encode(['action' => 'completePayment', 'spi_token' => '[REDACTED]'], JSON_UNESCAPED_UNICODE);
        $this->updatePayment((int) $payment['id'], [
            'complete_payload_json' => $completePayload,
        ]);

        $complete = $this->client->completePayment($spiToken);

        return $this->applyPaymentCompletion((int) $payment['id'], $complete);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPayment(int $paymentId): ?array
    {
        $db = Database::getInstance();
        $row = $db->selectOne('SELECT * FROM rac_powertranz_payments WHERE id = :id', [':id' => $paymentId]);

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPaymentByReference(string $reference): ?array
    {
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }
        $db = Database::getInstance();
        $row = $db->selectOne(
            'SELECT * FROM rac_powertranz_payments WHERE test_reference = :r OR payment_reference = :r OR order_identifier = :r LIMIT 1',
            [':r' => $reference]
        );

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLastTestPayment(): ?array
    {
        $db = Database::getInstance();
        $row = $db->selectOne('SELECT * FROM rac_powertranz_payments ORDER BY id DESC LIMIT 1');

        return is_array($row) ? $this->toPublicRow($row, false) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPublicPaymentByReference(string $reference): ?array
    {
        $row = $this->getPaymentByReference($reference);
        if ($row === null) {
            return null;
        }

        return $this->toPublicRow($row, false);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPublicPayment(int $paymentId): ?array
    {
        $row = $this->getPayment($paymentId);
        if ($row === null) {
            return null;
        }

        return $this->toPublicRow($row, false);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPaymentForFrame(int $paymentId): ?array
    {
        $row = $this->getPayment($paymentId);
        if ($row === null) {
            return null;
        }
        if (empty($row['redirect_data_vault'])) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'redirect_html' => (string) $row['redirect_data_vault'],
            'status' => (string) ($row['status'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function toPublicRow(array $row, bool $includeFrameUrl = true): array
    {
        $public = [
            'ok' => true,
            'payment_id' => (int) ($row['id'] ?? 0),
            'test_reference' => (string) ($row['test_reference'] ?? $row['payment_reference'] ?? ''),
            'payment_reference' => (string) ($row['payment_reference'] ?? $row['test_reference'] ?? ''),
            'order_identifier' => (string) ($row['order_identifier'] ?? ''),
            'transaction_identifier' => (string) ($row['transaction_identifier'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
            'approved' => !empty($row['approved']),
            'iso_response_code' => (string) ($row['iso_response_code'] ?? ''),
            'response_message' => (string) ($row['response_message'] ?? ''),
            'error_message' => (string) ($row['error_message'] ?? ''),
            'authorization_code' => (string) ($row['authorization_code'] ?? ''),
            'rrn' => (string) ($row['rrn'] ?? ''),
            'card_brand' => (string) ($row['card_brand'] ?? ''),
            'amount' => (float) ($row['amount'] ?? 0),
            'currency' => (string) ($row['currency'] ?? $row['currency_code'] ?? ''),
            'currency_code' => (string) ($row['currency_code'] ?? $row['currency'] ?? ''),
            'mode' => (string) ($row['mode'] ?? ''),
            'environment' => (string) ($row['environment'] ?? ''),
            'has_redirect_data' => !empty($row['redirect_data_present']),
            'has_spi_token' => !empty($row['spi_token_hash']),
            'updated_at' => (string) ($row['updated_at'] ?? $row['created_at'] ?? ''),
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
        if ($includeFrameUrl && !empty($row['redirect_data_present'])) {
            $public['frame_url'] = '/admin/powertranz-payment-frame.php?payment_id=' . (int) $row['id'];
        }

        return $public;
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function insertPayment(array $fields): int
    {
        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');
        $db->execute(
            'INSERT INTO rac_powertranz_payments (
                reservation_id, test_reference, payment_reference, transaction_identifier, order_identifier,
                amount, tax_amount, currency, currency_code, mode, environment, status,
                request_payload_json, request_json_sanitized, created_at, updated_at
            ) VALUES (
                :reservation_id, :test_reference, :payment_reference, :transaction_identifier, :order_identifier,
                :amount, :tax_amount, :currency, :currency_code, :mode, :environment, :status,
                :request_payload_json, :request_json_sanitized, :created_at, :updated_at
            )',
            [
                ':reservation_id' => $fields['reservation_id'] ?? null,
                ':test_reference' => $fields['test_reference'],
                ':payment_reference' => $fields['payment_reference'] ?? $fields['test_reference'],
                ':transaction_identifier' => $fields['transaction_identifier'],
                ':order_identifier' => $fields['order_identifier'],
                ':amount' => $fields['amount'],
                ':tax_amount' => $fields['tax_amount'] ?? 0,
                ':currency' => $fields['currency'] ?? $fields['currency_code'],
                ':currency_code' => $fields['currency_code'] ?? $fields['currency'],
                ':mode' => $fields['mode'],
                ':environment' => $fields['environment'],
                ':status' => $fields['status'],
                ':request_payload_json' => $fields['request_payload_json'] ?? $fields['request_json_sanitized'] ?? null,
                ':request_json_sanitized' => $fields['request_json_sanitized'] ?? $fields['request_payload_json'] ?? null,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]
        );

        return (int) $db->lastInsertId();
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function updatePayment(int $paymentId, array $fields): void
    {
        if ($fields === []) {
            return;
        }
        $db = Database::getInstance();
        $sets = [];
        $params = [':id' => $paymentId];
        foreach ($fields as $key => $value) {
            $sets[] = $key . ' = :' . $key;
            $params[':' . $key] = $value;
        }
        $sets[] = 'updated_at = :updated_at';
        $params[':updated_at'] = date('Y-m-d H:i:s');
        $db->execute('UPDATE rac_powertranz_payments SET ' . implode(', ', $sets) . ' WHERE id = :id', $params);
    }

    /**
     * @return array{ok: bool, message?: string, payment?: array<string, mixed>, api?: array<string, mixed>}
     */
    private function applyAuthResponse(int $paymentId, array $api): array
    {
        $data = $api['data'] ?? null;
        $iso = $this->client->extractIsoCode(is_array($data) ? $data : null);
        $message = $this->client->extractResponseMessage(is_array($data) ? $data : null);
        $redirect = is_array($data) ? $this->client->extractRedirectData($data) : '';
        $spiToken = is_array($data) ? $this->client->extractSpiToken($data) : '';

        $status = 'error';
        if ($redirect !== '' && in_array($iso, ['SP4', 'SP1', '00', '3D0'], true)) {
            $status = 'redirect_ready';
        } elseif ($api['ok'] && $redirect !== '' && $spiToken !== '') {
            $status = 'redirect_ready';
        }

        $responseJson = json_encode($this->client->sanitizeResponse(is_array($data) ? $data : null), JSON_UNESCAPED_UNICODE);
        $update = [
            'status' => $status,
            'iso_response_code' => $iso,
            'response_message' => $message !== '' ? $message : ((string) ($api['error'] ?? 'Respuesta Powertranz')),
            'response_payload_json' => $responseJson,
            'auth_response_json_sanitized' => $responseJson,
            'redirect_data_present' => $redirect !== '' ? 1 : 0,
        ];
        if ($status === 'error') {
            $update['error_message'] = $update['response_message'];
        }
        if ($redirect !== '') {
            $update['redirect_data_vault'] = $redirect;
        }
        if ($spiToken !== '') {
            $update['spi_token_vault'] = $spiToken;
            $update['spi_token_hash'] = hash('sha256', $spiToken);
            $update['spi_token_expires_at'] = date('Y-m-d H:i:s', time() + 300);
        }
        $this->updatePayment($paymentId, $update);

        $payment = $this->getPublicPayment($paymentId);
        $ok = $status === 'redirect_ready';

        return [
            'ok' => $ok,
            'message' => $ok ? 'Pago listo para HPP/3DS.' : ($payment['response_message'] ?? 'No se pudo iniciar el pago.'),
            'payment' => $payment,
            'api' => [
                'http_code' => (int) ($api['http_code'] ?? 0),
                'iso_response_code' => $iso,
                'response_message' => $message,
                'has_redirect_data' => $redirect !== '',
                'has_spi_token' => $spiToken !== '',
            ],
        ];
    }

    /**
     * @return array{ok: bool, message?: string, payment?: array<string, mixed>, api?: array<string, mixed>}
     */
    private function applyPaymentCompletion(int $paymentId, array $api): array
    {
        $data = $api['data'] ?? null;
        $iso = $this->client->extractIsoCode(is_array($data) ? $data : null);
        $message = $this->client->extractResponseMessage(is_array($data) ? $data : null);
        $approved = $this->client->isApproved(is_array($data) ? $data : null);

        $status = 'error';
        if ($api['ok']) {
            $status = $approved ? 'approved' : 'declined';
        }

        $completeResponseJson = json_encode($this->client->sanitizeResponse(is_array($data) ? $data : null), JSON_UNESCAPED_UNICODE);
        $finalMessage = $message !== '' ? $message : ((string) ($api['error'] ?? ''));

        $this->updatePayment($paymentId, [
            'status' => $status,
            'approved' => $approved ? 1 : 0,
            'iso_response_code' => $iso,
            'response_message' => $finalMessage,
            'error_message' => $status === 'error' ? $finalMessage : null,
            'authorization_code' => is_array($data) ? trim((string) ($data['AuthorizationCode'] ?? $data['authorizationCode'] ?? '')) : '',
            'rrn' => is_array($data) ? trim((string) ($data['RRN'] ?? $data['Rrn'] ?? $data['rrn'] ?? '')) : '',
            'card_brand' => is_array($data) ? trim((string) ($data['CardBrand'] ?? $data['cardBrand'] ?? '')) : '',
            'complete_response_json' => $completeResponseJson,
            'payment_response_json_sanitized' => $completeResponseJson,
            'completed_at' => date('Y-m-d H:i:s'),
            'spi_token_vault' => null,
        ]);

        return [
            'ok' => $approved,
            'message' => $message !== '' ? $message : ($approved ? 'Pago aprobado.' : 'Pago rechazado o error.'),
            'payment' => $this->getPublicPayment($paymentId),
            'api' => [
                'http_code' => (int) ($api['http_code'] ?? 0),
                'iso_response_code' => $iso,
                'approved' => $approved,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $callback
     * @return array<string, mixed>|null
     */
    private function findPaymentFromCallback(array $callback): ?array
    {
        $db = Database::getInstance();
        $txn = trim((string) ($callback['TransactionIdentifier'] ?? $callback['transactionIdentifier'] ?? ''));
        if ($txn !== '') {
            $row = $db->selectOne('SELECT * FROM rac_powertranz_payments WHERE transaction_identifier = :t LIMIT 1', [':t' => $txn]);
            if (is_array($row)) {
                return $row;
            }
        }
        $order = PowertranzSanitizer::orderIdentifier((string) ($callback['OrderIdentifier'] ?? $callback['orderIdentifier'] ?? ''));
        if ($order !== '') {
            $row = $db->selectOne('SELECT * FROM rac_powertranz_payments WHERE order_identifier = :o LIMIT 1', [':o' => $order]);
            if (is_array($row)) {
                return $row;
            }
        }
        $spiToken = trim((string) ($callback['SpiToken'] ?? $callback['spiToken'] ?? ''));
        if ($spiToken !== '') {
            $hash = hash('sha256', $spiToken);
            $row = $db->selectOne('SELECT * FROM rac_powertranz_payments WHERE spi_token_hash = :h ORDER BY id DESC LIMIT 1', [':h' => $hash]);
            if (is_array($row)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|array{error: string}
     */
    private function buildHppPayload(string $transactionId, string $orderId, float $amount, string $currency): array
    {
        $merchantUrl = self::merchantResponseUrl();
        $extended = [
            'ThreeDSecure' => [
                'ChallengeWindowSize' => 4,
                'ChallengeIndicator' => '01',
            ],
            'MerchantResponseUrl' => $merchantUrl,
        ];

        if ($this->client->hasHppConfig()) {
            $extended['HostedPage'] = [
                'PageSet' => (string) POWERTRANZ_HPP_PAGE_SET,
                'PageName' => (string) POWERTRANZ_HPP_PAGE_NAME,
            ];
        }

        return [
            'TransactionIdentifier' => $transactionId,
            'TotalAmount' => $amount,
            'TaxAmount' => 0.0,
            'CurrencyCode' => $currency,
            'ThreeDSecure' => true,
            'OrderIdentifier' => $orderId,
            'AddressMatch' => false,
            'BillingAddress' => [
                'FirstName' => PowertranzSanitizer::name('Automarket'),
                'LastName' => PowertranzSanitizer::name('Test'),
                'Line1' => PowertranzSanitizer::addressLine('Panama City'),
                'City' => PowertranzSanitizer::text('Panama'),
                'State' => 'PA',
                'PostalCode' => PowertranzSanitizer::postalCode('00000'),
                'CountryCode' => '840',
                'EmailAddress' => 'test@automarket.com.pa',
                'PhoneNumber' => PowertranzSanitizer::phone('507-000-0000'),
            ],
            'ExtendedData' => $extended,
        ];
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function generateOrderIdentifier(): string
    {
        return PowertranzSanitizer::orderIdentifier(
            'AM-RAC-PTZ-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6))
        );
    }
}
