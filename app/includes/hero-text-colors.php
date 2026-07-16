<?php
/**
 * Colores administrables del título/subtítulo del hero (AM-ADJ-02).
 * Solo acepta hex #RGB / #RRGGBB; vacío = fallback CSS existente.
 */

/**
 * Normaliza un color hex a #RRGGBB o cadena vacía si es inválido.
 */
function am_normalize_hex_color(mixed $value): string
{
    if (!is_string($value)) {
        return '';
    }

    $raw = trim($value);
    if ($raw === '') {
        return '';
    }

    if (!preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $raw)) {
        return '';
    }

    $hex = substr($raw, 1);
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    return '#' . strtoupper($hex);
}

/**
 * Lee y normaliza un color desde un array de datos (clave opcional).
 */
function am_hero_text_color_from(array $data, string $key): string
{
    return am_normalize_hex_color($data[$key] ?? '');
}

/**
 * Atributo style="color: …" o cadena vacía (no altera clases CSS existentes).
 */
function am_hero_text_color_attr(mixed $value): string
{
    $color = am_normalize_hex_color($value);
    if ($color === '') {
        return '';
    }

    return ' style="color: ' . $color . ';"';
}

/**
 * Fragmento CSS inline sin atributo (p.ej. para concatenar styles).
 */
function am_hero_text_color_css(mixed $value): string
{
    $color = am_normalize_hex_color($value);
    if ($color === '') {
        return '';
    }

    return 'color: ' . $color . ';';
}

/**
 * Normaliza color desde POST y lo guarda solo si es válido; vacío limpia la clave.
 *
 * @param array<string, mixed> $target
 */
function am_apply_hero_text_color_from_post(array &$target, string $postKey, string $storageKey): void
{
    $normalized = am_normalize_hex_color($_POST[$postKey] ?? '');
    if ($normalized === '') {
        unset($target[$storageKey]);
        return;
    }
    $target[$storageKey] = $normalized;
}
