<?php
/**
 * Cliente HTTP Powertranz / First Atlantic Commerce (SPI + HPP).
 * AM-RAC-PAY-POWERTRANZ-0A — no expone credenciales en respuestas/logs sanitizados.
 */
declare(strict_types=1);

require_once __DIR__ . '/PowertranzSanitizer.php';

class PowertranzClient
{
    private string $baseUrl;
    private string $powertranzId;
    private string $powertranzPassword;
    private int $timeoutSeconds;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) (defined('POWERTRANZ_BASE_URL') ? POWERTRANZ_BASE_URL : ''), '/');
        $this->powertranzId = trim((string) (defined('POWERTRANZ_ID') ? POWERTRANZ_ID : ''));
        $this->powertranzPassword = trim((string) (defined('POWERTRANZ_PASSWORD') ? POWERTRANZ_PASSWORD : ''));
        $this->timeoutSeconds = defined('POWERTRANZ_TIMEOUT_SECONDS')
            ? max(5, (int) POWERTRANZ_TIMEOUT_SECONDS)
            : 45;
    }

    public static function isEnabled(): bool
    {
        if (defined('POWERTRANZ_ENABLED') && POWERTRANZ_ENABLED === false) {
            return false;
        }

        return self::isConfigured();
    }

    public static function isConfigured(): bool
    {
        return defined('POWERTRANZ_ID')
            && defined('POWERTRANZ_PASSWORD')
            && trim((string) POWERTRANZ_ID) !== ''
            && trim((string) POWERTRANZ_PASSWORD) !== ''
            && defined('POWERTRANZ_BASE_URL')
            && trim((string) POWERTRANZ_BASE_URL) !== '';
    }

    public static function currencyCode(): string
    {
        if (defined('POWERTRANZ_CURRENCY') && trim((string) POWERTRANZ_CURRENCY) !== '') {
            return trim((string) POWERTRANZ_CURRENCY);
        }
        if (defined('POWERTRANZ_CURRENCY_CODE') && trim((string) POWERTRANZ_CURRENCY_CODE) !== '') {
            return trim((string) POWERTRANZ_CURRENCY_CODE);
        }

        return '840';
    }

    public function getEnvironment(): string
    {
        return defined('POWERTRANZ_ENV') ? (string) POWERTRANZ_ENV : 'staging';
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function hasHppConfig(): bool
    {
        $pageSet = defined('POWERTRANZ_HPP_PAGE_SET') ? trim((string) POWERTRANZ_HPP_PAGE_SET) : '';
        $pageName = defined('POWERTRANZ_HPP_PAGE_NAME') ? trim((string) POWERTRANZ_HPP_PAGE_NAME) : '';

        return $pageSet !== '' && $pageName !== '';
    }

    /**
     * @return array{ok: bool, http_code: int, data: array<string, mixed>|null, raw: string, error?: string}
     */
    public function alive(): array
    {
        return $this->request('GET', '/api/alive');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ok: bool, http_code: int, data: array<string, mixed>|null, raw: string, error?: string}
     */
    public function saleHpp(array $payload): array
    {
        return $this->request('POST', '/api/spi/sale', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ok: bool, http_code: int, data: array<string, mixed>|null, raw: string, error?: string}
     */
    public function authHpp(array $payload): array
    {
        return $this->request('POST', '/api/spi/auth', $payload);
    }

    /**
     * @return array{ok: bool, http_code: int, data: array<string, mixed>|null, raw: string, error?: string}
     */
    public function completePayment(string $spiToken): array
    {
        $token = trim($spiToken);
        if ($token === '') {
            return [
                'ok' => false,
                'http_code' => 0,
                'data' => null,
                'raw' => '',
                'error' => 'SpiToken vacío.',
            ];
        }

        if (!self::isConfigured()) {
            return [
                'ok' => false,
                'http_code' => 0,
                'data' => null,
                'raw' => '',
                'error' => 'Powertranz no configurado.',
            ];
        }

        // Powertranz SPI payment: body = token JSON-quoted string, sin headers de merchant auth.
        return $this->requestRaw(
            'POST',
            $this->baseUrl . '/api/spi/payment',
            json_encode($token, JSON_UNESCAPED_UNICODE),
            false
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ok: bool, http_code: int, data: array<string, mixed>|null, raw: string, error?: string}
     */
    public function capture(array $payload): array
    {
        return $this->request('POST', '/api/capture', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ok: bool, http_code: int, data: array<string, mixed>|null, raw: string, error?: string}
     */
    public function void(array $payload): array
    {
        return $this->request('POST', '/api/void', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ok: bool, http_code: int, data: array<string, mixed>|null, raw: string, error?: string}
     */
    public function refund(array $payload): array
    {
        return $this->request('POST', '/api/refund', $payload);
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>|null
     */
    public function sanitizePayload(?array $payload): ?array
    {
        return PowertranzSanitizer::sanitizePayload($payload);
    }

    /**
     * @param array<string, mixed>|null $response
     * @return array<string, mixed>|null
     */
    public function sanitizeResponse(?array $response): ?array
    {
        return PowertranzSanitizer::sanitizePayload($response);
    }

    /**
     * @return array<string, string>
     */
    public function debugHeadersSanitized(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json; charset=utf-8',
            'Powertranz-PowertranzId' => $this->powertranzId !== '' ? '[SET]' : '[MISSING]',
            'Powertranz-PowertranzPassword' => $this->powertranzPassword !== '' ? '[SET]' : '[MISSING]',
        ];
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public function extractIsoCode(?array $data): string
    {
        return strtoupper(trim((string) ($data['IsoResponseCode'] ?? $data['isoResponseCode'] ?? '')));
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public function extractResponseMessage(?array $data): string
    {
        return trim((string) ($data['ResponseMessage'] ?? $data['responseMessage'] ?? ''));
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public function extractRedirectData(?array $data): string
    {
        return (string) ($data['RedirectData'] ?? $data['redirectData'] ?? '');
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public function extractSpiToken(?array $data): string
    {
        return trim((string) ($data['SpiToken'] ?? $data['spiToken'] ?? ''));
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public function isApproved(?array $data): bool
    {
        if ($data === null) {
            return false;
        }
        $approved = $data['Approved'] ?? $data['approved'] ?? false;

        return $approved === true || $approved === 1 || $approved === 'true' || $approved === '1';
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array{ok: bool, http_code: int, data: array<string, mixed>|null, raw: string, error?: string}
     */
    private function request(string $method, string $path, ?array $payload = null): array
    {
        if (!self::isConfigured()) {
            return [
                'ok' => false,
                'http_code' => 0,
                'data' => null,
                'raw' => '',
                'error' => 'Powertranz no configurado.',
            ];
        }

        $body = null;
        if ($payload !== null && strtoupper($method) !== 'GET') {
            $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        return $this->requestRaw(
            strtoupper($method),
            $this->baseUrl . $path,
            $body,
            true
        );
    }

    /**
     * @return array{ok: bool, http_code: int, data: array<string, mixed>|null, raw: string, error?: string}
     */
    private function requestRaw(string $method, string $url, ?string $body, bool $withAuthHeaders): array
    {
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json; charset=utf-8',
        ];
        if ($withAuthHeaders) {
            $headers[] = 'Powertranz-PowertranzId: ' . $this->powertranzId;
            $headers[] = 'Powertranz-PowertranzPassword: ' . $this->powertranzPassword;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'http_code' => 0, 'data' => null, 'raw' => '', 'error' => 'No se pudo iniciar cURL.'];
        }

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
        ];

        if ($body !== null && strtoupper($method) !== 'GET') {
            $opts[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return [
                'ok' => false,
                'http_code' => $httpCode,
                'data' => null,
                'raw' => '',
                'error' => $curlError !== '' ? $curlError : 'Error de red Powertranz.',
            ];
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return [
                'ok' => false,
                'http_code' => $httpCode,
                'data' => null,
                'raw' => (string) $raw,
                'error' => 'Respuesta JSON inválida de Powertranz.',
            ];
        }

        return [
            'ok' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data' => $decoded,
            'raw' => (string) $raw,
        ];
    }
}
