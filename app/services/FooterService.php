<?php
/**
 * Configuración y contenido del pie de página (grupo comercial).
 */

class FooterService
{
    public const PAGE_KEYS = ['sobre_nosotros', 'terminos', 'faq', 'subastas'];

    public const PAGE_LABELS = [
        'sobre_nosotros' => 'Sobre nosotros',
        'terminos' => 'Términos y condiciones',
        'faq' => 'Preguntas frecuentes',
        'subastas' => 'Subastas',
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

        foreach ($site['homepage']['noticias'] ?? [] as $item) {
            $posts[] = $this->normalizePost($item, 'rentacar', 'Rent A Car', '/noticia.php?id=', 'thumbnail');
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
            'privacy_url' => '#privacidad',
            'cookies_url' => '#cookies',
            'resources_title' => 'Recursos',
            'also_know_title' => 'Conoce también',
            'follow_title' => 'Síguenos',
            'payment_title' => 'Medios de pago',
            'payment_badges_html' => '<span class="badge bg-light text-dark px-2 py-1 fs-6"><i class="bi bi-credit-card-2-back-fill text-primary"></i> Visa</span>
                <span class="badge bg-light text-dark px-2 py-1 fs-6"><i class="bi bi-credit-card-2-front-fill text-danger"></i> Mastercard</span>
                <span class="badge bg-light text-dark px-2 py-1 fs-6"><i class="bi bi-bank text-success"></i> ACH</span>',
        ], $stored['general'] ?? []);

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
                ['id' => 's1', 'label' => 'Facebook', 'icon' => 'bi-facebook', 'url' => '#', 'sort_order' => 1, 'active' => true],
                ['id' => 's2', 'label' => 'Instagram', 'icon' => 'bi-instagram', 'url' => '#', 'sort_order' => 2, 'active' => true],
                ['id' => 's3', 'label' => 'LinkedIn', 'icon' => 'bi-linkedin', 'url' => '#', 'sort_order' => 3, 'active' => true],
                ['id' => 's4', 'label' => 'YouTube', 'icon' => 'bi-youtube', 'url' => '#', 'sort_order' => 4, 'active' => true],
            ];
        }

        $sucursales = $stored['sucursales'] ?? [];

        return [
            'general' => $general,
            'pages' => $pages,
            'also_know' => $alsoKnow,
            'social' => $social,
            'sucursales' => $sucursales,
        ];
    }

    public static function slugifyPageKey(string $key): string
    {
        return str_replace('_', '-', $key);
    }
}
