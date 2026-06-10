<?php
/**
 * Public reservation API on Automarket backend (BARS via Node).
 */

require_once __DIR__ . '/BranchDataService.php';

class AutomarketReservationApiService {
    private string $baseUrl;

    public function __construct() {
        $this->baseUrl = rtrim(BranchDataService::partnerImageBaseUrl(), '/');
    }

    public function getBaseUrl(): string {
        return $this->baseUrl;
    }

    /**
     * @return array{ok: bool, data: ?array, error: ?string, httpCode: int}
     */
    public function createReservation(array $payload): array {
        return $this->request('POST', '/api/reservation', $payload, 45);
    }

    /**
     * @return array{ok: bool, data: ?array, error: ?string, httpCode: int}
     */
    public function lookupReservation(string $code, string $lastName = ''): array {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return ['ok' => false, 'data' => null, 'error' => 'Código de reserva requerido.', 'httpCode' => 400];
        }
        $path = '/api/reservation/' . rawurlencode($code);
        $lastName = trim($lastName);
        if ($lastName !== '') {
            $path .= '?lastName=' . rawurlencode($lastName);
        }
        $result = $this->request('GET', $path, null, 20);
        if ($result['ok'] && is_array($result['data']) && isset($result['data']['reservation'])) {
            $result['data'] = $result['data']['reservation'];
        }
        return $result;
    }

    /**
     * Build body expected by POST /api/reservation from checkout payload.
     */
    public static function buildCreatePayload(array $input): array {
        $search = $input['search'] ?? [];
        $vehicle = $input['vehicle'] ?? [];
        $extras = $input['extras'] ?? null;

        $firstName = trim($input['first_name'] ?? '');
        $lastName = trim($input['last_name'] ?? '');
        if ($firstName === '' && !empty($input['customer_name'])) {
            $parts = preg_split('/\s+/', trim($input['customer_name']), 2);
            $firstName = $parts[0] ?? '';
            $lastName = $parts[1] ?? '';
        }

        $phonePrefix = trim($input['phone_prefix'] ?? '+507');
        $phoneNumber = trim($input['phone'] ?? $input['customer_phone'] ?? '');
        $phone = $phoneNumber;
        if ($phoneNumber !== '' && $phonePrefix !== '' && strpos($phoneNumber, '+') !== 0) {
            $phone = preg_replace('/\s+/', '', $phonePrefix . $phoneNumber);
        }

        $rateType = ($input['rate_type'] ?? 'web') === 'counter' ? 'counter' : 'web';
        $rateCode = $vehicle['rateCode'] ?? 'WEB';
        if ($rateType === 'counter' && ($rateCode === '' || $rateCode === 'WEB' || $rateCode === 'Best')) {
            $rateCode = 'NONE';
        }

        $vendorRateId = $vehicle['vendorRateId'] ?? ($vehicle['pricing']['quoteToken'] ?? '');
        if ($rateType === 'counter' && !empty($vehicle['rates']) && is_array($vehicle['rates'])) {
            foreach ($vehicle['rates'] as $r) {
                $rc = strtoupper(trim((string) ($r['rateCode'] ?? '')));
                if ($rc !== 'WEB' && $rc !== 'BEST' && !empty($r['vendorRateId'])) {
                    $vendorRateId = (string) $r['vendorRateId'];
                    break;
                }
            }
        }

        $coverageCode = '';
        if (is_array($extras)) {
            $cov = strtoupper(trim((string) ($extras['protection'] ?? '')));
            if ($cov !== '' && $cov !== 'NONE') {
                $coverageCode = $cov;
            }
        }

        return array_filter([
            'locationCode' => strtoupper(trim($search['locationCode'] ?? '')),
            'returnLocationCode' => strtoupper(trim($search['returnLocationCode'] ?? $search['locationCode'] ?? '')),
            'pickupDate' => $search['pickupDate'] ?? '',
            'pickupTime' => $search['pickupTime'] ?? '10:00',
            'returnDate' => $search['returnDate'] ?? '',
            'returnTime' => $search['returnTime'] ?? '10:00',
            'sippCode' => $vehicle['sippCode'] ?? '',
            'vendorRateId' => $vendorRateId,
            'rateCode' => $rateCode,
            'coverageCode' => $coverageCode ?: null,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => trim($input['email'] ?? $input['customer_email'] ?? ''),
            'phone' => $phone,
            'docType' => trim($input['doc_type'] ?? 'LIC'),
            'docNumber' => trim($input['doc_number'] ?? ''),
            'countryCode' => strtoupper(trim($input['country_code'] ?? 'PA')),
            'flightNumber' => trim($input['flight_number'] ?? '') ?: null,
            'airlineCode' => trim($input['airline_code'] ?? '') ?: null,
            'remarks' => trim($input['remarks'] ?? $input['customer_comments'] ?? '') ?: null,
            'birthDate' => trim($input['birth_date'] ?? '') ?: null,
            'extras' => is_array($extras) ? $extras : null,
        ], static function ($v) {
            return $v !== null && $v !== '';
        });
    }

    /**
     * @return array{ok: bool, data: ?array, error: ?string, httpCode: int}
     */
    private function request(string $method, string $path, ?array $body, int $timeout): array {
        $url = $this->baseUrl . $path;
        $ch = curl_init($url);
        $headers = ['Accept: application/json'];
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = json_encode($body ?? [], JSON_UNESCAPED_UNICODE);
            $headers[] = 'Content-Type: application/json';
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }

        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            am_log('Reservation API cURL error: ' . $curlError, 'ERROR');
            return ['ok' => false, 'data' => null, 'error' => 'No se pudo conectar con el sistema de reservas.', 'httpCode' => 0];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'data' => null, 'error' => 'Respuesta inválida del sistema de reservas.', 'httpCode' => $httpCode];
        }

        if ($httpCode >= 200 && $httpCode < 300 && empty($decoded['error'])) {
            return ['ok' => true, 'data' => $decoded, 'error' => null, 'httpCode' => $httpCode];
        }

        $err = $decoded['error'] ?? ('Error del servidor (' . $httpCode . ')');
        return ['ok' => false, 'data' => $decoded, 'error' => $err, 'httpCode' => $httpCode];
    }
}
