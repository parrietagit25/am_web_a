<?php
/**
 * Automarket partner availability API (handoff contract).
 */

class AutomarketApiService {
    private $endpointUrl;
    private $user;
    private $pass;
    private $imageBase;

    public function __construct() {
        $this->user = defined('AUTOMARKET_PARTNER_USER') ? AUTOMARKET_PARTNER_USER : '';
        $this->pass = defined('AUTOMARKET_PARTNER_PASS') ? AUTOMARKET_PARTNER_PASS : '';
        $this->imageBase = BranchDataService::partnerImageBaseUrl();
        $this->endpointUrl = $this->resolveEndpointUrl();
    }

    private function resolveEndpointUrl(): string {
        if (defined('AUTOMARKET_API_BASE_URL') && AUTOMARKET_API_BASE_URL !== '') {
            return rtrim(AUTOMARKET_API_BASE_URL, '/') . '/api/partner/availability';
        }
        $url = defined('AUTOMARKET_API_URL') ? AUTOMARKET_API_URL : '';
        if ($url !== '' && stripos($url, '/api/partner/availability') === false) {
            return rtrim($url, '/') . '/api/partner/availability';
        }
        return $url;
    }

    public function isConfigured(): bool {
        return $this->endpointUrl !== ''
            && $this->user !== ''
            && $this->pass !== ''
            && strpos($this->user, 'TU_') !== 0;
    }

    /**
     * @param array $params
     * @return array Normalized API response for frontend
     */
    public function getAvailability(array $params): array {
        if (!$this->isConfigured()) {
            am_log('Partner API not configured', 'ERROR');
            return $this->errorResponse('El servicio de disponibilidad no está configurado. Contacte al administrador.');
        }

        $age = (string) ($params['age'] ?? '25');
        if (!in_array($age, ['23', '25'], true)) {
            return $this->errorResponse('Edad del conductor no válida. Solo se admiten 23-24 o 25+ años en línea.');
        }

        $payload = [
            'locationCode' => strtoupper(trim($params['locationCode'] ?? 'PTY')),
            'returnLocationCode' => strtoupper(trim($params['returnLocationCode'] ?? $params['locationCode'] ?? 'PTY')),
            'pickupDate' => $params['pickupDate'] ?? '',
            'pickupTime' => $params['pickupTime'] ?? '10:00',
            'returnDate' => $params['returnDate'] ?? '',
            'returnTime' => $params['returnTime'] ?? '10:00',
            'age' => $age,
            'promoCode' => trim($params['promoCode'] ?? ''),
        ];

        if ($payload['returnLocationCode'] === '') {
            $payload['returnLocationCode'] = $payload['locationCode'];
        }

        $cacheKey = $this->buildCacheKey($payload);
        $cached = $this->readCache($cacheKey, $payload);
        if ($cached !== null) {
            return $cached;
        }

        am_log('Partner availability request: ' . json_encode($payload), 'INFO');

        $responseHeaders = [];
        $ch = curl_init($this->endpointUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_USERPWD => $this->user . ':' . $this->pass,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 35,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADERFUNCTION => function ($curl, $headerLine) use (&$responseHeaders) {
                $len = strlen($headerLine);
                $parts = explode(':', $headerLine, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return $len;
            },
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            am_log('Partner API cURL error: ' . $curlError, 'ERROR');
            return $this->errorResponse('No se pudo conectar con el sistema de búsqueda. Intente de nuevo en unos minutos.');
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            am_log("Partner API invalid JSON (HTTP $httpCode): " . substr((string) $response, 0, 300), 'ERROR');
            return $this->errorResponse('Respuesta inválida del sistema de búsqueda.');
        }

        if ($httpCode === 401) {
            return $this->errorResponse('Credenciales del partner API inválidas.');
        }
        if ($httpCode >= 400) {
            $msg = $decoded['error'] ?? "Error del servidor ($httpCode)";
            return $this->errorResponse($msg);
        }

        $result = $this->normalizeResponse($decoded, $responseHeaders);
        $this->writeCache($cacheKey, $payload, $result);
        return $result;
    }

    private function normalizeResponse(array $decoded, array $headers): array {
        $vehicles = $decoded['vehicles'] ?? [];
        if (is_array($vehicles)) {
            foreach ($vehicles as $i => $v) {
                $vehicles[$i] = $this->normalizeVehicle($v);
            }
        } else {
            $vehicles = [];
        }

        $fallback = $decoded['catalogFallback'] ?? [];
        if (is_array($fallback)) {
            foreach ($fallback as $i => $v) {
                $fallback[$i] = $this->normalizeVehicle($v, true);
            }
        } else {
            $fallback = [];
        }

        return [
            'success' => true,
            'source' => $decoded['source'] ?? ($headers['x-source'] ?? 'API'),
            'xCache' => $headers['x-cache'] ?? 'unknown',
            'vehicles' => $vehicles,
            'miss' => !empty($decoded['miss']),
            'reason' => $decoded['reason'] ?? null,
            'catalogFallback' => $fallback,
            'rateCodes' => $decoded['rateCodes'] ?? [],
            'message' => null,
        ];
    }

    private function normalizeVehicle(array $v, bool $isFallback = false): array {
        if (!empty($v['image']) && is_string($v['image'])) {
            $img = $v['image'];
            if (!preg_match('#^https?://#i', $img) && strpos($img, '/api/img') !== 0) {
                $v['image'] = (strpos($img, '/') === 0)
                    ? $this->imageBase . $img
                    : $this->imageBase . '/' . $img;
            } elseif (strpos($img, '/api/img') === 0) {
                $v['image'] = $this->imageBase . $img;
            }
        }
        if (!$isFallback && !isset($v['priceCounterTotal']) && isset($v['priceTotal'])) {
            $v['priceCounterTotal'] = round((float) $v['priceTotal'] * 1.07, 2);
        }
        if ($isFallback && isset($v['basePrice']) && !isset($v['base_price'])) {
            $v['base_price'] = $v['basePrice'];
        }
        $v['_isFallback'] = $isFallback;
        return $v;
    }

    private function errorResponse(string $message): array {
        return [
            'success' => false,
            'source' => 'ERROR',
            'xCache' => 'BYPASS',
            'vehicles' => [],
            'miss' => false,
            'reason' => null,
            'catalogFallback' => [],
            'rateCodes' => [],
            'message' => $message,
        ];
    }

    private function buildCacheKey(array $payload): string {
        return implode('|', [
            $payload['locationCode'],
            $payload['returnLocationCode'],
            $payload['pickupDate'],
            $payload['pickupTime'],
            $payload['returnDate'],
            $payload['returnTime'],
            $payload['age'],
            $payload['promoCode'] ?? '',
        ]);
    }

    private function cacheFilePath(string $key): string {
        $dir = __DIR__ . '/../storage/cache/rac_availability';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir . '/' . hash('sha256', $key) . '.json';
    }

    private function readCache(string $key, array $payload): ?array {
        $path = $this->cacheFilePath($key);
        if (!is_file($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['expires_at']) || empty($data['body'])) {
            return null;
        }
        if (time() > (int) $data['expires_at']) {
            @unlink($path);
            return null;
        }
        $body = $data['body'];
        $body['xCache'] = ($body['xCache'] ?? '') . '+LOCAL';
        return $body;
    }

    private function writeCache(string $key, array $payload, array $result): void {
        if (!$result['success'] || !empty($result['miss'])) {
            return;
        }
        $ttl = empty($result['vehicles']) ? 45 : 300;
        $path = $this->cacheFilePath($key);
        file_put_contents($path, json_encode([
            'expires_at' => time() + $ttl,
            'body' => $result,
        ], JSON_UNESCAPED_UNICODE));
    }
}
