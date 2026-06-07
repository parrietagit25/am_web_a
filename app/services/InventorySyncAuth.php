<?php
/**
 * Autenticación del sync de inventario (mismo token que api_web.php en GoDaddy).
 */
class InventorySyncAuth
{
    private const DEFAULT_TOKEN = 'SI5dGxz/2/AqWkOYuz6t4r3KYGbqGxOj3MhT3T/hp!J6Du9ko=6ITrMBNJU5WzUj?ep3VWb8gwxGv9RPgq?r0y=A8gdF2cJ!fWil1G??6voWqJvRdip1M?0u/sol-ON?';

    public static function expectedToken(): string
    {
        if (defined('INVENTORY_SYNC_TOKEN') && INVENTORY_SYNC_TOKEN !== '') {
            return INVENTORY_SYNC_TOKEN;
        }
        return self::DEFAULT_TOKEN;
    }

    public static function verifyRequest(): bool
    {
        $header = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
        return $header !== '' && hash_equals(self::expectedToken(), $header);
    }
}
