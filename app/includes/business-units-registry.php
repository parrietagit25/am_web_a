<?php
/**
 * Unidades de negocio: oficiales (config) + personalizadas (site_data.json).
 */

/** @return list<string> */
function am_builtin_business_unit_keys(): array
{
    static $keys = null;
    if ($keys === null) {
        $keys = array_keys(require __DIR__ . '/../config/business-units.php');
    }

    return $keys;
}

function am_is_builtin_business_unit(string $key): bool
{
    return in_array($key, am_builtin_business_unit_keys(), true);
}

function am_normalize_business_unit_key(string $raw): string
{
    $key = strtolower(trim($raw));
    $key = preg_replace('/[^a-z0-9_]+/', '_', $key);
    $key = trim((string) $key, '_');

    return $key !== '' ? $key : 'unidad';
}

/** @return array<string, int> */
function am_default_business_unit_sort_orders(): array
{
    return [
        'rentacar' => 10,
        'seminuevos' => 20,
        'leasing' => 30,
        'renting' => 40,
        'taller' => 50,
    ];
}

/**
 * @param array<string, mixed> $unit
 * @return array<string, mixed>
 */
function am_normalize_custom_business_unit(string $key, array $unit): array
{
    require_once __DIR__ . '/../services/UnitContentService.php';

    $label = trim((string) ($unit['label'] ?? strtoupper($key)));
    $slug = trim((string) ($unit['slug'] ?? ''));
    if ($slug === '') {
        $slug = 'unidad.php?u=' . rawurlencode($key);
    }

    return [
        'key' => $key,
        'label' => $label !== '' ? $label : strtoupper($key),
        'slug' => $slug,
        'color' => trim((string) ($unit['color'] ?? '#1f347f')) ?: '#1f347f',
        'logo_title' => trim((string) ($unit['logo_title'] ?? 'Automarket')) ?: 'Automarket',
        'logo_subtitle' => trim((string) ($unit['logo_subtitle'] ?? $label)) ?: $label,
        'menu' => is_array($unit['menu'] ?? null) ? $unit['menu'] : [],
        'activeClass' => trim((string) ($unit['activeClass'] ?? ('active-' . $key))),
        'heroTitle' => trim((string) ($unit['heroTitle'] ?? '')),
        'heroSubtitle' => trim((string) ($unit['heroSubtitle'] ?? '')),
        'ctaText' => trim((string) ($unit['ctaText'] ?? '')),
        'ctaLink' => trim((string) ($unit['ctaLink'] ?? '')),
        'sort_order' => intval($unit['sort_order'] ?? 60),
        'hero_image_url' => trim((string) ($unit['hero_image_url'] ?? '')),
        'body_html' => (string) ($unit['body_html'] ?? ''),
        'pages' => is_array($unit['pages'] ?? null) ? $unit['pages'] : [],
        'content' => is_array($unit['content'] ?? null) ? $unit['content'] : UnitContentService::defaultContentNode(),
        'is_custom' => true,
    ];
}

/**
 * @param array<string, array<string, mixed>> $units
 * @return array<string, array<string, mixed>>
 */
function am_sort_business_units(array $units): array
{
    uasort($units, static function (array $a, array $b): int {
        $order = intval($a['sort_order'] ?? 99) <=> intval($b['sort_order'] ?? 99);
        if ($order !== 0) {
            return $order;
        }

        return strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
    });

    return $units;
}

/**
 * @param array<string, array<string, mixed>> $stored
 * @return array<string, array<string, mixed>>
 */
function am_merge_business_units(array $stored): array
{
    $defaults = require __DIR__ . '/../config/business-units.php';
    $sortDefaults = am_default_business_unit_sort_orders();
    $merged = [];

    foreach ($defaults as $key => $default) {
        $unit = (isset($stored[$key]) && is_array($stored[$key]))
            ? array_merge($default, $stored[$key])
            : $default;
        $unit['key'] = $key;
        $unit['is_custom'] = false;
        if (!isset($unit['sort_order'])) {
            $unit['sort_order'] = $sortDefaults[$key] ?? 99;
        }
        $merged[$key] = $unit;
    }

    foreach ($stored as $key => $unit) {
        if (!is_string($key) || am_is_builtin_business_unit($key) || !is_array($unit)) {
            continue;
        }
        $normalizedKey = am_normalize_business_unit_key($key);
        if ($normalizedKey === '' || isset($merged[$normalizedKey])) {
            continue;
        }
        $merged[$normalizedKey] = am_normalize_custom_business_unit($normalizedKey, $unit);
    }

    return am_sort_business_units($merged);
}

/** @param array<string, array<string, mixed>> $units */
function am_custom_business_units(array $units): array
{
    $custom = [];
    foreach ($units as $key => $unit) {
        if (!is_string($key) || !is_array($unit) || am_is_builtin_business_unit($key)) {
            continue;
        }
        $custom[$key] = $unit;
    }

    return am_sort_business_units($custom);
}

function am_normalize_custom_unit_page_slug(string $raw): string
{
    $slug = strtolower(trim($raw));
    $slug = preg_replace('/[^a-z0-9_-]+/', '-', $slug);
    $slug = trim((string) $slug, '-');

    return $slug;
}

function am_parse_custom_unit_page_slug_from_link(string $link, string $unitKey): ?string
{
    $link = trim($link);
    if ($link === '' || stripos($link, 'unidad.php') === false) {
        return null;
    }

    $path = parse_url($link, PHP_URL_PATH) ?: $link;
    if (!preg_match('/unidad\.php$/i', basename((string) $path))) {
        return null;
    }

    $query = parse_url($link, PHP_URL_QUERY);
    if (!is_string($query) || $query === '') {
        return null;
    }

    parse_str($query, $params);
    $u = am_normalize_business_unit_key((string) ($params['u'] ?? ''));
    if ($u !== $unitKey) {
        return null;
    }

    $page = am_normalize_custom_unit_page_slug((string) ($params['p'] ?? ''));
    if ($page === '') {
        return null;
    }

    return $page;
}

function am_is_external_menu_link(string $link): bool
{
    $link = trim($link);

    return $link !== '' && preg_match('/^https?:\/\//i', $link) === 1;
}

function am_custom_unit_internal_page_url(string $unitKey, string $pageSlug): string
{
    return 'unidad.php?u=' . rawurlencode($unitKey) . '&p=' . rawurlencode($pageSlug);
}

/**
 * @param array<string, mixed> $item
 */
function am_derive_custom_unit_page_slug_from_menu_item(array $item, string $unitKey): ?string
{
    $link = trim((string) ($item['link'] ?? ''));
    if ($link === '' || $link === '#') {
        return null;
    }
    if (am_is_external_menu_link($link)) {
        return null;
    }

    $fromUnidad = am_parse_custom_unit_page_slug_from_link($link, $unitKey);
    if ($fromUnidad !== null && $fromUnidad !== '') {
        return $fromUnidad;
    }

    $path = parse_url($link, PHP_URL_PATH);
    $basename = basename((string) ($path ?: $link));
    if (preg_match('/^unidad\.php$/i', $basename)) {
        return null;
    }
    if (preg_match('/^([a-z0-9_-]+)\.php$/i', $basename, $m)) {
        return am_normalize_custom_unit_page_slug($m[1]);
    }
    if (preg_match('/^\/?([a-z0-9_-]+)\/?$/i', $link, $m)) {
        return am_normalize_custom_unit_page_slug($m[1]);
    }

    $label = trim((string) ($item['label'] ?? ''));
    if ($label !== '') {
        $fromLabel = am_normalize_custom_unit_page_slug(
            preg_replace('/[^a-z0-9]+/', '-', strtolower($label))
        );

        return $fromLabel !== '' ? $fromLabel : null;
    }

    return null;
}

/**
 * @param array<string, mixed> $item
 * @return array<string, mixed>
 */
function am_normalize_custom_unit_menu_item(array $item, string $unitKey): array
{
    if (am_is_builtin_business_unit($unitKey)) {
        return $item;
    }

    $submenu = [];
    if (!empty($item['submenu']) && is_array($item['submenu'])) {
        foreach ($item['submenu'] as $sub) {
            if (is_array($sub)) {
                $submenu[] = am_normalize_custom_unit_menu_item($sub, $unitKey);
            }
        }
        $item['submenu'] = $submenu;
    }

    $link = trim((string) ($item['link'] ?? ''));
    if (!empty($submenu) && ($link === '' || $link === '#')) {
        $item['link'] = '#';

        return $item;
    }

    $slug = am_derive_custom_unit_page_slug_from_menu_item($item, $unitKey);
    if ($slug !== null && $slug !== '') {
        $item['link'] = am_custom_unit_internal_page_url($unitKey, $slug);
    }

    return $item;
}

/**
 * @return array<string, array{slug: string, label: string, tab_slug: string}>
 */
function am_custom_unit_editable_pages(array $unit, string $unitKey): array
{
    $pages = [
        '__main__' => [
            'slug' => '',
            'label' => 'Principal',
            'tab_slug' => 'unit-' . $unitKey,
        ],
    ];

    $menuItems = [];
    foreach ($unit['menu'] ?? [] as $item) {
        if (is_array($item)) {
            $menuItems[] = $item;
            foreach ($item['submenu'] ?? [] as $sub) {
                if (is_array($sub)) {
                    $menuItems[] = $sub;
                }
            }
        }
    }

    foreach ($menuItems as $item) {
        $link = trim((string) ($item['link'] ?? ''));
        $hasSubmenu = !empty($item['submenu']) && is_array($item['submenu']);
        if ($hasSubmenu && ($link === '' || $link === '#')) {
            continue;
        }

        $pageSlug = am_derive_custom_unit_page_slug_from_menu_item($item, $unitKey);
        if ($pageSlug === null || $pageSlug === '' || isset($pages[$pageSlug])) {
            continue;
        }
        $pages[$pageSlug] = [
            'slug' => $pageSlug,
            'label' => trim((string) ($item['label'] ?? $pageSlug)),
            'tab_slug' => 'unit-' . $unitKey . '-' . $pageSlug,
        ];
    }

    return $pages;
}

/**
 * @return array{heroTitle: string, heroSubtitle: string, hero_image_url: string, body_html: string}
 */
function am_custom_unit_page_content(array $unit, string $pageSlug): array
{
    if ($pageSlug === '') {
        return [
            'heroTitle' => trim((string) ($unit['heroTitle'] ?? '')),
            'heroSubtitle' => trim((string) ($unit['heroSubtitle'] ?? '')),
            'hero_image_url' => trim((string) ($unit['hero_image_url'] ?? '')),
            'body_html' => (string) ($unit['body_html'] ?? ''),
        ];
    }

    $page = is_array($unit['pages'][$pageSlug] ?? null) ? $unit['pages'][$pageSlug] : [];

    return [
        'heroTitle' => trim((string) ($page['heroTitle'] ?? $page['label'] ?? '')),
        'heroSubtitle' => trim((string) ($page['heroSubtitle'] ?? '')),
        'hero_image_url' => trim((string) ($page['hero_image_url'] ?? '')),
        'body_html' => (string) ($page['body_html'] ?? ''),
    ];
}

/** @deprecated Use am_merge_business_units() */
function am_filter_builtin_business_units(array $units): array
{
    return am_merge_business_units($units);
}

/**
 * @param array<string, mixed> $siteData
 */
function am_ensure_business_units_sort_order(array &$siteData): bool
{
    if (!isset($siteData['global']['business_units']) || !is_array($siteData['global']['business_units'])) {
        return false;
    }

    $modified = false;
    $sortDefaults = am_default_business_unit_sort_orders();
    foreach ($siteData['global']['business_units'] as $key => &$unit) {
        if (!is_array($unit)) {
            continue;
        }
        if (!isset($unit['sort_order'])) {
            $unit['sort_order'] = $sortDefaults[$key] ?? 60;
            $modified = true;
        }
    }
    unset($unit);

    return $modified;
}

/** @param array<string, mixed> $siteData */
function am_strip_custom_business_units(array &$siteData): bool
{
    return false;
}

/**
 * @param array<string, mixed> $posted
 * @param array<string, array<string, mixed>> $existing
 * @return array<string, array<string, mixed>>
 */
function am_build_business_units_from_post(array $posted, array $existing, array $orderKeys): array
{
    $mergedExisting = am_merge_business_units($existing);
    $updated = [];
    $seen = [];

    foreach ($orderKeys as $rawKey) {
        $key = am_normalize_business_unit_key((string) $rawKey);
        if ($key === '' || isset($seen[$key]) || !isset($posted[$key]) || !is_array($posted[$key])) {
            continue;
        }
        $seen[$key] = true;
        $unitData = $posted[$key];
        $base = $mergedExisting[$key] ?? am_normalize_custom_business_unit($key, []);

        $unit = $base;
        $unit['label'] = trim((string) ($unitData['label'] ?? $base['label']));
        $unit['logo_subtitle'] = trim((string) ($unitData['logo_subtitle'] ?? $base['logo_subtitle']));
        $unit['color'] = trim((string) ($unitData['color'] ?? $base['color']));
        $unit['heroTitle'] = trim((string) ($unitData['heroTitle'] ?? $base['heroTitle'] ?? ''));
        $unit['heroSubtitle'] = trim((string) ($unitData['heroSubtitle'] ?? $base['heroSubtitle'] ?? ''));
        $unit['sort_order'] = (count($updated) + 1) * 10;

        if (am_is_builtin_business_unit($key)) {
            $unit['is_custom'] = false;
            $unit['slug'] = $base['slug'];
            $unit['activeClass'] = $base['activeClass'] ?? ('active-' . $key);
        } else {
            $unit['is_custom'] = true;
            $unit['key'] = $key;
            $slug = trim((string) ($unitData['slug'] ?? $base['slug'] ?? ''));
            $unit['slug'] = $slug !== '' ? $slug : ('unidad.php?u=' . rawurlencode($key));
            $unit['activeClass'] = 'active-' . $key;
            $unit['logo_title'] = $base['logo_title'] ?? 'Automarket';
            $unit['ctaText'] = trim((string) ($unitData['ctaText'] ?? $base['ctaText'] ?? ''));
            $unit['ctaLink'] = trim((string) ($unitData['ctaLink'] ?? $base['ctaLink'] ?? ''));
            $unit['hero_image_url'] = trim((string) ($base['hero_image_url'] ?? ''));
            $unit['body_html'] = (string) ($base['body_html'] ?? '');
            $unit['pages'] = is_array($base['pages'] ?? null) ? $base['pages'] : [];
        }

        if (isset($unitData['menu']) && is_array($unitData['menu'])) {
            $parsedMenu = [];
            foreach ($unitData['menu'] as $menuItem) {
                $label = trim((string) ($menuItem['label'] ?? ''));
                $link = trim((string) ($menuItem['link'] ?? ''));
                if ($label === '' || $link === '') {
                    continue;
                }
                $newItem = [
                    'label' => $label,
                    'link' => $link,
                ];
                if (isset($menuItem['submenu']) && is_array($menuItem['submenu'])) {
                    $submenu = [];
                    foreach ($menuItem['submenu'] as $sub) {
                        $subLabel = trim((string) ($sub['label'] ?? ''));
                        $subLink = trim((string) ($sub['link'] ?? ''));
                        if ($subLabel !== '' && $subLink !== '') {
                            $submenu[] = [
                                'label' => $subLabel,
                                'link' => $subLink,
                            ];
                        }
                    }
                    if (!empty($submenu)) {
                        $newItem['submenu'] = $submenu;
                    }
                }
                $parsedMenu[] = $newItem;
            }
            $parsedMenu = array_map(
                static fn (array $menuItem): array => am_normalize_custom_unit_menu_item($menuItem, $key),
                $parsedMenu
            );
            $unit['menu'] = $parsedMenu;
        }

        unset($unit['is_custom']);
        $updated[$key] = $unit;
    }

    foreach (am_builtin_business_unit_keys() as $builtinKey) {
        if (!isset($updated[$builtinKey]) && isset($mergedExisting[$builtinKey])) {
            $updated[$builtinKey] = $mergedExisting[$builtinKey];
        }
    }

    return am_sort_business_units($updated);
}

/** @param array<string, mixed> $businessUnits */
function am_normalize_all_custom_unit_menus(array &$businessUnits): bool
{
    $modified = false;
    foreach ($businessUnits as $key => &$unit) {
        if (!is_string($key) || am_is_builtin_business_unit($key) || !is_array($unit)) {
            continue;
        }
        if (!isset($unit['menu']) || !is_array($unit['menu'])) {
            continue;
        }
        $normalized = array_map(
            static fn (array $menuItem): array => am_normalize_custom_unit_menu_item($menuItem, $key),
            $unit['menu']
        );
        if (json_encode($normalized) !== json_encode($unit['menu'])) {
            $unit['menu'] = $normalized;
            $modified = true;
        }
    }
    unset($unit);

    return $modified;
}
