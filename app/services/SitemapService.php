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
        ['path' => '/sucursales-grupo.php', 'changefreq' => 'monthly', 'priority' => '0.7'],
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

        foreach (self::collectLocationUrls($base, $lastmod, $contentService) as $locationEntry) {
            $urls[] = $locationEntry;
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
    private static function collectLocationUrls(string $base, string $lastmod, ContentService $contentService): array
    {
        require_once __DIR__ . '/LocationService.php';
        require_once __DIR__ . '/../includes/location-public-helper.php';

        $siteData = $contentService->getAll();
        $locationService = new LocationService($siteData);
        $urls = [];

        foreach ($locationService->getAll() as $location) {
            if (!is_array($location)) {
                continue;
            }
            if (($location['active'] ?? true) === false) {
                continue;
            }

            $slug = trim((string) ($location['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $urls[] = [
                'loc' => rtrim($base, '/') . am_location_detail_path($slug),
                'changefreq' => 'monthly',
                'priority' => '0.6',
                'lastmod' => $lastmod,
            ];
        }

        return $urls;
    }

    /**
     * @return list<array{loc:string,changefreq:string,priority:string,lastmod?:string}>
     */
    private static function collectVehicleUrls(string $base, string $defaultLastmod): array
    {
        try {
            self::bootstrapDatabaseConstants();

            require_once __DIR__ . '/Database.php';

            $db = Database::getInstance();
            $driver = $db->getDriverName();
            $limit = self::MAX_VEHICLE_URLS;
            $rows = $db->select(
                "SELECT id, Make, Model, Year, LicensePlate, date_update, trg_updatefechaWeb, LoadDate
                 FROM Automarket_Invs_web
                 WHERE Status = 'DISPONIBLE'
                 ORDER BY id DESC
                 LIMIT {$limit}"
            );

            if (!is_array($rows) || $rows === []) {
                self::logSitemapWarning(
                    'Sitemap vehicle query returned 0 rows (driver=' . $driver . ').'
                );

                return [];
            }

            $seen = [];
            $urls = [];

            foreach ($rows as $vehicle) {
                if (!is_array($vehicle)) {
                    continue;
                }

                $path = self::vehicleDetallePath($vehicle);
                if ($path === null) {
                    continue;
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

            if ($urls === []) {
                self::logSitemapWarning(
                    'Sitemap vehicle rows found but no valid detalle URLs (driver=' . $driver . ').'
                );
            }

            return $urls;
        } catch (Throwable $e) {
            self::logSitemapWarning('Sitemap vehicle URLs skipped: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * URL de detalle existente en el sitio (mismos parámetros que detalle.php).
     *
     * @param array<string, mixed> $vehicle
     */
    private static function vehicleDetallePath(array $vehicle): ?string
    {
        $placa = trim((string) ($vehicle['LicensePlate'] ?? ''));
        if ($placa !== '') {
            return '/detalle.php?placa=' . rawurlencode($placa);
        }

        if (!empty($vehicle['id'])) {
            return '/detalle.php?id=' . (int) $vehicle['id'];
        }

        return null;
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

        $envHost = self::readEnvValue('DB_HOST');
        $envName = self::readEnvValue('DB_NAME');
        $envUser = self::readEnvValue('DB_USER');
        $envPass = self::readEnvValue('DB_PASS');

        if (is_string($envHost) && $envHost !== ''
            && is_string($envName) && $envName !== ''
            && is_string($envUser)
            && is_string($envPass)) {
            $requireMysql = self::readEnvValue('DB_REQUIRE_MYSQL');
            define(
                'DB_REQUIRE_MYSQL',
                $requireMysql === '1' || $requireMysql === 'true' || $requireMysql === 'TRUE'
            );
            define('DB_HOST', $envHost);
            define('DB_NAME', $envName);
            define('DB_USER', $envUser);
            define('DB_PASS', $envPass);

            return;
        }

        $defines = self::extractDatabaseDefinesFromConfig(__DIR__ . '/../config/config.php');
        if ($defines === null) {
            self::logSitemapWarning('Sitemap DB bootstrap: no MySQL constants found (env or config.php).');

            return;
        }

        if (isset($defines['DB_REQUIRE_MYSQL'])) {
            define('DB_REQUIRE_MYSQL', (bool) $defines['DB_REQUIRE_MYSQL']);
        }

        define('DB_HOST', $defines['DB_HOST']);
        define('DB_NAME', $defines['DB_NAME']);
        define('DB_USER', $defines['DB_USER']);
        define('DB_PASS', $defines['DB_PASS']);
    }

    /**
     * @return array{DB_HOST:string,DB_NAME:string,DB_USER:string,DB_PASS:string,DB_REQUIRE_MYSQL?:bool}|null
     */
    private static function extractDatabaseDefinesFromConfig(string $configPath): ?array
    {
        if (!is_readable($configPath)) {
            return null;
        }

        $source = file_get_contents($configPath);
        if ($source === false || $source === '') {
            return null;
        }

        $activeSource = '';
        foreach (explode("\n", $source) as $line) {
            $trim = ltrim($line);
            if ($trim === '' || str_starts_with($trim, '//') || str_starts_with($trim, '#')) {
                continue;
            }
            $activeSource .= $line . "\n";
        }

        $parsed = [];
        foreach (['DB_REQUIRE_MYSQL', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $key) {
            $pattern = '/define\s*\(\s*[\'"]' . preg_quote($key, '/') . '[\'"]\s*,\s*([^)]+)\)/';
            if (preg_match($pattern, $activeSource, $matches)) {
                $parsed[$key] = self::parsePhpScalar($matches[1]);
            }
        }

        $host = $parsed['DB_HOST'] ?? null;
        $name = $parsed['DB_NAME'] ?? null;
        $user = $parsed['DB_USER'] ?? null;
        $pass = $parsed['DB_PASS'] ?? null;

        if (!is_string($host) || $host === ''
            || !is_string($name) || $name === ''
            || !is_string($user)
            || !is_string($pass)) {
            return null;
        }

        $result = [
            'DB_HOST' => $host,
            'DB_NAME' => $name,
            'DB_USER' => $user,
            'DB_PASS' => $pass,
        ];

        if (array_key_exists('DB_REQUIRE_MYSQL', $parsed)) {
            $result['DB_REQUIRE_MYSQL'] = (bool) $parsed['DB_REQUIRE_MYSQL'];
        }

        return $result;
    }

    private static function parsePhpScalar(string $raw): mixed
    {
        $raw = trim($raw);

        if ($raw === 'true') {
            return true;
        }
        if ($raw === 'false') {
            return false;
        }
        if ($raw === 'null') {
            return null;
        }
        if (preg_match('/^[\'"](.*)[\'"]\s*$/s', $raw, $matches)) {
            return stripcslashes($matches[1]);
        }
        if (preg_match(
            "/getenv\(\s*['\"]([A-Z0-9_]+)['\"]\s*\)\s*\?:\s*['\"](.*)['\"]\s*$/s",
            $raw,
            $matches
        )) {
            $env = self::readEnvValue($matches[1]);

            return (is_string($env) && $env !== '') ? $env : stripcslashes($matches[2]);
        }
        if (preg_match("/getenv\(\s*['\"]([A-Z0-9_]+)['\"]\s*\)/", $raw, $matches)) {
            $env = self::readEnvValue($matches[1]);

            return is_string($env) ? $env : '';
        }

        return null;
    }

    private static function readEnvValue(string $key): string|false
    {
        $value = getenv($key);
        if (is_string($value) && $value !== '') {
            return $value;
        }
        if (isset($_ENV[$key]) && is_string($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && is_string($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }

        return false;
    }

    private static function logSitemapWarning(string $message): void
    {
        if (function_exists('am_log')) {
            am_log($message, 'WARNING');

            return;
        }

        $logDir = __DIR__ . '/../storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $line = '[' . date('Y-m-d H:i:s') . '] [WARNING] ' . $message . PHP_EOL;
        @file_put_contents($logDir . '/app.log', $line, FILE_APPEND | LOCK_EX);
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
