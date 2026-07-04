<?php
/**
 * Schema pagos Powertranz aislados (AM-RAC-PAY-POWERTRANZ-0A/0B).
 */
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

class PowertranzDatabaseSchema
{
    private static bool $ensured = false;

    public static function ensure(): void
    {
        if (self::$ensured) {
            return;
        }
        $db = Database::getInstance();
        $driver = $db->getDriverName();

        if ($driver === 'mysql') {
            $db->execute("CREATE TABLE IF NOT EXISTS rac_powertranz_payments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                reservation_id INT UNSIGNED NULL,
                test_reference VARCHAR(64) NOT NULL,
                payment_reference VARCHAR(64) NULL,
                transaction_identifier VARCHAR(64) NOT NULL,
                order_identifier VARCHAR(64) NOT NULL,
                amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                currency VARCHAR(8) NOT NULL DEFAULT '840',
                currency_code VARCHAR(8) NOT NULL DEFAULT '840',
                mode VARCHAR(16) NOT NULL DEFAULT 'sale',
                environment VARCHAR(16) NOT NULL DEFAULT 'staging',
                status VARCHAR(32) NOT NULL DEFAULT 'created',
                approved TINYINT(1) NOT NULL DEFAULT 0,
                iso_response_code VARCHAR(16) NULL,
                response_message VARCHAR(255) NULL,
                error_message VARCHAR(255) NULL,
                authorization_code VARCHAR(64) NULL,
                rrn VARCHAR(64) NULL,
                card_brand VARCHAR(32) NULL,
                spi_token_hash VARCHAR(64) NULL,
                spi_token_expires_at DATETIME NULL,
                spi_token_vault TEXT NULL,
                redirect_data_present TINYINT(1) NOT NULL DEFAULT 0,
                redirect_data_vault MEDIUMTEXT NULL,
                request_payload_json LONGTEXT NULL,
                response_payload_json LONGTEXT NULL,
                complete_payload_json LONGTEXT NULL,
                complete_response_json LONGTEXT NULL,
                request_json_sanitized LONGTEXT NULL,
                auth_response_json_sanitized LONGTEXT NULL,
                merchant_response_json_sanitized LONGTEXT NULL,
                payment_response_json_sanitized LONGTEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL,
                completed_at DATETIME NULL,
                UNIQUE KEY uq_ptz_test_reference (test_reference),
                UNIQUE KEY uq_ptz_transaction_identifier (transaction_identifier),
                KEY idx_ptz_order_identifier (order_identifier),
                KEY idx_ptz_status (status),
                KEY idx_ptz_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $db->execute("CREATE TABLE IF NOT EXISTS rac_powertranz_payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                reservation_id INTEGER NULL,
                test_reference TEXT NOT NULL UNIQUE,
                payment_reference TEXT NULL,
                transaction_identifier TEXT NOT NULL UNIQUE,
                order_identifier TEXT NOT NULL,
                amount REAL NOT NULL DEFAULT 0,
                tax_amount REAL NOT NULL DEFAULT 0,
                currency TEXT NOT NULL DEFAULT '840',
                currency_code TEXT NOT NULL DEFAULT '840',
                mode TEXT NOT NULL DEFAULT 'sale',
                environment TEXT NOT NULL DEFAULT 'staging',
                status TEXT NOT NULL DEFAULT 'created',
                approved INTEGER NOT NULL DEFAULT 0,
                iso_response_code TEXT NULL,
                response_message TEXT NULL,
                error_message TEXT NULL,
                authorization_code TEXT NULL,
                rrn TEXT NULL,
                card_brand TEXT NULL,
                spi_token_hash TEXT NULL,
                spi_token_expires_at TEXT NULL,
                spi_token_vault TEXT NULL,
                redirect_data_present INTEGER NOT NULL DEFAULT 0,
                redirect_data_vault TEXT NULL,
                request_payload_json TEXT NULL,
                response_payload_json TEXT NULL,
                complete_payload_json TEXT NULL,
                complete_response_json TEXT NULL,
                request_json_sanitized TEXT NULL,
                auth_response_json_sanitized TEXT NULL,
                merchant_response_json_sanitized TEXT NULL,
                payment_response_json_sanitized TEXT NULL,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT NULL,
                completed_at TEXT NULL
            )");
            $db->execute('CREATE INDEX IF NOT EXISTS idx_ptz_order_identifier ON rac_powertranz_payments(order_identifier)');
            $db->execute('CREATE INDEX IF NOT EXISTS idx_ptz_status ON rac_powertranz_payments(status)');
        }

        self::migrateColumns($db, $driver);
        self::$ensured = true;
    }

    private static function migrateColumns(Database $db, string $driver): void
    {
        $columns = [
            'test_reference' => $driver === 'mysql' ? 'VARCHAR(64) NULL' : 'TEXT NULL',
            'payment_reference' => $driver === 'mysql' ? 'VARCHAR(64) NULL' : 'TEXT NULL',
            'currency' => $driver === 'mysql' ? "VARCHAR(8) NOT NULL DEFAULT '840'" : "TEXT NOT NULL DEFAULT '840'",
            'error_message' => $driver === 'mysql' ? 'VARCHAR(255) NULL' : 'TEXT NULL',
            'request_payload_json' => $driver === 'mysql' ? 'LONGTEXT NULL' : 'TEXT NULL',
            'response_payload_json' => $driver === 'mysql' ? 'LONGTEXT NULL' : 'TEXT NULL',
            'complete_payload_json' => $driver === 'mysql' ? 'LONGTEXT NULL' : 'TEXT NULL',
            'complete_response_json' => $driver === 'mysql' ? 'LONGTEXT NULL' : 'TEXT NULL',
        ];

        foreach ($columns as $name => $definition) {
            if (!self::columnExists($db, $driver, $name)) {
                $db->execute('ALTER TABLE rac_powertranz_payments ADD COLUMN ' . $name . ' ' . $definition);
            }
        }

        if ($driver === 'sqlite') {
            $db->execute("UPDATE rac_powertranz_payments SET test_reference = payment_reference WHERE (test_reference IS NULL OR test_reference = '') AND payment_reference IS NOT NULL AND payment_reference != ''");
            $db->execute("UPDATE rac_powertranz_payments SET payment_reference = test_reference WHERE (payment_reference IS NULL OR payment_reference = '') AND test_reference IS NOT NULL AND test_reference != ''");
            $db->execute("UPDATE rac_powertranz_payments SET currency = currency_code WHERE (currency IS NULL OR currency = '') AND currency_code IS NOT NULL AND currency_code != ''");
            $db->execute("UPDATE rac_powertranz_payments SET request_payload_json = request_json_sanitized WHERE request_payload_json IS NULL AND request_json_sanitized IS NOT NULL");
            $db->execute("UPDATE rac_powertranz_payments SET response_payload_json = auth_response_json_sanitized WHERE response_payload_json IS NULL AND auth_response_json_sanitized IS NOT NULL");
            $db->execute("UPDATE rac_powertranz_payments SET complete_response_json = payment_response_json_sanitized WHERE complete_response_json IS NULL AND payment_response_json_sanitized IS NOT NULL");
        } else {
            $db->execute("UPDATE rac_powertranz_payments SET test_reference = payment_reference WHERE test_reference IS NULL AND payment_reference IS NOT NULL");
            $db->execute("UPDATE rac_powertranz_payments SET payment_reference = test_reference WHERE payment_reference IS NULL AND test_reference IS NOT NULL");
            $db->execute("UPDATE rac_powertranz_payments SET currency = currency_code WHERE currency IS NULL AND currency_code IS NOT NULL");
            $db->execute('UPDATE rac_powertranz_payments SET request_payload_json = request_json_sanitized WHERE request_payload_json IS NULL AND request_json_sanitized IS NOT NULL');
            $db->execute('UPDATE rac_powertranz_payments SET response_payload_json = auth_response_json_sanitized WHERE response_payload_json IS NULL AND auth_response_json_sanitized IS NOT NULL');
            $db->execute('UPDATE rac_powertranz_payments SET complete_response_json = payment_response_json_sanitized WHERE complete_response_json IS NULL AND payment_response_json_sanitized IS NOT NULL');
        }
    }

    private static function columnExists(Database $db, string $driver, string $column): bool
    {
        if ($driver === 'mysql') {
            $row = $db->selectOne(
                'SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c',
                [':t' => 'rac_powertranz_payments', ':c' => $column]
            );

            return (int) ($row['c'] ?? 0) > 0;
        }

        $rows = $db->select('PRAGMA table_info(rac_powertranz_payments)');
        foreach ($rows as $row) {
            if (($row['name'] ?? '') === $column) {
                return true;
            }
        }

        return false;
    }
}
