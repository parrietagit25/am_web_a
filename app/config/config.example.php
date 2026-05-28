<?php
/**
 * Automarket — plantilla de configuración
 * En el servidor: copiar este archivo como config.php y completar valores reales.
 */

// Error reporting (producción: desactivar display_errors)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/i18n.php';

date_default_timezone_set('America/Panama');

define('AUTOMARKET_API_URL', 'https://tu-api.ejemplo.com/api/partner/availability');
define('AUTOMARKET_PARTNER_USER', 'TU_USUARIO');
define('AUTOMARKET_PARTNER_PASS', 'TU_PASSWORD');

define('PIPEDRIVE_API_TOKEN', 'TU_TOKEN_PIPEDRIVE');
define('PIPEDRIVE_COMPANY_DOMAIN', 'tu-dominio');
define('PIPEDRIVE_LEASING_PIPELINE_ID', null);
define('PIPEDRIVE_LEASING_STAGE_ID', null);

define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'CAMBIAR_PASSWORD_SEGURO');

// define('DB_HOST', 'localhost');
// define('DB_NAME', 'automarket');
// define('DB_USER', 'root');
// define('DB_PASS', '');

function esc($value) {
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function am_log($message, $level = 'INFO') {
    $logDir = __DIR__ . '/../storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/app.log';
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] [$level] $message" . PHP_EOL, FILE_APPEND);
}

define('RESEND_API_KEY', 'TU_RESEND_API_KEY');

// Webhook n8n — leads Seminuevos → Pipedrive (pipeline 21)
define('N8N_SEMINUEVOS_WEBHOOK_URL', 'https://n8n.grupopcr.com.pa/webhook/seminuevos');
define('N8N_SEMINUEVOS_JWT_SECRET', 'TU_JWT_SECRET_N8N');
// Opcional: si IT entrega un Bearer fijo, úsalo en lugar de firmar con el secret:
// define('N8N_SEMINUEVOS_JWT_TOKEN', '');
