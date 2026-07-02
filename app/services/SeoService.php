<?php
/**
 * SEO service (global defaults + per-page overrides).
 */
class SeoService {
    private ContentService $contentService;

    public function __construct(ContentService $contentService) {
        $this->contentService = $contentService;
    }

    public static function getPageOptions(): array {
        return [
            'home' => 'Inicio (Rent A Car)',
            'venta-autos' => 'Venta de Autos',
            'inventario' => 'Inventario',
            'detalle' => 'Detalle de vehículo',
            'financiamiento' => 'Financiamiento',
            'nuestro-equipo' => 'Nuestro Equipo',
            'contactos' => 'Contactos (genérico)',
            'leasing' => 'Leasing',
            'leasing-flota' => 'Leasing - Flota',
            'leasing-equipo' => 'Leasing - Equipo',
            'leasing-sucursales' => 'Leasing - Sucursales',
            'leasing-contactos' => 'Leasing - Contactos',
            'renting' => 'Renting',
            'renting-servicios' => 'Renting - Servicios',
            'renting-sobre-nosotros' => 'Renting - Sobre Nosotros',
            'renting-contactos' => 'Renting - Contactos',
            'taller' => 'Taller',
            'taller-sucursales' => 'Taller - Sucursales',
            'taller-sobre-nosotros' => 'Taller - Sobre Nosotros',
            'blog' => 'Blog',
            'noticia' => 'Noticia',
            'flota' => 'Flota',
            'sucursales' => 'Sucursales',
            'terminos-condiciones' => 'Términos y Condiciones',
            'requisitos-alquiler' => 'Requisitos de alquiler',
            'pago-seguro' => 'Pago Seguro',
        ];
    }

    public function resolveForRequest(string $fallbackTitle, string $fallbackDescription): array {
        $siteData = $this->contentService->getAll();
        $seoGlobal = $siteData['seo']['global'] ?? [];
        $seoPages = $siteData['seo']['pages'] ?? [];

        $pageKey = $this->detectPageKey();
        $pageSeo = is_array($seoPages[$pageKey] ?? null) ? $seoPages[$pageKey] : [];

        $siteName = trim((string)($seoGlobal['site_name'] ?? 'Automarket'));
        $titleSuffix = trim((string)($seoGlobal['title_suffix'] ?? '| ' . $siteName));
        $defaultTitle = trim((string)($seoGlobal['default_title'] ?? ''));
        $defaultDesc = trim((string)($seoGlobal['default_description'] ?? $fallbackDescription));
        $defaultOgImage = trim((string)($seoGlobal['default_og_image'] ?? ''));
        $defaultRobots = trim((string)($seoGlobal['default_robots'] ?? 'index,follow'));
        $canonicalBase = rtrim(trim((string)($seoGlobal['canonical_base_url'] ?? '')), '/');

        $title = trim((string)($pageSeo['title'] ?? ''));
        if ($title === '') {
            $title = $defaultTitle !== '' ? $defaultTitle : trim($fallbackTitle . ' ' . $titleSuffix);
        }

        $pageFallbackDesc = self::getPageFallbackDescription($pageKey, $fallbackDescription);

        $description = trim((string)($pageSeo['description'] ?? ''));
        if ($description === '') {
            $description = $pageFallbackDesc !== ''
                ? $pageFallbackDesc
                : ($defaultDesc !== '' ? $defaultDesc : $fallbackDescription);
        }

        $keywords = trim((string)($pageSeo['keywords'] ?? ''));
        $robots = trim((string)($pageSeo['robots'] ?? ''));
        if ($robots === '') {
            $robots = $defaultRobots;
        }

        $canonical = trim((string)($pageSeo['canonical_url'] ?? ''));
        if ($canonical === '' && $canonicalBase !== '') {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            $canonical = $canonicalBase . $path;
        }

        $hreflangBase = $canonicalBase !== '' ? $canonicalBase : 'https://www.automarket.com.pa';

        $ogTitle = trim((string)($pageSeo['og_title'] ?? ''));
        if ($ogTitle === '') {
            $ogTitle = $title;
        }

        $ogDescription = trim((string)($pageSeo['og_description'] ?? ''));
        if ($ogDescription === '') {
            $ogDescription = $description;
        }

        $ogImage = trim((string)($pageSeo['og_image'] ?? ''));
        if ($ogImage === '') {
            $ogImage = $defaultOgImage;
        }
        // SE12: fallback seguro — garantiza que og:image / twitter:image nunca queden vacíos.
        // Se activa solo cuando tanto la imagen de página como el global default_og_image
        // están en blanco (campo no configurado en el admin SEO).
        if ($ogImage === '') {
            $ogImage = 'https://www.automarket.com.pa/assets/img/uploads/hero_bg_6a15c9ebb5ca7.webp';
        }
        $ogImage = self::toAbsoluteUrl($ogImage, $canonicalBase);

        return [
            'page_key' => $pageKey,
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'robots' => $robots,
            'canonical' => $canonical,
            'canonical_base' => $hreflangBase,
            'og_title' => $ogTitle,
            'og_description' => $ogDescription,
            'og_image' => $ogImage,
            'site_name' => $siteName,
        ];
    }

    private function detectPageKey(): string {
        $script = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
        $base = preg_replace('/\.php$/', '', $script) ?: 'home';
        if ($base === 'index' || $base === 'rent-a-car') {
            return 'home';
        }
        return $base;
    }

    /**
     * Descripciones únicas por página cuando no hay override en admin.
     */
    public static function getPageFallbackDescription(string $pageKey, string $callerFallback = ''): string {
        $map = [
            'home' => 'Reserva tu vehículo en Panamá con Automarket Rent a Car. Flota moderna, sucursales en todo el país y reserva en línea.',
            'venta-autos' => 'Encuentra autos seminuevos certificados en Panamá. Garantía, financiamiento y asesoría personalizada con Automarket.',
            'inventario' => 'Explora el inventario de autos seminuevos disponibles en Automarket Panamá. Filtra por marca, precio y ubicación.',
            'detalle' => 'Consulta la ficha completa de este vehículo seminuevo en Automarket Panamá.',
            'financiamiento' => 'Financiamiento de autos seminuevos en Panamá. Requisitos, perfiles y aliados bancarios de Automarket.',
            'nuestro-equipo' => 'Conoce al equipo de asesores de venta de Automarket Seminuevos en Panamá.',
            'contactos' => 'Contáctanos en Automarket Panamá. Rent a Car, Seminuevos, Leasing, Renting y Taller.',
            'leasing' => 'Leasing operativo para empresas en Panamá. Flota, mantenimiento y movilidad con Automarket.',
            'leasing-flota' => 'Flota de vehículos disponibles para leasing operativo con Automarket en Panamá.',
            'leasing-equipo' => 'Equipo comercial de Leasing Operativo Automarket en Panamá.',
            'leasing-sucursales' => 'Sucursales de Leasing Operativo Automarket en Panamá.',
            'leasing-contactos' => 'Solicita información de leasing operativo con Automarket en Panamá.',
            'renting' => 'Renting de autos en Panamá con cuota mensual todo incluido. Cotiza tu plan con Automarket.',
            'renting-servicios' => 'Servicios de renting corporativo y movilidad con Automarket Panamá.',
            'renting-sobre-nosotros' => 'Conoce Automarket Renting: soluciones de movilidad a largo plazo en Panamá.',
            'renting-contactos' => 'Contacta al equipo de Automarket Renting en Panamá.',
            'taller' => 'Taller autorizado Automarket en Panamá. Mantenimiento preventivo y correctivo.',
            'taller-sucursales' => 'Ubicación y horarios de los talleres Automarket en Panamá.',
            'taller-sobre-nosotros' => 'Servicios, marcas y equipo del taller Automarket en Panamá.',
            'blog' => 'Noticias y artículos del grupo Automarket en Panamá.',
            'noticia' => 'Artículo de Automarket Panamá.',
            'flota' => 'Catálogo de vehículos de alquiler Automarket Rent a Car en Panamá.',
            'sucursales' => 'Sucursales de Automarket Rent a Car en Panamá. Horarios y ubicación.',
            'terminos-condiciones' => 'Términos y condiciones de alquiler de Automarket Rent a Car.',
            'requisitos-alquiler' => 'Requisitos para alquilar un vehículo con Automarket en Panamá.',
            'pago-seguro' => 'Paga tu reserva de alquiler de forma segura con Automarket Rent a Car.',
            'contenido-reciente' => 'Novedades y actualizaciones de Automarket en Panamá.',
            'seminuevos-sucursales' => 'Sucursales de venta de autos seminuevos Automarket en Panamá.',
        ];

        return trim($map[$pageKey] ?? $callerFallback);
    }

    private static function toAbsoluteUrl(string $url, string $canonicalBase): string {
        $url = trim($url);
        if ($url === '' || preg_match('#^https?://#i', $url)) {
            return $url;
        }
        $base = $canonicalBase !== '' ? $canonicalBase : 'https://www.automarket.com.pa';
        if ($url[0] !== '/') {
            $url = '/' . $url;
        }

        return rtrim($base, '/') . $url;
    }

    /**
     * Páginas donde no debe emitirse hreflang (formularios, admin, detalle dinámico).
     */
    public static function shouldEmitHreflang(): bool
    {
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $skipScripts = [
            'sitemap.php', 'landing.php', 'detalle.php', 'noticia.php', 'unidad.php',
            'reservar.php', 'confirmacion.php', 'mi-reserva.php', 'extras.php', 'resultados.php',
        ];
        if (in_array($script, $skipScripts, true)) {
            return false;
        }
        $path = $_SERVER['SCRIPT_NAME'] ?? '';
        if (str_contains($path, '/admin/') || str_contains($path, '/api/')) {
            return false;
        }

        return true;
    }

    /**
     * @return list<array{hreflang:string,href:string}>
     */
    public static function buildHreflangAlternates(string $canonicalBase): array
    {
        if (!self::shouldEmitHreflang()) {
            return [];
        }

        $base = rtrim(trim($canonicalBase), '/');
        if ($base === '') {
            $base = 'https://www.automarket.com.pa';
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $query = [];
        $rawQuery = parse_url($uri, PHP_URL_QUERY);
        if (is_string($rawQuery) && $rawQuery !== '') {
            parse_str($rawQuery, $query);
        }
        unset($query['lang']);

        $build = static function (string $lang) use ($base, $path, $query): string {
            $q = $query;
            $q['lang'] = $lang;
            $qs = http_build_query($q);

            return rtrim($base, '/') . $path . ($qs !== '' ? '?' . $qs : '');
        };

        $esUrl = $build('es');

        return [
            ['hreflang' => 'es', 'href' => $esUrl],
            ['hreflang' => 'en', 'href' => $build('en')],
            ['hreflang' => 'x-default', 'href' => $esUrl],
        ];
    }

    public static function canonicalBaseFromSiteData(array $siteData): string
    {
        $base = rtrim(trim((string) ($siteData['seo']['global']['canonical_base_url'] ?? '')), '/');

        return $base !== '' ? $base : 'https://www.automarket.com.pa';
    }
}

