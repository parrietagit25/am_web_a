<?php
/**
 * Set site language and redirect back
 */
require_once __DIR__ . '/../config/config.php';

$lang = $_GET['lang'] ?? 'es';
if (!in_array($lang, ['es', 'en'], true)) {
    $lang = 'es';
}

$_SESSION['lang'] = $lang;
setcookie('am_lang', $lang, time() + 365 * 24 * 3600, '/');

$redirect = $_GET['redirect'] ?? '/';
if (!is_string($redirect) || $redirect === '' || str_starts_with($redirect, '//') || str_contains($redirect, '://')) {
    $redirect = '/';
}

header('Location: ' . $redirect);
exit;
