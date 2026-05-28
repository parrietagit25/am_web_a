<?php
/**
 * Cliente para el webhook n8n AMCorp (leads corporativos Leasing → Pipedrive).
 */

class N8nAmcorpLeadService
{
    /** @var string[] */
    public const TIPOS_VEHICULO = ['SUV', 'Sedán', 'Pickup', 'Van', 'Hatchback', 'Otro'];

    private string $endpoint;
    private string $jwtSecret;

    public function __construct(?string $endpoint = null, ?string $jwtSecret = null)
    {
        $this->endpoint = $endpoint ?? $this->resolveConfig(
            'N8N_AMCORP_WEBHOOK_URL',
            'https://n8n.grupopcr.com.pa/webhook/amcorp-lead'
        );
        $this->jwtSecret = $jwtSecret ?? $this->resolveConfig('N8N_AMCORP_JWT_SECRET', '');
        if ($this->jwtSecret === '') {
            $this->jwtSecret = $this->resolveConfig('N8N_SEMINUEVOS_JWT_SECRET', '');
        }
    }

    /**
     * @param array{
     *   empresa:string,
     *   nombre:string,
     *   telefono:string,
     *   email:string,
     *   tipo_vehiculo:string,
     *   fecha_alquiler:string,
     *   primera_vez:string,
     *   direccion:string
     * } $input
     * @return array{ok:bool,http_code:int,data:array<string,mixed>|null,error:?string,errores:array<int,string>}
     */
    public function submitLead(array $input): array
    {
        if ($this->jwtSecret === '') {
            am_log('N8N_AMCORP_JWT_SECRET no configurado; lead AMCorp no enviado.', 'WARNING');
            return [
                'ok' => false,
                'http_code' => 503,
                'data' => null,
                'error' => 'Integración CRM no configurada en el servidor.',
                'errores' => [],
            ];
        }

        $payload = [
            'empresa' => $input['empresa'],
            'nombre' => $input['nombre'],
            'telefono' => $input['telefono'],
            'email' => $input['email'],
            'tipo_vehiculo' => $input['tipo_vehiculo'],
            'fecha_alquiler' => $input['fecha_alquiler'],
            'primera_vez' => $input['primera_vez'],
            'direccion' => $input['direccion'],
        ];

        $staticToken = $this->resolveConfig('N8N_AMCORP_JWT_TOKEN', '');
        if ($staticToken === '') {
            $staticToken = $this->resolveConfig('N8N_SEMINUEVOS_JWT_TOKEN', '');
        }
        $token = $staticToken !== '' ? $staticToken : $this->createBearerToken($this->jwtSecret);

        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $responseBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false) {
            am_log('n8n AMCorp: error de conexión — ' . $curlError, 'ERROR');
            return [
                'ok' => false,
                'http_code' => 502,
                'data' => null,
                'error' => 'No se pudo conectar con el servicio de leads.',
                'errores' => [],
            ];
        }

        $data = json_decode($responseBody, true);
        if (!is_array($data)) {
            $data = ['raw' => $responseBody];
        }

        $ok = $httpCode === 200 && !empty($data['success']);
        $errores = is_array($data['errores'] ?? null) ? $data['errores'] : [];

        if (!$ok) {
            $detail = $errores !== []
                ? implode(' · ', $errores)
                : ($data['message'] ?? $data['error'] ?? ('HTTP ' . $httpCode));
            am_log('n8n AMCorp: respuesta no exitosa — ' . $detail, 'WARNING');
        }

        return [
            'ok' => $ok,
            'http_code' => $httpCode,
            'data' => $data,
            'error' => $ok ? null : ($data['message'] ?? 'Error al registrar el lead en CRM.'),
            'errores' => $errores,
        ];
    }

    public static function isValidFechaAlquiler(string $fecha): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return false;
        }
        $parts = explode('-', $fecha);
        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }

    public static function isValidTelefono(string $telefono): bool
    {
        $digits = preg_replace('/\D/', '', $telefono);
        return strlen($digits) >= 7;
    }

    public static function normalizePrimeraVez(string $value): string
    {
        $v = strtoupper(trim($value));
        return $v === 'SI' ? 'SI' : 'NO';
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
