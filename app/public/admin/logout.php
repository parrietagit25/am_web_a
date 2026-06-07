<?php
/**
 * Admin Logout Controller
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/AdminUserService.php';
require_once __DIR__ . '/../../services/AdminAuditService.php';

if (AdminUserService::isLoggedIn()) {
    $user = AdminUserService::current();
    AdminAuditService::logAuthEvent(
        'logout',
        'success',
        (string)($user['username'] ?? ''),
        'Sesión cerrada'
    );
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

header('Location: /admin/login.php');
exit;
