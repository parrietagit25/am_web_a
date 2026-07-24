<?php
/**
 * API JSON — Laboratorio BARS / Partner (super admin o permiso rac_bars_lab).
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/AdminUserService.php';
require_once __DIR__ . '/../services/BarsApiLabService.php';
require_once __DIR__ . '/../includes/admin-auth.php';

AdminUserService::ensureSchema();

if (!AdminUserService::isLoggedIn() || (!AdminUserService::isSuperAdmin() && !admin_can('rac_bars_lab'))) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Requiere super admin o permiso Lab BARS / Partner.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method === 'GET') {
    echo json_encode([
        'ok' => true,
        'status' => BarsApiLabService::status(),
        'catalog' => BarsApiLabService::catalog(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$token = (string) ($input['admin_csrf_token'] ?? '');
if (!admin_verify_csrf($token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'CSRF inválido. Recarga la página.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$apiId = trim((string) ($input['api_id'] ?? ''));
$action = trim((string) ($input['lab_action'] ?? ''));
$params = is_array($input['params'] ?? null) ? $input['params'] : [];

if ($apiId === '' || $action === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'api_id y lab_action son obligatorios.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = BarsApiLabService::run($apiId, $action, $params);
$result['api_id'] = $apiId;
$result['lab_action'] = $action;

if (empty($result['ok'])) {
    http_response_code(422);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
