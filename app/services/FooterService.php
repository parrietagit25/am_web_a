<?php
/**
 * Configuración y contenido del pie de página (grupo comercial).
 */

class FooterService
{
    public const PAGE_KEYS = [
        'sobre_nosotros',
        'terminos',
        'faq',
        'subastas',
        'privacidad',
        'cookies',
    ];

    public const PAGE_LABELS = [
        'sobre_nosotros' => 'Sobre nosotros',
        'terminos' => 'Términos y condiciones',
        'faq' => 'Preguntas frecuentes',
        'subastas' => 'Subastas',
        'privacidad' => 'Política de privacidad',
        'cookies' => 'Política de cookies',
    ];

    public const UNIT_LABELS = [
        'rentacar' => 'Rent A Car',
        'seminuevos' => 'Venta de Autos',
        'leasing' => 'Leasing Operativo',
        'renting' => 'Renting',
        'taller' => 'Taller',
        'grupo' => 'Grupo PCR',
    ];

    private ContentService $content;

    public function __construct(?ContentService $content = null)
    {
        $this->content = $content ?? new ContentService();
    }

    public function getFooter(): array
    {
        $site = $this->content->getAll();
        $global = $site['global'] ?? [];
        $stored = $site['footer'] ?? [];
        return $this->mergeDefaults($stored, $global);
    }

    public function getPage(string $key): ?array
    {
        if (!in_array($key, self::PAGE_KEYS, true)) {
            return null;
        }
        $footer = $this->getFooter();
        $page = $footer['pages'][$key] ?? null;
        if (!is_array($page) || ($page['active'] ?? true) === false) {
            return null;
        }
        return $page;
    }

    /** @return array<int, array<string, mixed>> */
    public function collectAllBlogPosts(): array
    {
        $site = $this->content->getAll();
        $posts = [];

        require_once __DIR__ . '/UnitContentService.php';
        UnitContentService::ensureMigrated($site, 'rentacar');

        foreach (UnitContentService::getItems($site, 'rentacar', 'news') as $item) {
            if (!UnitContentService::isPubliclyVisible($item)) {
                continue;
            }
            $legacy = UnitContentService::newsToLegacyNoticia($item);
            $posts[] = $this->normalizePost($legacy, 'rentacar', 'Rent A Car', '/noticia.php?id=', 'thumbnail');
        }
        foreach (UnitContentService::getItems($site, 'rentacar', 'blog') as $item) {
            if (!UnitContentService::isPubliclyVisible($item)) {
                continue;
            }
            $legacy = UnitContentService::newsToLegacyNoticia($item);
            $posts[] = $this->normalizePost($legacy, 'rentacar', 'Rent A Car', '/noticia.php?type=blog&id=', 'thumbnail');
        }
        foreach ($site['leasing']['posts'] ?? [] as $item) {
            $id = $item['id'] ?? 0;
            $posts[] = $this->normalizePost($item, 'leasing', 'Leasing Operativo', '/leasing.php#post-', 'image_url', $id);
        }
        foreach ($site['renting']['posts'] ?? [] as $item) {
            $id = $item['id'] ?? 0;
            $posts[] = $this->normalizePost($item, 'renting', 'Renting', '/renting-publicacion.php?id=', 'image_url', $id);
        }

        usort($posts, function ($a, $b) {
            return strcmp($b['sort_date'] ?? '', $a['sort_date'] ?? '');
        });

        return $posts;
    }

    /** @return array<int, array<string, mixed>> */
    public function getActiveSucursales(): array
    {
        $footer = $this->getFooter();
        $list = $footer['sucursales'] ?? [];
        $active = array_filter($list, function ($s) {
            return ($s['active'] ?? true) !== false;
        });
        usort($active, function ($a, $b) {
            return intval($a['sort_order'] ?? 99) - intval($b['sort_order'] ?? 99);
        });
        return array_values($active);
    }

    /**
     * Columnas de enlaces activas, ordenadas y con links renderizables (B1).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveColumns(): array
    {
        $footer = $this->getFooter();

        return self::filterActiveColumns($footer['columns'] ?? [], $footer['general'] ?? []);
    }

    /**
     * @param array<int, mixed> $columns
     * @param array<string, mixed> $general
     * @return array<int, array<string, mixed>>
     */
    public static function filterActiveColumns(array $columns, array $general = []): array
    {
        $normalized = self::normalizeColumns($columns, $general);
        $active = [];

        foreach ($normalized as $column) {
            if (($column['active'] ?? true) === false) {
                continue;
            }

            $links = self::filterRenderableColumnLinks($column['links'] ?? []);
            if ($links === []) {
                continue;
            }

            $column['links'] = $links;
            $active[] = $column;
        }

        usort($active, static fn ($a, $b) => intval($a['sort_order'] ?? 99) <=> intval($b['sort_order'] ?? 99));

        return array_values($active);
    }

    /**
     * Seed por defecto cuando site_data no tiene footer.columns (prod).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultFooterColumns(): array
    {
        return [
            [
                'id'         => 'recursos',
                'title'      => 'Recursos',
                'sort_order' => 1,
                'active'     => true,
                'links'      => [
                    ['id' => 'res1', 'label' => 'Sobre nosotros',         'url' => '/pagina-institucional.php?p=sobre-nosotros', 'sort_order' => 1, 'active' => true],
                    ['id' => 'res2', 'label' => 'Términos y condiciones', 'url' => '/pagina-institucional.php?p=terminos',        'sort_order' => 2, 'active' => true],
                    ['id' => 'res3', 'label' => 'Preguntas frecuentes',   'url' => '/pagina-institucional.php?p=faq',             'sort_order' => 3, 'active' => true],
                    ['id' => 'res4', 'label' => 'Sucursales',             'url' => '/sucursales-grupo.php',                       'sort_order' => 4, 'active' => true],
                    ['id' => 'res5', 'label' => 'Sostenibilidad',         'url' => '/sostenibilidad.php',                         'sort_order' => 5, 'active' => true],
                    ['id' => 'res6', 'label' => 'Subastas',               'url' => '/pagina-institucional.php?p=subastas',         'sort_order' => 6, 'active' => true],
                    ['id' => 'res7', 'label' => 'Blog',                   'url' => '/blog-grupo.php',                             'sort_order' => 7, 'active' => true],
                ],
            ],
        ];
    }

    public static function isExternalFooterLink(string $url): bool
    {
        $url = trim($url);

        return $url !== '' && preg_match('/^https?:\/\//i', $url) === 1;
    }

    /**
     * @return array{link_type: string, open_in: string}
     */
    public static function deriveFooterLinkMeta(string $url, ?string $linkType = null, ?string $openIn = null): array
    {
        $isExternal = self::isExternalFooterLink($url);
        $derivedType = $isExternal ? 'external' : 'internal';
        $type = in_array($linkType, ['internal', 'external'], true) ? $linkType : $derivedType;

        if ($type === 'external' || $isExternal) {
            $type = 'external';
            $open = in_array($openIn, ['same', 'new'], true) ? $openIn : 'new';
        } else {
            $type = 'internal';
            $open = in_array($openIn, ['same', 'new'], true) ? $openIn : 'same';
        }

        return ['link_type' => $type, 'open_in' => $open];
    }

    /**
     * @param array<string, mixed> $link
     * @return array<string, mixed>
     */
    public static function normalizeFooterLink(array $link, int $index = 0, string $columnId = 'col'): array
    {
        $label = trim((string) ($link['label'] ?? ''));
        $url = trim((string) ($link['url'] ?? ''));
        $id = trim((string) ($link['id'] ?? ''));
        if ($id === '') {
            $id = $columnId . '_link_' . $index;
        }

        $meta = self::deriveFooterLinkMeta(
            $url,
            isset($link['link_type']) ? (string) $link['link_type'] : null,
            isset($link['open_in']) ? (string) $link['open_in'] : null
        );

        return [
            'id'         => $id,
            'label'      => $label,
            'url'        => $url,
            'sort_order' => intval($link['sort_order'] ?? 99),
            'active'     => ($link['active'] ?? true) !== false,
            'link_type'  => $meta['link_type'],
            'open_in'    => $meta['open_in'],
        ];
    }

    /**
     * @param array<int, mixed> $links
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeColumnLinks(array $links, string $columnId): array
    {
        $normalized = [];
        $seenUrls = [];

        foreach ($links as $i => $link) {
            if (!is_array($link)) {
                continue;
            }

            $entry = self::normalizeFooterLink($link, (int) $i, $columnId);
            $urlKey = strtolower(rtrim($entry['url'], '/'));
            if ($urlKey === '' || $urlKey === '#') {
                $normalized[] = $entry;
                continue;
            }
            if (isset($seenUrls[$urlKey])) {
                continue;
            }
            $seenUrls[$urlKey] = true;
            $normalized[] = $entry;
        }

        usort($normalized, static fn ($a, $b) => intval($a['sort_order'] ?? 99) <=> intval($b['sort_order'] ?? 99));

        return array_values($normalized);
    }

    /**
     * @param array<int, mixed> $links
     * @return array<int, array<string, mixed>>
     */
    public static function filterRenderableColumnLinks(array $links): array
    {
        $renderable = [];

        foreach ($links as $link) {
            if (!is_array($link)) {
                continue;
            }
            if (($link['active'] ?? true) === false) {
                continue;
            }
            $label = trim((string) ($link['label'] ?? ''));
            $url = trim((string) ($link['url'] ?? ''));
            if ($label === '' || $url === '' || $url === '#') {
                continue;
            }
            $renderable[] = $link;
        }

        usort($renderable, static fn ($a, $b) => intval($a['sort_order'] ?? 99) <=> intval($b['sort_order'] ?? 99));

        return array_values($renderable);
    }

    /**
     * @param array<string, mixed> $column
     * @param array<string, mixed> $general
     * @return array<string, mixed>
     */
    public static function normalizeFooterColumn(array $column, int $index = 0, array $general = []): array
    {
        $id = trim((string) ($column['id'] ?? ''));
        if ($id === '') {
            $id = 'col_' . $index;
        }

        $title = trim((string) ($column['title'] ?? ''));
        if ($title === '' && $id === 'recursos') {
            $title = trim((string) ($general['resources_title'] ?? 'Recursos'));
        }
        if ($title === '') {
            $title = 'Columna';
        }

        $links = self::normalizeColumnLinks(is_array($column['links'] ?? null) ? $column['links'] : [], $id);

        return [
            'id'         => $id,
            'title'      => $title,
            'sort_order' => intval($column['sort_order'] ?? ($id === 'recursos' ? 1 : (($index + 1) * 10))),
            'active'     => ($column['active'] ?? true) !== false,
            'links'      => $links,
        ];
    }

    /**
     * Normaliza N columnas; garantiza legacy recursos si falta.
     *
     * @param array<int, mixed> $columns
     * @param array<string, mixed> $general
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeColumns(array $columns, array $general = []): array
    {
        if ($columns === []) {
            $columns = self::defaultFooterColumns();
        }

        $normalized = [];
        $seenIds = [];

        foreach ($columns as $i => $column) {
            if (!is_array($column)) {
                continue;
            }

            $entry = self::normalizeFooterColumn($column, (int) $i, $general);
            $colId = $entry['id'];
            if (isset($seenIds[$colId])) {
                continue;
            }
            $seenIds[$colId] = true;
            $normalized[] = $entry;
        }

        $hasRecursos = false;
        foreach ($normalized as $col) {
            if (($col['id'] ?? '') === 'recursos') {
                $hasRecursos = true;
                break;
            }
        }
        if (!$hasRecursos) {
            array_unshift($normalized, self::normalizeFooterColumn(self::defaultFooterColumns()[0], 0, $general));
        }

        usort($normalized, static fn ($a, $b) => intval($a['sort_order'] ?? 99) <=> intval($b['sort_order'] ?? 99));

        return array_values($normalized);
    }

    private function normalizePost(array $item, string $unitKey, string $unitLabel, string $urlPrefix, string $imageKey, $id = null): array
    {
        $postId = $id ?? ($item['id'] ?? 0);
        $url = $urlPrefix . urlencode((string) $postId);
        if ($unitKey === 'leasing') {
            $url = '/leasing.php';
        }

        return [
            'id' => $postId,
            'unit_key' => $unitKey,
            'unit_label' => $unitLabel,
            'title' => $item['title'] ?? '',
            'excerpt' => $item['excerpt'] ?? ($item['desc'] ?? ''),
            'date' => $item['date'] ?? '',
            'sort_date' => $item['date'] ?? date('Y-m-d'),
            'thumbnail' => $item[$imageKey] ?? ($item['banner'] ?? ''),
            'link_text' => $item['link_text'] ?? 'Ver más',
            'url' => $url,
        ];
    }

    private function mergeDefaults(array $stored, array $global): array
    {
        $general = array_merge([
            'tagline' => '"Juntos transformamos la movilidad en satisfacción"',
            'address' => $global['address'] ?? 'Vía España, Edificio Automarket, Ciudad de Panamá',
            'phone_display' => $global['phone_display'] ?? '(507) 279-2700',
            'email' => $global['email'] ?? 'info@automarket.com.pa',
            'logo_url' => '/assets/img/logo.png',
            'copyright' => $global['footer_copyright'] ?? 'Automarket. Todos los derechos reservados.',
            'privacy_url' => '/pagina-institucional.php?p=privacidad',
            'cookies_url' => '/pagina-institucional.php?p=cookies',
            'resources_title' => 'Recursos',
            'also_know_title' => 'Conoce también',
            'follow_title' => 'Síguenos',
            'payment_title' => 'Medios de pago',
            'payment_badges_html' => '',
        ], $stored['general'] ?? []);

        $general['copyright'] = self::normalizeCopyrightText((string) ($general['copyright'] ?? ''));

        if (in_array($general['privacy_url'] ?? '', ['#privacidad', '#', ''], true)) {
            $general['privacy_url'] = '/pagina-institucional.php?p=privacidad';
        }
        if (in_array($general['cookies_url'] ?? '', ['#cookies', '#', ''], true)) {
            $general['cookies_url'] = '/pagina-institucional.php?p=cookies';
        }

        $pages = $stored['pages'] ?? [];
        foreach (self::PAGE_KEYS as $key) {
            if (!isset($pages[$key])) {
                $pages[$key] = [
                    'title' => self::PAGE_LABELS[$key],
                    'content_html' => '',
                    'active' => true,
                ];
            }
        }

        $alsoKnow = $stored['also_know'] ?? [];
        if ($alsoKnow === []) {
            $alsoKnow = [
                ['id' => 'ak1', 'label' => 'Rent A Car', 'url' => '/rent-a-car.php', 'sort_order' => 1, 'active' => true],
                ['id' => 'ak2', 'label' => 'Venta de Autos', 'url' => '/venta-autos.php', 'sort_order' => 2, 'active' => true],
                ['id' => 'ak3', 'label' => 'Leasing Operativo', 'url' => '/leasing.php', 'sort_order' => 3, 'active' => true],
                ['id' => 'ak4', 'label' => 'Renting', 'url' => '/renting.php', 'sort_order' => 4, 'active' => true],
                ['id' => 'ak5', 'label' => 'Taller', 'url' => '/taller.php', 'sort_order' => 5, 'active' => true],
            ];
        }

        $social = $stored['social'] ?? [];
        if ($social === []) {
            $social = [
                ['id' => 's1', 'label' => 'Facebook',  'icon' => 'bi-facebook',  'url' => '#', 'sort_order' => 1, 'active' => true],
                ['id' => 's2', 'label' => 'Instagram', 'icon' => 'bi-instagram', 'url' => '#', 'sort_order' => 2, 'active' => true],
                ['id' => 's3', 'label' => 'LinkedIn',  'icon' => 'bi-linkedin',  'url' => '#', 'sort_order' => 3, 'active' => true],
                ['id' => 's4', 'label' => 'YouTube',   'icon' => 'bi-youtube',   'url' => '#', 'sort_order' => 4, 'active' => true],
            ];
        }

        $social = self::filterRenderableSocial($social);

        $sucursales = $stored['sucursales'] ?? [];

        $columns = self::normalizeColumns($stored['columns'] ?? [], $general);

        return [
            'general'   => $general,
            'pages'     => $pages,
            'also_know' => $alsoKnow,
            'social'    => $social,
            'sucursales'=> $sucursales,
            'columns'   => $columns,
        ];
    }

    /**
     * Evita duplicar © y año cuando el CMS ya los incluye en el texto.
     */
    public static function normalizeCopyrightText(string $raw): string
    {
        $text = trim(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($text === '') {
            return 'Automarket. Todos los derechos reservados.';
        }

        $currentYear = (string) date('Y');
        $yearPattern = preg_quote($currentYear, '/');

        $text = preg_replace('/\s*©\s*/u', ' ', $text);
        $text = preg_replace('/\b' . $yearPattern . '\b/u', ' ', $text);
        $text = preg_replace('/^(?:\d{4}\s*[-–—:.]?\s*)+/u', '', $text);
        $text = preg_replace('/\s{2,}/u', ' ', $text);
        $text = trim($text, " \t\n\r\0\x0B©-–—:.");

        return trim($text) !== '' ? trim($text) : 'Automarket. Todos los derechos reservados.';
    }

    /**
     * @return array<string, array{label: string, icon: string, hosts: list<string>}>
     */
    public static function socialPlatformCatalog(): array
    {
        return [
            'facebook'  => ['label' => 'Facebook',  'icon' => 'bi-facebook',  'hosts' => ['facebook.com', 'fb.com', 'fb.me']],
            'instagram' => ['label' => 'Instagram', 'icon' => 'bi-instagram', 'hosts' => ['instagram.com']],
            'linkedin'  => ['label' => 'LinkedIn',  'icon' => 'bi-linkedin',  'hosts' => ['linkedin.com']],
            'youtube'   => ['label' => 'YouTube',   'icon' => 'bi-youtube',   'hosts' => ['youtube.com', 'youtu.be']],
            'tiktok'    => ['label' => 'TikTok',    'icon' => 'bi-tiktok',    'hosts' => ['tiktok.com']],
            'twitter'   => ['label' => 'Twitter',   'icon' => 'bi-twitter-x', 'hosts' => ['twitter.com', 'x.com']],
        ];
    }

    public static function detectSocialPlatformFromUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '' || $url === '#') {
            return null;
        }

        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        $host = preg_replace('/^www\./', '', $host);
        if ($host === '') {
            return null;
        }

        foreach (self::socialPlatformCatalog() as $platform => $meta) {
            if (in_array($host, $meta['hosts'], true)) {
                return $platform;
            }
        }

        return null;
    }

    public static function detectSocialPlatformFromLabel(string $label): ?string
    {
        $normalized = strtolower(trim($label));
        $normalized = preg_replace('/[^a-z0-9]/', '', $normalized) ?? '';
        if ($normalized === '') {
            return null;
        }

        foreach (array_keys(self::socialPlatformCatalog()) as $platform) {
            if ($normalized === $platform || str_contains($normalized, $platform)) {
                return $platform;
            }
        }

        return null;
    }

    public static function detectSocialPlatformFromIcon(string $icon): ?string
    {
        $icon = strtolower(trim($icon));
        if ($icon === '') {
            return null;
        }

        foreach (self::socialPlatformCatalog() as $platform => $meta) {
            if (str_contains($icon, $platform)) {
                return $platform;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $entry
     */
    public static function isSocialEntryRenderable(array $entry): bool
    {
        if (($entry['active'] ?? true) === false) {
            return false;
        }

        $url = trim((string) ($entry['url'] ?? ''));
        if ($url === '' || $url === '#') {
            return false;
        }

        $urlPlatform = self::detectSocialPlatformFromUrl($url);
        if ($urlPlatform === null) {
            return false;
        }

        $labelPlatform = self::detectSocialPlatformFromLabel((string) ($entry['label'] ?? ''));
        if ($labelPlatform !== null && $labelPlatform !== $urlPlatform) {
            return false;
        }

        $iconPlatform = self::detectSocialPlatformFromIcon((string) ($entry['icon'] ?? ''));
        if ($iconPlatform !== null && $iconPlatform !== $urlPlatform) {
            return false;
        }

        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     * @return array<int, array<string, mixed>>
     */
    public static function filterRenderableSocial(array $entries): array
    {
        $seenUrls = [];
        $seenPlatforms = [];
        $filtered = [];

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $entry = self::normalizeSocialEntry($entry);
            if (!self::isSocialEntryRenderable($entry)) {
                continue;
            }

            $url = strtolower(rtrim(trim((string) ($entry['url'] ?? '')), '/'));
            $platform = self::detectSocialPlatformFromUrl((string) ($entry['url'] ?? ''));
            if ($url !== '' && isset($seenUrls[$url])) {
                continue;
            }
            if ($platform !== null && isset($seenPlatforms[$platform])) {
                continue;
            }

            if ($url !== '') {
                $seenUrls[$url] = true;
            }
            if ($platform !== null) {
                $seenPlatforms[$platform] = true;
            }

            $filtered[] = $entry;
        }

        usort($filtered, static fn ($a, $b) => intval($a['sort_order'] ?? 99) <=> intval($b['sort_order'] ?? 99));

        return array_values($filtered);
    }

    public static function isSocialUrlMatchingPlatform(string $platformKey, string $url): bool
    {
        $platformKey = strtolower(trim($platformKey));
        $urlPlatform = self::detectSocialPlatformFromUrl($url);

        return $urlPlatform !== null && $urlPlatform === $platformKey;
    }

    /**
     * Corrige inconsistencias entre label, icon y URL de una red social.
     *
     * Reglas:
     * - Si la URL contiene un dominio reconocido, el label e icon se fuerzan al valor correcto.
     * - Si el label es "TikTok" (o variantes) pero la URL no es de TikTok, se fuerza icon bi-tiktok
     *   y se deja la URL tal cual (puede ser '#'). No se inventa URL.
     *
     * @param array<string, mixed> $entry
     * @return array<string, mixed>
     */
    public static function normalizeSocialEntry(array $entry): array
    {
        $url = trim((string) ($entry['url'] ?? '#'));
        $labelPlatform = self::detectSocialPlatformFromLabel((string) ($entry['label'] ?? ''));
        $urlPlatform = self::detectSocialPlatformFromUrl($url);
        $catalog = self::socialPlatformCatalog();

        if ($urlPlatform !== null && ($labelPlatform === null || $labelPlatform === $urlPlatform)) {
            $entry['label'] = $catalog[$urlPlatform]['label'];
            $entry['icon']  = $catalog[$urlPlatform]['icon'];

            return $entry;
        }

        if ($labelPlatform !== null) {
            $entry['icon'] = $catalog[$labelPlatform]['icon'];
        }

        return $entry;
    }

    public static function slugifyPageKey(string $key): string
    {
        return str_replace('_', '-', $key);
    }
}
