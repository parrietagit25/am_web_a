<?php
/**
 * Chatbot sessions & messages schema (SQLite + MySQL).
 */

class ChatbotDatabaseSchema {
    private static $ensured = false;

    public static function ensure(): void {
        if (self::$ensured) {
            return;
        }
        $db = Database::getInstance();
        $driver = $db->getDriverName();

        if ($driver === 'mysql') {
            $db->execute("CREATE TABLE IF NOT EXISTS chatbot_sessions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                session_token VARCHAR(64) NOT NULL,
                lang VARCHAR(8) NOT NULL DEFAULT 'es',
                active_unit VARCHAR(32) NULL,
                page_url VARCHAR(500) NULL,
                message_count INT UNSIGNED NOT NULL DEFAULT 0,
                model_used VARCHAR(40) NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(500) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ended_at DATETIME NULL,
                UNIQUE KEY uq_session_token (session_token),
                KEY idx_updated (updated_at),
                KEY idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $db->execute("CREATE TABLE IF NOT EXISTS chatbot_messages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                session_id INT UNSIGNED NOT NULL,
                role VARCHAR(16) NOT NULL,
                content LONGTEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_session (session_id),
                CONSTRAINT fk_chatbot_messages_session
                    FOREIGN KEY (session_id) REFERENCES chatbot_sessions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $db->execute("CREATE TABLE IF NOT EXISTS chatbot_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_token TEXT NOT NULL UNIQUE,
                lang TEXT NOT NULL DEFAULT 'es',
                active_unit TEXT,
                page_url TEXT,
                message_count INTEGER NOT NULL DEFAULT 0,
                model_used TEXT,
                ip_address TEXT,
                user_agent TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                ended_at TEXT
            )");

            $db->execute("CREATE TABLE IF NOT EXISTS chatbot_messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER NOT NULL,
                role TEXT NOT NULL,
                content TEXT NOT NULL,
                created_at TEXT NOT NULL,
                FOREIGN KEY (session_id) REFERENCES chatbot_sessions(id) ON DELETE CASCADE
            )");

            $db->execute('CREATE INDEX IF NOT EXISTS idx_chatbot_messages_session ON chatbot_messages(session_id)');
            $db->execute('CREATE INDEX IF NOT EXISTS idx_chatbot_sessions_updated ON chatbot_sessions(updated_at)');
        }

        self::$ensured = true;
    }
}
