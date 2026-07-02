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
            $legacy = UnitContentService::newsToLegacyNoticia($item);
            $posts[] = $this->normalizePost($legacy, 'rentacar', 'Rent A Car', '/noticia.php?id=', 'thumbnail');
        }
        foreach (UnitContentService::getItems($site, 'rentacar', 'blog') as $item) {
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

        $social = array_map([self::class, 'normalizeSocialEntry'], $social);

        $sucursales = $stored['sucursales'] ?? [];

        $columns = $stored['columns'] ?? [];
        if ($columns === []) {
            $columns = [
                [
                    'id'    => 'recursos',
                    'title' => 'Recursos',
                    'links' => [
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
        static $domainMap = [
            'facebook.com'  => ['label' => 'Facebook',  'icon' => 'bi-facebook'],
            'instagram.com' => ['label' => 'Instagram', 'icon' => 'bi-instagram'],
            'linkedin.com'  => ['label' => 'LinkedIn',  'icon' => 'bi-linkedin'],
            'youtube.com'   => ['label' => 'YouTube',   'icon' => 'bi-youtube'],
            'youtu.be'      => ['label' => 'YouTube',   'icon' => 'bi-youtube'],
            'tiktok.com'    => ['label' => 'TikTok',    'icon' => 'bi-tiktok'],
            'twitter.com'   => ['label' => 'Twitter',   'icon' => 'bi-twitter-x'],
            'x.com'         => ['label' => 'Twitter',   'icon' => 'bi-twitter-x'],
        ];

        static $labelIconMap = [
            'facebook'  => 'bi-facebook',
            'instagram' => 'bi-instagram',
            'linkedin'  => 'bi-linkedin',
            'youtube'   => 'bi-youtube',
            'tiktok'    => 'bi-tiktok',
            'twitter'   => 'bi-twitter-x',
            'x'         => 'bi-twitter-x',
        ];

        $url = $entry['url'] ?? '#';

        // Normalizar por URL cuando apunta a un dominio reconocido
        if ($url !== '#' && $url !== '') {
            $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
            $host = preg_replace('/^www\./', '', $host);
            if (isset($domainMap[$host])) {
                $entry['label'] = $domainMap[$host]['label'];
                $entry['icon']  = $domainMap[$host]['icon'];
                return $entry;
            }
        }

        // Normalizar icon por label cuando la URL es '#' o vacía
        $labelKey = strtolower(trim((string) ($entry['label'] ?? '')));
        if (isset($labelIconMap[$labelKey])) {
            $entry['icon'] = $labelIconMap[$labelKey];
        }

        return $entry;
    }

    public static function slugifyPageKey(string $key): string
    {
        return str_replace('_', '-', $key);
    }
}
