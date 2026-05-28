<?php
/**
 * Automarket Configuration File
 */

// Error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/i18n.php';

// Timezone setup
date_default_timezone_set('America/Panama');

// Define API environment constants
define('AUTOMARKET_API_URL', 'https://automarket-rentacar-fme3z.ondigitalocean.app/api/partner/availability');
define('AUTOMARKET_PARTNER_USER', 'dolPanamaRW');
define('AUTOMARKET_PARTNER_PASS', 'VfsbJpYp');

// Pipedrive CRM config
define('PIPEDRIVE_API_TOKEN', 'c54eb92479c31269ab3c6cec2e3d38e162eacf94');
define('PIPEDRIVE_COMPANY_DOMAIN', 'grupopcr');
define('PIPEDRIVE_LEASING_PIPELINE_ID', null);
define('PIPEDRIVE_LEASING_STAGE_ID', null);

// Admin Dashboard Credentials
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'automarket2026');

// Database Configuration (Uncomment and configure for production MySQL)
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'automarketdev');
// define('DB_USER', 'root');
// define('DB_PASS', '');


/**
 * XSS prevention helper. Escapes input for safe printing in HTML context.
 * 
 * @param string $value
 * @return string
 */
function esc($value) {
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Log helper for application actions
 * 
 * @param string $message
 * @param string $level
 */
function am_log($message, $level = 'INFO') {
    $logDir = __DIR__ . '/../storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/app.log';
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] [$level] $message" . PHP_EOL, FILE_APPEND);
}

// Resend API Key for Email Notifications
define('RESEND_API_KEY', 're_PSZJTUqL_Ct4EteRZkRSJcUQhPo3oXXhM'); // Replace with your actual Resend API Key
