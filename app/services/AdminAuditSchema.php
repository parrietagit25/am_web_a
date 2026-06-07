<?php
/**
 * Admin audit log schema (SQLite + MySQL).
 */
class AdminAuditSchema
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
            $db->execute("CREATE TABLE IF NOT EXISTS admin_audit_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                event_type VARCHAR(32) NOT NULL DEFAULT 'action',
                user_id INT UNSIGNED NULL,
                username VARCHAR(80) NULL,
                display_name VARCHAR(120) NULL,
                action VARCHAR(80) NULL,
                action_label VARCHAR(200) NULL,
                action_type VARCHAR(32) NULL,
                permission VARCHAR(64) NULL,
                module_label VARCHAR(120) NULL,
                entity_type VARCHAR(80) NULL,
                entity_id VARCHAR(120) NULL,
                entity_label VARCHAR(255) NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'success',
                message TEXT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(500) NULL,
                post_summary_json LONGTEXT NULL,
                meta_json LONGTEXT NULL,
                KEY idx_audit_created (created_at),
                KEY idx_audit_user (username),
                KEY idx_audit_action (action),
                KEY idx_audit_status (status),
                KEY idx_audit_entity (entity_type, entity_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $db->execute("CREATE TABLE IF NOT EXISTS admin_audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                event_type TEXT NOT NULL DEFAULT 'action',
                user_id INTEGER,
                username TEXT,
                display_name TEXT,
                action TEXT,
                action_label TEXT,
                action_type TEXT,
                permission TEXT,
                module_label TEXT,
                entity_type TEXT,
                entity_id TEXT,
                entity_label TEXT,
                status TEXT NOT NULL DEFAULT 'success',
                message TEXT,
                ip_address TEXT,
                user_agent TEXT,
                post_summary_json TEXT,
                meta_json TEXT
            )");
            $db->execute('CREATE INDEX IF NOT EXISTS idx_audit_created ON admin_audit_logs (created_at)');
            $db->execute('CREATE INDEX IF NOT EXISTS idx_audit_user ON admin_audit_logs (username)');
            $db->execute('CREATE INDEX IF NOT EXISTS idx_audit_action ON admin_audit_logs (action)');
            $db->execute('CREATE INDEX IF NOT EXISTS idx_audit_status ON admin_audit_logs (status)');
        }

        self::$ensured = true;
    }
}
