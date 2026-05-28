<?php
/**
 * Cliente para el webhook n8n PipeDrive_AMSeminuevos (captura de leads Seminuevos).
 */

class N8nSeminuevosLeadService
{
    public const PROVINCIA_FIELD_KEY = '8348d2ffe326bb5b16f928fda40c6763abed6a13';

    /** @var string[] */
    public const PROVINCIAS_VALIDAS = [
        'Bocas Del Toro',
        'Coclé',
        'Colón',
        'Chiriquí',
        'Darien',
        'Herrera',
        'Los Santos',
        'Panamá',
        'Veraguas',
        'Panamá Oeste (La Chorrera)',
    ];

    private string $endpoint;
    private string $jwtSecret;

    public function __construct(?string $endpoint = null, ?string $jwtSecret = null)
    {
        $this->endpoint = $endpoint ?? $this->resolveConfig('N8N_SEMINUEVOS_WEBHOOK_URL', 'https://n8n.grupopcr.com.pa/webhook/seminuevos');
        $this->jwtSecret = $jwtSecret ?? $this->resolveConfig('N8N_SEMINUEVOS_JWT_SECRET', '');
    }

    /**
     * @param array{nombre:string,email:string,telefono:string,auto_interes:string,provincia:string} $input
     * @return array{ok:bool,http_code:int,data:array<string,mixed>|null,error:?string}
     */
    public function submitLead(array $input): array
    {
        if ($this->jwtSecret === '') {
            am_log('N8N_SEMINUEVOS_JWT_SECRET no configurado; lead no enviado a n8n.', 'WARNING');
            return [
                'ok' => false,
                'http_code' => 503,
                'data' => null,
                'error' => 'Integración CRM no configurada en el servidor.',
            ];
        }

        $payload = [
            'Nombre Completo' => $input['nombre'],
            'Correo Electrónico' => $input['email'],
            'Número Telefónico' => $input['telefono'],
            'Auto de tu Interés' => $input['auto_interes'],
            self::PROVINCIA_FIELD_KEY => $input['provincia'],
        ];

        $staticToken = $this->resolveConfig('N8N_SEMINUEVOS_JWT_TOKEN', '');
        $token = $staticToken !== '' ? $staticToken : $this->createBearerToken($this->jwtSecret);

        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $responseBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false) {
            am_log('n8n seminuevos: error de conexión — ' . $curlError, 'ERROR');
            return [
                'ok' => false,
                'http_code' => 502,
                'data' => null,
                'error' => 'No se pudo conectar con el servicio de leads.',
            ];
        }

        $data = json_decode($responseBody, true);
        if (!is_array($data)) {
            $data = ['raw' => $responseBody];
        }

        $ok = $httpCode === 201 && !empty($data['success']);

        if (!$ok) {
            $detail = $data['details'] ?? $data['error'] ?? ('HTTP ' . $httpCode);
            am_log('n8n seminuevos: respuesta no exitosa — ' . $detail, 'WARNING');
        }

        return [
            'ok' => $ok,
            'http_code' => $httpCode,
            'data' => $data,
            'error' => $ok ? null : ($data['details'] ?? $data['error'] ?? 'Error al registrar el lead en CRM.'),
        ];
    }

    public static function isProvinciaValid(string $provincia): bool
    {
        if ($provincia === '') {
            return true;
        }
        return in_array($provincia, self::PROVINCIAS_VALIDAS, true);
    }

    private function resolveConfig(string $envKey, string $default): string
    {
        if (defined($envKey)) {
            $val = constant($envKey);
            if (is_string($val) && $val !== '') {
                return $val;
            }
        }
        $fromEnv = getenv($envKey);
        if (is_string($fromEnv) && $fromEnv !== '') {
            return $fromEnv;
        }
        return $default;
    }

    private function createBearerToken(string $secret): string
    {
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_UNESCAPED_UNICODE));
        $now = time();
        $payload = $this->base64UrlEncode(json_encode([
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_UNESCAPED_UNICODE));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $header . '.' . $payload, $secret, true));
        return $header . '.' . $payload . '.' . $signature;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
