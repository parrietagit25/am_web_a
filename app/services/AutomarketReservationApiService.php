<?php
/**
 * Gateway de reservas RAC: BARS SOAP local (RentWorks) con fallback Partner DO.
 * Mantiene el contrato usado por rac-reservation.php, lookup y lab.
 */

require_once __DIR__ . '/BranchDataService.php';
require_once __DIR__ . '/RacBirthDateService.php';
require_once __DIR__ . '/RacPublicRateService.php';
require_once __DIR__ . '/BarsChargeIds.php';
require_once __DIR__ . '/BarsReservationClient.php';

class AutomarketReservationApiService {
    private string $baseUrl;

    public function __construct() {
        $this->baseUrl = rtrim(BranchDataService::partnerImageBaseUrl(), '/');
    }

    public function getBaseUrl(): string {
        return $this->baseUrl;
    }

    public static function partnerFallbackEnabled(): bool {
        if (defined('RAC_RESERVATION_PARTNER_FALLBACK')) {
            return (bool) RAC_RESERVATION_PARTNER_FALLBACK;
        }
        // Durante la migración: si SOAP falla, intenta Partner.
        return true;
    }

    /**
     * @return array{ok: bool, data: ?array, error: ?string, httpCode: int, source?: string}
     */
    public function createReservation(array $payload): array {
        $bars = new BarsReservationClient();
        if ($bars->isConfigured()) {
            $local = $bars->createReservation($payload, false);
            if (!empty($local['ok'])) {
                $data = is_array($local['data'] ?? null) ? $local['data'] : [];
                if (empty($data['confirmationNumber']) && !empty($local['confirmation'])) {
                    $data['confirmationNumber'] = $local['confirmation'];
                }
                am_log('RAC reservation created via local BARS SOAP: ' . ($data['confirmationNumber'] ?? ''), 'INFO');
                return [
                    'ok' => true,
                    'data' => $data,
                    'error' => null,
                    'httpCode' => (int) ($local['http_code'] ?? 200),
                    'source' => 'local_bars_soap',
                ];
            }

            $soapError = (string) ($local['error'] ?? 'Error SOAP BARS al crear reserva.');
            am_log('RAC local BARS create failed: ' . $soapError, 'WARNING');

            if (!self::partnerFallbackEnabled()) {
                return [
                    'ok' => false,
                    'data' => is_array($local['data'] ?? null) ? $local['data'] : null,
                    'error' => $soapError,
                    'httpCode' => (int) ($local['http_code'] ?? 0),
                    'source' => 'local_bars_soap',
                ];
            }
            am_log('RAC create falling back to Partner DO', 'WARNING');
        }

        $partner = $this->request('POST', '/api/reservation', $payload, 45);
        $partner['source'] = 'partner_do';
        return $partner;
    }

    /**
     * @return array{ok: bool, data: ?array, error: ?string, httpCode: int, source?: string}
     */
    public function lookupReservation(string $code, string $lastName = ''): array {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return ['ok' => false, 'data' => null, 'error' => 'Código de reserva requerido.', 'httpCode' => 400];
        }

        $bars = new BarsReservationClient();
        if ($bars->isConfigured()) {
            $local = $bars->lookupReservation($code, $lastName, false);
            if (!empty($local['ok']) && is_array($local['reservation'] ?? null)) {
                $reservation = $local['reservation'];
                // Preferir el código Type=14 que el cliente suele consultar.
                if (!empty($local['confirmation'])) {
                    $reservation['confirmationNumber'] = $local['confirmation'];
                }
                am_log('RAC reservation lookup via local BARS SOAP: ' . $code, 'INFO');
                return [
                    'ok' => true,
                    'data' => $reservation,
                    'error' => null,
                    'httpCode' => (int) ($local['http_code'] ?? 200),
                    'source' => 'local_bars_soap',
                ];
            }

            $soapError = (string) ($local['error'] ?? 'No encontrada en BARS.');
            // Si BARS responde "no encontrada", no insistir en Partner salvo fallback explícito.
            $notFound = (bool) preg_match('/Code=284\b|No reservations found/i', $soapError);
            if ($notFound && !self::partnerFallbackEnabled()) {
                return [
                    'ok' => false,
                    'data' => null,
                    'error' => $soapError,
                    'httpCode' => (int) ($local['http_code'] ?? 404),
                    'source' => 'local_bars_soap',
                ];
            }
            if (!$notFound) {
                am_log('RAC local BARS lookup failed: ' . $soapError, 'WARNING');
            }
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
        $result['source'] = 'partner_do';
        return $result;
    }

    /**
     * Build body expected by POST /api/reservation / BarsReservationClient from checkout payload.
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

        $rateType = RacPublicRateService::normalizeRateType($input['rate_type'] ?? 'web');
        $rateCode = $vehicle['rateCode'] ?? RacPublicRateService::barsRateCodeForChannel($rateType);
        if ($rateType === 'counter' && ($rateCode === '' || $rateCode === 'WEB' || $rateCode === 'Best')) {
            $rateCode = RacPublicRateService::barsRateCodeForChannel('counter');
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
        if ($coverageCode === '') {
            $covInput = strtoupper(trim((string) ($input['coverage_code'] ?? '')));
            if ($covInput !== '' && $covInput !== 'NONE') {
                $coverageCode = $covInput;
            }
        }

        $promoCode = strtoupper(trim((string) ($search['promoCode'] ?? $input['promo_code'] ?? $input['promoCode'] ?? '')));
        $age = (int) ($search['age'] ?? $input['age'] ?? $input['driver_age'] ?? 0);
        $vehicleCharges = BarsChargeIds::fromCheckoutExtras(is_array($extras) ? $extras : [], $search);

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
            'birthDate' => RacBirthDateService::normalize($input['birth_date'] ?? null),
            'extras' => is_array($extras) ? $extras : null,
            'vehicle_charges' => $vehicleCharges !== [] ? $vehicleCharges : null,
            'bars_passthrough' => true,
            'promoCode' => $promoCode !== '' ? $promoCode : null,
            'age' => $age > 0 ? $age : null,
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
