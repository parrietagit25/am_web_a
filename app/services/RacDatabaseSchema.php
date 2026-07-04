<?php
/**
 * RAC reservations schema (SQLite + MySQL).
 */

class RacDatabaseSchema {
    private static $ensured = false;

    public static function ensure(): void {
        if (self::$ensured) {
            return;
        }
        $db = Database::getInstance();
        $driver = $db->getDriverName();

        if ($driver === 'mysql') {
            $db->execute("CREATE TABLE IF NOT EXISTS rac_reservations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                reservation_code VARCHAR(24) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                customer_name VARCHAR(200) NOT NULL,
                customer_email VARCHAR(200) NOT NULL,
                customer_phone VARCHAR(50) NOT NULL,
                customer_comments TEXT NULL,
                location_code VARCHAR(20) NOT NULL,
                return_location_code VARCHAR(20) NOT NULL,
                pickup_date DATE NOT NULL,
                pickup_time VARCHAR(8) NOT NULL,
                return_date DATE NOT NULL,
                return_time VARCHAR(8) NOT NULL,
                driver_age VARCHAR(4) NOT NULL DEFAULT '25',
                promo_code VARCHAR(64) NULL,
                sipp_code VARCHAR(12) NULL,
                vehicle_name VARCHAR(200) NOT NULL,
                vehicle_category VARCHAR(100) NULL,
                vendor_rate_id VARCHAR(64) NULL,
                quote_token VARCHAR(64) NULL,
                rate_type VARCHAR(16) NOT NULL DEFAULT 'web',
                price_web DECIMAL(12,2) NULL,
                price_counter DECIMAL(12,2) NULL,
                price_total DECIMAL(12,2) NULL,
                price_total_estimated DECIMAL(12,2) NULL,
                coverage_code VARCHAR(32) NULL,
                coverage_name VARCHAR(200) NULL,
                coverage_amount DECIMAL(12,2) NULL,
                coverage_deductible DECIMAL(12,2) NULL,
                price_rental_base DECIMAL(12,2) NULL,
                price_saf DECIMAL(12,2) NULL,
                price_itbms DECIMAL(12,2) NULL,
                equipment_json LONGTEXT NULL,
                vehicle_snapshot_json LONGTEXT NULL,
                search_snapshot_json LONGTEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL,
                UNIQUE KEY uq_reservation_code (reservation_code),
                KEY idx_created (created_at),
                KEY idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $db->execute("CREATE TABLE IF NOT EXISTS rac_alert_emails (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                label VARCHAR(120) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            self::migrateReservationColumns($db, 'mysql');
        } else {
            $db->execute("CREATE TABLE IF NOT EXISTS rac_reservations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                reservation_code TEXT NOT NULL UNIQUE,
                status TEXT NOT NULL DEFAULT 'pending',
                customer_name TEXT NOT NULL,
                customer_email TEXT NOT NULL,
                customer_phone TEXT NOT NULL,
                customer_comments TEXT,
                location_code TEXT NOT NULL,
                return_location_code TEXT NOT NULL,
                pickup_date TEXT NOT NULL,
                pickup_time TEXT NOT NULL,
                return_date TEXT NOT NULL,
                return_time TEXT NOT NULL,
                driver_age TEXT NOT NULL DEFAULT '25',
                promo_code TEXT,
                sipp_code TEXT,
                vehicle_name TEXT NOT NULL,
                vehicle_category TEXT,
                vendor_rate_id TEXT,
                quote_token TEXT,
                rate_type TEXT NOT NULL DEFAULT 'web',
                price_web REAL,
                price_counter REAL,
                price_total REAL,
                price_total_estimated REAL,
                coverage_code TEXT,
                coverage_name TEXT,
                coverage_amount REAL,
                coverage_deductible REAL,
                price_rental_base REAL,
                price_saf REAL,
                price_itbms REAL,
                equipment_json TEXT,
                vehicle_snapshot_json TEXT,
                search_snapshot_json TEXT,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT
            )");

            $db->execute("CREATE TABLE IF NOT EXISTS rac_alert_emails (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                label TEXT,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            )");

            self::migrateReservationColumns($db, 'sqlite');
        }

        self::ensureAddonTables($db, $driver);
        self::$ensured = true;
    }

    private static function ensureAddonTables(Database $db, string $driver): void
    {
        if ($driver === 'mysql') {
            $db->execute("CREATE TABLE IF NOT EXISTS rac_protection_products (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(32) NOT NULL,
                name VARCHAR(200) NOT NULL,
                description TEXT NULL,
                enabled TINYINT(1) NOT NULL DEFAULT 1,
                price_type VARCHAR(32) NOT NULL DEFAULT 'fixed_daily',
                price_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                currency VARCHAR(8) NOT NULL DEFAULT 'USD',
                applies_per VARCHAR(16) NOT NULL DEFAULT 'day',
                vehicle_code VARCHAR(16) NULL,
                vehicle_name VARCHAR(200) NULL,
                min_rental_days INT NULL,
                max_rental_days INT NULL,
                pickup_location VARCHAR(20) NULL,
                return_location VARCHAR(20) NULL,
                sort_order INT NOT NULL DEFAULT 100,
                visible_public TINYINT(1) NOT NULL DEFAULT 1,
                is_default TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL,
                UNIQUE KEY uq_rac_protection_code (code),
                KEY idx_rac_protection_enabled (enabled, visible_public, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $db->execute("CREATE TABLE IF NOT EXISTS rac_extra_products (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(32) NOT NULL,
                name VARCHAR(200) NOT NULL,
                description TEXT NULL,
                enabled TINYINT(1) NOT NULL DEFAULT 1,
                price_type VARCHAR(32) NOT NULL DEFAULT 'fixed_total',
                price_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                currency VARCHAR(8) NOT NULL DEFAULT 'USD',
                applies_per VARCHAR(16) NOT NULL DEFAULT 'rental',
                max_quantity INT NOT NULL DEFAULT 1,
                vehicle_code VARCHAR(16) NULL,
                vehicle_name VARCHAR(200) NULL,
                min_rental_days INT NULL,
                max_rental_days INT NULL,
                pickup_location VARCHAR(20) NULL,
                return_location VARCHAR(20) NULL,
                sort_order INT NOT NULL DEFAULT 100,
                visible_public TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL,
                UNIQUE KEY uq_rac_extra_code (code),
                KEY idx_rac_extra_enabled (enabled, visible_public, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $db->execute("CREATE TABLE IF NOT EXISTS rac_reservation_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                reservation_id INT UNSIGNED NOT NULL,
                item_type VARCHAR(16) NOT NULL,
                item_code VARCHAR(32) NOT NULL,
                item_name VARCHAR(200) NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
                total_price DECIMAL(12,2) NOT NULL DEFAULT 0,
                currency VARCHAR(8) NOT NULL DEFAULT 'USD',
                pricing_json LONGTEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_reservation_items_res (reservation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $db->execute("CREATE TABLE IF NOT EXISTS rac_protection_products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                description TEXT,
                enabled INTEGER NOT NULL DEFAULT 1,
                price_type TEXT NOT NULL DEFAULT 'fixed_daily',
                price_amount REAL NOT NULL DEFAULT 0,
                currency TEXT NOT NULL DEFAULT 'USD',
                applies_per TEXT NOT NULL DEFAULT 'day',
                vehicle_code TEXT,
                vehicle_name TEXT,
                min_rental_days INTEGER,
                max_rental_days INTEGER,
                pickup_location TEXT,
                return_location TEXT,
                sort_order INTEGER NOT NULL DEFAULT 100,
                visible_public INTEGER NOT NULL DEFAULT 1,
                is_default INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT
            )");

            $db->execute("CREATE TABLE IF NOT EXISTS rac_extra_products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                description TEXT,
                enabled INTEGER NOT NULL DEFAULT 1,
                price_type TEXT NOT NULL DEFAULT 'fixed_total',
                price_amount REAL NOT NULL DEFAULT 0,
                currency TEXT NOT NULL DEFAULT 'USD',
                applies_per TEXT NOT NULL DEFAULT 'rental',
                max_quantity INTEGER NOT NULL DEFAULT 1,
                vehicle_code TEXT,
                vehicle_name TEXT,
                min_rental_days INTEGER,
                max_rental_days INTEGER,
                pickup_location TEXT,
                return_location TEXT,
                sort_order INTEGER NOT NULL DEFAULT 100,
                visible_public INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT
            )");

            $db->execute("CREATE TABLE IF NOT EXISTS rac_reservation_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                reservation_id INTEGER NOT NULL,
                item_type TEXT NOT NULL,
                item_code TEXT NOT NULL,
                item_name TEXT NOT NULL,
                quantity INTEGER NOT NULL DEFAULT 1,
                unit_price REAL NOT NULL DEFAULT 0,
                total_price REAL NOT NULL DEFAULT 0,
                currency TEXT NOT NULL DEFAULT 'USD',
                pricing_json TEXT,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            )");
            $db->execute("CREATE INDEX IF NOT EXISTS idx_rac_res_items_res ON rac_reservation_items (reservation_id)");
        }
    }

    private static function migrateReservationColumns(Database $db, string $driver): void {
        $columns = $driver === 'mysql'
            ? [
                'coverage_name' => 'VARCHAR(200) NULL',
                'coverage_amount' => 'DECIMAL(12,2) NULL',
                'coverage_deductible' => 'DECIMAL(12,2) NULL',
                'price_rental_base' => 'DECIMAL(12,2) NULL',
                'price_saf' => 'DECIMAL(12,2) NULL',
                'price_itbms' => 'DECIMAL(12,2) NULL',
                'bars_confirmation_code' => 'VARCHAR(64) NULL',
                'extras_snapshot_json' => 'LONGTEXT NULL',
                'bars_cache_key' => 'VARCHAR(64) NULL',
                'bars_snapshot_id' => 'INT UNSIGNED NULL',
                'calculated_rate_id' => 'INT UNSIGNED NULL',
                'vehicle_code' => 'VARCHAR(16) NULL',
                'rental_days' => 'INT NULL',
                'currency' => 'VARCHAR(8) NULL',
                'base_daily_rate' => 'DECIMAL(12,2) NULL',
                'base_total_rate' => 'DECIMAL(12,2) NULL',
                'final_daily_rate' => 'DECIMAL(12,2) NULL',
                'final_total_rate' => 'DECIMAL(12,2) NULL',
                'discount_amount_total' => 'DECIMAL(12,2) NULL',
                'applied_rules_json' => 'LONGTEXT NULL',
                'rate_source' => 'VARCHAR(32) NULL',
                'rate_locked_at' => 'DATETIME NULL',
            ]
            : [
                'coverage_name' => 'TEXT',
                'coverage_amount' => 'REAL',
                'coverage_deductible' => 'REAL',
                'price_rental_base' => 'REAL',
                'price_saf' => 'REAL',
                'price_itbms' => 'REAL',
                'bars_confirmation_code' => 'TEXT',
                'extras_snapshot_json' => 'TEXT',
                'bars_cache_key' => 'TEXT',
                'bars_snapshot_id' => 'INTEGER',
                'calculated_rate_id' => 'INTEGER',
                'vehicle_code' => 'TEXT',
                'rental_days' => 'INTEGER',
                'currency' => 'TEXT',
                'base_daily_rate' => 'REAL',
                'base_total_rate' => 'REAL',
                'final_daily_rate' => 'REAL',
                'final_total_rate' => 'REAL',
                'discount_amount_total' => 'REAL',
                'applied_rules_json' => 'TEXT',
                'rate_source' => 'TEXT',
                'rate_locked_at' => 'TEXT',
            ];

        foreach ($columns as $name => $type) {
            try {
                $db->execute("ALTER TABLE rac_reservations ADD COLUMN {$name} {$type}");
            } catch (Exception $e) {
                // Columna ya existe
            }
        }
    }
}
