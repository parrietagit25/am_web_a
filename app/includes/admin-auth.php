<?php
require_once __DIR__ . '/../services/AdminUserService.php';
require_once __DIR__ . '/../services/AdminPermissionRegistry.php';

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

function admin_guard_post_action(string $action): bool
{
    if ($action === '') {
        return true;
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
}
