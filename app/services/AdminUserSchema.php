<?php
/**
 * Admin users schema (SQLite + MySQL).
 */
class AdminUserSchema
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
            $db->execute("CREATE TABLE IF NOT EXISTS admin_users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(80) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                display_name VARCHAR(120) NULL,
                is_super_admin TINYINT(1) NOT NULL DEFAULT 0,
                permissions_json LONGTEXT NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL,
                UNIQUE KEY uq_admin_username (username)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $db->execute("CREATE TABLE IF NOT EXISTS admin_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                display_name TEXT,
                is_super_admin INTEGER NOT NULL DEFAULT 0,
                permissions_json TEXT NOT NULL DEFAULT '[]',
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT
            )");
        }

        self::$ensured = true;
    }
}
