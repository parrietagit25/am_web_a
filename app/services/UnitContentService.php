<?php
/**
 * Gestor unificado de contenido por unidad de negocio (latest, blog, news).
 */
require_once __DIR__ . '/HeaderBannerService.php';

class UnitContentService
{
    public const TYPES = ['latest', 'blog', 'news'];

    public const TYPE_LABELS = [
        'latest' => 'Novedades',
        'blog' => 'Blog',
        'news' => 'Noticias',
    ];

    public const LATEST_SUBTYPES = [
        'promotion' => 'Promoción',
        'event' => 'Evento',
        'institutional' => 'Información institucional / comercial',
    ];

    /** @var array<string, string> */
    private static array $unitDataKeys = [
        'rentacar' => 'homepage',
        'leasing' => 'leasing',
        'renting' => 'renting',
        'seminuevos' => 'seminuevos',
        'taller' => 'taller',
    ];

    public static function unitDataKey(string $unitKey): string
    {
        return self::$unitDataKeys[$unitKey] ?? $unitKey;
    }

    public static function isCustomUnit(string $unitKey): bool
    {
        require_once __DIR__ . '/../includes/business-units-registry.php';

        return !am_is_builtin_business_unit($unitKey);
    }

    /** @param array<string, mixed> $siteData */
    public static function isSupportedUnit(string $unitKey, array $siteData): bool
    {
        if (self::isCustomUnit($unitKey)) {
            $custom = $siteData['global']['business_units'] ?? [];

            return is_array($custom) && isset($custom[$unitKey]);
        }

        return isset(self::$unitDataKeys[$unitKey]);
    }

    /** @return string[] */
    public static function contentTabSlugs(string $unitKey): array
    {
        return [
            $unitKey . '-content-config',
            $unitKey . '-content-latest',
            $unitKey . '-content-news',
            $unitKey . '-content-blog',
        ];
    }

    public static function generalTabSlug(string $unitKey): string
    {
        return self::isCustomUnit($unitKey)
            ? 'unit-' . $unitKey . '-general'
            : $unitKey . '-general';
    }

    public static function contentPermissionKey(string $unitKey): string
    {
        static $map = [
            'rentacar' => 'news',
            'seminuevos' => 'semi_home',
            'leasing' => 'leasing_home',
            'renting' => 'renting_publicaciones',
            'taller' => 'taller_home',
        ];

        return $map[$unitKey] ?? 'global';
    }

    /** @param array<string, mixed> $siteData @return list<string> */
    public static function listAllUnitKeys(array $siteData): array
    {
        require_once __DIR__ . '/../includes/business-units-registry.php';
        $merged = am_merge_business_units($siteData['global']['business_units'] ?? []);

        return array_keys($merged);
    }

    /** @param array<string, mixed> $siteData */
    public static function unitLabel(array $siteData, string $unitKey): string
    {
        require_once __DIR__ . '/../includes/business-units-registry.php';
        $merged = am_merge_business_units($siteData['global']['business_units'] ?? []);
        $label = trim((string) ($merged[$unitKey]['label'] ?? ''));

        return $label !== '' ? $label : strtoupper($unitKey);
    }

    /** @param array<string, mixed> $siteData */
    public static function unitHomePath(array $siteData, string $unitKey): string
    {
        require_once __DIR__ . '/../includes/business-units-registry.php';
        $merged = am_merge_business_units($siteData['global']['business_units'] ?? []);
        $slug = trim((string) ($merged[$unitKey]['slug'] ?? ''));
        if ($slug === '') {
            return '/unidad.php?u=' . rawurlencode($unitKey);
        }
        if (str_contains($slug, '?')) {
            return '/' . ltrim($slug, '/');
        }

        return '/' . ltrim($slug, '/');
    }

    public static function defaultContentNode(): array
    {
        return self::mergeDefaultContent([]);
    }

    public static function isValidType(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    /** @param array<string, mixed> $siteData */
    public static function ensureMigrated(array &$siteData, string $unitKey): bool
    {
        if (!self::isSupportedUnit($unitKey, $siteData)) {
            return false;
        }

        $changed = false;
        if ($unitKey === 'rentacar') {
            $changed = self::migrateRentacarNoticias($siteData) || $changed;
        }
        if (in_array($unitKey, ['leasing', 'renting'], true)) {
            $changed = self::migrateLegacyPostsToBlog($siteData, $unitKey) || $changed;
        }
        if (self::ensureContentInitialized($siteData, $unitKey)) {
            $changed = true;
        }

        return $changed;
    }

    /** @param array<string, mixed> $siteData */
    private static function migrateRentacarNoticias(array &$siteData): bool
    {
        $dataKey = self::unitDataKey('rentacar');
        if (!isset($siteData[$dataKey]) || !is_array($siteData[$dataKey])) {
            $siteData[$dataKey] = [];
        }

        $content = $siteData[$dataKey]['content'] ?? [];
        if (!is_array($content)) {
            $content = [];
        }

        if (!empty($content['_migrated_from_noticias'])) {
            return false;
        }

        $changed = true;
        $legacy = $siteData[$dataKey]['noticias'] ?? [];
        if (!is_array($legacy) || empty($legacy)) {
            $content['_migrated_from_noticias'] = true;
            self::setContentNode($siteData, 'rentacar', $content);

            return $changed;
        }

        $news = [];
        foreach ($legacy as $row) {
            if (!is_array($row)) {
                continue;
            }
            $news[] = self::legacyNoticiaToNews($row);
        }

        $content['news'] = $news;
        $content['_migrated_from_noticias'] = true;

        $showOnHome = $siteData[$dataKey]['noticias_show_on_home'] ?? true;
        $settingsOverrides = [
            'home_block_enabled' => ($showOnHome !== false && $showOnHome !== 0 && $showOnHome !== '0'),
        ];

        $rotation = [];
        foreach ($news as $newsItem) {
            if (!empty($newsItem['show_on_home'])) {
                $rotation[] = [
                    'source_type' => 'news',
                    'item_id' => intval($newsItem['id'] ?? 0),
                    'sort_order' => count($rotation),
                ];
            }
        }
        if (!empty($rotation)) {
            $settingsOverrides['home_rotation'] = $rotation;
            $settingsOverrides['home_display_mode'] = count($rotation) > 1 ? 'rotation' : 'single';
            $settingsOverrides['home_single'] = $rotation[0];
        }

        $content['settings'] = self::normalizeSettings($content['settings'] ?? [], $settingsOverrides);

        self::setContentNode($siteData, 'rentacar', $content);
        self::syncRentacarLegacyNoticias($siteData);

        return $changed;
    }

    /** @param array<string, mixed> $siteData */
    private static function migrateLegacyPostsToBlog(array &$siteData, string $unitKey): bool
    {
        $raw = self::getRawContentArray($siteData, $unitKey);
        if (!empty($raw['_migrated_from_posts'])) {
            return false;
        }

        $dataKey = self::unitDataKey($unitKey);
        $posts = $siteData[$dataKey]['posts'] ?? [];
        if (!is_array($posts) || empty($posts)) {
            $raw['_migrated_from_posts'] = true;
            self::setContentNode($siteData, $unitKey, $raw);

            return true;
        }

        $blog = is_array($raw['blog'] ?? null) ? $raw['blog'] : [];
        foreach ($posts as $post) {
            if (!is_array($post)) {
                continue;
            }
            $blog[] = self::legacyPostToBlog($post);
        }

        $raw['blog'] = $blog;
        $raw['_migrated_from_posts'] = true;
        self::setContentNode($siteData, $unitKey, $raw);

        return true;
    }

    /** @param array<string, mixed> $siteData */
    private static function ensureContentInitialized(array &$siteData, string $unitKey): bool
    {
        if (self::hasContentStorage($siteData, $unitKey)) {
            return false;
        }

        self::setContentNode($siteData, $unitKey, []);

        return true;
    }

    /** @param array<string, mixed> $siteData */
    private static function hasContentStorage(array $siteData, string $unitKey): bool
    {
        if (self::isCustomUnit($unitKey)) {
            return isset($siteData['global']['business_units'][$unitKey]['content']);
        }

        $dataKey = self::unitDataKey($unitKey);

        return isset($siteData[$dataKey]['content']);
    }

    /** @param array<string, mixed> $siteData @return array<string, mixed> */
    private static function getRawContentArray(array $siteData, string $unitKey): array
    {
        if (self::isCustomUnit($unitKey)) {
            $content = $siteData['global']['business_units'][$unitKey]['content'] ?? [];
        } else {
            $dataKey = self::unitDataKey($unitKey);
            $content = $siteData[$dataKey]['content'] ?? [];
        }

        return is_array($content) ? $content : [];
    }

    /** @param array<string, mixed> $siteData @param array<string, mixed> $content */
    public static function setContentNode(array &$siteData, string $unitKey, array $content): void
    {
        $merged = self::mergeDefaultContent($content);
        if (self::isCustomUnit($unitKey)) {
            if (!isset($siteData['global']['business_units'][$unitKey]) || !is_array($siteData['global']['business_units'][$unitKey])) {
                $siteData['global']['business_units'][$unitKey] = ['key' => $unitKey];
            }
            $siteData['global']['business_units'][$unitKey]['content'] = $merged;

            return;
        }

        $dataKey = self::unitDataKey($unitKey);
        if (!isset($siteData[$dataKey]) || !is_array($siteData[$dataKey])) {
            $siteData[$dataKey] = [];
        }
        $siteData[$dataKey]['content'] = $merged;
    }

    /** @param array<string, mixed> $legacy */
    public static function legacyPostToBlog(array $legacy): array
    {
        return self::normalizeItem([
            'id' => $legacy['id'] ?? time(),
            'title' => $legacy['title'] ?? '',
            'slug' => $legacy['slug'] ?? '',
            'date' => $legacy['date'] ?? '',
            'excerpt' => $legacy['excerpt'] ?? '',
            'link_text' => $legacy['link_text'] ?? 'Ver Más',
            'thumbnail' => $legacy['image_url'] ?? ($legacy['thumbnail'] ?? ''),
            'banner' => $legacy['image_url'] ?? ($legacy['banner'] ?? ''),
            'content' => $legacy['content'] ?? '',
            'published' => $legacy['published'] ?? true,
            'show_on_home' => $legacy['show_on_home'] ?? false,
            'sort_order' => $legacy['sort_order'] ?? 0,
        ], 'blog');
    }

    /**
     * Leasing/Renting (fase futura): publicaciones actuales son contenido evergreen → blog.
     *
     * @return array<string, string>
     */
    public static function futureMigrationTargets(): array
    {
        return [
            'leasing' => 'blog',
            'renting' => 'blog',
        ];
    }

    /** @param array<string, mixed> $siteData */
    public static function getContentNode(array $siteData, string $unitKey): array
    {
        return self::mergeDefaultContent(self::getRawContentArray($siteData, $unitKey));
    }

    /** @param array<string, mixed> $siteData @return list<array<string, mixed>> */
    public static function getItems(array $siteData, string $unitKey, string $type): array
    {
        if (!self::isValidType($type)) {
            return [];
        }

        $content = self::getContentNode($siteData, $unitKey);
        $items = $content[$type] ?? [];
        if (!is_array($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalized[] = self::normalizeItem($row, $type);
        }

        usort($normalized, static function (array $a, array $b): int {
            $orderA = intval($a['sort_order'] ?? 999);
            $orderB = intval($b['sort_order'] ?? 999);
            if ($orderA !== $orderB) {
                return $orderA - $orderB;
            }
            return strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));
        });

        return $normalized;
    }

    /** @param array<string, mixed> $siteData */
    public static function findItem(array $siteData, string $unitKey, string $type, int $id): ?array
    {
        foreach (self::getItems($siteData, $unitKey, $type) as $item) {
            if (intval($item['id'] ?? 0) === $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Busca un artículo publicado por slug dentro de unit+type.
     * Retorna null si el slug está vacío, hay colisión o no hay coincidencia visible.
     *
     * @param array<string, mixed> $siteData
     * @return array<string, mixed>|null
     */
    public static function findItemBySlug(array $siteData, string $unitKey, string $type, string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '' || !self::isValidType($type) || !self::isSupportedUnit($unitKey, $siteData)) {
            return null;
        }

        self::ensureMigrated($siteData, $unitKey);

        $matches = [];
        foreach (self::getItems($siteData, $unitKey, $type) as $item) {
            $itemSlug = trim((string) ($item['slug'] ?? ''));
            if ($itemSlug === '' || strcasecmp($itemSlug, $slug) !== 0) {
                continue;
            }
            if (!self::isPubliclyVisible($item)) {
                continue;
            }
            $matches[] = $item;
        }

        if (count($matches) > 1) {
            return null;
        }
        if (count($matches) === 1) {
            return $matches[0];
        }

        if ($unitKey === 'rentacar' && $type === 'news') {
            $legacyMatches = [];
            foreach (self::getLegacyNoticias($siteData, $unitKey) as $legacy) {
                $legacyItem = self::legacyNoticiaToNews($legacy);
                $itemSlug = trim((string) ($legacyItem['slug'] ?? ''));
                if ($itemSlug === '' || strcasecmp($itemSlug, $slug) !== 0) {
                    continue;
                }
                if (!self::isPubliclyVisible($legacyItem)) {
                    continue;
                }
                $legacyMatches[] = $legacyItem;
            }
            if (count($legacyMatches) === 1) {
                return $legacyMatches[0];
            }
        }

        return null;
    }

    /** @param array<string, mixed> $legacy */
    public static function legacyNoticiaToNews(array $legacy): array
    {
        return self::normalizeItem([
            'id' => $legacy['id'] ?? time(),
            'title' => $legacy['title'] ?? '',
            'slug' => $legacy['slug'] ?? '',
            'date' => $legacy['date'] ?? '',
            'excerpt' => $legacy['excerpt'] ?? ($legacy['desc'] ?? ''),
            'link_text' => $legacy['link_text'] ?? 'Ver Más',
            'thumbnail' => $legacy['thumbnail'] ?? '',
            'banner' => $legacy['banner'] ?? '',
            'subheading' => $legacy['subheading'] ?? '',
            'description' => $legacy['description'] ?? '',
            'content' => $legacy['content'] ?? '',
            'published' => $legacy['published'] ?? true,
            'show_on_home' => $legacy['show_on_home'] ?? true,
            'publish_from' => $legacy['publish_from'] ?? '',
            'publish_until' => $legacy['publish_until'] ?? '',
            'category_ids' => $legacy['category_ids'] ?? [],
            'tag_ids' => $legacy['tag_ids'] ?? [],
            'topic_ids' => $legacy['topic_ids'] ?? [],
            'sort_order' => $legacy['sort_order'] ?? 0,
        ], 'news');
    }

    /** @param array<string, mixed> $row */
    public static function normalizeItem(array $row, string $type): array
    {
        $title = trim((string) ($row['title'] ?? ''));
        $slug = trim((string) ($row['slug'] ?? ''));
        if ($slug === '' && $title !== '') {
            $slug = self::slugify($title);
        }

        $item = [
            'id' => intval($row['id'] ?? 0) ?: time(),
            'title' => $title,
            'slug' => $slug,
            'date' => trim((string) ($row['date'] ?? '')),
            'excerpt' => trim((string) ($row['excerpt'] ?? ($row['desc'] ?? ''))),
            'link_text' => trim((string) ($row['link_text'] ?? 'Ver Más')) ?: 'Ver Más',
            'thumbnail' => trim((string) ($row['thumbnail'] ?? ($row['image_url'] ?? ''))),
            'banner' => trim((string) ($row['banner'] ?? '')),
            'subheading' => trim((string) ($row['subheading'] ?? '')),
            'description' => trim((string) ($row['description'] ?? '')),
            'content' => (string) ($row['content'] ?? ''),
            'meta_title' => trim((string) ($row['meta_title'] ?? '')),
            'meta_description' => trim((string) ($row['meta_description'] ?? '')),
            'published' => !isset($row['published']) || filter_var($row['published'], FILTER_VALIDATE_BOOLEAN),
            'show_on_home' => array_key_exists('show_on_home', $row)
                ? filter_var($row['show_on_home'], FILTER_VALIDATE_BOOLEAN)
                : ($type === 'latest'),
            'publish_from' => trim((string) ($row['publish_from'] ?? '')),
            'publish_until' => trim((string) ($row['publish_until'] ?? '')),
            'category_ids' => self::normalizeIdList($row['category_ids'] ?? []),
            'tag_ids' => self::normalizeIdList($row['tag_ids'] ?? []),
            'topic_ids' => self::normalizeIdList($row['topic_ids'] ?? []),
            'sort_order' => intval($row['sort_order'] ?? 0),
            'created_at' => trim((string) ($row['created_at'] ?? '')),
            'updated_at' => trim((string) ($row['updated_at'] ?? '')),
        ];

        if ($type === 'latest') {
            $subtype = trim((string) ($row['subtype'] ?? 'promotion'));
            if (!isset(self::LATEST_SUBTYPES[$subtype])) {
                $subtype = 'promotion';
            }
            $item['subtype'] = $subtype;
        }

        return $item;
    }

    /** @return array<string, array{banner: string, kicker: string, title: string, subtitle: string, align: string}> */
    public static function defaultPageHeaders(string $unitLabel = ''): array
    {
        $label = trim($unitLabel) !== '' ? trim($unitLabel) : 'Automarket';

        return [
            'news' => [
                'enabled' => true,
                'banner' => '',
                'alt' => '',
                'kicker' => 'Actualidad',
                'title' => 'Noticias',
                'subtitle' => 'Comunicados, novedades y actualidad de ' . $label . '.',
                'align' => 'left',
                'button_text' => '',
                'button_url' => '',
            ],
            'blog' => [
                'enabled' => true,
                'banner' => '',
                'alt' => '',
                'kicker' => 'Recursos',
                'title' => 'Blog',
                'subtitle' => 'Artículos, guías y contenido permanente.',
                'align' => 'center',
                'button_text' => '',
                'button_url' => '',
            ],
            'latest' => [
                'enabled' => true,
                'banner' => '',
                'alt' => '',
                'kicker' => 'Destacados',
                'title' => 'Novedades',
                'subtitle' => 'Promociones, eventos e información destacada de ' . $label . '.',
                'align' => 'center',
                'button_text' => '',
                'button_url' => '',
            ],
        ];
    }

    /** @param array<string, mixed> $row */
    public static function normalizePageHeader(array $row, string $type): array
    {
        if (!self::isValidType($type)) {
            $type = 'news';
        }

        $defaults = self::defaultPageHeaders()[$type];
        $align = trim((string) ($row['align'] ?? $defaults['align']));
        if (!in_array($align, ['left', 'center', 'right'], true)) {
            $align = $defaults['align'];
        }

        $title = trim((string) ($row['title'] ?? ''));
        if ($title === '') {
            $title = $defaults['title'];
        }

        $buttonUrl = HeaderBannerService::sanitizeLinkUrl($row['button_url'] ?? '');

        return [
            'enabled' => !array_key_exists('enabled', $row)
                || filter_var($row['enabled'], FILTER_VALIDATE_BOOLEAN),
            'banner' => trim((string) ($row['banner'] ?? '')),
            'alt' => trim((string) ($row['alt'] ?? '')),
            'kicker' => trim((string) ($row['kicker'] ?? $defaults['kicker'])),
            'title' => $title,
            'subtitle' => trim((string) ($row['subtitle'] ?? '')),
            'align' => $align,
            'button_text' => $buttonUrl !== '' ? trim((string) ($row['button_text'] ?? '')) : '',
            'button_url' => $buttonUrl,
        ];
    }

    /** @param array<string, mixed> $headers */
    public static function normalizePageHeaders(array $headers, ?string $unitLabel = null): array
    {
        $defaults = self::defaultPageHeaders($unitLabel ?? '');
        $normalized = [];

        foreach (self::TYPES as $type) {
            $row = is_array($headers[$type] ?? null) ? $headers[$type] : [];
            $normalized[$type] = self::normalizePageHeader(array_merge($defaults[$type], $row), $type);
            if ($normalized[$type]['subtitle'] === '' && $unitLabel !== null) {
                $normalized[$type]['subtitle'] = $defaults[$type]['subtitle'];
            }
        }

        return $normalized;
    }

    /** @param array<string, mixed> $siteData @return array{enabled: bool, banner: string, alt: string, kicker: string, title: string, subtitle: string, align: string, button_text: string, button_url: string} */
    public static function getPageHeader(array $siteData, string $unitKey, string $type): array
    {
        if (!self::isValidType($type)) {
            $type = 'news';
        }

        $unitLabel = self::unitLabel($siteData, $unitKey);
        $raw = self::getRawContentArray($siteData, $unitKey);
        $storedHeaders = [];
        if (isset($raw['settings']['page_headers']) && is_array($raw['settings']['page_headers'])) {
            $storedHeaders = $raw['settings']['page_headers'];
        }

        $headers = self::normalizePageHeaders($storedHeaders, $unitLabel);
        $header = $headers[$type];
        if ($header['subtitle'] === '') {
            $header['subtitle'] = self::defaultPageHeaders($unitLabel)[$type]['subtitle'];
        }

        return $header;
    }

    /** @return array{news: bool, blog: bool, latest: bool} */
    public static function normalizeNavMenuItems(mixed $items): array
    {
        $items = is_array($items) ? $items : [];
        $normalized = [];
        foreach (self::TYPES as $type) {
            $normalized[$type] = !isset($items[$type]) || filter_var($items[$type], FILTER_VALIDATE_BOOLEAN);
        }

        return $normalized;
    }

    /** @param array<string, mixed> $settings @param array<string, mixed> $overrides */
    public static function normalizeSettings(array $settings, array $overrides = []): array
    {
        $mode = trim((string) ($settings['home_display_mode'] ?? 'rotation'));
        if (!in_array($mode, ['single', 'rotation'], true)) {
            $mode = 'rotation';
        }

        $normalized = [
            'nav_menu_enabled' => !isset($settings['nav_menu_enabled']) || filter_var($settings['nav_menu_enabled'], FILTER_VALIDATE_BOOLEAN),
            'nav_menu_items' => self::normalizeNavMenuItems($settings['nav_menu_items'] ?? []),
            'home_block_enabled' => !isset($settings['home_block_enabled']) || filter_var($settings['home_block_enabled'], FILTER_VALIDATE_BOOLEAN),
            'home_display_mode' => $mode,
            'home_single' => self::normalizeSpotlightRef($settings['home_single'] ?? []),
            'home_rotation' => self::normalizeRotationList($settings['home_rotation'] ?? []),
            'home_rotation_interval_ms' => max(3000, intval($settings['home_rotation_interval_ms'] ?? 6000)),
            'latest_show_on_home' => !isset($settings['latest_show_on_home']) || filter_var($settings['latest_show_on_home'], FILTER_VALIDATE_BOOLEAN),
            'latest_home_limit' => max(1, min(12, intval($settings['latest_home_limit'] ?? 4))),
            'home_spotlight_title' => trim((string) ($settings['home_spotlight_title'] ?? '')),
            'home_latest_title' => trim((string) ($settings['home_latest_title'] ?? '')),
            'home_latest_subtitle' => trim((string) ($settings['home_latest_subtitle'] ?? '')),
            'page_headers' => self::normalizePageHeaders($settings['page_headers'] ?? []),
        ];

        foreach ($overrides as $key => $value) {
            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /**
     * Configuración del menú «CONTENIDO» del navbar público.
     *
     * @param array<string, mixed> $siteData
     * @return array{enabled: bool, items: array{news: bool, blog: bool, latest: bool}}
     */
    public static function getNavMenuSettings(array $siteData, string $unitKey): array
    {
        $settings = self::getContentNode($siteData, $unitKey)['settings'] ?? [];

        return [
            'enabled' => !isset($settings['nav_menu_enabled']) || filter_var($settings['nav_menu_enabled'], FILTER_VALIDATE_BOOLEAN),
            'items' => self::normalizeNavMenuItems($settings['nav_menu_items'] ?? []),
        ];
    }

    /** @param array<string, mixed> $siteData @return list<array<string, mixed>> */
    public static function getResolvedHomeSpotlight(array $siteData, string $unitKey): array
    {
        $settings = self::getContentNode($siteData, $unitKey)['settings'] ?? [];
        if (empty($settings['home_block_enabled'])) {
            return [];
        }

        $mode = $settings['home_display_mode'] ?? 'rotation';
        if ($mode === 'single') {
            $ref = self::normalizeSpotlightRef($settings['home_single'] ?? []);
            $item = self::resolveSpotlightRef($siteData, $unitKey, $ref);
            return $item ? [$item] : [];
        }

        $resolved = [];
        foreach (self::normalizeRotationList($settings['home_rotation'] ?? []) as $ref) {
            $item = self::resolveSpotlightRef($siteData, $unitKey, $ref);
            if ($item) {
                $resolved[] = $item;
            }
        }

        return $resolved;
    }

    /** @param array<string, mixed> $siteData */
    public static function getLegacyNoticias(array $siteData, string $unitKey): array
    {
        if ($unitKey === 'rentacar') {
            $migrated = self::getItems($siteData, $unitKey, 'news');
            if (!empty($migrated)) {
                return array_map([self::class, 'newsToLegacyNoticia'], $migrated);
            }
        }

        $dataKey = self::unitDataKey($unitKey);
        $legacy = $siteData[$dataKey]['noticias'] ?? [];

        return is_array($legacy) ? $legacy : [];
    }

    /** @param array<string, mixed> $item */
    public static function newsToLegacyNoticia(array $item): array
    {
        return [
            'id' => $item['id'] ?? 0,
            'date' => $item['date'] ?? '',
            'title' => $item['title'] ?? '',
            'desc' => $item['excerpt'] ?? '',
            'link_text' => $item['link_text'] ?? 'Ver Más',
            'thumbnail' => $item['thumbnail'] ?? '',
            'banner' => $item['banner'] ?? ($item['thumbnail'] ?? ''),
            'subheading' => $item['subheading'] ?? '',
            'description' => $item['description'] ?? '',
            'content' => $item['content'] ?? '',
            'show_on_home' => $item['show_on_home'] ?? true,
        ];
    }

    /** @param array<string, mixed> $siteData */
    public static function syncRentacarLegacyNoticias(array &$siteData): void
    {
        if (!isset($siteData['homepage']) || !is_array($siteData['homepage'])) {
            $siteData['homepage'] = [];
        }

        $settings = self::getContentNode($siteData, 'rentacar')['settings'] ?? [];
        $siteData['homepage']['noticias_show_on_home'] = !empty($settings['home_block_enabled']);
        $siteData['homepage']['noticias'] = self::getLegacyNoticias($siteData, 'rentacar');
    }

    /** @param array<string, mixed> $siteData @return list<array<string, mixed>> */
    public static function getTaxonomy(array $siteData, string $unitKey, string $kind): array
    {
        $taxonomy = self::getContentNode($siteData, $unitKey)['taxonomy'] ?? [];
        $list = $taxonomy[$kind] ?? [];
        if (!is_array($list)) {
            return [];
        }

        $out = [];
        foreach ($list as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug === '') {
                $slug = self::slugify($name);
            }
            $out[] = [
                'id' => intval($row['id'] ?? 0) ?: time(),
                'name' => $name,
                'slug' => $slug,
            ];
        }

        usort($out, static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        return $out;
    }

    /** @param array<string, mixed> $siteData @return list<array<string, mixed>> */
    public static function getAllPublishedForPicker(array $siteData, string $unitKey): array
    {
        $picker = [];
        foreach (self::TYPES as $type) {
            foreach (self::getItems($siteData, $unitKey, $type) as $item) {
                if (empty($item['published']) || !self::isWithinSchedule($item)) {
                    continue;
                }
                $picker[] = [
                    'source_type' => $type,
                    'item_id' => intval($item['id'] ?? 0),
                    'title' => $item['title'] ?? '',
                    'type_label' => self::TYPE_LABELS[$type] ?? $type,
                    'date' => $item['date'] ?? '',
                ];
            }
        }

        return $picker;
    }

    /** @param array<string, mixed> $item */
    public static function showsOnLatestHomeBlock(array $item, string $type): bool
    {
        if (!isset($item['show_on_home'])) {
            return $type === 'latest';
        }

        return filter_var($item['show_on_home'], FILTER_VALIDATE_BOOLEAN);
    }

    /** @param array<string, mixed> $siteData @return list<string> */
    public static function enumerateUnitKeys(array $siteData): array
    {
        $keys = ['rentacar', 'leasing', 'renting', 'seminuevos', 'taller'];
        $custom = $siteData['global']['business_units'] ?? [];
        if (is_array($custom)) {
            foreach (array_keys($custom) as $key) {
                $key = trim((string) $key);
                if ($key !== '' && !in_array($key, $keys, true)) {
                    $keys[] = $key;
                }
            }
        }

        return $keys;
    }

    /** Títulos demo conocidos (coincidencia exacta, case-insensitive). */
    private const DEMO_TITLES = [
        'prueba',
        'prueba noticias',
        'prueba contenido mas reciente',
        'prueba blog',
    ];

    /** Slugs demo conocidos (coincidencia exacta). */
    private const DEMO_SLUGS = [
        'prueba',
        'prueba-noticias',
        'prueba-contenido-mas-reciente',
    ];

    /**
     * Contenido de prueba/demo: no indexar ni mostrar en www aunque published=true.
     * Conservador: no excluye títulos legítimos como «Prueba de manejo».
     *
     * @param array<string, mixed> $item
     */
    public static function isDemoContent(array $item): bool
    {
        if (!empty($item['is_demo'])) {
            return true;
        }

        $title = mb_strtolower(trim((string) ($item['title'] ?? '')), 'UTF-8');
        if ($title !== '' && in_array($title, self::DEMO_TITLES, true)) {
            return true;
        }

        $slug = mb_strtolower(trim((string) ($item['slug'] ?? '')), 'UTF-8');
        if ($slug !== '' && in_array($slug, self::DEMO_SLUGS, true)) {
            return true;
        }

        return false;
    }

    /**
     * Visible en frontend público y elegible para sitemap/schema.
     *
     * @param array<string, mixed> $item
     */
    public static function isPubliclyVisible(array $item): bool
    {
        if (empty($item['published']) || !self::isWithinSchedule($item)) {
            return false;
        }

        return !self::isDemoContent($item);
    }

    /** @param array<string, mixed> $item */
    public static function articleIsoDate(array $item): ?string
    {
        foreach (['updated_at', 'created_at', 'date'] as $key) {
            $raw = trim((string) ($item[$key] ?? ''));
            if ($raw === '') {
                continue;
            }
            $ts = strtotime($raw);
            if ($ts !== false) {
                return date('c', $ts);
            }
        }

        return null;
    }

    /** @param array<string, mixed> $item */
    public static function articleDescription(array $item): string
    {
        foreach (['excerpt', 'description', 'subheading'] as $key) {
            $text = trim(strip_tags((string) ($item[$key] ?? '')));
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    /** @param array<string, mixed> $item */
    public static function articleMetaTitle(array $item): string
    {
        $meta = trim((string) ($item['meta_title'] ?? ''));
        if ($meta !== '') {
            return $meta;
        }

        return trim((string) ($item['title'] ?? ''));
    }

    /** @param array<string, mixed> $item */
    public static function articleMetaDescription(array $item): string
    {
        $meta = trim(strip_tags((string) ($item['meta_description'] ?? '')));
        if ($meta !== '') {
            return $meta;
        }

        return self::articleDescription($item);
    }

    /** @param array<string, mixed> $item */
    public static function isWithinSchedule(array $item): bool
    {
        $now = time();
        $from = trim((string) ($item['publish_from'] ?? ''));
        $until = trim((string) ($item['publish_until'] ?? ''));

        if ($from !== '') {
            $fromTs = strtotime($from);
            if ($fromTs !== false && $now < $fromTs) {
                return false;
            }
        }

        if ($until !== '') {
            $untilTs = strtotime($until);
            if ($untilTs !== false && $now > $untilTs) {
                return false;
            }
        }

        return true;
    }

    public static function detailUrl(string $unitKey, string $type, int $id, string $slug = ''): string
    {
        if (self::hasFriendlySlug($slug)) {
            return self::friendlyDetailPath($unitKey, $type, $slug);
        }

        return self::legacyDetailUrl($unitKey, $type, $id);
    }

    /** @param array<string, mixed> $item */
    public static function detailUrlForItem(array $item, string $unitKey, string $type): string
    {
        return self::detailUrl(
            $unitKey,
            $type,
            intval($item['id'] ?? 0),
            trim((string) ($item['slug'] ?? ''))
        );
    }

    public static function friendlyDetailPath(string $unitKey, string $type, string $slug): string
    {
        return '/blog/'
            . rawurlencode(trim($unitKey))
            . '/'
            . rawurlencode(trim($type))
            . '/'
            . rawurlencode(trim($slug));
    }

    private static function hasFriendlySlug(string $slug): bool
    {
        $slug = trim($slug);

        return $slug !== '' && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/i', $slug) === 1;
    }

    private static function legacyDetailUrl(string $unitKey, string $type, int $id): string
    {
        if ($unitKey === 'rentacar') {
            if ($type === 'blog') {
                return '/noticia.php?type=blog&id=' . $id;
            }
            if ($type === 'latest') {
                return '/noticia.php?type=latest&id=' . $id;
            }

            return '/noticia.php?id=' . $id;
        }

        return '/noticia.php?unit=' . rawurlencode($unitKey) . '&type=' . rawurlencode($type) . '&id=' . $id;
    }

    public static function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');

        return $text !== '' ? $text : 'articulo';
    }

    /** @param mixed $list @return list<int> */
    private static function normalizeIdList($list): array
    {
        if (!is_array($list)) {
            return [];
        }

        $ids = [];
        foreach ($list as $id) {
            $id = intval($id);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /** @param array<string, mixed> $content */
    private static function mergeDefaultContent(array $content): array
    {
        $merged = [
            'settings' => self::normalizeSettings($content['settings'] ?? []),
            'taxonomy' => [
                'categories' => self::normalizeTaxonomyList($content['taxonomy']['categories'] ?? []),
                'tags' => self::normalizeTaxonomyList($content['taxonomy']['tags'] ?? []),
                'topics' => self::normalizeTaxonomyList($content['taxonomy']['topics'] ?? []),
            ],
            'latest' => [],
            'blog' => [],
            'news' => [],
            '_migrated_from_noticias' => !empty($content['_migrated_from_noticias']),
            '_migrated_from_posts' => !empty($content['_migrated_from_posts']),
        ];

        foreach (self::TYPES as $type) {
            $rows = $content[$type] ?? [];
            if (!is_array($rows)) {
                continue;
            }
            foreach ($rows as $row) {
                if (is_array($row)) {
                    $merged[$type][] = self::normalizeItem($row, $type);
                }
            }
        }

        return $merged;
    }

    /** @param mixed $list @return list<array<string, mixed>> */
    private static function normalizeTaxonomyList($list): array
    {
        if (!is_array($list)) {
            return [];
        }

        $out = [];
        foreach ($list as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $out[] = [
                'id' => intval($row['id'] ?? 0) ?: time(),
                'name' => $name,
                'slug' => trim((string) ($row['slug'] ?? '')) ?: self::slugify($name),
            ];
        }

        return $out;
    }

    /** @param mixed $ref @return array{source_type: string, item_id: int} */
    private static function normalizeSpotlightRef($ref): array
    {
        if (!is_array($ref)) {
            return ['source_type' => 'news', 'item_id' => 0];
        }

        $type = trim((string) ($ref['source_type'] ?? 'news'));
        if (!self::isValidType($type)) {
            $type = 'news';
        }

        return [
            'source_type' => $type,
            'item_id' => intval($ref['item_id'] ?? 0),
        ];
    }

    /** @param mixed $list @return list<array{source_type: string, item_id: int, sort_order: int}> */
    private static function normalizeRotationList($list): array
    {
        if (!is_array($list)) {
            return [];
        }

        $out = [];
        foreach ($list as $idx => $ref) {
            $normalized = self::normalizeSpotlightRef($ref);
            if ($normalized['item_id'] <= 0) {
                continue;
            }
            $normalized['sort_order'] = intval(is_array($ref) ? ($ref['sort_order'] ?? $idx) : $idx);
            $out[] = $normalized;
        }

        usort($out, static fn (array $a, array $b): int => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

        return $out;
    }

    /** @param array{source_type: string, item_id: int} $ref @param array<string, mixed> $siteData */
    private static function resolveSpotlightRef(array $siteData, string $unitKey, array $ref): ?array
    {
        $type = $ref['source_type'] ?? 'news';
        $id = intval($ref['item_id'] ?? 0);
        if ($id <= 0 || !self::isValidType($type)) {
            return null;
        }

        $item = self::findItem($siteData, $unitKey, $type, $id);
        if (!$item || !self::isPubliclyVisible($item)) {
            return null;
        }

        $item['source_type'] = $type;
        $item['detail_url'] = self::detailUrlForItem($item, $unitKey, $type);

        return $item;
    }
}
