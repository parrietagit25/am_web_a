<?php
declare(strict_types=1);

/**
 * Helpers de Instagram para tarjetas de equipo.
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

    if (preg_match('#instagram\.com/([^/?#]+)#i', $raw, $m)) {
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
