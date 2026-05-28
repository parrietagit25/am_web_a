<?php
/**
 * API: Chatbot IA (mensajes vía OpenAI, historial en sesión PHP).
 */
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ChatbotService.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Solicitud inválida.']);
    exit;
}

$action = trim($input['action'] ?? 'message');
$lang = current_lang();
$activeUnit = trim($input['active_unit'] ?? '');
$pageUrl = trim($input['page_url'] ?? '');

$chatbot = new ChatbotService();

if ($action === 'reset') {
    $chatbot->clearSession();
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Conversación reiniciada.']);
    exit;
}

if ($action === 'start_flow') {
    $flowId = trim($input['flow_id'] ?? '');
    $result = $chatbot->startGuideFlow(
        $flowId,
        $lang,
        $activeUnit !== '' ? $activeUnit : null,
        $pageUrl !== '' ? $pageUrl : null
    );
    if (!$result['ok']) {
        $code = (int) ($result['code'] ?? 400);
        http_response_code($code >= 400 && $code < 600 ? $code : 400);
        echo json_encode(['status' => 'error', 'message' => $result['error'] ?? 'Error']);
        exit;
    }
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'reply' => $result['reply'] ?? '',
        'flow' => $result['flow'] ?? null,
        'speak' => $result['speak'] ?? true,
        'completed' => $result['completed'] ?? false,
    ]);
    exit;
}

$message = trim($input['message'] ?? '');
$result = $chatbot->reply($message, $lang, $activeUnit !== '' ? $activeUnit : null, $pageUrl !== '' ? $pageUrl : null);

if (!$result['ok']) {
    $code = (int) ($result['code'] ?? 400);
    http_response_code($code >= 400 && $code < 600 ? $code : 400);
    echo json_encode([
        'status' => 'error',
        'message' => $result['error'] ?? 'Error desconocido.',
    ]);
    exit;
}

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'reply' => $result['reply'],
    'flow' => $result['flow'] ?? null,
    'speak' => $result['speak'] ?? true,
    'completed' => $result['completed'] ?? false,
    'reservation_code' => $result['reservation_code'] ?? null,
]);
