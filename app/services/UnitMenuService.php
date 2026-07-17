<?php
/**
 * Resolución y validación del menú secundario por unidad de negocio.
 */
declare(strict_types=1);

require_once __DIR__ . '/HeaderBannerService.php';
require_once __DIR__ . '/UnitContentService.php';

class UnitMenuService
{
    private const MAX_ITEMS = 30;
    private const MAX_SUBMENU_ITEMS = 20;
    private const MAX_LABEL_LENGTH = 100;
    private const MAX_URL_LENGTH = 500;

    /**
     * Ausencia de menu_published conserva el comportamiento legacy.
     *
     * @param array<string, mixed> $unit
     * @return list<array<string, mixed>>
     */
    public static function resolve(array $unit): array
    {
        if (array_key_exists('menu_published', $unit)
            && !filter_var($unit['menu_published'], FILTER_VALIDATE_BOOLEAN)) {
            return [];
        }

        $menu = is_array($unit['menu'] ?? null) ? $unit['menu'] : [];

        return self::normalizeItems($menu);
    }

    /**
     * @param array<int|string, mixed> $items
     * @return list<array<string, mixed>>
     */
    public static function normalizeItems(array $items, bool $strict = false): array
    {
        return self::deduplicateTree(
            self::normalizeList($items, $strict, false, self::MAX_ITEMS),
            false
        );
    }

    /**
     * Aplica el POST sobre global.business_units sin escribir otra unidad.
     *
     * @param array<string, mixed> $siteData
     * @param array<string, mixed> $post
     */
    public static function apply(array &$siteData, string $unitKey, array $post): ?string
    {
        $unitKey = strtolower(trim($unitKey));
        if (!preg_match('/^[a-z0-9_]+$/', $unitKey)
            || !UnitContentService::isSupportedUnit($unitKey, $siteData)) {
            return 'Unidad de negocio no válida.';
        }

        $rawMenu = $post['menu'] ?? [];
        if (!is_array($rawMenu)) {
            return 'La estructura del menú no es válida.';
        }

        try {
            $storedMenu = self::normalizeList($rawMenu, true, true, self::MAX_ITEMS);
            $storedMenu = self::deduplicateTree($storedMenu, true);
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        }

        if (!isset($siteData['global']['business_units'])
            || !is_array($siteData['global']['business_units'])) {
            $siteData['global']['business_units'] = [];
        }
        if (!isset($siteData['global']['business_units'][$unitKey])
            || !is_array($siteData['global']['business_units'][$unitKey])) {
            $siteData['global']['business_units'][$unitKey] = ['key' => $unitKey];
        }

        $siteData['global']['business_units'][$unitKey]['menu_published'] =
            filter_var($post['menu_published'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $siteData['global']['business_units'][$unitKey]['menu'] = $storedMenu;

        return null;
    }

    /**
     * @param array<int|string, mixed> $items
     * @return list<array<string, mixed>>
     */
    private static function normalizeList(
        array $items,
        bool $strict,
        bool $includeInactive,
        int $limit
    ): array {
        if (count($items) > $limit && $strict) {
            throw new InvalidArgumentException('El menú supera la cantidad máxima de elementos permitidos.');
        }

        $normalized = [];
        foreach (array_slice(array_values($items), 0, $limit) as $index => $item) {
            if (!is_array($item)) {
                if ($strict) {
                    throw new InvalidArgumentException('Uno de los elementos del menú no es válido.');
                }
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            if ($label === ''
                || !mb_check_encoding($label, 'UTF-8')
                || strip_tags($label) !== $label
                || self::hasUnsafeText($label)) {
                if ($strict) {
                    throw new InvalidArgumentException('El texto del menú no es válido o contiene HTML.');
                }
                continue;
            }
            if (mb_strlen($label, 'UTF-8') > self::MAX_LABEL_LENGTH) {
                if ($strict) {
                    throw new InvalidArgumentException('El texto del menú supera 100 caracteres.');
                }
                $label = mb_substr($label, 0, self::MAX_LABEL_LENGTH, 'UTF-8');
            }

            $active = !array_key_exists('active', $item)
                || filter_var($item['active'], FILTER_VALIDATE_BOOLEAN);
            $rawLink = trim((string) ($item['link'] ?? ''));
            $rawSubmenu = is_array($item['submenu'] ?? null) ? $item['submenu'] : [];
            $submenu = self::normalizeList(
                $rawSubmenu,
                $strict,
                $includeInactive,
                self::MAX_SUBMENU_ITEMS
            );

            $link = self::sanitizeLink($rawLink, $submenu !== []);
            if ($rawLink !== '' && $link === '') {
                if ($strict) {
                    throw new InvalidArgumentException(
                        'Uno de los enlaces del menú no es válido. Use una ruta interna, un ancla o una URL HTTPS.'
                    );
                }
                continue;
            }
            if ($link === '' && $submenu === []) {
                if ($strict) {
                    throw new InvalidArgumentException('Cada elemento activo debe tener un enlace o submenú válido.');
                }
                continue;
            }

            $sortOrder = filter_var(
                $item['sort_order'] ?? $index,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0, 'max_range' => 100000]]
            );
            if ($sortOrder === false) {
                if ($strict) {
                    throw new InvalidArgumentException('El orden de uno de los elementos no es válido.');
                }
                $sortOrder = $index;
            }

            if (!$active && !$includeInactive) {
                continue;
            }
            if ($active && $link === '#' && $submenu === []) {
                continue;
            }

            $row = [
                'label' => $label,
                'link' => $link,
                'active' => $active,
                'sort_order' => (int) $sortOrder,
                '_index' => $index,
            ];
            if ($submenu !== []) {
                $row['submenu'] = $submenu;
            }
            $normalized[] = $row;
        }

        usort($normalized, static function (array $a, array $b): int {
            $order = $a['sort_order'] <=> $b['sort_order'];

            return $order !== 0 ? $order : ($a['_index'] <=> $b['_index']);
        });

        $seen = [];
        $deduplicated = [];
        foreach ($normalized as $row) {
            $duplicateKey = $row['link'] === '#'
                ? '#|' . mb_strtolower($row['label'], 'UTF-8')
                : mb_strtolower($row['link'], 'UTF-8');
            if (!empty($row['active']) && isset($seen[$duplicateKey])) {
                continue;
            }
            if (!empty($row['active'])) {
                $seen[$duplicateKey] = true;
            }
            unset($row['_index']);
            $deduplicated[] = $row;
        }

        return array_values($deduplicated);
    }

    private static function sanitizeLink(string $url, bool $hasSubmenu): string
    {
        if ($url === '#' && $hasSubmenu) {
            return '#';
        }
        if (mb_strlen($url, 'UTF-8') > self::MAX_URL_LENGTH) {
            return '';
        }

        $sanitized = HeaderBannerService::sanitizeLinkUrl($url);
        if ($sanitized !== '') {
            return $sanitized;
        }

        $isSafeLegacyRelative = preg_match(
            '/^[a-z0-9][a-z0-9._~!$&()*+,;=@%\/?#-]*$/i',
            $url
        ) === 1;
        if ($isSafeLegacyRelative
            && !str_contains($url, '..')
            && !str_contains($url, '//')
            && !str_contains($url, '\\')) {
            return $url;
        }

        return '';
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private static function deduplicateTree(array $items, bool $includeInactive): array
    {
        $seen = [];
        $walk = static function (array $rows) use (&$walk, &$seen, $includeInactive): array {
            $result = [];
            foreach ($rows as $row) {
                $active = !empty($row['active']);
                if (!$active) {
                    if ($includeInactive) {
                        $result[] = $row;
                    }
                    continue;
                }

                $link = (string) ($row['link'] ?? '');
                if ($link !== '' && $link !== '#') {
                    $key = mb_strtolower($link, 'UTF-8');
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;
                }

                if (is_array($row['submenu'] ?? null)) {
                    $row['submenu'] = $walk($row['submenu']);
                    if ($row['submenu'] === []) {
                        unset($row['submenu']);
                    }
                }
                if ($link === '#' && empty($row['submenu'])) {
                    continue;
                }
                $result[] = $row;
            }

            return array_values($result);
        };

        return $walk($items);
    }

    private static function hasUnsafeText(string $value): bool
    {
        return preg_match('/[\x00-\x1F\x7F<>]/u', $value) === 1;
    }
}
