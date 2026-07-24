<?php
/**
 * Acceso al laboratorio RAC (/lab/*).
 * Fuera del admin y del sitio público CMS.
 */
declare(strict_types=1);

function lab_rac_secret(): string
{
    return defined('LAB_RAC_SECRET') ? trim((string) LAB_RAC_SECRET) : '';
}

function lab_rac_host_is_local(): bool
{
    $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
    $host = preg_replace('/:\d+$/', '', $host) ?? $host;

    return in_array($host, ['localhost', '127.0.0.1', 'test.automarket.com.pa'], true);
}

function lab_rac_is_unlocked(): bool
{
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }

    return !empty($_SESSION['lab_rac_ok']);
}

function lab_rac_unlock(string $key): bool
{
    $secret = lab_rac_secret();
    if ($secret === '') {
        // Sin secreto: solo localhost/test.
        if (!lab_rac_host_is_local()) {
            return false;
        }
        $_SESSION['lab_rac_ok'] = true;
        return true;
    }

    if (!hash_equals($secret, $key)) {
        return false;
    }
    $_SESSION['lab_rac_ok'] = true;

    return true;
}

/**
 * Gate HTML/API. Si no hay acceso, termina la request.
 */
function lab_rac_require_access(): void
{
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }

    $secret = lab_rac_secret();
    $key = trim((string) ($_GET['key'] ?? $_POST['key'] ?? ''));
    if ($key === '' && isset($_SERVER['HTTP_X_LAB_KEY'])) {
        $key = trim((string) $_SERVER['HTTP_X_LAB_KEY']);
    }

    if ($key !== '' && lab_rac_unlock($key)) {
        return;
    }

    if (lab_rac_is_unlocked()) {
        return;
    }

    // Auto-unlock en local si no hay secreto configurado.
    if ($secret === '' && lab_rac_host_is_local() && lab_rac_unlock('')) {
        return;
    }

    $wantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
        || str_ends_with((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '-api.php');

    if ($wantsJson) {
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok' => false,
            'error' => 'Lab bloqueado. Abre /lab/rac-ciclo.php?key=TU_LAB_RAC_SECRET',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(401);
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    $hasSecret = $secret !== '';
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Lab RAC — acceso</title>';
    echo '<meta name="robots" content="noindex,nofollow">';
    echo '<style>body{font-family:system-ui;background:#0b1220;color:#e8eefc;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0}';
    echo '.box{background:#152238;border:1px solid #2a3b5c;border-radius:16px;padding:28px;max-width:420px;width:92%}';
    echo 'input,button{width:100%;padding:12px;border-radius:8px;border:0;margin-top:8px;box-sizing:border-box}';
    echo 'button{background:#c51f17;color:#fff;font-weight:700;cursor:pointer}</style></head><body><div class="box">';
    echo '<h1 style="margin:0 0 8px;font-size:1.25rem">Lab RAC — ciclo de reserva</h1>';
    echo '<p style="opacity:.8;font-size:.9rem">Sandbox fuera del sitio y del admin. ';
    echo $hasSecret
        ? 'Ingresa el valor de <code>LAB_RAC_SECRET</code>.'
        : 'Define <code>LAB_RAC_SECRET</code> en config.php o úsalo solo en localhost.';
    echo '</p><form method="get"><input type="password" name="key" placeholder="Clave lab" required autofocus>';
    echo '<button type="submit">Entrar</button></form></div></body></html>';
    exit;
}
