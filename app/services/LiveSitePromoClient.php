<?php
declare(strict_types=1);

/**
 * Cotización promo del sitio público (ajax + paso2).
 * BARS VehAvailRate no aplica Marketing; el paso2 sí muestra precio tachado por SIPP.
 */
final class LiveSitePromoClient
{
    private const DEFAULT_BASE = 'https://www.automarketrentacar.com/es';

    /** @var array<string, array<string, array<string, float>>> */
    private static array $memory = [];

    public static function isEnabled(): bool
    {
        $raw = getenv('RAC_LIVE_PROMO_QUOTE');
        if ($raw === false && defined('RAC_LIVE_PROMO_QUOTE')) {
            $raw = (string) constant('RAC_LIVE_PROMO_QUOTE');
        }
        $v = strtolower(trim((string) ($raw === false ? '1' : $raw)));

        return in_array($v, ['1', 'true', 'yes', 'on', ''], true);
    }

    /**
     * @param array<string, mixed> $search
     * @return array<string, array{web:float,webWas:float,counter:float,counterWas:float}>
     */
    public static function quotesBySipp(array $search): array
    {
        $code = trim((string) ($search['promoCode'] ?? ''));
        if ($code === '' || !self::isEnabled()) {
            return [];
        }

        $key = implode('|', [
            strtoupper((string) ($search['locationCode'] ?? '')),
            strtoupper((string) ($search['returnLocationCode'] ?? $search['locationCode'] ?? '')),
            (string) ($search['pickupDate'] ?? ''),
            (string) ($search['pickupTime'] ?? ''),
            (string) ($search['returnDate'] ?? ''),
            (string) ($search['returnTime'] ?? ''),
            (string) ($search['age'] ?? '25'),
            strtoupper($code),
        ]);
        if (isset(self::$memory[$key])) {
            return self::$memory[$key];
        }

        $parsed = self::fetchAndParse($search, $code);
        self::$memory[$key] = $parsed;

        return $parsed;
    }

    /**
     * @return array<string, array{web:float,webWas:float,counter:float,counterWas:float}>
     */
    public static function parsePaso2(string $html): array
    {
        $out = [];
        $re = '/<span class="precio"><span class=[\'"]nombre_dinero[\'"]>USD<\/span>\s*(\d+)\.<sup>(\d+)<\/sup><\/span>\s*'
            . '(?:<span class="precio2"><span class=[\'"]nombre_dinero[\'"]>USD<\/span>\s*(\d+)\.<sup>(\d+)<\/sup><\/span>)?\s*'
            . '<a href="#" data-id-auto="([A-Z]{4})"[^>]*>(WebExclusivo|Reservar)/s';

        if (!preg_match_all($re, $html, $matches, PREG_SET_ORDER)) {
            return [];
        }

        foreach ($matches as $m) {
            $now = ((int) $m[1]) + (((int) $m[2]) / 100);
            $was = isset($m[3]) && $m[3] !== ''
                ? ((int) $m[3]) + (((int) $m[4]) / 100)
                : $now;
            $sipp = strtoupper($m[5]);
            $kind = $m[6];
            if (!isset($out[$sipp])) {
                $out[$sipp] = [
                    'web' => 0.0,
                    'webWas' => 0.0,
                    'counter' => 0.0,
                    'counterWas' => 0.0,
                ];
            }
            if ($kind === 'WebExclusivo') {
                $out[$sipp]['web'] = round($now, 2);
                $out[$sipp]['webWas'] = round($was, 2);
            } else {
                $out[$sipp]['counter'] = round($now, 2);
                $out[$sipp]['counterWas'] = round($was, 2);
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $search
     * @return array<string, array{web:float,webWas:float,counter:float,counterWas:float}>
     */
    private static function fetchAndParse(array $search, string $code): array
    {
        $base = rtrim(self::baseUrl(), '/');
        $pick = strtoupper(trim((string) ($search['locationCode'] ?? '')));
        $ret = strtoupper(trim((string) ($search['returnLocationCode'] ?? $pick)));
        if ($ret === '') {
            $ret = $pick;
        }

        $pickupDate = self::dmy((string) ($search['pickupDate'] ?? ''));
        $returnDate = self::dmy((string) ($search['returnDate'] ?? ''));
        if ($pick === '' || $pickupDate === '' || $returnDate === '') {
            return [];
        }

        $fields = [
            'sucursal' => $pick,
            'sucursal2' => $ret,
            'fecha_ini' => $pickupDate,
            'hora_ini' => self::hm((string) ($search['pickupTime'] ?? '10:00')),
            'fecha_fin' => $returnDate,
            'hora_fin' => self::hm((string) ($search['returnTime'] ?? '10:00')),
            'edad' => (string) ($search['age'] ?? '25'),
            'promo_code' => $code,
            'tiene_promo_code' => '1',
        ];
        if ($pick !== $ret) {
            $fields['tiene_sucursal2'] = '1';
        }

        $cookie = tempnam(sys_get_temp_dir(), 'ampromo');
        if ($cookie === false) {
            return [];
        }

        $home = self::curl($base . '/', null, $cookie, false);
        if ($home === null) {
            @unlink($cookie);
            return [];
        }

        $ajaxUrl = $base . '/ajax?funcion=ajax_buscar_reserva1&method=post&echo=0';
        $ajax = self::curl($ajaxUrl, http_build_query($fields), $cookie, true);
        $ok = false;
        if ($ajax !== null) {
            $json = json_decode($ajax, true);
            $ok = is_array($json) && ($json['error'] ?? null) === '';
        }
        $html = $ok ? self::curl($base . '/paso2', null, $cookie, false) : null;
        @unlink($cookie);

        if (!is_string($html) || $html === '') {
            return [];
        }

        return self::parsePaso2($html);
    }

    private static function baseUrl(): string
    {
        $raw = getenv('RAC_LIVE_PROMO_QUOTE_URL');
        if ($raw === false && defined('RAC_LIVE_PROMO_QUOTE_URL')) {
            $raw = (string) constant('RAC_LIVE_PROMO_QUOTE_URL');
        }
        $url = trim((string) ($raw === false ? self::DEFAULT_BASE : $raw));

        return $url !== '' ? $url : self::DEFAULT_BASE;
    }

    private static function dmy(string $iso): string
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $iso, $m)) {
            return '';
        }

        return $m[3] . '/' . $m[2] . '/' . $m[1];
    }

    private static function hm(string $t): string
    {
        $t = trim($t);
        if (preg_match('/^(\d{1,2}):(\d{2})/', $t, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        return '10:00';
    }

    private static function curl(string $url, ?string $post, string $cookie, bool $isPost): ?string
    {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '',
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_COOKIEJAR => $cookie,
            CURLOPT_COOKIEFILE => $cookie,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; AutomarketPromoQuote/1.0)',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/json,application/xhtml+xml;q=0.9,*/*;q=0.8',
                'Accept-Language: es-PA,es;q=0.9',
            ],
            CURLOPT_REFERER => rtrim(self::baseUrl(), '/') . '/',
        ];
        if ($isPost) {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = (string) $post;
            $opts[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!is_string($body) || $http >= 400) {
            return null;
        }

        return $body;
    }
}
