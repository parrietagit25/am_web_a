<?php
/**
 * Generación de sitemap.xml (páginas estáticas principales).
 */
class SitemapService
{
    /** @var list<array{path:string,query?:array<string,string>,changefreq?:string,priority?:string}> */
    private const STATIC_PAGES = [
        ['path' => '/', 'changefreq' => 'weekly', 'priority' => '1.0'],
        ['path' => '/rent-a-car.php', 'changefreq' => 'weekly', 'priority' => '0.9'],
        ['path' => '/venta-autos.php', 'changefreq' => 'weekly', 'priority' => '0.9'],
        ['path' => '/inventario.php', 'changefreq' => 'daily', 'priority' => '0.9'],
        ['path' => '/leasing.php', 'changefreq' => 'weekly', 'priority' => '0.8'],
        ['path' => '/renting.php', 'changefreq' => 'weekly', 'priority' => '0.8'],
        ['path' => '/taller.php', 'changefreq' => 'weekly', 'priority' => '0.8'],
        ['path' => '/financiamiento.php', 'changefreq' => 'monthly', 'priority' => '0.7'],
        ['path' => '/sucursales.php', 'changefreq' => 'monthly', 'priority' => '0.7'],
        ['path' => '/seminuevos-sucursales.php', 'changefreq' => 'monthly', 'priority' => '0.7'],
        ['path' => '/leasing-sucursales.php', 'changefreq' => 'monthly', 'priority' => '0.7'],
        ['path' => '/renting-sucursales.php', 'changefreq' => 'monthly', 'priority' => '0.7'],
        ['path' => '/taller-sucursales.php', 'changefreq' => 'monthly', 'priority' => '0.7'],
        ['path' => '/contactos.php', 'changefreq' => 'monthly', 'priority' => '0.6'],
        ['path' => '/pagina-institucional.php', 'query' => ['p' => 'sobre-nosotros'], 'changefreq' => 'yearly', 'priority' => '0.5'],
        ['path' => '/pagina-institucional.php', 'query' => ['p' => 'faq'], 'changefreq' => 'yearly', 'priority' => '0.5'],
        ['path' => '/sostenibilidad.php', 'changefreq' => 'yearly', 'priority' => '0.5'],
    ];

    public static function canonicalBase(ContentService $contentService): string
    {
        $siteData = $contentService->getAll();
        $base = rtrim(trim((string) ($siteData['seo']['global']['canonical_base_url'] ?? '')), '/');

        return $base !== '' ? $base : 'https://www.automarket.com.pa';
    }

    /**
     * @return list<array{loc:string,changefreq:string,priority:string,lastmod?:string}>
     */
    public static function collectUrls(ContentService $contentService): array
    {
        $base = self::canonicalBase($contentService);
        $lastmod = date('Y-m-d');
        $urls = [];

        foreach (self::STATIC_PAGES as $page) {
            $path = $page['path'];
            $query = $page['query'] ?? [];
            $loc = rtrim($base, '/') . $path;
            if (!empty($query)) {
                $loc .= '?' . http_build_query($query);
            }
            $urls[] = [
                'loc' => $loc,
                'changefreq' => $page['changefreq'] ?? 'monthly',
                'priority' => $page['priority'] ?? '0.5',
                'lastmod' => $lastmod,
            ];
        }

        return $urls;
    }

    public static function renderXml(ContentService $contentService): string
    {
        $urls = self::collectUrls($contentService);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $entry) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($entry['loc'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</loc>\n";
            if (!empty($entry['lastmod'])) {
                $xml .= '    <lastmod>' . htmlspecialchars($entry['lastmod'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</lastmod>\n";
            }
            $xml .= '    <changefreq>' . htmlspecialchars($entry['changefreq'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</changefreq>\n";
            $xml .= '    <priority>' . htmlspecialchars($entry['priority'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= "</urlset>\n";

        return $xml;
    }
}
