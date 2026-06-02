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

function t_unit(string $unitKey, string $defaultLabel): string {
    return t(TranslationService::unitKeyFor($unitKey), $defaultLabel);
}

function t_cta(string $ctaText): string {
    $map = [
        'Buscar vehículo' => 'cta.buscar_vehiculo',
        'Ver Flota' => 'cta.ver_flota',
        'Ver inventario' => 'cta.ver_inventario',
        'Cotizar Leasing' => 'cta.cotizar_leasing',
    ];
    $key = $map[trim($ctaText)] ?? null;
    return $key ? t($key, $ctaText) : $ctaText;
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
