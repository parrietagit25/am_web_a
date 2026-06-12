<?php
/**
 * i18n bootstrap — load after config.php (session active)
 */
require_once __DIR__ . '/../services/TranslationService.php';

$translator = TranslationService::getInstance();

function t(string $key, ?string $fallback = null): string {
    return TranslationService::getInstance()->translate($key, $fallback);
}

function current_lang(): string {
    return TranslationService::getInstance()->getLang();
}

function t_menu(string $label): string {
    $key = TranslationService::menuKeyForLabel($label);
    return $key ? t($key, $label) : $label;
}

/** Primera letra mayúscula, resto minúsculas (español/inglés). */
function formatMenuSentenceCase(string $text): string {
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    $lower = mb_strtolower($text, 'UTF-8');
    return mb_strtoupper(mb_substr($lower, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($lower, 1, null, 'UTF-8');
}

/** Etiquetas de submenú: traducción + capitalización uniforme. */
function t_submenu(string $label): string {
    return formatMenuSentenceCase(t_menu($label));
}

function t_unit(string $unitKey, string $defaultLabel): string {
    return t(TranslationService::unitKeyFor($unitKey), $defaultLabel);
}

function t_cta(string $ctaText): string {
    $trimmed = trim($ctaText);
    $map = [
        'Buscar vehículo' => 'cta.buscar_vehiculo',
        'Ver Flota' => 'cta.ver_flota',
        'Ver inventario' => 'cta.ver_inventario',
        'Cotizar Leasing' => 'cta.cotizar_leasing',
    ];
    $key = $map[$trimmed] ?? null;
    if (!$key) {
        $upper = mb_strtoupper($trimmed, 'UTF-8');
        foreach ($map as $label => $translationKey) {
            if (mb_strtoupper($label, 'UTF-8') === $upper) {
                $key = $translationKey;
                break;
            }
        }
    }
    $text = $key ? t($key, $trimmed) : $trimmed;
    return mb_strtoupper($text, 'UTF-8');
}

function lang_url(string $lang): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($uri);
    $path = $parts['path'] ?? '/';
    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    $query['lang'] = $lang;
    return $path . '?' . http_build_query($query);
}
