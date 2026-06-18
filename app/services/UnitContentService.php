<?php
/**
 * Gestor unificado de contenido por unidad de negocio (latest, blog, news).
 */
class UnitContentService
{
    public const TYPES = ['latest', 'blog', 'news'];

    public const TYPE_LABELS = [
        'latest' => 'Contenido más reciente',
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

    public static function isValidType(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    /** @param array<string, mixed> $siteData */
    public static function ensureMigrated(array &$siteData, string $unitKey): bool
    {
        if ($unitKey !== 'rentacar') {
            return false;
        }

        $dataKey = self::unitDataKey($unitKey);
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
            $siteData[$dataKey]['content'] = self::mergeDefaultContent($content);
            return true;
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

        $siteData[$dataKey]['content'] = self::mergeDefaultContent($content);
        self::syncRentacarLegacyNoticias($siteData);

        return $changed;
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
        $dataKey = self::unitDataKey($unitKey);
        $content = $siteData[$dataKey]['content'] ?? [];
        if (!is_array($content)) {
            $content = [];
        }

        return self::mergeDefaultContent($content);
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
            'published' => !isset($row['published']) || filter_var($row['published'], FILTER_VALIDATE_BOOLEAN),
            'show_on_home' => !isset($row['show_on_home']) || filter_var($row['show_on_home'], FILTER_VALIDATE_BOOLEAN),
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

    /** @param array<string, mixed> $settings @param array<string, mixed> $overrides */
    public static function normalizeSettings(array $settings, array $overrides = []): array
    {
        $mode = trim((string) ($settings['home_display_mode'] ?? 'rotation'));
        if (!in_array($mode, ['single', 'rotation'], true)) {
            $mode = 'rotation';
        }

        $normalized = [
            'home_block_enabled' => !isset($settings['home_block_enabled']) || filter_var($settings['home_block_enabled'], FILTER_VALIDATE_BOOLEAN),
            'home_display_mode' => $mode,
            'home_single' => self::normalizeSpotlightRef($settings['home_single'] ?? []),
            'home_rotation' => self::normalizeRotationList($settings['home_rotation'] ?? []),
            'home_rotation_interval_ms' => max(3000, intval($settings['home_rotation_interval_ms'] ?? 6000)),
            'latest_show_on_home' => !isset($settings['latest_show_on_home']) || filter_var($settings['latest_show_on_home'], FILTER_VALIDATE_BOOLEAN),
            'latest_home_limit' => max(1, min(12, intval($settings['latest_home_limit'] ?? 4))),
        ];

        foreach ($overrides as $key => $value) {
            $normalized[$key] = $value;
        }

        return $normalized;
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

    public static function detailUrl(string $unitKey, string $type, int $id): string
    {
        if ($unitKey === 'rentacar') {
            if ($type === 'blog') {
                return '/blog.php?type=blog&id=' . $id;
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
        if (!$item || empty($item['published']) || !self::isWithinSchedule($item)) {
            return null;
        }

        $item['source_type'] = $type;
        $item['detail_url'] = self::detailUrl($unitKey, $type, $id);

        return $item;
    }
}
