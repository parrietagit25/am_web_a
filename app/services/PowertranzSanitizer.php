<?php
/**
 * Sanitización de textos y payloads Powertranz (sin tildes ni caracteres problemáticos).
 * AM-RAC-PAY-POWERTRANZ-0A
 */
declare(strict_types=1);

class PowertranzSanitizer
{
    private const SENSITIVE_KEYS = [
        'spitoken', 'pantoken', 'token', 'cardnumber', 'pan', 'cvv', 'cvc', 'cvc2',
        'password', 'powertranz-powertranzpassword', 'powertranz-powertranzid',
        'authorization', 'redirectdata',
    ];

    public static function text(string $value, int $maxLen = 255): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii === false) {
            $ascii = $value;
        }
        $ascii = preg_replace('/[^A-Za-z0-9 @\.,\-\/]/', ' ', (string) $ascii) ?? '';
        $ascii = preg_replace('/\s+/', ' ', $ascii) ?? '';
        $ascii = trim($ascii);

        return mb_substr($ascii, 0, $maxLen);
    }

    public static function name(string $value): string
    {
        return self::text($value, 80);
    }

    public static function addressLine(string $value): string
    {
        return self::text($value, 120);
    }

    public static function phone(string $value): string
    {
        $digits = preg_replace('/[^0-9\-]/', '', $value) ?? '';

        return mb_substr($digits, 0, 20);
    }

    public static function postalCode(string $value): string
    {
        return mb_substr(preg_replace('/[\s\-]/', '', $value) ?? '', 0, 16);
    }

    public static function orderIdentifier(string $value): string
    {
        $clean = preg_replace('/[^A-Za-z0-9\-_]/', '', strtoupper(trim($value))) ?? '';

        return mb_substr($clean, 0, 64);
    }

    /**
     * @param array<string, mixed>|null $data
     * @return array<string, mixed>|null
     */
    public static function sanitizePayload(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        return self::walk($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function walk(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $keyStr = (string) $key;
            if (self::isSensitiveKey($keyStr)) {
                $out[$keyStr] = '[REDACTED]';
                continue;
            }
            if (is_array($value)) {
                $out[$keyStr] = self::walk($value);
                continue;
            }
            if (is_string($value) && strlen($value) > 4000) {
                $out[$keyStr] = '[TRUNCATED:' . strlen($value) . ' chars]';
                continue;
            }
            $out[$keyStr] = $value;
        }

        return $out;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $norm = strtolower(str_replace(['_', '-', ' '], '', $key));
        foreach (self::SENSITIVE_KEYS as $sensitive) {
            $needle = str_replace(['_', '-', ' '], '', $sensitive);
            if ($norm === $needle || str_contains($norm, $needle)) {
                return true;
            }
        }

        return false;
    }
}
