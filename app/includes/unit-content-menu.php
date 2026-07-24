<?php
/**
 * Menú «Contenido» inyectado en el navbar de cada unidad de negocio.
 */

/**
 * @param array{news: bool, blog: bool, latest: bool}|null $enabledItems null = todos visibles (legacy)
 * @return array{label: string, link: string, submenu: list<array{label: string, link: string}>}|null
 */
function unit_content_nav_menu_item(string $unitKey, ?array $enabledItems = null): ?array
{
    $query = $unitKey !== 'rentacar' ? ('?unit=' . rawurlencode($unitKey)) : '';

    $submenu = [];
    if ($enabledItems === null || !empty($enabledItems['news'])) {
        $submenu[] = ['label' => 'Noticias', 'link' => '/noticias.php' . $query];
    }
    if ($enabledItems === null || !empty($enabledItems['blog'])) {
        $submenu[] = ['label' => 'Blog', 'link' => '/blog.php' . $query];
    }
    if ($enabledItems === null || !empty($enabledItems['latest'])) {
        $submenu[] = ['label' => 'Novedades', 'link' => '/contenido-reciente.php' . $query];
    }

    if ($submenu === []) {
        return null;
    }

    return [
        'label' => 'CONTENIDO',
        'link' => '#',
        'submenu' => $submenu,
    ];
}

/**
 * Quita enlaces duplicados a blog/noticias/contenido de submenús existentes.
 *
 * @param list<array<string, mixed>> $menu
 * @return list<array<string, mixed>>
 */
function unit_content_strip_legacy_menu_links(array $menu): array
{
    $skip = ['blog', 'noticias', 'cont. reciente', 'contenido reciente', 'contenido más reciente', 'novedades'];

    foreach ($menu as &$item) {
        if (empty($item['submenu']) || !is_array($item['submenu'])) {
            continue;
        }
        $item['submenu'] = array_values(array_filter($item['submenu'], static function ($sub) use ($skip): bool {
            if (!is_array($sub)) {
                return false;
            }
            $label = mb_strtolower(trim((string) ($sub['label'] ?? '')), 'UTF-8');
            $link = mb_strtolower(trim((string) ($sub['link'] ?? '')), 'UTF-8');
            foreach ($skip as $needle) {
                if ($label === $needle || str_contains($label, $needle)) {
                    return false;
                }
            }
            if (str_contains($link, 'blog.php') || str_contains($link, 'noticias.php') || str_contains($link, 'contenido-reciente.php')) {
                return false;
            }

            return true;
        }));
    }
    unset($item);

    return $menu;
}

/**
 * Inserta «Contenido» antes de CONTACTOS (o al final si no hay contacto).
 * Respeta la configuración CMS nav_menu_enabled / nav_menu_items de la unidad.
 *
 * @param list<array<string, mixed>> $menu
 * @param array<string, mixed>|null $siteData
 * @return list<array<string, mixed>>
 */
function unit_content_inject_nav_menu(array $menu, string $unitKey, ?array $siteData = null): array
{
    $enabledItems = null;
    if (is_array($siteData)) {
        require_once __DIR__ . '/../services/UnitContentService.php';
        $navSettings = UnitContentService::getNavMenuSettings($siteData, $unitKey);
        if (!$navSettings['enabled']) {
            return $menu;
        }
        $enabledItems = $navSettings['items'];
    }

    $contentItem = unit_content_nav_menu_item($unitKey, $enabledItems);
    if ($contentItem === null) {
        return $menu;
    }

    // Solo deduplicar enlaces legacy cuando el menú CONTENIDO se va a mostrar.
    $menu = unit_content_strip_legacy_menu_links($menu);

    foreach ($menu as $item) {
        $label = mb_strtoupper(trim((string) ($item['label'] ?? '')), 'UTF-8');
        if ($label === 'CONTENIDO') {
            return $menu;
        }
    }

    $insertAt = count($menu);
    foreach ($menu as $index => $item) {
        $label = mb_strtoupper(trim((string) ($item['label'] ?? '')), 'UTF-8');
        if (str_contains($label, 'CONTACTO')) {
            $insertAt = $index;
            break;
        }
    }

    array_splice($menu, $insertAt, 0, [$contentItem]);

    return $menu;
}
