<?php
/**
 * Maestro de Páginas genéricas (Generales → Maestro de Páginas).
 * Almacena en site_data.json -> generic_pages[] y se publica en /p/{slug}.
 */

declare(strict_types=1);

class GenericPageService
{
    private const MAX_TITLE_LENGTH = 150;
    private const MIN_SLUG_LENGTH = 3;
    private const MAX_SLUG_LENGTH = 80;

    /**
     * Slugs que no pueden usarse porque chocan con rutas o páginas funcionales.
     *
     * @var list<string>
     */
    public const RESERVED_SLUGS = [
        'admin', 'api', 'assets', 'index', 'p', 'px', 'l', 'pagina', 'landing',
        'rent-a-car', 'venta-autos', 'leasing', 'renting', 'taller', 'unidad',
        'flota', 'sucursal', 'sucursales', 'inventario', 'detalle', 'autos',
        'reservar', 'resultados', 'extras', 'confirmacion', 'mi-reserva',
        'pago-seguro', 'contactos', 'financiamiento', 'blog', 'noticias',
        'noticia', 'contenido-reciente', 'requisitos-alquiler',
        'terminos-condiciones', 'sobre-nosotros', 'sostenibilidad', 'sitemap',
    ];

    /** @param array<string, mixed> $siteData @return list<array<string, mixed>> */
    public static function all(array $siteData): array
    {
        $pages = $siteData['generic_pages'] ?? [];
        if (!is_array($pages)) {
            return [];
        }
        $normalized = [];
        foreach ($pages as $page) {
            if (is_array($page) && trim((string) ($page['id'] ?? '')) !== '') {
                $normalized[] = $page;
            }
        }
        usort($normalized, static function (array $a, array $b): int {
            return strcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
        });

        return $normalized;
    }

    /** @param array<string, mixed> $siteData @return list<array<string, mixed>> */
    public static function published(array $siteData): array
    {
        return array_values(array_filter(self::all($siteData), static function (array $page): bool {
            return !isset($page['active']) || filter_var($page['active'], FILTER_VALIDATE_BOOLEAN);
        }));
    }

    /** @param array<string, mixed> $siteData @return array<string, mixed>|null */
    public static function findById(array $siteData, string $id): ?array
    {
        foreach (self::all($siteData) as $page) {
            if ((string) ($page['id'] ?? '') === $id) {
                return $page;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $siteData @return array<string, mixed>|null */
    public static function findBySlug(array $siteData, string $slug, bool $onlyPublished = false): ?array
    {
        $slug = strtolower(trim($slug));
        $pages = $onlyPublished ? self::published($siteData) : self::all($siteData);
        foreach ($pages as $page) {
            if (strtolower((string) ($page['slug'] ?? '')) === $slug) {
                return $page;
            }
        }

        return null;
    }

    public static function publicPath(string $slug): string
    {
        return '/p/' . rawurlencode(strtolower(trim($slug)));
    }

    public static function slugFromTitle(string $title): string
    {
        $slug = mb_strtolower(trim($title), 'UTF-8');
        $slug = strtr($slug, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ]);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return substr($slug, 0, self::MAX_SLUG_LENGTH);
    }

    /**
     * Valida un slug. Devuelve mensaje de error o null si es válido.
     *
     * @param array<string, mixed> $siteData
     */
    public static function validateSlug(array $siteData, string $slug, string $excludeId = ''): ?string
    {
        if (strlen($slug) < self::MIN_SLUG_LENGTH || strlen($slug) > self::MAX_SLUG_LENGTH) {
            return 'El link (slug) debe tener entre ' . self::MIN_SLUG_LENGTH . ' y ' . self::MAX_SLUG_LENGTH . ' caracteres.';
        }
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return 'El link (slug) solo puede tener minúsculas, números y guiones internos.';
        }
        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            return 'Ese link está reservado para una página funcional del sitio. Usa otro.';
        }
        $existing = self::findBySlug($siteData, $slug);
        if ($existing !== null && (string) ($existing['id'] ?? '') !== $excludeId) {
            return 'Ya existe una página con ese link. Debe ser único.';
        }

        return null;
    }

    /** Permite HTML de contenido; elimina scripts, iframes, eventos inline y javascript:. */
    public static function sanitizeContentHtml(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? '';
        $html = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $html) ?? '';
        $html = preg_replace('/<object\b[^>]*>.*?<\/object>/is', '', $html) ?? '';
        $html = preg_replace('/<embed\b[^>]*\/?>/is', '', $html) ?? '';
        $html = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/\s(href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2/i', '', $html) ?? '';

        return trim($html);
    }

    /**
     * Crea o actualiza una página. Devuelve mensaje de error o null si guardó en $siteData.
     *
     * @param array<string, mixed> $siteData
     * @param array<string, mixed> $input title, slug, content_html, active
     */
    public static function apply(array &$siteData, array $input, string $editId = ''): ?string
    {
        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '' || mb_strlen($title, 'UTF-8') > self::MAX_TITLE_LENGTH) {
            return 'El título es obligatorio (máximo ' . self::MAX_TITLE_LENGTH . ' caracteres).';
        }

        $subtitle = trim((string) ($input['subtitle'] ?? ''));
        if (mb_strlen($subtitle, 'UTF-8') > 250) {
            return 'El subtítulo no puede superar 250 caracteres.';
        }

        $slug = strtolower(trim((string) ($input['slug'] ?? '')));
        if ($slug === '') {
            $slug = self::slugFromTitle($title);
        }
        $slugError = self::validateSlug($siteData, $slug, $editId);
        if ($slugError !== null) {
            return $slugError;
        }

        $contentHtml = self::sanitizeContentHtml((string) ($input['content_html'] ?? ''));
        $active = filter_var($input['active'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $now = date('c');

        if (!isset($siteData['generic_pages']) || !is_array($siteData['generic_pages'])) {
            $siteData['generic_pages'] = [];
        }

        if ($editId === '') {
            $siteData['generic_pages'][] = [
                'id' => 'gp_' . bin2hex(random_bytes(6)),
                'title' => $title,
                'subtitle' => $subtitle,
                'slug' => $slug,
                'content_html' => $contentHtml,
                'active' => $active,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            return null;
        }

        foreach ($siteData['generic_pages'] as &$page) {
            if (!is_array($page) || (string) ($page['id'] ?? '') !== $editId) {
                continue;
            }
            $oldSlug = strtolower(trim((string) ($page['slug'] ?? '')));
            $page['title'] = $title;
            $page['subtitle'] = $subtitle;
            $page['slug'] = $slug;
            $page['content_html'] = $contentHtml;
            $page['active'] = $active;
            $page['updated_at'] = $now;
            unset($page);

            if ($oldSlug !== '' && $oldSlug !== $slug) {
                self::syncMenuLinks($siteData, $oldSlug, $slug);
            }

            return null;
        }
        unset($page);

        return 'Página no encontrada.';
    }

    /** @param array<string, mixed> $siteData */
    public static function delete(array &$siteData, string $id): bool
    {
        if (!isset($siteData['generic_pages']) || !is_array($siteData['generic_pages'])) {
            return false;
        }
        $before = count($siteData['generic_pages']);
        $siteData['generic_pages'] = array_values(array_filter(
            $siteData['generic_pages'],
            static fn ($page) => !is_array($page) || (string) ($page['id'] ?? '') !== $id
        ));

        return count($siteData['generic_pages']) < $before;
    }

    /**
     * @param array<string, mixed> $siteData
     * @return list<string> etiquetas "Unidad → Enlace" que apuntan a la página
     */
    public static function menuReferences(array $siteData, string $slug): array
    {
        $path = self::publicPath($slug);
        $refs = [];
        $units = $siteData['global']['business_units'] ?? [];
        if (!is_array($units)) {
            return $refs;
        }
        foreach ($units as $unitKey => $unit) {
            if (!is_array($unit) || !is_array($unit['menu'] ?? null)) {
                continue;
            }
            foreach ($unit['menu'] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (self::linkMatchesPath((string) ($item['link'] ?? ''), $path)) {
                    $refs[] = (string) $unitKey . ' → ' . (string) ($item['label'] ?? '');
                }
                foreach (($item['submenu'] ?? []) as $sub) {
                    if (is_array($sub) && self::linkMatchesPath((string) ($sub['link'] ?? ''), $path)) {
                        $refs[] = (string) $unitKey . ' → ' . (string) ($item['label'] ?? '') . ' → ' . (string) ($sub['label'] ?? '');
                    }
                }
            }
        }

        return $refs;
    }

    /**
     * Al cambiar un slug, reescribe los links del menú de todas las unidades.
     *
     * @param array<string, mixed> $siteData
     */
    private static function syncMenuLinks(array &$siteData, string $oldSlug, string $newSlug): void
    {
        if (!isset($siteData['global']['business_units']) || !is_array($siteData['global']['business_units'])) {
            return;
        }
        $oldPath = self::publicPath($oldSlug);
        $newPath = self::publicPath($newSlug);

        foreach ($siteData['global']['business_units'] as &$unit) {
            if (!is_array($unit) || !is_array($unit['menu'] ?? null)) {
                continue;
            }
            foreach ($unit['menu'] as &$item) {
                if (!is_array($item)) {
                    continue;
                }
                $item['link'] = self::replaceLinkPath((string) ($item['link'] ?? ''), $oldPath, $newPath);
                if (is_array($item['submenu'] ?? null)) {
                    foreach ($item['submenu'] as &$sub) {
                        if (is_array($sub)) {
                            $sub['link'] = self::replaceLinkPath((string) ($sub['link'] ?? ''), $oldPath, $newPath);
                        }
                    }
                    unset($sub);
                }
            }
            unset($item);
        }
        unset($unit);
    }

    private static function linkMatchesPath(string $link, string $path): bool
    {
        $linkPath = (string) (parse_url(trim($link), PHP_URL_PATH) ?: '');

        return $linkPath !== '' && rtrim($linkPath, '/') === rtrim($path, '/');
    }

    private static function replaceLinkPath(string $link, string $oldPath, string $newPath): string
    {
        if (!self::linkMatchesPath($link, $oldPath)) {
            return $link;
        }
        $query = (string) (parse_url(trim($link), PHP_URL_QUERY) ?: '');

        return $newPath . ($query !== '' ? '?' . $query : '');
    }
}
