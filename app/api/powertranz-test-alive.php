<?php
/**
 * Prueba alive Powertranz — AM-RAC-PAY-POWERTRANZ-0A.
 * Solo super admin autenticado. JSON sin secretos.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/AdminUserService.php';
require_once __DIR__ . '/../services/PowertranzClient.php';
require_once __DIR__ . '/../includes/admin-auth.php';

AdminUserService::ensureSchema();

if (!AdminUserService::isLoggedIn() || !AdminUserService::isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado. Requiere super admin.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$client = new PowertranzClient();

if (!PowertranzClient::isEnabled()) {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'enabled' => false,
        'configured' => PowertranzClient::isConfigured(),
        'error' => 'Powertranz deshabilitado o sin credenciales en config.php.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = $client->alive();

echo json_encode([
    'ok' => (bool) ($result['ok'] ?? false),
    'http_code' => (int) ($result['http_code'] ?? 0),
    'enabled' => PowertranzClient::isEnabled(),
    'configured' => PowertranzClient::isConfigured(),
    'has_hpp_config' => $client->hasHppConfig(),
    'base_url' => $client->getBaseUrl(),
    'environment' => $client->getEnvironment(),
    'response' => $client->sanitizeResponse($result['data'] ?? null),
    'error' => $result['error'] ?? null,
], JSON_UNESCAPED_UNICODE);
