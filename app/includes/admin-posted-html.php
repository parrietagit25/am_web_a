<?php
/**
 * Decodifica HTML enviado desde admin (texto plano o prefijo b64: para eludir WAF).
 */
function am_admin_decode_posted_html(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }

    if (strncmp($raw, 'b64:', 4) === 0) {
        $decoded = base64_decode(substr($raw, 4), true);
        return is_string($decoded) ? $decoded : '';
    }

    return $raw;
}
