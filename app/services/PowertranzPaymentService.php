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

    public static function isAutoCompleteEnabled(): bool
    {
        return defined('POWERTRANZ_AUTO_COMPLETE_ENABLED') && POWERTRANZ_AUTO_COMPLETE_ENABLED === true;
    }

    public static function isDiagnosticMode(): bool
    {
        return !self::isAutoCompleteEnabled();
    }

    public static function hppRawFrameUrl(int $paymentId): string
    {
        return '/admin/powertranz-hpp-raw.php?payment_id=' . $paymentId;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function analyzeRedirectDataForPayment(int $paymentId): ?array
    {
        $row = $this->getPayment($paymentId);
        if ($row === null || empty($row['redirect_data_vault'])) {
            return null;
        }

        return PowertranzSanitizer::analyzeRedirectData((string) $row['redirect_data_vault']);
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
     * @param array<string, mixed> $requestMeta
     * @return array{ok: bool, message?: string, payment?: array<string, mixed>}
     */
    public function handleMerchantReturn(string $rawBody, ?array $parsed = null, array $requestMeta = []): array
    {
        $callback = is_array($parsed) ? $this->normalizeCallback($parsed) : null;
        if ($callback === null && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            $callback = is_array($decoded) ? $this->normalizeCallback($decoded) : null;
        }

        if ($callback === null || $callback === []) {
            return [
                'ok' => false,
                'message' => 'MerchantResponseUrl recibido sin payload válido.',
                'diagnostic_mode' => self::isDiagnosticMode(),
            ];
        }

        $callback = $this->expandCallbackResponse($callback);
        $returnEnvelope = $this->buildReturnEnvelope($rawBody, $callback, $requestMeta);
        $returnEnvelope['callback_indicates_hpp_completed'] = $this->callbackIndicatesHppCompleted($callback);
        $returnEnvelope['callback_indicates_hpp_failure'] = $this->callbackIndicatesHppFailure($callback);
        $returnEnvelope['auto_complete_enabled'] = self::isAutoCompleteEnabled();

        $payment = $this->findPaymentFromCallback($callback);
        if ($payment === null) {
            return [
                'ok' => false,
                'message' => 'No se encontró el pago asociado al callback.',
                'diagnostic_mode' => self::isDiagnosticMode(),
            ];
        }

        $paymentId = (int) $payment['id'];
        $currentStatus = (string) ($payment['status'] ?? '');

        if (in_array($currentStatus, ['approved', 'declined', 'expired', 'complete_error'], true)) {
            return [
                'ok' => $currentStatus === 'approved',
                'message' => 'Retorno ignorado: pago ya finalizado (' . $currentStatus . ').',
                'payment' => $this->getPublicPayment($paymentId),
            ];
        }

        $returnEnvelope['payment_id'] = $paymentId;
        $returnEnvelope['test_reference'] = (string) ($payment['test_reference'] ?? '');
        $returnEnvelope['order_identifier'] = (string) ($payment['order_identifier'] ?? '');
        $returnJson = json_encode(PowertranzSanitizer::sanitizePayload($returnEnvelope), JSON_UNESCAPED_UNICODE);

        if ($this->callbackIndicatesHppFailure($callback)) {
            $failureMsg = $this->hppFailureMessage($callback);
            $this->updatePayment($paymentId, [
                'status' => 'hpp_error',
                'merchant_response_json_sanitized' => $returnJson,
                'error_message' => $failureMsg,
            ]);

            return [
                'ok' => false,
                'message' => $failureMsg,
                'payment' => $this->getPublicPayment($paymentId),
                'diagnostic_mode' => self::isDiagnosticMode(),
            ];
        }

        $this->updatePayment($paymentId, [
            'status' => self::isDiagnosticMode() ? 'return_received_diagnostic' : 'return_received',
            'merchant_response_json_sanitized' => $returnJson,
        ]);

        if (!$this->callbackHasValidReturn($callback)) {
            $this->updatePayment($paymentId, [
                'status' => self::isDiagnosticMode() ? 'return_empty_diagnostic' : 'return_error',
                'error_message' => 'MerchantResponseUrl recibido sin payload válido',
            ]);

            return [
                'ok' => false,
                'message' => 'MerchantResponseUrl recibido sin payload válido.',
                'payment' => $this->getPublicPayment($paymentId),
                'diagnostic_mode' => self::isDiagnosticMode(),
            ];
        }

        if (!$this->callbackIndicatesHppCompleted($callback)) {
            $this->updatePayment($paymentId, [
                'status' => self::isDiagnosticMode() ? 'return_received_diagnostic' : 'return_error',
                'error_message' => 'Retorno recibido antes de completar HPP/3DS',
            ]);

            return [
                'ok' => false,
                'message' => 'Retorno recibido antes de completar HPP/3DS. Complete el pago en el iframe embebido.',
                'payment' => $this->getPublicPayment($paymentId),
                'diagnostic_mode' => self::isDiagnosticMode(),
            ];
        }

        if (self::isDiagnosticMode()) {
            return [
                'ok' => false,
                'message' => 'Modo diagnóstico: completePayment bloqueado. Revise callback guardado.',
                'payment' => $this->getPublicPayment($paymentId),
                'diagnostic_mode' => true,
            ];
        }

        $spiToken = $this->client->extractSpiToken($callback);
        if ($spiToken === '') {
            $spiToken = trim((string) ($payment['spi_token_vault'] ?? ''));
        }

        if ($spiToken === '') {
            $this->updatePayment($paymentId, [
                'status' => 'return_error',
                'error_message' => 'Callback sin SpiToken.',
            ]);

            return [
                'ok' => false,
                'message' => 'Callback sin SpiToken.',
                'payment' => $this->getPublicPayment($paymentId),
            ];
        }

        if ($this->isSpiTokenExpired($payment)) {
            $this->updatePayment($paymentId, [
                'status' => 'expired',
                'error_message' => 'SpiToken expirado (máx. 5 minutos).',
            ]);

            return [
                'ok' => false,
                'message' => 'SpiToken expirado. Inicie un pago nuevo.',
                'payment' => $this->getPublicPayment($paymentId),
            ];
        }

        $completePayload = json_encode([
            'action' => 'completePayment',
            'endpoint' => '/api/spi/payment',
            'content_type' => 'application/json-patch+json',
            'accept' => 'text/plain',
            'spi_token' => '[REDACTED]',
        ], JSON_UNESCAPED_UNICODE);

        $this->updatePayment($paymentId, [
            'status' => 'complete_pending',
            'complete_payload_json' => $completePayload,
        ]);

        $complete = $this->client->completePayment($spiToken);

        return $this->applyPaymentCompletion($paymentId, $complete);
    }

    public function markHppOpened(int $paymentId): void
    {
        $row = $this->getPayment($paymentId);
        if ($row === null) {
            return;
        }
        $status = (string) ($row['status'] ?? '');
        if (!in_array($status, ['redirect_ready', 'hpp_opened'], true)) {
            return;
        }
        if ($status === 'redirect_ready') {
            $this->updatePayment($paymentId, ['status' => 'hpp_opened']);
        }
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

        return is_array($row) ? $this->toPublicRow($row, true) : null;
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

        return $this->toPublicRow($row, true);
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

        return $this->toPublicRow($row, true);
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
            $public['frame_url'] = self::hppRawFrameUrl((int) $row['id']);
            $public['redirect_analysis'] = PowertranzSanitizer::analyzeRedirectData((string) ($row['redirect_data_vault'] ?? ''));
        }

        $public['diagnostic_mode'] = self::isDiagnosticMode();
        $public['auto_complete_enabled'] = self::isAutoCompleteEnabled();

        $req = json_decode((string) ($row['request_payload_json'] ?? ''), true);
        if (is_array($req)) {
            $hosted = $req['ExtendedData']['HostedPage'] ?? null;
            if (is_array($hosted)) {
                $public['hpp_page_set'] = (string) ($hosted['PageSet'] ?? '');
                $public['hpp_page_name'] = (string) ($hosted['PageName'] ?? '');
            }
        }
        if (empty($public['hpp_page_set']) && defined('POWERTRANZ_HPP_PAGE_SET')) {
            $public['hpp_page_set'] = trim((string) POWERTRANZ_HPP_PAGE_SET);
        }
        if (empty($public['hpp_page_name']) && defined('POWERTRANZ_HPP_PAGE_NAME')) {
            $public['hpp_page_name'] = trim((string) POWERTRANZ_HPP_PAGE_NAME);
        }
        if ((string) ($row['status'] ?? '') === 'hpp_error') {
            $public['hpp_error_code'] = self::extractHppErrorCode((string) ($row['error_message'] ?? ''));
        }

        $initDiagnostic = PowertranzSanitizer::extractDiagnostic((string) ($row['response_payload_json'] ?? ''));
        $completeDiagnostic = PowertranzSanitizer::extractDiagnostic((string) ($row['complete_response_json'] ?? ''));
        if ($initDiagnostic !== null) {
            $public['init_diagnostic'] = $initDiagnostic;
            $public['non_json_error'] = true;
            $public['non_json_phase'] = 'init';
        } elseif ($completeDiagnostic !== null) {
            $public['complete_diagnostic'] = $completeDiagnostic;
            $public['non_json_error'] = true;
            $public['non_json_phase'] = 'complete';
        } else {
            $public['non_json_error'] = str_contains((string) ($row['error_message'] ?? ''), 'no JSON');
            $public['non_json_phase'] = null;
        }

        $status = (string) ($row['status'] ?? '');
        $iso = strtoupper((string) ($row['iso_response_code'] ?? ''));
        $public['init_hpp_ready'] = !empty($row['redirect_data_present'])
            && in_array($iso, ['SP4', 'SP1', '3D0', '00'], true)
            && in_array($status, ['redirect_ready', 'hpp_opened', 'return_received', 'returned_from_3ds', 'complete_pending'], true);

        $public['can_open_hpp'] = !empty($row['redirect_data_present'])
            && !in_array($status, ['approved', 'declined', 'expired'], true);

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

        if (!is_array($data)) {
            $diagnostic = is_array($api['diagnostic'] ?? null)
                ? $api['diagnostic']
                : PowertranzSanitizer::buildHttpDiagnostic(
                    (string) ($api['raw'] ?? ''),
                    (int) ($api['http_code'] ?? 0),
                    0,
                    (string) ($api['error'] ?? ''),
                    '',
                    ''
                );
            $diagnostic['phase'] = 'init';
            $responseJson = json_encode(PowertranzSanitizer::sanitizePayload($diagnostic), JSON_UNESCAPED_UNICODE);

            $this->updatePayment($paymentId, [
                'status' => 'error',
                'iso_response_code' => '',
                'response_message' => 'Powertranz devolvió respuesta no JSON',
                'error_message' => 'Powertranz devolvió respuesta no JSON',
                'response_payload_json' => $responseJson,
                'auth_response_json_sanitized' => $responseJson,
                'redirect_data_present' => 0,
            ]);

            $payment = $this->getPublicPayment($paymentId);

            return [
                'ok' => false,
                'message' => 'Powertranz devolvió respuesta no JSON',
                'payment' => $payment,
                'api' => [
                    'http_code' => (int) ($api['http_code'] ?? 0),
                    'iso_response_code' => '',
                    'response_message' => 'Powertranz devolvió respuesta no JSON',
                    'has_redirect_data' => false,
                    'has_spi_token' => false,
                    'diagnostic' => $diagnostic,
                ],
            ];
        }

        $iso = $this->client->extractIsoCode($data);
        $message = $this->client->extractResponseMessage($data);
        $redirect = $this->client->extractRedirectData($data);
        $spiToken = $this->client->extractSpiToken($data);

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
        $existing = $this->getPayment($paymentId);
        $data = $api['data'] ?? null;

        if (!is_array($data)) {
            $diagnostic = is_array($api['diagnostic'] ?? null)
                ? $api['diagnostic']
                : PowertranzSanitizer::buildHttpDiagnostic(
                    (string) ($api['raw'] ?? ''),
                    (int) ($api['http_code'] ?? 0),
                    0,
                    (string) ($api['error'] ?? ''),
                    '',
                    ''
                );
            $diagnostic['phase'] = 'complete';
            $completeResponseJson = json_encode(PowertranzSanitizer::sanitizePayload($diagnostic), JSON_UNESCAPED_UNICODE);

            $initIso = strtoupper(trim((string) ($existing['iso_response_code'] ?? '')));
            $hadHpp = !empty($existing['redirect_data_present']);

            $update = [
                'status' => 'complete_error',
                'error_message' => $this->completeErrorMessage($api, $diagnostic),
                'complete_response_json' => $completeResponseJson,
                'payment_response_json_sanitized' => $completeResponseJson,
                'completed_at' => date('Y-m-d H:i:s'),
            ];
            if (!$hadHpp || !in_array($initIso, ['SP4', 'SP1', '3D0', '00'], true)) {
                $update['response_message'] = $update['error_message'];
            }

            $this->updatePayment($paymentId, $update);

            return [
                'ok' => false,
                'message' => 'Powertranz devolvió respuesta no JSON al completar pago',
                'payment' => $this->getPublicPayment($paymentId),
                'api' => [
                    'http_code' => (int) ($api['http_code'] ?? 0),
                    'iso_response_code' => $initIso,
                    'approved' => false,
                    'diagnostic' => $diagnostic,
                ],
            ];
        }

        $iso = $this->client->extractIsoCode($data);
        $message = $this->client->extractResponseMessage($data);
        $approved = $this->client->isApproved($data);

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
            'CurrencyCode' => (string) $currency,
            'ThreeDSecure' => true,
            'Source' => (object) [],
            'OrderIdentifier' => $orderId,
            'AddressMatch' => false,
            'BillingAddress' => [
                'FirstName' => PowertranzSanitizer::name('John'),
                'LastName' => PowertranzSanitizer::name('Smith'),
                'Line1' => PowertranzSanitizer::addressLine('1200 Whitewall Blvd.'),
                'Line2' => PowertranzSanitizer::addressLine('Unit 15'),
                'City' => PowertranzSanitizer::text('Boston'),
                'State' => 'MA',
                'PostalCode' => PowertranzSanitizer::postalCode('02116'),
                'CountryCode' => '840',
                'EmailAddress' => 'john.smith@gmail.com',
                'PhoneNumber' => PowertranzSanitizer::phone('617-345-6790'),
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

    /**
     * @param array<string, mixed> $parsed
     * @return array<string, mixed>
     */
    private function normalizeCallback(array $parsed): array
    {
        $out = [];
        foreach ($parsed as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (is_string($value) || is_numeric($value) || is_bool($value)) {
                $out[(string) $key] = $value;
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed>|null $callback
     * @param array<string, mixed> $requestMeta
     * @return array<string, mixed>
     */
    private function buildReturnEnvelope(string $rawBody, ?array $callback, array $requestMeta): array
    {
        $getKeys = array_keys($requestMeta['get'] ?? $_GET);
        $postKeys = array_keys($requestMeta['post'] ?? $_POST);
        $hasSpi = is_array($callback) && $this->client->extractSpiToken($callback) !== '';

        return [
            '_ptz_return_meta' => true,
            'method' => strtoupper((string) ($requestMeta['method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET')),
            'content_type' => PowertranzSanitizer::text((string) ($requestMeta['content_type'] ?? $_SERVER['CONTENT_TYPE'] ?? ''), 120),
            'raw_body_length' => strlen($rawBody),
            'raw_body_preview' => PowertranzSanitizer::sanitizeRawBodyPreview($rawBody, 500),
            'get_keys' => array_values(array_map('strval', $getKeys)),
            'post_keys' => array_values(array_map('strval', $postKeys)),
            'spi_token_present' => $hasSpi,
            'callback' => $this->client->sanitizeResponse($callback),
        ];
    }

    /**
     * @param array<string, mixed> $callback
     * @return array<string, mixed>
     */
    private function expandCallbackResponse(array $callback): array
    {
        $response = $callback['Response'] ?? $callback['response'] ?? '';
        if (is_string($response) && trim($response) !== '') {
            $decoded = json_decode($response, true);
            if (is_array($decoded)) {
                return array_merge($callback, $decoded);
            }
        }

        return $callback;
    }

    /**
     * @param array<string, mixed> $callback
     */
    private function callbackHasValidReturn(array $callback): bool
    {
        if ($callback === []) {
            return false;
        }

        return trim((string) ($callback['TransactionIdentifier'] ?? $callback['transactionIdentifier'] ?? '')) !== ''
            || trim((string) ($callback['OrderIdentifier'] ?? $callback['orderIdentifier'] ?? '')) !== ''
            || $this->client->extractSpiToken($callback) !== ''
            || trim((string) ($callback['Response'] ?? $callback['response'] ?? '')) !== '';
    }

    /**
     * @param array<string, mixed> $callback
     */
    private function callbackIndicatesHppCompleted(array $callback): bool
    {
        $callback = $this->expandCallbackResponse($callback);

        if ($this->callbackIndicatesHppFailure($callback)) {
            return false;
        }

        if ($this->client->isApproved($callback)) {
            return true;
        }

        foreach ([
            'AuthenticationStatus', 'authenticationStatus',
            'RiskManagement', 'riskManagement',
            'RiskManagementResponse', 'riskManagementResponse',
            'PaRes', 'paRes', 'CRes', 'cRes',
        ] as $key) {
            if (trim((string) ($callback[$key] ?? '')) !== '') {
                return true;
            }
        }

        $iso = strtoupper(trim((string) ($callback['IsoResponseCode'] ?? $callback['isoResponseCode'] ?? '')));

        return in_array($iso, ['00', '3D0'], true);
    }

    /**
     * @param array<string, mixed> $callback
     */
    private function callbackIndicatesHppFailure(array $callback): bool
    {
        $callback = $this->expandCallbackResponse($callback);
        $iso = strtoupper(trim((string) ($callback['IsoResponseCode'] ?? $callback['isoResponseCode'] ?? '')));

        if (in_array($iso, ['12', '57', '05', '14', '51', '54', '55', '61', '62', '65', '75', '91', '96'], true)) {
            return true;
        }

        $errors = $callback['Errors'] ?? $callback['errors'] ?? null;
        if (is_array($errors) && $errors !== []) {
            return true;
        }

        $msg = strtolower(trim((string) ($callback['ResponseMessage'] ?? $callback['responseMessage'] ?? '')));
        if ($msg !== '' && (
            str_contains($msg, 'invalid')
            || str_contains($msg, 'not found')
            || str_contains($msg, 'declined')
            || str_contains($msg, 'error')
            || str_contains($msg, 'failed')
        )) {
            return true;
        }

        if (($callback['Approved'] ?? $callback['approved'] ?? null) === false && $iso !== '' && !in_array($iso, ['SP4', 'SP1', '97'], true)) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $callback
     */
    private function hppFailureMessage(array $callback): string
    {
        $callback = $this->expandCallbackResponse($callback);
        $errors = $callback['Errors'] ?? $callback['errors'] ?? [];
        if (is_array($errors) && isset($errors[0]) && is_array($errors[0])) {
            $code = trim((string) ($errors[0]['Code'] ?? $errors[0]['code'] ?? ''));
            $message = trim((string) ($errors[0]['Message'] ?? $errors[0]['message'] ?? ''));
            if ($code !== '' || $message !== '') {
                return 'HPP error' . ($code !== '' ? ' ' . $code : '') . ($message !== '' ? ': ' . PowertranzSanitizer::text($message, 120) : '');
            }
        }

        $msg = trim((string) ($callback['ResponseMessage'] ?? $callback['responseMessage'] ?? ''));
        if ($msg !== '') {
            return PowertranzSanitizer::text($msg, 180);
        }

        return 'Error HPP antes de completar tarjeta.';
    }

    /**
     * @param array<string, mixed> $payment
     */
    private function isSpiTokenExpired(array $payment): bool
    {
        $expires = trim((string) ($payment['spi_token_expires_at'] ?? ''));
        if ($expires === '') {
            $created = strtotime((string) ($payment['created_at'] ?? ''));
            if ($created === false) {
                return false;
            }

            return (time() - $created) > 300;
        }
        $ts = strtotime($expires);

        return $ts !== false && time() > $ts;
    }

    /**
     * @param array<string, mixed> $api
     * @param array<string, mixed> $diagnostic
     */
    private function completeErrorMessage(array $api, array $diagnostic): string
    {
        $http = (int) ($api['http_code'] ?? ($diagnostic['http_code'] ?? 0));
        $class = (string) ($diagnostic['classification'] ?? '');
        if ($http === 400 && $class === 'empty_response') {
            return 'completePayment HTTP 400 (respuesta vacía). Revise headers Accept/Content-Type.';
        }
        if ($http >= 400) {
            return 'completePayment HTTP ' . $http;
        }

        return 'Powertranz devolvió respuesta no JSON al completar pago';
    }

    private static function extractHppErrorCode(string $errorMessage): string
    {
        if (preg_match('/HPP error\s+(\d+)/i', $errorMessage, $m) === 1) {
            return trim($m[1]);
        }

        return '';
    }
}
