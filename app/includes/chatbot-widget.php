<?php
/**
 * Widget flotante del chatbot IA (solo si está habilitado y configurado).
 */
if (!isset($globalSettings)) {
    if (!class_exists('ContentService')) {
        require_once __DIR__ . '/../services/ContentService.php';
    }
    $globalSettings = (new ContentService())->get('global');
}
require_once __DIR__ . '/../services/ChatbotService.php';

$chatbotConfig = ChatbotService::mergeConfig($globalSettings ?? []);
$chatbotPublic = ChatbotService::getPublicPayload($chatbotConfig, current_lang());

if (empty($chatbotPublic['enabled'])) {
    return;
}

$chatbotJson = htmlspecialchars(json_encode($chatbotPublic, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
$chatActiveUnit = isset($activeUnit) ? (string) $activeUnit : '';
?>
<link rel="stylesheet" href="/assets/css/chatbot.css?v=2">
<div id="am-chatbot-root"
     data-chatbot="<?php echo $chatbotJson; ?>"
     data-active-unit="<?php echo esc($chatActiveUnit); ?>"
     aria-live="polite"></div>
<script src="/assets/js/chatbot.js?v=2" defer></script>
