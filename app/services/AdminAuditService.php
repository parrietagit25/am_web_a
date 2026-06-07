<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/AdminAuditSchema.php';
require_once __DIR__ . '/AdminAuditActionCatalog.php';
require_once __DIR__ . '/AdminPermissionRegistry.php';
require_once __DIR__ . '/AdminUserService.php';

class AdminAuditService
{
    private const MAX_STRING = 1500;
    private const REDACT_KEYS = [
        'password', 'password_hash', 'pass', 'csrf', 'csrf_token',
    ];

    public static function ensureSchema(): void
    {
        AdminAuditSchema::ensure();
    }

    public static function logPostAction(string $action, string $status, string $message = ''): void
    {
        if ($action === '') {
            return;
        }
        self::write([
            'event_type' => 'action',
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'post_summary_json' => json_encode(self::sanitizePost($_POST), JSON_UNESCAPED_UNICODE),
            'meta_json' => json_encode(self::extractMeta($action, $_POST), JSON_UNESCAPED_UNICODE),
        ]);
    }

    public static function logAuthEvent(string $event, string $status, string $username = '', string $message = ''): void
    {
        self::write([
            'event_type' => 'auth',
            'action' => $event,
            'status' => $status,
            'message' => $message,
            'username' => $username !== '' ? $username : null,
            'display_name' => $username !== '' ? $username : null,
            'post_summary_json' => json_encode(['username' => $username], JSON_UNESCAPED_UNICODE),
            'meta_json' => json_encode(['event' => $event], JSON_UNESCAPED_UNICODE),
        ], false);
    }

    /** @param array<string, mixed> $overrides @param bool $useCurrentUser */
    private static function write(array $overrides, bool $useCurrentUser = true): void
    {
        try {
            self::ensureSchema();
            $action = (string)($overrides['action'] ?? '');
            $describe = AdminAuditActionCatalog::describe($action);
            $permission = AdminPermissionRegistry::permissionForAction($action);
            $meta = json_decode((string)($overrides['meta_json'] ?? '{}'), true);
            if (!is_array($meta)) {
                $meta = [];
            }
            $entity = self::resolveEntity($describe['entity'], $meta);

            $user = $useCurrentUser ? AdminUserService::current() : null;
            $row = [
                'event_type' => (string)($overrides['event_type'] ?? 'action'),
                'user_id' => $user['id'] ?? ($overrides['user_id'] ?? null),
                'username' => $user['username'] ?? ($overrides['username'] ?? null),
                'display_name' => $user['display_name'] ?? ($overrides['display_name'] ?? null),
                'action' => $action,
                'action_label' => $describe['label'],
                'action_type' => $describe['type'],
                'permission' => $permission,
                'module_label' => AdminAuditActionCatalog::moduleLabel($permission),
                'entity_type' => $entity['type'],
                'entity_id' => $entity['id'],
                'entity_label' => $entity['label'],
                'status' => (string)($overrides['status'] ?? 'success'),
                'message' => self::truncate((string)($overrides['message'] ?? ''), 1000),
                'ip_address' => self::clientIp(),
                'user_agent' => self::truncate((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 500),
                'post_summary_json' => (string)($overrides['post_summary_json'] ?? '{}'),
                'meta_json' => (string)($overrides['meta_json'] ?? '{}'),
            ];

            Database::getInstance()->execute(
                'INSERT INTO admin_audit_logs
                (event_type, user_id, username, display_name, action, action_label, action_type, permission,
                 module_label, entity_type, entity_id, entity_label, status, message, ip_address, user_agent,
                 post_summary_json, meta_json, created_at)
                 VALUES
                (:event_type, :user_id, :username, :display_name, :action, :action_label, :action_type, :permission,
                 :module_label, :entity_type, :entity_id, :entity_label, :status, :message, :ip_address, :user_agent,
                 :post_summary_json, :meta_json, ' . self::nowSql() . ')',
                $row
            );
        } catch (Throwable $e) {
            am_log('Admin audit log failed: ' . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{rows: array<int, array<string, mixed>>, total: int, page: int, pages: int}
     */
    public static function listLogs(array $filters = []): array
    {
        self::ensureSchema();
        $db = Database::getInstance();

        $page = max(1, intval($filters['page'] ?? 1));
        $limit = min(100, max(10, intval($filters['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $where = ['1=1'];
        $params = [];

        if (!empty($filters['username'])) {
            $where[] = 'username LIKE :username';
            $params[':username'] = '%' . trim((string)$filters['username']) . '%';
        }
        if (!empty($filters['action'])) {
            $where[] = 'action = :action';
            $params[':action'] = trim((string)$filters['action']);
        }
        if (!empty($filters['action_type'])) {
            $where[] = 'action_type = :action_type';
            $params[':action_type'] = trim((string)$filters['action_type']);
        }
        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = trim((string)$filters['status']);
        }
        if (!empty($filters['permission'])) {
            $where[] = 'permission = :permission';
            $params[':permission'] = trim((string)$filters['permission']);
        }
        if (!empty($filters['q'])) {
            $where[] = '(action_label LIKE :q OR entity_label LIKE :q OR message LIKE :q OR entity_id LIKE :q OR post_summary_json LIKE :q)';
            $params[':q'] = '%' . trim((string)$filters['q']) . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= :date_from';
            $params[':date_from'] = trim((string)$filters['date_from']) . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= :date_to';
            $params[':date_to'] = trim((string)$filters['date_to']) . ' 23:59:59';
        }

        $whereSql = implode(' AND ', $where);
        $countRow = $db->selectOne("SELECT COUNT(*) AS cnt FROM admin_audit_logs WHERE $whereSql", $params);
        $total = intval($countRow['cnt'] ?? 0);
        $pages = max(1, (int)ceil($total / $limit));

        $rows = $db->select(
            "SELECT * FROM admin_audit_logs WHERE $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset",
            $params
        );

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }

    /** @return array<string, mixed>|null */
    public static function getLog(int $id): ?array
    {
        self::ensureSchema();
        $row = Database::getInstance()->selectOne(
            'SELECT * FROM admin_audit_logs WHERE id = :id LIMIT 1',
            [':id' => $id]
        );
        return $row ?: null;
    }

    /** @return array<int, array{username: string, count: int}> */
    public static function topUsers(int $days = 30, int $limit = 10): array
    {
        self::ensureSchema();
        $db = Database::getInstance();
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $db->select(
            'SELECT COALESCE(display_name, username, \'(desconocido)\') AS username, COUNT(*) AS count
             FROM admin_audit_logs
             WHERE created_at >= :since AND event_type = \'action\' AND status = \'success\'
             GROUP BY COALESCE(display_name, username)
             ORDER BY count DESC
             LIMIT ' . intval($limit),
            [':since' => $since]
        );
    }

    /** @param array<string, mixed> $post @return array<string, mixed> */
    private static function extractMeta(string $action, array $post): array
    {
        $meta = [
            'action' => $action,
            'tab' => trim((string)($post['tab'] ?? $_GET['tab'] ?? '')),
        ];

        $idKeys = [
            'id', 'user_id', 'news_id', 'vehicle_id', 'post_id', 'landing_id', 'sucursal_id',
            'agent_id', 'bank_id', 'brand_id', 'car_id', 'session_id', 'reservation_id',
            'reservation_code', 'message_id', 'payment_id', 'quote_id', 'lead_id', 'email_id',
            'item_id', 'card_id', 'page_key', 'seo_page', 'footer_page_key',
        ];
        foreach ($idKeys as $key) {
            if (!empty($post[$key])) {
                $meta['entity_id'] = trim((string)$post[$key]);
                $meta['entity_id_key'] = $key;
                break;
            }
        }

        $labelKeys = [
            'title', 'name', 'label', 'username', 'display_name', 'vehicle_name', 'customer_name',
            'customer_email', 'email', 'page_title', 'hero_title', 'headline', 'subject',
        ];
        foreach ($labelKeys as $key) {
            if (!empty($post[$key]) && is_string($post[$key])) {
                $meta['entity_label'] = self::truncate(trim($post[$key]), 200);
                $meta['entity_label_key'] = $key;
                break;
            }
        }

        if ($action === 'save_admin_user') {
            $meta['target_username'] = trim((string)($post['username'] ?? ''));
            $meta['is_super_admin'] = !empty($post['is_super_admin']);
            $meta['permissions'] = array_values(array_filter((array)($post['permissions'] ?? [])));
        }

        if ($action === 'update_rac_reservation_status') {
            $meta['new_status'] = trim((string)($post['status'] ?? ''));
            $meta['reservation_code'] = trim((string)($post['reservation_code'] ?? $post['code'] ?? ''));
        }

        return $meta;
    }

    /** @param array<string, mixed> $meta @return array{type: string, id: string|null, label: string|null} */
    private static function resolveEntity(string $entityType, array $meta): array
    {
        return [
            'type' => $entityType,
            'id' => isset($meta['entity_id']) ? (string)$meta['entity_id'] : (
                isset($meta['reservation_code']) ? (string)$meta['reservation_code'] : null
            ),
            'label' => isset($meta['entity_label']) ? (string)$meta['entity_label'] : (
                isset($meta['target_username']) ? (string)$meta['target_username'] : null
            ),
        ];
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private static function sanitizePost(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $keyLower = strtolower((string)$key);
            if (in_array($keyLower, self::REDACT_KEYS, true) || str_contains($keyLower, 'password')) {
                $out[$key] = '[OCULTO]';
                continue;
            }
            if (is_array($value)) {
                $out[$key] = self::sanitizePost($value);
                continue;
            }
            if (is_string($value)) {
                if (strlen($value) > self::MAX_STRING) {
                    $out[$key] = mb_substr($value, 0, self::MAX_STRING) . '… [truncado, ' . strlen($value) . ' chars]';
                } else {
                    $out[$key] = $value;
                }
                continue;
            }
            $out[$key] = $value;
        }
        return $out;
    }

    private static function clientIp(): string
    {
        $candidates = [
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
            $_SERVER['HTTP_CLIENT_IP'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
        ];
        foreach ($candidates as $raw) {
            $raw = trim((string)$raw);
            if ($raw === '') {
                continue;
            }
            $ip = trim(explode(',', $raw)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '';
    }

    private static function truncate(string $value, int $max): string
    {
        if (mb_strlen($value) <= $max) {
            return $value;
        }
        return mb_substr($value, 0, $max) . '…';
    }

    private static function nowSql(): string
    {
        return Database::getInstance()->getDriverName() === 'mysql' ? 'NOW()' : "datetime('now')";
    }
}
