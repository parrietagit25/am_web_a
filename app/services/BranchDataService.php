<?php
/**
 * Sucursales RAC (handoff 04-data).
 */

class BranchDataService {
    private static $sucursales = null;
    private static $closedReturnDays = null;

    public static function getSucursales(): array {
        if (self::$sucursales === null) {
            $path = __DIR__ . '/../data/sucursales.json';
            $raw = is_file($path) ? file_get_contents($path) : '[]';
            $decoded = json_decode($raw, true);
            self::$sucursales = is_array($decoded) ? $decoded : [];
        }
        return self::$sucursales;
    }

    public static function getClosedReturnDays(): array {
        if (self::$closedReturnDays === null) {
            $path = __DIR__ . '/../data/closedReturnDays.json';
            $raw = is_file($path) ? file_get_contents($path) : '{}';
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                $decoded = [];
            }
            unset($decoded['_comment']);
            self::$closedReturnDays = $decoded;
        }
        return self::$closedReturnDays;
    }

    public static function findByCode(string $code): ?array {
        $code = strtoupper(trim($code));
        foreach (self::getSucursales() as $s) {
            if (($s['code'] ?? '') === $code) {
                return $s;
            }
        }
        return null;
    }

    public static function getBranchPayloadForJs(): array {
        $closed = self::getClosedReturnDays();
        $list = [];
        foreach (self::getSucursales() as $s) {
            $code = $s['code'] ?? '';
            if ($code === '') {
                continue;
            }
            $list[] = [
                'code' => $code,
                'name' => $s['name'] ?? $code,
                'shortName' => $s['shortName'] ?? $code,
                'phone' => $s['phone'] ?? '',
                'note' => $s['note'] ?? null,
                'dailyHours' => $s['dailyHours'] ?? [],
                'closedReturnDays' => $closed[$code] ?? ($s['closedReturnDays'] ?? []),
            ];
        }
        return $list;
    }

    public static function partnerImageBaseUrl(): string {
        if (defined('AUTOMARKET_PARTNER_IMAGE_BASE') && AUTOMARKET_PARTNER_IMAGE_BASE !== '') {
            return rtrim(AUTOMARKET_PARTNER_IMAGE_BASE, '/');
        }
        $apiUrl = defined('AUTOMARKET_API_URL') ? AUTOMARKET_API_URL : '';
        if (preg_match('#^(https?://[^/]+)#', $apiUrl, $m)) {
            return $m[1];
        }
        return 'https://automarket-rentacar-fme3z.ondigitalocean.app';
    }
}
