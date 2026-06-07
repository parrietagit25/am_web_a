<?php
/**
 * Telemetría de visitantes — schema SQLite + MySQL.
 */
class TelemetrySchema
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
            $db->execute("CREATE TABLE IF NOT EXISTS telemetry_visitors (
                visitor_id VARCHAR(64) PRIMARY KEY,
                first_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                visit_count INT UNSIGNED NOT NULL DEFAULT 1,
                ip_address VARCHAR(45) NULL,
                country VARCHAR(80) NULL,
                country_code VARCHAR(8) NULL,
                region VARCHAR(120) NULL,
                city VARCHAR(120) NULL,
                latitude DECIMAL(10,7) NULL,
                longitude DECIMAL(10,7) NULL,
                timezone VARCHAR(64) NULL,
                isp VARCHAR(200) NULL,
                user_agent VARCHAR(500) NULL,
                browser VARCHAR(80) NULL,
                os VARCHAR(80) NULL,
                device_type VARCHAR(32) NULL,
                language VARCHAR(16) NULL,
                screen_width SMALLINT UNSIGNED NULL,
                screen_height SMALLINT UNSIGNED NULL,
                referrer_first VARCHAR(500) NULL,
                KEY idx_tv_last_seen (last_seen_at),
                KEY idx_tv_country (country_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $db->execute("CREATE TABLE IF NOT EXISTS telemetry_events (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL,
                visitor_id VARCHAR(64) NOT NULL,
                session_id VARCHAR(64) NOT NULL,
                event_type VARCHAR(32) NOT NULL DEFAULT 'page_view',
                page_path VARCHAR(500) NOT NULL,
                page_title VARCHAR(255) NULL,
                page_query VARCHAR(500) NULL,
                business_unit VARCHAR(32) NULL,
                entity_type VARCHAR(64) NULL,
                entity_id VARCHAR(120) NULL,
                entity_label VARCHAR(255) NULL,
                duration_seconds INT UNSIGNED NOT NULL DEFAULT 0,
                scroll_depth TINYINT UNSIGNED NOT NULL DEFAULT 0,
                ip_address VARCHAR(45) NULL,
                country VARCHAR(80) NULL,
                city VARCHAR(120) NULL,
                referrer VARCHAR(500) NULL,
                utm_source VARCHAR(120) NULL,
                utm_medium VARCHAR(120) NULL,
                utm_campaign VARCHAR(120) NULL,
                meta_json LONGTEXT NULL,
                KEY idx_te_created (created_at),
                KEY idx_te_visitor (visitor_id),
                KEY idx_te_session (session_id),
                KEY idx_te_type (event_type),
                KEY idx_te_unit (business_unit),
                KEY idx_te_entity (entity_type, entity_id),
                KEY idx_te_path (page_path(191))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $db->execute("CREATE TABLE IF NOT EXISTS telemetry_visitors (
                visitor_id TEXT PRIMARY KEY,
                first_seen_at TEXT NOT NULL DEFAULT (datetime('now')),
                last_seen_at TEXT NOT NULL DEFAULT (datetime('now')),
                visit_count INTEGER NOT NULL DEFAULT 1,
                ip_address TEXT,
                country TEXT,
                country_code TEXT,
                region TEXT,
                city TEXT,
                latitude REAL,
                longitude REAL,
                timezone TEXT,
                isp TEXT,
                user_agent TEXT,
                browser TEXT,
                os TEXT,
                device_type TEXT,
                language TEXT,
                screen_width INTEGER,
                screen_height INTEGER,
                referrer_first TEXT
            )");
            $db->execute("CREATE TABLE IF NOT EXISTS telemetry_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT,
                visitor_id TEXT NOT NULL,
                session_id TEXT NOT NULL,
                event_type TEXT NOT NULL DEFAULT 'page_view',
                page_path TEXT NOT NULL,
                page_title TEXT,
                page_query TEXT,
                business_unit TEXT,
                entity_type TEXT,
                entity_id TEXT,
                entity_label TEXT,
                duration_seconds INTEGER NOT NULL DEFAULT 0,
                scroll_depth INTEGER NOT NULL DEFAULT 0,
                ip_address TEXT,
                country TEXT,
                city TEXT,
                referrer TEXT,
                utm_source TEXT,
                utm_medium TEXT,
                utm_campaign TEXT,
                meta_json TEXT
            )");
            $db->execute('CREATE INDEX IF NOT EXISTS idx_te_created ON telemetry_events (created_at)');
            $db->execute('CREATE INDEX IF NOT EXISTS idx_te_visitor ON telemetry_events (visitor_id)');
            $db->execute('CREATE INDEX IF NOT EXISTS idx_te_session ON telemetry_events (session_id)');
            $db->execute('CREATE INDEX IF NOT EXISTS idx_te_unit ON telemetry_events (business_unit)');
            $db->execute('CREATE INDEX IF NOT EXISTS idx_te_entity ON telemetry_events (entity_type, entity_id)');
        }

        self::$ensured = true;
    }
}
