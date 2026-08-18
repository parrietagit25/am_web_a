<?php
declare(strict_types=1);

/**
 * Borrador de checkout RAC (pending_payment → paid → fulfilled).
 * No es la reserva de RentWorks; solo el registro previo al cobro.
 */
final class RacCheckoutStore
{
    public static function directory(): string
    {
        $dir = dirname(__DIR__) . '/storage/rac_checkouts';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    public static function newToken(): string
    {
        return 'chk_' . bin2hex(random_bytes(16));
    }

    public static function secret(): string
    {
        if (defined('POWERTRANZ_PASSWORD') && trim((string) POWERTRANZ_PASSWORD) !== '') {
            return (string) POWERTRANZ_PASSWORD;
        }

        return 'am-rac-checkout-local';
    }

    public static function fulfillHmac(string $token): string
    {
        return hash_hmac('sha256', $token . '|paid', self::secret());
    }

    public static function isValidFulfillInput(array $input): bool
    {
        $token = trim((string) ($input['_checkout_fulfill'] ?? ''));
        $hmac = trim((string) ($input['_checkout_hmac'] ?? ''));
        if ($token === '' || $hmac === '' || !hash_equals(self::fulfillHmac($token), $hmac)) {
            return false;
        }
        $row = self::get($token);

        return is_array($row) && in_array((string) ($row['status'] ?? ''), ['paid', 'fulfilling', 'fulfilled'], true);
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    public static function save(array $record): array
    {
        $token = trim((string) ($record['token'] ?? ''));
        if ($token === '' || !preg_match('/^chk_[a-f0-9]{8,64}$/', $token)) {
            $token = self::newToken();
        }
        $record['token'] = $token;
        $record['updated_at'] = date('c');
        if (empty($record['created_at'])) {
            $record['created_at'] = $record['updated_at'];
        }
        $path = self::path($token);
        $json = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            return $record;
        }
        file_put_contents($path, $json, LOCK_EX);

        return $record;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $token): ?array
    {
        $token = trim($token);
        if ($token === '' || !preg_match('/^chk_[a-f0-9]{8,64}$/', $token)) {
            return null;
        }
        $path = self::path($token);
        if (!is_file($path)) {
            return null;
        }
        $raw = file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($data) ? $data : null;
    }

    /**
     * @param array<string, mixed> $patch
     * @return array<string, mixed>|null
     */
    public static function update(string $token, array $patch): ?array
    {
        $row = self::get($token);
        if ($row === null) {
            return null;
        }

        return self::save(array_merge($row, $patch));
    }

    public static function delete(string $token): void
    {
        $path = self::path($token);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private static function path(string $token): string
    {
        return self::directory() . '/' . $token . '.json';
    }
}
