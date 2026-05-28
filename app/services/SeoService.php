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
        $defaultDesc = trim((string)($seoGlobal['default_description'] ?? $fallbackDescription));
        $defaultOgImage = trim((string)($seoGlobal['default_og_image'] ?? ''));
        $defaultRobots = trim((string)($seoGlobal['default_robots'] ?? 'index,follow'));
        $canonicalBase = rtrim(trim((string)($seoGlobal['canonical_base_url'] ?? '')), '/');

        $title = trim((string)($pageSeo['title'] ?? ''));
        if ($title === '') {
            $title = trim($fallbackTitle . ' ' . $titleSuffix);
        }

        $description = trim((string)($pageSeo['description'] ?? ''));
        if ($description === '') {
            $description = $defaultDesc;
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

        return [
            'page_key' => $pageKey,
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'robots' => $robots,
            'canonical' => $canonical,
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
}

