<?php
/**
 * Menú «Contenido» inyectado en el navbar de cada unidad de negocio.
 */

/** @return array{label: string, link: string, submenu: list<array{label: string, link: string}>} */
function unit_content_nav_menu_item(string $unitKey): array
{
    $query = $unitKey !== 'rentacar' ? ('?unit=' . rawurlencode($unitKey)) : '';

    return [
        'label' => 'CONTENIDO',
        'link' => '#',
        'submenu' => [
            ['label' => 'Noticias', 'link' => '/noticias.php' . $query],
            ['label' => 'Blog', 'link' => '/blog.php' . $query],
            ['label' => 'Cont. Reciente', 'link' => '/contenido-reciente.php' . $query],
        ],
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
    $skip = ['blog', 'noticias', 'cont. reciente', 'contenido reciente', 'contenido más reciente'];

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
 *
 * @param list<array<string, mixed>> $menu
 * @return list<array<string, mixed>>
 */
function unit_content_inject_nav_menu(array $menu, string $unitKey): array
{
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

    $contentItem = unit_content_nav_menu_item($unitKey);
    array_splice($menu, $insertAt, 0, [$contentItem]);

    return $menu;
}
