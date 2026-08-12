<?php
/**
 * Decodifica HTML/JSON enviado desde admin (texto plano o prefijo b64: para eludir WAF).
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

/**
 * Decodifica en $_POST cualquier campo string con prefijo b64: (bypass Cloudflare WAF).
 *
 * @param array<string, mixed> $post
 */
function am_admin_decode_posted_fields(array &$post): void
{
    foreach ($post as $key => $value) {
        if (!is_string($value)) {
            continue;
        }
        if (strncmp($value, 'b64:', 4) !== 0) {
            continue;
        }
        $post[$key] = am_admin_decode_posted_html($value);
    }
}
