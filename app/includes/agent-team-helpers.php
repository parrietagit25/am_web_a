<?php
declare(strict_types=1);

/**
 * Helpers de Instagram / contacto para tarjetas de equipo.
 */

/**
 * Normaliza handle: acepta @user, user o URL de Instagram.
 */
function am_agent_instagram_handle(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }

    if (preg_match('~instagram\.com/([^/?#]+)~i', $raw, $m)) {
        $raw = (string) $m[1];
    }

    $raw = ltrim($raw, '@');
    $raw = preg_replace('/[^A-Za-z0-9._]/', '', $raw) ?? '';

    return $raw;
}

function am_agent_instagram_url(string $raw): string
{
    $handle = am_agent_instagram_handle($raw);
    if ($handle === '') {
        return '';
    }

    return 'https://www.instagram.com/' . rawurlencode($handle) . '/';
}

/**
 * @param array<string, mixed> $agent
 * @return array{handle: string, url: string, display: string}
 */
function am_agent_instagram_meta(array $agent): array
{
    $handle = am_agent_instagram_handle((string) ($agent['instagram'] ?? ''));

    return [
        'handle' => $handle,
        'url' => $handle !== '' ? am_agent_instagram_url($handle) : '',
        'display' => $handle !== '' ? '@' . $handle : '',
    ];
}

/**
 * Clasifica longitud del correo para tipografía del overlay.
 *
 * @return 'short'|'medium'|'long'
 */
function am_agent_email_length_class(string $email): string
{
    $len = strlen(trim($email));
    if ($len <= 22) {
        return 'short';
    }
    if ($len <= 32) {
        return 'medium';
    }

    return 'long';
}

/**
 * Líneas de widgets (círculos) con fallback si el CMS tiene un párrafo antiguo.
 *
 * @return list<string>
 */
function am_team_highlight_lines(?string $highlightsStr): array
{
    $defaults = [
        '**4 Sucursales** a nivel Nacional.',
        '**Equipo de Ventas** especializado.',
        'Asesoría en **Financiamiento y Seguros.**',
        '**Respaldo y Garantía.**',
    ];

    $raw = trim((string) $highlightsStr);
    if ($raw === '') {
        return $defaults;
    }

    $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: [])));
    if ($lines === []) {
        return $defaults;
    }

    // Contenido legacy: 1–2 párrafos largos en highlights → no usar como widgets
    $joinedLen = strlen(implode(' ', $lines));
    if (count($lines) < 2 || (count($lines) <= 2 && $joinedLen > 180)) {
        return $defaults;
    }

    return array_slice($lines, 0, 8);
}

/**
 * Escapa y convierte **texto** en span rojo bold (widgets).
 * Usa | para salto de línea dentro del widget.
 */
function am_team_highlight_html(string $line): string
{
    $normalized = str_replace('|', "\n", $line);
    $escaped = htmlspecialchars($normalized, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $withBreaks = nl2br($escaped, false);

    return (string) preg_replace(
        '/\*\*(.+?)\*\*/',
        '<span class="text-danger fw-bold">$1</span>',
        $withBreaks
    );
}
