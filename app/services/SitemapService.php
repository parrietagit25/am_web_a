<?php
/**
 * Generación de sitemap.xml (páginas estáticas + vehículos disponibles).
 */
class SitemapService
{
    private const MAX_VEHICLE_URLS = 1000;

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
            $urls[] = self::entryFromStaticPage($base, $page, $lastmod);
        }

        foreach (self::collectVehicleUrls($base, $lastmod) as $vehicleEntry) {
            $urls[] = $vehicleEntry;
        }

        return $urls;
    }

    /**
     * @param array{path:string,query?:array<string,string>,changefreq?:string,priority?:string} $page
     * @return array{loc:string,changefreq:string,priority:string,lastmod?:string}
     */
    private static function entryFromStaticPage(string $base, array $page, string $lastmod): array
    {
        $path = $page['path'];
        $query = $page['query'] ?? [];
        $loc = rtrim($base, '/') . $path;
        if (!empty($query)) {
            $loc .= '?' . http_build_query($query);
        }

        return [
            'loc' => $loc,
            'changefreq' => $page['changefreq'] ?? 'monthly',
            'priority' => $page['priority'] ?? '0.5',
            'lastmod' => $lastmod,
        ];
    }

    /**
     * @return list<array{loc:string,changefreq:string,priority:string,lastmod?:string}>
     */
    private static function collectVehicleUrls(string $base, string $defaultLastmod): array
    {
        try {
            self::bootstrapDatabaseConstants();

            require_once __DIR__ . '/Database.php';
            require_once __DIR__ . '/VehicleSlugHelper.php';

            $db = Database::getInstance();
            $limit = self::MAX_VEHICLE_URLS;
            $rows = $db->select(
                "SELECT id, Make, Model, Year, LicensePlate, date_update, trg_updatefechaWeb, LoadDate
                 FROM Automarket_Invs_web
                 WHERE Status = 'DISPONIBLE'
                 ORDER BY id DESC
                 LIMIT {$limit}"
            );

            if (!is_array($rows) || $rows === []) {
                return [];
            }

            $seen = [];
            $urls = [];

            foreach ($rows as $vehicle) {
                if (!is_array($vehicle)) {
                    continue;
                }

                $path = VehicleSlugHelper::toDetalleUrl($vehicle);
                if ($path === null) {
                    $placa = trim((string) ($vehicle['LicensePlate'] ?? ''));
                    if ($placa !== '') {
                        $path = '/detalle.php?placa=' . rawurlencode($placa);
                    } elseif (!empty($vehicle['id'])) {
                        $path = '/detalle.php?id=' . (int) $vehicle['id'];
                    } else {
                        continue;
                    }
                }

                if (isset($seen[$path])) {
                    continue;
                }
                $seen[$path] = true;

                $urls[] = [
                    'loc' => rtrim($base, '/') . $path,
                    'changefreq' => 'weekly',
                    'priority' => '0.6',
                    'lastmod' => self::vehicleLastmod($vehicle, $defaultLastmod),
                ];
            }

            return $urls;
        } catch (Throwable $e) {
            if (function_exists('am_log')) {
                am_log('Sitemap vehicle URLs skipped: ' . $e->getMessage(), 'WARNING');
            }

            return [];
        }
    }

    /**
     * @param array<string, mixed> $vehicle
     */
    private static function vehicleLastmod(array $vehicle, string $fallback): string
    {
        foreach (['date_update', 'trg_updatefechaWeb', 'LoadDate'] as $field) {
            $raw = trim((string) ($vehicle[$field] ?? ''));
            if ($raw === '' || $raw === '0000-00-00' || $raw === '0000-00-00 00:00:00') {
                continue;
            }
            $timestamp = strtotime($raw);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
        }

        return $fallback;
    }

    /**
     * Define constantes DB sin cargar config.php (evita session_start en sitemap).
     */
    private static function bootstrapDatabaseConstants(): void
    {
        if (defined('DB_HOST')) {
            return;
        }

        $envHost = getenv('DB_HOST');
        $envName = getenv('DB_NAME');
        $envUser = getenv('DB_USER');
        $envPass = getenv('DB_PASS');

        if ($envHost !== false && $envHost !== ''
            && $envName !== false && $envName !== ''
            && $envUser !== false
            && $envPass !== false) {
            $requireMysql = getenv('DB_REQUIRE_MYSQL');
            define('DB_REQUIRE_MYSQL', $requireMysql === '1' || $requireMysql === 'true' || $requireMysql === 'TRUE');
            define('DB_HOST', $envHost);
            define('DB_NAME', $envName);
            define('DB_USER', $envUser);
            define('DB_PASS', $envPass);

            return;
        }

        $configPath = __DIR__ . '/../config/config.php';
        if (!is_readable($configPath)) {
            return;
        }

        $lines = file($configPath, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        $defines = [];
        foreach ($lines as $line) {
            $trim = ltrim($line);
            if ($trim === '' || str_starts_with($trim, '//') || str_starts_with($trim, '#')) {
                continue;
            }
            if (preg_match("/define\(\s*'([^']+)'\s*,\s*(true|false|'[^']*')\s*\)/", $trim, $matches)) {
                $defines[$matches[1]] = $matches[2];
            }
        }

        if (empty($defines['DB_HOST']) || empty($defines['DB_NAME'])
            || !isset($defines['DB_USER'], $defines['DB_PASS'])) {
            return;
        }

        if (isset($defines['DB_REQUIRE_MYSQL'])) {
            define('DB_REQUIRE_MYSQL', $defines['DB_REQUIRE_MYSQL'] === 'true');
        }

        foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $key) {
            $raw = $defines[$key];
            define($key, trim($raw, "'"));
        }
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
