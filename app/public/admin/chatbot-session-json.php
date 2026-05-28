<?php
/**
 * JSON detalle de sesión chatbot (solo admin autenticado).
 */
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/ChatbotSessionService.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit;
}

$id = (int) ($_GET['session_id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
    exit;
}

$svc = new ChatbotSessionService();
$session = $svc->getSession($id);
if (!$session) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Sesión no encontrada.']);
    exit;
}

$units = require __DIR__ . '/../../config/business-units.php';
$unitKey = $session['active_unit'] ?? '';
$unitLabel = $unitKey !== '' ? ($units[$unitKey]['label'] ?? $unitKey) : '—';

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'session' => [
        'id' => (int) $session['id'],
        'session_token' => $session['session_token'] ?? '',
        'lang' => $session['lang'] ?? '',
        'active_unit' => $unitLabel,
        'page_url' => $session['page_url'] ?? '',
        'message_count' => (int) ($session['message_count'] ?? 0),
        'model_used' => $session['model_used'] ?? '',
        'ip_address' => $session['ip_address'] ?? '',
        'user_agent' => $session['user_agent'] ?? '',
        'created_at' => $session['created_at'] ?? '',
        'updated_at' => $session['updated_at'] ?? '',
        'ended_at' => $session['ended_at'] ?? '',
        'messages' => $svc->getMessages($id),
    ],
], JSON_UNESCAPED_UNICODE);
