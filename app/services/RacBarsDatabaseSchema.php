<?php
/**
 * BARS rate cache schema (SQLite + MySQL).
 * AM-RAC-BARS-CACHE-2A
 */

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

class RacBarsDatabaseSchema
{
    private static $ensured = false;

    public static function ensure(): void
    {
        if (self::$ensured) {
            return;
        }

        $db = Database::getInstance();
        $driver = $db->getDriverName();

        if ($driver === 'mysql') {
            self::ensureMysql($db);
        } else {
            self::ensureSqlite($db);
        }

        self::$ensured = true;
    }

    private static function ensureMysql(Database $db): void
    {
        $db->execute("CREATE TABLE IF NOT EXISTS rac_bars_rate_snapshots (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            cache_key VARCHAR(64) NOT NULL,
            pickup_location VARCHAR(20) NOT NULL,
            return_location VARCHAR(20) NOT NULL,
            pickup_datetime VARCHAR(32) NOT NULL,
            return_datetime VARCHAR(32) NOT NULL,
            rate_qualifier VARCHAR(32) NOT NULL DEFAULT 'WEB',
            http_code INT NOT NULL DEFAULT 0,
            success TINYINT(1) NOT NULL DEFAULT 0,
            warning_175 TINYINT(1) NOT NULL DEFAULT 0,
            total_count INT NOT NULL DEFAULT 0,
            available_count INT NOT NULL DEFAULT 0,
            unavailable_count INT NOT NULL DEFAULT 0,
            min_daily_rate DECIMAL(12,2) NULL,
            max_daily_rate DECIMAL(12,2) NULL,
            query_ms INT NULL,
            warnings_json LONGTEXT NULL,
            requested_classes_json LONGTEXT NULL,
            source VARCHAR(32) NOT NULL DEFAULT 'manual',
            fetched_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_cache_key_fetched (cache_key, fetched_at),
            KEY idx_fetched_at (fetched_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->execute("CREATE TABLE IF NOT EXISTS rac_bars_rates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            snapshot_id INT UNSIGNED NULL,
            cache_key VARCHAR(64) NOT NULL,
            vehicle_code VARCHAR(16) NOT NULL,
            vehicle_name VARCHAR(200) NOT NULL,
            available TINYINT(1) NOT NULL DEFAULT 0,
            currency VARCHAR(8) NOT NULL DEFAULT 'USD',
            daily_rate DECIMAL(12,2) NULL,
            total_rate DECIMAL(12,2) NULL,
            unit_name VARCHAR(32) NULL,
            raw_status VARCHAR(64) NULL,
            warnings_json LONGTEXT NULL,
            raw_json_sanitized LONGTEXT NULL,
            pickup_location VARCHAR(20) NOT NULL,
            return_location VARCHAR(20) NOT NULL,
            pickup_datetime VARCHAR(32) NOT NULL,
            return_datetime VARCHAR(32) NOT NULL,
            rate_qualifier VARCHAR(32) NOT NULL DEFAULT 'WEB',
            fetched_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_cache_vehicle (cache_key, vehicle_code),
            KEY idx_cache_key (cache_key),
            KEY idx_snapshot_id (snapshot_id),
            KEY idx_available (available)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->execute("CREATE TABLE IF NOT EXISTS rac_bars_rate_refresh_schedules (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            enabled TINYINT(1) NOT NULL DEFAULT 1,
            pickup_location VARCHAR(20) NOT NULL,
            return_location VARCHAR(20) NOT NULL,
            days_ahead INT NOT NULL DEFAULT 1,
            rental_days INT NOT NULL DEFAULT 3,
            pickup_time VARCHAR(8) NOT NULL DEFAULT '10:00',
            return_time VARCHAR(8) NOT NULL DEFAULT '10:00',
            rate_qualifier VARCHAR(32) NOT NULL DEFAULT 'WEB',
            scheduled_times_json LONGTEXT NOT NULL,
            last_run_at DATETIME NULL,
            next_run_at DATETIME NULL,
            last_status VARCHAR(32) NULL,
            last_message TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            KEY idx_enabled_next (enabled, next_run_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->execute("CREATE TABLE IF NOT EXISTS rac_bars_rate_refresh_runs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            schedule_id INT UNSIGNED NULL,
            run_type VARCHAR(16) NOT NULL DEFAULT 'manual',
            started_at DATETIME NOT NULL,
            finished_at DATETIME NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'error',
            message TEXT NULL,
            http_code INT NULL,
            warning_175 TINYINT(1) NOT NULL DEFAULT 0,
            total_count INT NULL,
            available_count INT NULL,
            unavailable_count INT NULL,
            snapshot_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_schedule_id (schedule_id),
            KEY idx_started_at (started_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::ensurePricingMysql($db);
    }

    private static function ensurePricingMysql(Database $db): void
    {
        $db->execute("CREATE TABLE IF NOT EXISTS rac_rate_rules (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            description TEXT NULL,
            enabled TINYINT(1) NOT NULL DEFAULT 1,
            priority INT NOT NULL DEFAULT 100,
            stackable TINYINT(1) NOT NULL DEFAULT 1,
            stop_processing TINYINT(1) NOT NULL DEFAULT 0,
            rule_type VARCHAR(32) NOT NULL DEFAULT 'promotion',
            adjustment_type VARCHAR(32) NOT NULL,
            adjustment_value DECIMAL(12,2) NOT NULL DEFAULT 0,
            currency VARCHAR(8) NOT NULL DEFAULT 'USD',
            valid_from DATE NULL,
            valid_to DATE NULL,
            days_of_week_json LONGTEXT NULL,
            min_rental_days INT NULL,
            max_rental_days INT NULL,
            pickup_location VARCHAR(20) NULL,
            return_location VARCHAR(20) NULL,
            rate_qualifier VARCHAR(32) NULL,
            applies_to VARCHAR(32) NOT NULL DEFAULT 'all',
            created_by INT UNSIGNED NULL,
            updated_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            KEY idx_enabled_priority (enabled, priority, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->execute("CREATE TABLE IF NOT EXISTS rac_rate_rule_targets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            rule_id INT UNSIGNED NOT NULL,
            target_type VARCHAR(32) NOT NULL,
            target_value VARCHAR(200) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_rule_id (rule_id),
            KEY idx_target (target_type, target_value)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->execute("CREATE TABLE IF NOT EXISTS rac_calculated_rates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            source_rate_id INT UNSIGNED NULL,
            snapshot_id INT UNSIGNED NULL,
            cache_key VARCHAR(64) NOT NULL,
            vehicle_code VARCHAR(16) NOT NULL,
            vehicle_name VARCHAR(200) NOT NULL,
            available TINYINT(1) NOT NULL DEFAULT 0,
            currency VARCHAR(8) NOT NULL DEFAULT 'USD',
            base_daily_rate DECIMAL(12,2) NULL,
            base_total_rate DECIMAL(12,2) NULL,
            final_daily_rate DECIMAL(12,2) NULL,
            final_total_rate DECIMAL(12,2) NULL,
            rental_days INT NOT NULL DEFAULT 1,
            discount_amount_daily DECIMAL(12,2) NULL,
            discount_amount_total DECIMAL(12,2) NULL,
            surcharge_amount_daily DECIMAL(12,2) NULL,
            surcharge_amount_total DECIMAL(12,2) NULL,
            applied_rules_json LONGTEXT NULL,
            calculation_notes TEXT NULL,
            pickup_location VARCHAR(20) NOT NULL,
            return_location VARCHAR(20) NOT NULL,
            pickup_datetime VARCHAR(32) NOT NULL,
            return_datetime VARCHAR(32) NOT NULL,
            rate_qualifier VARCHAR(32) NOT NULL DEFAULT 'WEB',
            calculated_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_calc_cache_vehicle (cache_key, vehicle_code),
            KEY idx_cache_key (cache_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->execute("CREATE TABLE IF NOT EXISTS rac_rate_rule_audit_log (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            rule_id INT UNSIGNED NULL,
            action VARCHAR(32) NOT NULL,
            before_json LONGTEXT NULL,
            after_json LONGTEXT NULL,
            admin_user_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_rule_id (rule_id),
            KEY idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->execute("CREATE TABLE IF NOT EXISTS rac_rate_quotes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            quote_token VARCHAR(64) NOT NULL,
            cache_key VARCHAR(64) NOT NULL,
            calculated_rate_id INT UNSIGNED NULL,
            source_rate_id INT UNSIGNED NULL,
            snapshot_id INT UNSIGNED NULL,
            vehicle_code VARCHAR(16) NOT NULL,
            vehicle_name VARCHAR(200) NOT NULL,
            pickup_location VARCHAR(20) NOT NULL,
            return_location VARCHAR(20) NOT NULL,
            pickup_datetime VARCHAR(32) NOT NULL,
            return_datetime VARCHAR(32) NOT NULL,
            rental_days INT NOT NULL DEFAULT 1,
            currency VARCHAR(8) NOT NULL DEFAULT 'USD',
            base_daily_rate DECIMAL(12,2) NULL,
            base_total_rate DECIMAL(12,2) NULL,
            final_daily_rate DECIMAL(12,2) NULL,
            final_total_rate DECIMAL(12,2) NULL,
            discount_amount_daily DECIMAL(12,2) NULL,
            discount_amount_total DECIMAL(12,2) NULL,
            applied_rules_json LONGTEXT NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'active',
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            used_at DATETIME NULL,
            reservation_id INT UNSIGNED NULL,
            client_ip_hash VARCHAR(64) NULL,
            user_agent_hash VARCHAR(64) NULL,
            UNIQUE KEY uq_quote_token (quote_token),
            KEY idx_status_expires (status, expires_at),
            KEY idx_cache_vehicle (cache_key, vehicle_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private static function ensureSqlite(Database $db): void
    {
        $db->execute("CREATE TABLE IF NOT EXISTS rac_bars_rate_snapshots (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cache_key TEXT NOT NULL,
            pickup_location TEXT NOT NULL,
            return_location TEXT NOT NULL,
            pickup_datetime TEXT NOT NULL,
            return_datetime TEXT NOT NULL,
            rate_qualifier TEXT NOT NULL DEFAULT 'WEB',
            http_code INTEGER NOT NULL DEFAULT 0,
            success INTEGER NOT NULL DEFAULT 0,
            warning_175 INTEGER NOT NULL DEFAULT 0,
            total_count INTEGER NOT NULL DEFAULT 0,
            available_count INTEGER NOT NULL DEFAULT 0,
            unavailable_count INTEGER NOT NULL DEFAULT 0,
            min_daily_rate REAL NULL,
            max_daily_rate REAL NULL,
            query_ms INTEGER NULL,
            warnings_json TEXT NULL,
            requested_classes_json TEXT NULL,
            source TEXT NOT NULL DEFAULT 'manual',
            fetched_at TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
        $db->execute("CREATE INDEX IF NOT EXISTS idx_rac_bars_snapshots_cache ON rac_bars_rate_snapshots (cache_key, fetched_at)");

        $db->execute("CREATE TABLE IF NOT EXISTS rac_bars_rates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            snapshot_id INTEGER NULL,
            cache_key TEXT NOT NULL,
            vehicle_code TEXT NOT NULL,
            vehicle_name TEXT NOT NULL,
            available INTEGER NOT NULL DEFAULT 0,
            currency TEXT NOT NULL DEFAULT 'USD',
            daily_rate REAL NULL,
            total_rate REAL NULL,
            unit_name TEXT NULL,
            raw_status TEXT NULL,
            warnings_json TEXT NULL,
            raw_json_sanitized TEXT NULL,
            pickup_location TEXT NOT NULL,
            return_location TEXT NOT NULL,
            pickup_datetime TEXT NOT NULL,
            return_datetime TEXT NOT NULL,
            rate_qualifier TEXT NOT NULL DEFAULT 'WEB',
            fetched_at TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NULL,
            UNIQUE (cache_key, vehicle_code)
        )");
        $db->execute("CREATE INDEX IF NOT EXISTS idx_rac_bars_rates_cache ON rac_bars_rates (cache_key)");

        $db->execute("CREATE TABLE IF NOT EXISTS rac_bars_rate_refresh_schedules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            enabled INTEGER NOT NULL DEFAULT 1,
            pickup_location TEXT NOT NULL,
            return_location TEXT NOT NULL,
            days_ahead INTEGER NOT NULL DEFAULT 1,
            rental_days INTEGER NOT NULL DEFAULT 3,
            pickup_time TEXT NOT NULL DEFAULT '10:00',
            return_time TEXT NOT NULL DEFAULT '10:00',
            rate_qualifier TEXT NOT NULL DEFAULT 'WEB',
            scheduled_times_json TEXT NOT NULL,
            last_run_at TEXT NULL,
            next_run_at TEXT NULL,
            last_status TEXT NULL,
            last_message TEXT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NULL
        )");
        $db->execute("CREATE INDEX IF NOT EXISTS idx_rac_bars_schedules_due ON rac_bars_rate_refresh_schedules (enabled, next_run_at)");

        $db->execute("CREATE TABLE IF NOT EXISTS rac_bars_rate_refresh_runs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            schedule_id INTEGER NULL,
            run_type TEXT NOT NULL DEFAULT 'manual',
            started_at TEXT NOT NULL,
            finished_at TEXT NULL,
            status TEXT NOT NULL DEFAULT 'error',
            message TEXT NULL,
            http_code INTEGER NULL,
            warning_175 INTEGER NOT NULL DEFAULT 0,
            total_count INTEGER NULL,
            available_count INTEGER NULL,
            unavailable_count INTEGER NULL,
            snapshot_id INTEGER NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
        $db->execute("CREATE INDEX IF NOT EXISTS idx_rac_bars_runs_schedule ON rac_bars_rate_refresh_runs (schedule_id)");

        self::ensurePricingSqlite($db);
    }

    private static function ensurePricingSqlite(Database $db): void
    {
        $db->execute("CREATE TABLE IF NOT EXISTS rac_rate_rules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            enabled INTEGER NOT NULL DEFAULT 1,
            priority INTEGER NOT NULL DEFAULT 100,
            stackable INTEGER NOT NULL DEFAULT 1,
            stop_processing INTEGER NOT NULL DEFAULT 0,
            rule_type TEXT NOT NULL DEFAULT 'promotion',
            adjustment_type TEXT NOT NULL,
            adjustment_value REAL NOT NULL DEFAULT 0,
            currency TEXT NOT NULL DEFAULT 'USD',
            valid_from TEXT,
            valid_to TEXT,
            days_of_week_json TEXT,
            min_rental_days INTEGER,
            max_rental_days INTEGER,
            pickup_location TEXT,
            return_location TEXT,
            rate_qualifier TEXT,
            applies_to TEXT NOT NULL DEFAULT 'all',
            created_by INTEGER,
            updated_by INTEGER,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT
        )");
        $db->execute("CREATE INDEX IF NOT EXISTS idx_rac_rate_rules_enabled ON rac_rate_rules (enabled, priority, id)");

        $db->execute("CREATE TABLE IF NOT EXISTS rac_rate_rule_targets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            rule_id INTEGER NOT NULL,
            target_type TEXT NOT NULL,
            target_value TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
        $db->execute("CREATE INDEX IF NOT EXISTS idx_rac_rate_rule_targets_rule ON rac_rate_rule_targets (rule_id)");

        $db->execute("CREATE TABLE IF NOT EXISTS rac_calculated_rates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            source_rate_id INTEGER,
            snapshot_id INTEGER,
            cache_key TEXT NOT NULL,
            vehicle_code TEXT NOT NULL,
            vehicle_name TEXT NOT NULL,
            available INTEGER NOT NULL DEFAULT 0,
            currency TEXT NOT NULL DEFAULT 'USD',
            base_daily_rate REAL,
            base_total_rate REAL,
            final_daily_rate REAL,
            final_total_rate REAL,
            rental_days INTEGER NOT NULL DEFAULT 1,
            discount_amount_daily REAL,
            discount_amount_total REAL,
            surcharge_amount_daily REAL,
            surcharge_amount_total REAL,
            applied_rules_json TEXT,
            calculation_notes TEXT,
            pickup_location TEXT NOT NULL,
            return_location TEXT NOT NULL,
            pickup_datetime TEXT NOT NULL,
            return_datetime TEXT NOT NULL,
            rate_qualifier TEXT NOT NULL DEFAULT 'WEB',
            calculated_at TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT,
            UNIQUE (cache_key, vehicle_code)
        )");
        $db->execute("CREATE INDEX IF NOT EXISTS idx_rac_calculated_rates_cache ON rac_calculated_rates (cache_key)");

        $db->execute("CREATE TABLE IF NOT EXISTS rac_rate_rule_audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            rule_id INTEGER,
            action TEXT NOT NULL,
            before_json TEXT,
            after_json TEXT,
            admin_user_id INTEGER,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");

        $db->execute("CREATE TABLE IF NOT EXISTS rac_rate_quotes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            quote_token TEXT NOT NULL UNIQUE,
            cache_key TEXT NOT NULL,
            calculated_rate_id INTEGER,
            source_rate_id INTEGER,
            snapshot_id INTEGER,
            vehicle_code TEXT NOT NULL,
            vehicle_name TEXT NOT NULL,
            pickup_location TEXT NOT NULL,
            return_location TEXT NOT NULL,
            pickup_datetime TEXT NOT NULL,
            return_datetime TEXT NOT NULL,
            rental_days INTEGER NOT NULL DEFAULT 1,
            currency TEXT NOT NULL DEFAULT 'USD',
            base_daily_rate REAL,
            base_total_rate REAL,
            final_daily_rate REAL,
            final_total_rate REAL,
            discount_amount_daily REAL,
            discount_amount_total REAL,
            applied_rules_json TEXT,
            status TEXT NOT NULL DEFAULT 'active',
            expires_at TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            used_at TEXT,
            reservation_id INTEGER,
            client_ip_hash TEXT,
            user_agent_hash TEXT
        )");
        $db->execute("CREATE INDEX IF NOT EXISTS idx_rac_rate_quotes_status ON rac_rate_quotes (status, expires_at)");
    }
}
