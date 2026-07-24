<?php
require_once __DIR__ . '/../services/AdminUserService.php';
require_once __DIR__ . '/../services/AdminPermissionRegistry.php';
require_once __DIR__ . '/../services/AdminAuditService.php';

function admin_require_login(): void
{
    if (!AdminUserService::isLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function admin_can(string $permission): bool
{
    return AdminUserService::can($permission);
}

/** @param string[] $permissions */
function admin_can_any(array $permissions): bool
{
    return AdminUserService::canAny($permissions);
}

function admin_current_username(): string
{
    $user = AdminUserService::current();
    return $user['display_name'] ?? $user['username'] ?? 'admin';
}

function admin_csrf_token(): string
{
    if (empty($_SESSION['admin_csrf_token']) || !is_string($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['admin_csrf_token'];
}

function admin_csrf_field(): void
{
    echo '<input type="hidden" name="admin_csrf_token" value="'
        . esc(admin_csrf_token())
        . '">';
}

function admin_verify_csrf(string $token): bool
{
    $expected = $_SESSION['admin_csrf_token'] ?? '';

    return is_string($expected) && $expected !== '' && hash_equals($expected, $token);
}

function admin_guard_post_action(string $action): bool
{
    if ($action === '') {
        return true;
    }

    $csrfActions = [
        'save_generic_page',
        'delete_generic_page',
        'save_unit_footer',
        'save_unit_menu',
        'save_unit_terms_page',
        'save_terms',
        'add_semi_bank',
        'edit_semi_bank',
        'delete_semi_bank',
        'add_renting_brand',
        'edit_renting_brand',
        'delete_renting_brand',
        'add_taller_brand',
        'edit_taller_brand',
        'delete_taller_brand',
        'save_unit_allies_meta',
        'add_unit_ally',
        'edit_unit_ally',
        'delete_unit_ally',
    ];
    if (in_array($action, $csrfActions, true)
        && !admin_verify_csrf((string) ($_POST['admin_csrf_token'] ?? ''))) {
        return false;
    }

    $unitContentActions = [
        'save_unit_content_settings',
        'add_unit_content_item',
        'edit_unit_content_item',
        'delete_unit_content_item',
        'toggle_unit_content_home',
        'add_unit_content_taxonomy',
        'delete_unit_content_taxonomy',
    ];
    if (in_array($action, $unitContentActions, true)) {
        require_once __DIR__ . '/../services/UnitContentService.php';
        $unitKey = trim($_POST['content_unit'] ?? '');
        if ($unitKey === '') {
            return false;
        }
        return AdminUserService::can(UnitContentService::contentPermissionKey($unitKey));
    }

    if (AdminUserService::canAction($action)) {
        return true;
    }
    return false;
}

function admin_deny_post(string &$errorMsg, string $action = ''): void
{
    $errorMsg = 'No tiene permiso para realizar esta acción.'
        . ($action !== '' ? ' (' . $action . ')' : '');
    if ($action !== '') {
        AdminAuditService::logPostAction($action, 'denied', $errorMsg);
    }
}

function admin_log_post_result(string $action, string $successMsg, string $errorMsg): void
{
    if ($action === '') {
        return;
    }
    $status = $successMsg !== '' ? 'success' : ($errorMsg !== '' ? 'error' : 'unknown');
    $message = $successMsg !== '' ? $successMsg : $errorMsg;
    AdminAuditService::logPostAction($action, $status, $message);
}

function admin_flash_set(string $successMsg, string $errorMsg): void
{
    $_SESSION['admin_flash'] = [
        'success' => $successMsg,
        'error' => $errorMsg,
    ];
}

/** @return array{success: string, error: string} */
function admin_flash_consume(): array
{
    $flash = $_SESSION['admin_flash'] ?? ['success' => '', 'error' => ''];
    unset($_SESSION['admin_flash']);
    return [
        'success' => (string) ($flash['success'] ?? ''),
        'error' => (string) ($flash['error'] ?? ''),
    ];
}

function admin_sanitize_tab_slug(string $tab): ?string
{
    $tab = trim($tab);
    if ($tab === 'news' || $tab === 'rentacar-content') {
        $tab = 'rentacar-content-config';
    }
    if (preg_match('/^([a-z0-9_]+)-content$/', $tab, $m)) {
        $tab = $m[1] . '-content-config';
    }
    if ($tab === '' || !AdminUserService::canTab($tab)) {
        return null;
    }
    return $tab;
}

function admin_tab_slug_for_permission(string $permission): ?string
{
    foreach (AdminUserService::tabSlugOrder() as $slug => $perm) {
        if ($perm === $permission) {
            return $slug;
        }
    }
    return null;
}

function admin_tab_slug_for_action(string $action): ?string
{
    $action = trim($action);
    if ($action === '') {
        return null;
    }
    $permission = AdminPermissionRegistry::permissionForAction($action);
    if ($permission === null) {
        return null;
    }
    return admin_tab_slug_for_permission($permission);
}

function admin_resolve_redirect_tab(string $action): string
{
    $postTab = admin_sanitize_tab_slug(trim($_POST['admin_tab'] ?? ''));
    if ($postTab !== null) {
        return $postTab;
    }

    $getTab = admin_sanitize_tab_slug(trim($_GET['tab'] ?? ''));
    if ($getTab !== null) {
        return $getTab;
    }

    $actionTab = admin_tab_slug_for_action($action);
    if ($actionTab !== null) {
        $sanitized = admin_sanitize_tab_slug($actionTab);
        if ($sanitized !== null) {
            return $sanitized;
        }
    }

    $sessionTab = admin_sanitize_tab_slug(trim($_SESSION['admin_last_tab'] ?? ''));
    if ($sessionTab !== null) {
        return $sessionTab;
    }

    return AdminUserService::firstAllowedTabSlug();
}

function admin_redirect_after_post(string $action, string $successMsg, string $errorMsg): void
{
    if ($action === '' && $successMsg === '' && $errorMsg === '') {
        return;
    }

    admin_flash_set($successMsg, $errorMsg);

    $tab = admin_resolve_redirect_tab($action);
    $_SESSION['admin_last_tab'] = $tab;

    $query = ['tab' => $tab];
    foreach (['q', 'p', 'location_id'] as $key) {
        $fromPost = trim((string) ($_POST[$key] ?? ''));
        $fromGet = trim((string) ($_GET[$key] ?? ''));
        $val = $fromPost !== '' ? $fromPost : $fromGet;
        if ($val !== '') {
            $query[$key] = $val;
        }
    }

    header('Location: /admin/index.php?' . http_build_query($query), true, 303);
    exit;
}
