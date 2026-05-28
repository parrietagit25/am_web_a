<?php
/**
 * Acciones POST admin — Chatbot IA
 */

require_once __DIR__ . '/../services/ChatbotSessionService.php';

if ($action === 'delete_chatbot_session') {
    $sessionId = (int) ($_POST['session_id'] ?? 0);
    $svc = new ChatbotSessionService();
    if ($sessionId > 0 && $svc->deleteSession($sessionId)) {
        $successMsg = 'Sesión de chatbot eliminada.';
    } else {
        $errorMsg = 'No se pudo eliminar la sesión.';
    }
}
