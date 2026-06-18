<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/AdminUserSchema.php';
require_once __DIR__ . '/AdminPermissionRegistry.php';

class AdminUserService
{
    /** @var array<string, mixed>|null */
    private static $current = null;

    public static function ensureSchema(): void
    {
        AdminUserSchema::ensure();
        self::bootstrapDefaultSuperAdmin();
        self::repairSessionIfLegacyAdmin();
    }

    /** Garantiza que el usuario admin de config.php exista como super administrador. */
    private static function bootstrapDefaultSuperAdmin(): void
    {
        $db = Database::getInstance();
        $username = ADMIN_USER;
        $row = $db->selectOne(
            'SELECT * FROM admin_users WHERE username = :username LIMIT 1',
            [':username' => $username]
        );

        $nowExpr = $db->getDriverName() === 'mysql' ? 'NOW()' : "datetime('now')";

        if (!$row) {
            $db->execute(
                "INSERT INTO admin_users (username, password_hash, display_name, is_super_admin, permissions_json, is_active, created_at)
                 VALUES (:username, :password_hash, :display_name, 1, '[]', 1, $nowExpr)",
                [
                    ':username' => $username,
                    ':password_hash' => password_hash(ADMIN_PASS, PASSWORD_DEFAULT),
                    ':display_name' => 'Administrador',
                ]
            );
            return;
        }

        if (empty($row['is_super_admin']) || (string)($row['permissions_json'] ?? '[]') !== '[]') {
            $db->execute(
                "UPDATE admin_users SET is_super_admin = 1, permissions_json = '[]', is_active = 1, updated_at = $nowExpr
                 WHERE username = :username",
                [':username' => $username]
            );
        }
    }

    /** Corrige sesiones del admin principal creadas antes de asignar super admin. */
    public static function repairSessionIfLegacyAdmin(): void
    {
        if (!self::isLoggedIn()) {
            return;
        }
        if (!empty($_SESSION['admin_is_super'])) {
            return;
        }
        $username = (string)($_SESSION['admin_username'] ?? '');
        if ($username === '' || $username === ADMIN_USER) {
            self::loginLegacySuperAdmin();
        }
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['admin_logged_in']);
    }

    /** @return array<string, mixed>|null */
    public static function current(): ?array
    {
        if (self::$current !== null) {
            return self::$current;
        }
        if (!self::isLoggedIn()) {
            return null;
        }

        if (!empty($_SESSION['admin_is_super'])) {
            self::$current = [
                'id' => intval($_SESSION['admin_user_id'] ?? 0),
                'username' => (string)($_SESSION['admin_username'] ?? 'admin'),
                'display_name' => (string)($_SESSION['admin_display_name'] ?? $_SESSION['admin_username'] ?? 'admin'),
                'is_super_admin' => 1,
                'permissions' => [],
                'is_active' => 1,
            ];
            return self::$current;
        }

        $permissions = $_SESSION['admin_permissions'] ?? [];
        if (!is_array($permissions)) {
            $permissions = [];
        }

        self::$current = [
            'id' => intval($_SESSION['admin_user_id'] ?? 0),
            'username' => (string)($_SESSION['admin_username'] ?? ''),
            'display_name' => (string)($_SESSION['admin_display_name'] ?? $_SESSION['admin_username'] ?? ''),
            'is_super_admin' => 0,
            'permissions' => array_values(array_unique(array_map('strval', $permissions))),
            'is_active' => 1,
        ];

        return self::$current;
    }

    public static function isSuperAdmin(): bool
    {
        $user = self::current();
        return $user !== null && !empty($user['is_super_admin']);
    }

    /** @return string[] */
    public static function permissions(): array
    {
        if (self::isSuperAdmin()) {
            return AdminPermissionRegistry::allPermissionKeys();
        }
        $user = self::current();
        return $user['permissions'] ?? [];
    }

    public static function can(string $permission): bool
    {
        if (self::isSuperAdmin()) {
            return true;
        }
        if ($permission === 'global_sucursales' && in_array('global', self::permissions(), true)) {
            return true;
        }
        return in_array($permission, self::permissions(), true);
    }

    /** @param string[] $permissions */
    public static function canAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::can($permission)) {
                return true;
            }
        }
        return false;
    }

    public static function canAction(string $action): bool
    {
        $permission = AdminPermissionRegistry::permissionForAction($action);
        if ($permission === null) {
            return self::isSuperAdmin();
        }
        return self::can($permission);
    }

    public static function canTab(string $tabSlug): bool
    {
        $permission = AdminPermissionRegistry::permissionForTab($tabSlug);
        if ($permission === null) {
            return self::isSuperAdmin();
        }
        return self::can($permission);
    }

    public static function firstAllowedTabSlug(): string
    {
        $map = self::tabSlugOrder();
        foreach ($map as $slug => $permission) {
            if (self::can($permission)) {
                return $slug;
            }
        }
        return 'global';
    }

    /** @return array<string, string> tab slug => permission */
    public static function tabSlugOrder(): array
    {
        return [
            'global' => 'global',
            'global-sucursales' => 'global_sucursales',
            'translations' => 'translations',
            'seo' => 'seo',
            'landings' => 'landings',
            'footer' => 'footer',
            'users' => 'users',
            'audit-log' => 'audit_log',
            'telemetry' => 'telemetry',
            'hero' => 'hero',
            'news' => 'news',
            'opinions' => 'opinions',
            'vehicles' => 'vehicles',
            'sucursales' => 'sucursales',
            'terms' => 'terms',
            'requirements' => 'requirements',
            'contact' => 'contact',
            'payments' => 'payments',
            'rac-reservations' => 'rac_reservations',
            'semi-home' => 'semi_home',
            'semi-inventory' => 'semi_inventory',
            'semi-opinions' => 'semi_opinions',
            'semi-financing' => 'semi_financing',
            'semi-team' => 'semi_team',
            'semi-contact' => 'semi_contact',
            'leasing-home' => 'leasing_home',
            'leasing-sucursales' => 'leasing_sucursales',
            'leasing-flota' => 'leasing_flota',
            'leasing-equipo' => 'leasing_equipo',
            'leasing-contacto' => 'leasing_contacto',
            'renting-home' => 'renting_home',
            'renting-servicios' => 'renting_servicios',
            'renting-sobre' => 'renting_sobre',
            'renting-publicaciones' => 'renting_publicaciones',
            'renting-contacto' => 'renting_contacto',
            'renting-cotizaciones' => 'renting_cotizaciones',
            'renting-marcas' => 'renting_marcas',
            'renting-opiniones' => 'renting_opiniones',
            'taller-home' => 'taller_home',
            'taller-contacto' => 'taller_contacto',
            'taller-sobre' => 'taller_sobre',
            'taller-sucursales' => 'taller_sucursales',
            'chatbot' => 'chatbot',
            'chatbot-sessions' => 'chatbot_sessions',
        ];
    }

    /** @return array<string, mixed>|null */
    public static function authenticate(string $username, string $password): ?array
    {
        self::ensureSchema();
        $username = trim($username);
        if ($username === '' || $password === '') {
            return null;
        }

        $db = Database::getInstance();
        $row = $db->selectOne(
            'SELECT * FROM admin_users WHERE username = :username LIMIT 1',
            [':username' => $username]
        );
        if (!$row || empty($row['is_active'])) {
            return null;
        }
        if (!password_verify($password, (string)$row['password_hash'])) {
            return null;
        }

        $user = self::normalizeUserRow($row);
        if ($username === ADMIN_USER) {
            $user = self::ensureLegacyAdminIsSuper($user);
        }
        return $user;
    }

    /** @param array<string, mixed> $user @return array<string, mixed> */
    private static function ensureLegacyAdminIsSuper(array $user): array
    {
        if (!empty($user['is_super_admin']) && ($user['permissions'] ?? []) === []) {
            return $user;
        }

        $db = Database::getInstance();
        $nowExpr = $db->getDriverName() === 'mysql' ? 'NOW()' : "datetime('now')";
        $db->execute(
            "UPDATE admin_users SET is_super_admin = 1, permissions_json = '[]', is_active = 1, updated_at = $nowExpr
             WHERE id = :id",
            [':id' => intval($user['id'])]
        );

        $user['is_super_admin'] = true;
        $user['permissions'] = [];
        return $user;
    }

    public static function authenticateLegacy(string $username, string $password): bool
    {
        return $username === ADMIN_USER && $password === ADMIN_PASS;
    }

    /** @param array<string, mixed> $user */
    public static function loginSession(array $user): void
    {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = intval($user['id'] ?? 0);
        $_SESSION['admin_username'] = (string)($user['username'] ?? '');
        $_SESSION['admin_display_name'] = (string)($user['display_name'] ?? $user['username'] ?? '');
        $_SESSION['admin_is_super'] = !empty($user['is_super_admin']);
        $_SESSION['admin_permissions'] = !empty($user['is_super_admin'])
            ? []
            : ($user['permissions'] ?? []);
        self::$current = null;
    }

    public static function loginLegacySuperAdmin(): void
    {
        self::loginSession([
            'id' => 0,
            'username' => ADMIN_USER,
            'display_name' => 'Administrador',
            'is_super_admin' => 1,
            'permissions' => [],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function listUsers(): array
    {
        self::ensureSchema();
        $db = Database::getInstance();
        $rows = $db->select('SELECT * FROM admin_users ORDER BY username ASC');
        return array_map([self::class, 'normalizeUserRow'], $rows);
    }

    /** @return array<string, mixed>|null */
    public static function getUser(int $id): ?array
    {
        self::ensureSchema();
        $db = Database::getInstance();
        $row = $db->selectOne('SELECT * FROM admin_users WHERE id = :id LIMIT 1', [':id' => $id]);
        return $row ? self::normalizeUserRow($row) : null;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{ok: bool, message: string, id?: int}
     */
    public static function saveUser(array $data): array
    {
        self::ensureSchema();
        $id = intval($data['id'] ?? 0);
        $username = trim((string)($data['username'] ?? ''));
        $displayName = trim((string)($data['display_name'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $isSuper = !empty($data['is_super_admin']);
        $isActive = !isset($data['is_active']) || !empty($data['is_active']);
        $permissions = self::sanitizePermissions($data['permissions'] ?? []);

        if ($username === '') {
            return ['ok' => false, 'message' => 'El nombre de usuario es obligatorio.'];
        }
        if (!preg_match('/^[a-zA-Z0-9._-]{3,40}$/', $username)) {
            return ['ok' => false, 'message' => 'Usuario inválido (3-40 caracteres, letras, números, . _ -).'];
        }

        if ($username === ADMIN_USER) {
            $isSuper = true;
            $permissions = [];
            $isActive = true;
        }

        $db = Database::getInstance();
        $duplicate = $db->selectOne(
            'SELECT id FROM admin_users WHERE username = :username AND id != :id LIMIT 1',
            [':username' => $username, ':id' => $id]
        );
        if ($duplicate) {
            return ['ok' => false, 'message' => 'Ese nombre de usuario ya existe.'];
        }

        if ($id === 0 && $password === '') {
            return ['ok' => false, 'message' => 'La contraseña es obligatoria para usuarios nuevos.'];
        }
        if ($password !== '' && strlen($password) < 6) {
            return ['ok' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.'];
        }

        if ($isSuper && !self::isSuperAdmin()) {
            return ['ok' => false, 'message' => 'Solo un super administrador puede asignar rol de super administrador.'];
        }

        if ($isSuper) {
            $permissions = [];
        } elseif ($permissions === []) {
            return ['ok' => false, 'message' => 'Seleccione al menos un permiso o marque Super administrador.'];
        }

        $permissionsJson = json_encode(array_values($permissions), JSON_UNESCAPED_UNICODE);
        $nowExpr = $db->getDriverName() === 'mysql' ? 'NOW()' : "datetime('now')";

        if ($id > 0) {
            $existing = self::getUser($id);
            if (!$existing) {
                return ['ok' => false, 'message' => 'Usuario no encontrado.'];
            }
            if (!self::canManageUser($existing)) {
                return ['ok' => false, 'message' => 'No puede modificar este usuario.'];
            }

            $params = [
                ':username' => $username,
                ':display_name' => $displayName !== '' ? $displayName : $username,
                ':is_super_admin' => $isSuper ? 1 : 0,
                ':permissions_json' => $permissionsJson,
                ':is_active' => $isActive ? 1 : 0,
                ':id' => $id,
            ];
            $sql = "UPDATE admin_users SET username = :username, display_name = :display_name,
                    is_super_admin = :is_super_admin, permissions_json = :permissions_json,
                    is_active = :is_active, updated_at = $nowExpr";
            if ($password !== '') {
                $sql .= ', password_hash = :password_hash';
                $params[':password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = :id';
            $db->execute($sql, $params);
            return ['ok' => true, 'message' => 'Usuario actualizado correctamente.', 'id' => $id];
        }

        $db->execute(
            "INSERT INTO admin_users (username, password_hash, display_name, is_super_admin, permissions_json, is_active, created_at)
             VALUES (:username, :password_hash, :display_name, :is_super_admin, :permissions_json, :is_active, $nowExpr)",
            [
                ':username' => $username,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ':display_name' => $displayName !== '' ? $displayName : $username,
                ':is_super_admin' => $isSuper ? 1 : 0,
                ':permissions_json' => $permissionsJson,
                ':is_active' => $isActive ? 1 : 0,
            ]
        );

        return [
            'ok' => true,
            'message' => 'Usuario creado correctamente.',
            'id' => intval($db->lastInsertId()),
        ];
    }

    /** @return array{ok: bool, message: string} */
    public static function deleteUser(int $id): array
    {
        self::ensureSchema();
        $current = self::current();
        if (!$current) {
            return ['ok' => false, 'message' => 'Sesión no válida.'];
        }
        if ($id === intval($current['id'])) {
            return ['ok' => false, 'message' => 'No puede eliminar su propia cuenta.'];
        }

        $user = self::getUser($id);
        if (!$user) {
            return ['ok' => false, 'message' => 'Usuario no encontrado.'];
        }
        if (($user['username'] ?? '') === ADMIN_USER) {
            return ['ok' => false, 'message' => 'No se puede eliminar la cuenta principal de administración.'];
        }
        if (!self::canManageUser($user)) {
            return ['ok' => false, 'message' => 'No puede eliminar este usuario.'];
        }

        Database::getInstance()->execute('DELETE FROM admin_users WHERE id = :id', [':id' => $id]);
        return ['ok' => true, 'message' => 'Usuario eliminado correctamente.'];
    }

    /** @return array{ok: bool, message: string} */
    public static function toggleUser(int $id, bool $active): array
    {
        self::ensureSchema();
        $current = self::current();
        if (!$current) {
            return ['ok' => false, 'message' => 'Sesión no válida.'];
        }
        if ($id === intval($current['id']) && !$active) {
            return ['ok' => false, 'message' => 'No puede desactivar su propia cuenta.'];
        }

        $user = self::getUser($id);
        if (!$user) {
            return ['ok' => false, 'message' => 'Usuario no encontrado.'];
        }
        if (!self::canManageUser($user)) {
            return ['ok' => false, 'message' => 'No puede modificar este usuario.'];
        }

        $nowExpr = Database::getInstance()->getDriverName() === 'mysql' ? 'NOW()' : "datetime('now')";
        Database::getInstance()->execute(
            "UPDATE admin_users SET is_active = :active, updated_at = $nowExpr WHERE id = :id",
            [':active' => $active ? 1 : 0, ':id' => $id]
        );

        return ['ok' => true, 'message' => $active ? 'Usuario activado.' : 'Usuario desactivado.'];
    }

    /** @param array<string, mixed> $target */
    private static function canManageUser(array $target): bool
    {
        if (self::isSuperAdmin()) {
            return true;
        }
        if (!self::can('users')) {
            return false;
        }
        if (!empty($target['is_super_admin'])) {
            return false;
        }
        return true;
    }

    /** @param mixed $permissions @return string[] */
    private static function sanitizePermissions($permissions): array
    {
        if (!is_array($permissions)) {
            return [];
        }
        $allowed = AdminPermissionRegistry::allPermissionKeys();
        $clean = [];
        foreach ($permissions as $perm) {
            $perm = (string)$perm;
            if (in_array($perm, $allowed, true)) {
                $clean[] = $perm;
            }
        }
        return array_values(array_unique($clean));
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private static function normalizeUserRow(array $row): array
    {
        $permissions = json_decode((string)($row['permissions_json'] ?? '[]'), true);
        if (!is_array($permissions)) {
            $permissions = [];
        }

        return [
            'id' => intval($row['id'] ?? 0),
            'username' => (string)($row['username'] ?? ''),
            'display_name' => (string)($row['display_name'] ?? $row['username'] ?? ''),
            'is_super_admin' => !empty($row['is_super_admin']),
            'permissions' => self::sanitizePermissions($permissions),
            'is_active' => !isset($row['is_active']) || !empty($row['is_active']),
            'created_at' => (string)($row['created_at'] ?? ''),
            'updated_at' => (string)($row['updated_at'] ?? ''),
        ];
    }
}
