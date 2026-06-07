<?php
/**
 * Google reCAPTCHA v3 — verificación server-side para formularios públicos.
 */
class CaptchaService
{
    public static function isEnabled(): bool
    {
        return defined('RECAPTCHA_SECRET_KEY')
            && RECAPTCHA_SECRET_KEY !== ''
            && defined('RECAPTCHA_SITE_KEY')
            && RECAPTCHA_SITE_KEY !== '';
    }

    public static function siteKey(): string
    {
        return (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY !== '')
            ? RECAPTCHA_SITE_KEY
            : '';
    }

    /**
     * @param array<string, mixed>|null $input
     */
    public static function enforce(?array $input, string $format = 'status'): void
    {
        if (!self::isEnabled()) {
            return;
        }

        $token = is_array($input) ? trim((string) ($input['captcha_token'] ?? '')) : '';
        $verify = self::verify($token);

        if ($verify['ok']) {
            return;
        }

        http_response_code(403);
        $message = 'No pudimos verificar la solicitud. Recargue la página e inténtelo de nuevo.';
        if ($format === 'success') {
            echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * @return array{ok: bool, score?: float, error?: string}
     */
    public static function verify(string $token): array
    {
        if (!self::isEnabled()) {
            return ['ok' => true];
        }

        if ($token === '') {
            return ['ok' => false, 'error' => 'missing_token'];
        }

        $payload = http_build_query([
            'secret' => RECAPTCHA_SECRET_KEY,
            'response' => $token,
            'remoteip' => self::clientIp(),
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);

        $raw = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
        if ($raw === false) {
            am_log('Captcha verify: no response from Google', 'ERROR');
            return ['ok' => false, 'error' => 'network'];
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['success'])) {
            $codes = isset($data['error-codes']) ? implode(',', (array) $data['error-codes']) : 'unknown';
            am_log('Captcha verify failed: ' . $codes, 'WARNING');
            return ['ok' => false, 'error' => $codes];
        }

        // v3 incluye score; v2 (checkbox) no — solo validar score si viene en la respuesta
        if (array_key_exists('score', $data)) {
            $score = floatval($data['score']);
            $minScore = defined('RECAPTCHA_MIN_SCORE') ? floatval(RECAPTCHA_MIN_SCORE) : 0.5;
            if ($score < $minScore) {
                am_log('Captcha low score: ' . $score, 'WARNING');
                return ['ok' => false, 'error' => 'low_score', 'score' => $score];
            }
            return ['ok' => true, 'score' => $score];
        }

        return ['ok' => true];
    }

    private static function clientIp(): string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key) {
            $raw = trim((string) ($_SERVER[$key] ?? ''));
            if ($raw === '') {
                continue;
            }
            $ip = trim(explode(',', $raw)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '';
    }
}
