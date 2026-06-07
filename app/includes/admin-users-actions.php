<?php
if (!admin_can('users')) {
    return;
}

if ($action === 'save_admin_user') {
    $result = AdminUserService::saveUser([
        'id' => intval($_POST['user_id'] ?? 0),
        'username' => trim($_POST['username'] ?? ''),
        'display_name' => trim($_POST['display_name'] ?? ''),
        'password' => (string)($_POST['password'] ?? ''),
        'is_super_admin' => !empty($_POST['is_super_admin']),
        'is_active' => !empty($_POST['is_active']),
        'permissions' => $_POST['permissions'] ?? [],
    ]);
    if ($result['ok']) {
        $successMsg = $result['message'];
    } else {
        $errorMsg = $result['message'];
    }
} elseif ($action === 'delete_admin_user') {
    $result = AdminUserService::deleteUser(intval($_POST['user_id'] ?? 0));
    if ($result['ok']) {
        $successMsg = $result['message'];
    } else {
        $errorMsg = $result['message'];
    }
} elseif ($action === 'toggle_admin_user') {
    $result = AdminUserService::toggleUser(
        intval($_POST['user_id'] ?? 0),
        !empty($_POST['is_active'])
    );
    if ($result['ok']) {
        $successMsg = $result['message'];
    } else {
        $errorMsg = $result['message'];
    }
}
