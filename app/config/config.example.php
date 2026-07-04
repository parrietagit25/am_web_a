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

// Base URL del backend Rent-a-Car (handoff partner API)
define('AUTOMARKET_API_BASE_URL', 'https://automarket-rentacar-fme3z.ondigitalocean.app');
// Alternativa legacy: URL completa del endpoint availability
// define('AUTOMARKET_API_URL', 'https://automarket-rentacar-fme3z.ondigitalocean.app/api/partner/availability');
define('AUTOMARKET_PARTNER_USER', 'TU_USUARIO');
define('AUTOMARKET_PARTNER_PASS', 'TU_PASSWORD');
// Opcional: base para imágenes de vehículos (por defecto se deduce del API)
// define('AUTOMARKET_PARTNER_IMAGE_BASE', 'https://automarket-rentacar-fme3z.ondigitalocean.app');

define('PIPEDRIVE_API_TOKEN', 'TU_TOKEN_PIPEDRIVE');
define('PIPEDRIVE_COMPANY_DOMAIN', 'tu-dominio');
define('PIPEDRIVE_LEASING_PIPELINE_ID', null);
define('PIPEDRIVE_LEASING_STAGE_ID', null);

define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'CAMBIAR_PASSWORD_SEGURO');

// Sync inventario seminuevos (proceso Python). Token por defecto también en InventorySyncAuth.php
define('INVENTORY_SYNC_TOKEN', 'SI5dGxz/2/AqWkOYuz6t4r3KYGbqGxOj3MhT3T/hp!J6Du9ko=6ITrMBNJU5WzUj?ep3VWb8gwxGv9RPgq?r0y=A8gdF2cJ!fWil1G??6voWqJvRdip1M?0u/sol-ON?');
define('INVENTORY_SYNC_MIN_VEHICLES', 50);

// Google reCAPTCHA v2 checkbox — https://www.google.com/recaptcha/admin (tipo «Casilla de verificación»)
define('RECAPTCHA_SITE_KEY', '');
define('RECAPTCHA_SECRET_KEY', '');
define('RECAPTCHA_MIN_SCORE', 0.5);

// Bypass captcha solo reserva RAC en localhost (desarrollo local sin SSL).
// Activar únicamente en app/config/config.php local — NO commitear config.php.
// define('RAC_LOCAL_CAPTCHA_BYPASS', true);

// DB_REQUIRE_MYSQL: si es true, Database.php lanza RuntimeException en vez de
// caer a SQLite cuando falta configuración o falla la conexión a MySQL.
// define('DB_REQUIRE_MYSQL', false);
// define('DB_HOST', 'db');
// define('DB_NAME', 'automarket');
// define('DB_USER', 'automarket_app');
// define('DB_PASS', 'change_me');

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

// OpenAI — Chatbot IA en el sitio público (sk-...)
define('OPENAI_API_KEY', '');

// Webhook n8n — leads Seminuevos → Pipedrive (pipeline 21)
define('N8N_SEMINUEVOS_WEBHOOK_URL', 'https://n8n.grupopcr.com.pa/webhook/seminuevos');
define('N8N_SEMINUEVOS_JWT_SECRET', 'TU_JWT_SECRET_N8N');
// Opcional: si IT entrega un Bearer fijo, úsalo en lugar de firmar con el secret:
// define('N8N_SEMINUEVOS_JWT_TOKEN', '');

// Webhook n8n — AMCorp / Leasing Operativo → Pipedrive corporativo
define('N8N_AMCORP_WEBHOOK_URL', 'https://n8n.grupopcr.com.pa/webhook/amcorp-lead');
// Si es el mismo secret que Seminuevos, basta con N8N_SEMINUEVOS_JWT_SECRET; o define uno propio:
define('N8N_AMCORP_JWT_SECRET', 'TU_JWT_SECRET_N8N');
// define('N8N_AMCORP_JWT_TOKEN', '');

// Webhook n8n — Renting → Pipedrive (Pipeline 7)
define('N8N_RENTING_WEBHOOK_URL', 'https://n8n.grupopcr.com.pa/webhook/renting');
define('N8N_RENTING_JWT_SECRET', 'TU_JWT_SECRET_N8N');
// define('N8N_RENTING_JWT_TOKEN', '');

// BARS/RW Web — consulta SOAP directa de tarifas (prueba aislada AM-RAC-BARS-TEST-0A)
// define('BARS_RW_ENDPOINT', 'https://rwwebe.barscloud.com:8716/dolpanama/soap');
// define('BARS_RW_USER', 'TU_USUARIO_BARS');
// define('BARS_RW_PASSWORD', 'TU_PASSWORD_BARS');
// define('BARS_RW_MESSAGE_PASSWORD', 'TU_MESSAGE_PASSWORD_BARS');
// define('BARS_RW_REQUESTOR_ID', 'website');
// define('BARS_RW_RATE_QUALIFIER', 'WEB');

// Powertranz / First Atlantic Commerce — pagos HPP/3DS (AM-RAC-PAY-POWERTRANZ-0A/0B)
// define('POWERTRANZ_ENABLED', true);
// define('POWERTRANZ_ENV', 'staging');
// define('POWERTRANZ_BASE_URL', 'https://staging.ptranz.com');
// define('POWERTRANZ_ID', '');
// define('POWERTRANZ_PASSWORD', '');
// define('POWERTRANZ_MERCHANT_ID', '');
// define('POWERTRANZ_CURRENCY', '840');
// define('POWERTRANZ_CURRENCY_CODE', '840');
// define('POWERTRANZ_MODE', 'sale');
// define('POWERTRANZ_HPP_PAGE_SET', '');
// define('POWERTRANZ_HPP_PAGE_NAME', '');
// define('POWERTRANZ_TIMEOUT_SECONDS', 45);
// define('POWERTRANZ_MERCHANT_RESPONSE_URL', 'https://test.automarket.com.pa/api/powertranz-return.php');
// define('POWERTRANZ_MERCHANT_REDIRECT_URL', '');
