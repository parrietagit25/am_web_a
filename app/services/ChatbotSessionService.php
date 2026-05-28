<?php
/**
 * Persistencia de sesiones y mensajes del chatbot IA.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ChatbotDatabaseSchema.php';

class ChatbotSessionService {
    private const PHP_SESSION_TOKEN = 'chatbot_db_token';

    public function __construct() {
        ChatbotDatabaseSchema::ensure();
    }

    public static function generateToken(): string {
        return 'cbs_' . bin2hex(random_bytes(16));
    }

    /**
     * Obtiene o crea la sesión activa en BD vinculada a la sesión PHP del visitante.
     */
    public function resolveActiveSessionId(string $lang, ?string $activeUnit, ?string $pageUrl): int {
        $token = $_SESSION[self::PHP_SESSION_TOKEN] ?? '';
        if ($token !== '') {
            $row = Database::getInstance()->selectOne(
                'SELECT id FROM chatbot_sessions WHERE session_token = :token AND ended_at IS NULL',
                [':token' => $token]
            );
            if ($row) {
                return (int) $row['id'];
            }
        }

        $token = self::generateToken();
        $now = date('Y-m-d H:i:s');
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO chatbot_sessions (session_token, lang, active_unit, page_url, message_count, ip_address, user_agent, created_at, updated_at)
             VALUES (:token, :lang, :unit, :page, 0, :ip, :ua, :created, :updated)',
            [
                ':token' => $token,
                ':lang' => in_array($lang, ['es', 'en'], true) ? $lang : 'es',
                ':unit' => $activeUnit !== null && $activeUnit !== '' ? $activeUnit : null,
                ':page' => $pageUrl !== null && $pageUrl !== '' ? mb_substr($pageUrl, 0, 500) : null,
                ':ip' => $this->clientIp(),
                ':ua' => $this->clientUserAgent(),
                ':created' => $now,
                ':updated' => $now,
            ]
        );

        $_SESSION[self::PHP_SESSION_TOKEN] = $token;
        return (int) $db->lastInsertId();
    }

    public function endActiveSession(): void {
        $token = $_SESSION[self::PHP_SESSION_TOKEN] ?? '';
        if ($token === '') {
            return;
        }
        $now = date('Y-m-d H:i:s');
        Database::getInstance()->execute(
            'UPDATE chatbot_sessions SET ended_at = :ended, updated_at = :ended WHERE session_token = :token AND ended_at IS NULL',
            [':ended' => $now, ':token' => $token]
        );
        unset($_SESSION[self::PHP_SESSION_TOKEN]);
    }

    public function appendMessage(int $sessionId, string $role, string $content, ?string $modelUsed = null): void {
        $role = strtolower(trim($role));
        if (!in_array($role, ['user', 'assistant'], true)) {
            return;
        }
        $content = trim($content);
        if ($content === '') {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO chatbot_messages (session_id, role, content, created_at) VALUES (:sid, :role, :content, :created)',
            [
                ':sid' => $sessionId,
                ':role' => $role,
                ':content' => $content,
                ':created' => $now,
            ]
        );

        $params = [
            ':updated' => $now,
            ':sid' => $sessionId,
        ];
        $sql = 'UPDATE chatbot_sessions SET message_count = message_count + 1, updated_at = :updated';
        if ($modelUsed !== null && $modelUsed !== '') {
            $sql .= ', model_used = :model';
            $params[':model'] = $modelUsed;
        }
        $sql .= ' WHERE id = :sid';
        $db->execute($sql, $params);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listSessions(int $limit = 200): array {
        $limit = max(1, min(500, $limit));
        $db = Database::getInstance();
        if ($db->getDriverName() === 'mysql') {
            return $db->select(
                "SELECT s.*,
                    (SELECT m.content FROM chatbot_messages m
                     WHERE m.session_id = s.id AND m.role = 'user'
                     ORDER BY m.id ASC LIMIT 1) AS first_user_message
                 FROM chatbot_sessions s
                 ORDER BY s.updated_at DESC
                 LIMIT {$limit}"
            );
        }
        return $db->select(
            "SELECT s.*,
                (SELECT m.content FROM chatbot_messages m
                 WHERE m.session_id = s.id AND m.role = 'user'
                 ORDER BY m.id ASC LIMIT 1) AS first_user_message
             FROM chatbot_sessions s
             ORDER BY s.updated_at DESC
             LIMIT {$limit}"
        );
    }

    public function getSession(int $id): ?array {
        $row = Database::getInstance()->selectOne(
            'SELECT * FROM chatbot_sessions WHERE id = :id',
            [':id' => $id]
        );
        return $row ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMessages(int $sessionId): array {
        return Database::getInstance()->select(
            'SELECT * FROM chatbot_messages WHERE session_id = :sid ORDER BY id ASC',
            [':sid' => $sessionId]
        );
    }

    public function deleteSession(int $id): bool {
        $db = Database::getInstance();
        $db->execute('DELETE FROM chatbot_messages WHERE session_id = :id', [':id' => $id]);
        return $db->execute('DELETE FROM chatbot_sessions WHERE id = :id', [':id' => $id]) > 0;
    }

    private function clientIp(): ?string {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        if (is_string($ip) && strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        $ip = trim((string) $ip);
        return $ip !== '' ? mb_substr($ip, 0, 45) : null;
    }

    private function clientUserAgent(): ?string {
        $ua = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        return $ua !== '' ? mb_substr($ua, 0, 500) : null;
    }
}
