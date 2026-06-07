<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/TelemetrySchema.php';

class TelemetryService
{
    private const MAX_META_JSON = 8000;

    public static function ensureSchema(): void
    {
        TelemetrySchema::ensure();
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public static function ingest(array $payload): array
    {
        self::ensureSchema();

        $type = trim((string)($payload['type'] ?? 'pageview'));
        $visitorId = self::sanitizeId($payload['visitor_id'] ?? '');
        $sessionId = self::sanitizeId($payload['session_id'] ?? '');

        if ($visitorId === '') {
            $visitorId = self::generateId();
        }
        if ($sessionId === '') {
            $sessionId = self::generateId();
        }

        $ip = self::clientIp();
        $ua = self::truncate((string)($payload['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? ''), 500);
        $client = self::mergeClientInfo($payload, self::parseUserAgent($ua));

        if ($type === 'init') {
            self::upsertVisitor($visitorId, $payload, $ip, $ua, $client);
            return ['ok' => true, 'visitor_id' => $visitorId, 'session_id' => $sessionId];
        }

        if ($type === 'heartbeat' || $type === 'exit') {
            $hitId = intval($payload['hit_id'] ?? 0);
            if ($hitId <= 0) {
                return ['ok' => false, 'message' => 'hit_id requerido'];
            }
            self::updateHit($hitId, $visitorId, $payload, $type === 'exit');
            self::touchVisitor($visitorId);
            return ['ok' => true, 'hit_id' => $hitId];
        }

        if ($type === 'event') {
            $eventId = self::insertEvent($visitorId, $sessionId, 'custom', $payload, $ip);
            self::touchVisitor($visitorId);
            return ['ok' => true, 'event_id' => $eventId];
        }

        // pageview (default)
        self::upsertVisitor($visitorId, $payload, $ip, $ua, $client);
        $hitId = self::insertEvent($visitorId, $sessionId, 'page_view', $payload, $ip);
        return [
            'ok' => true,
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
            'hit_id' => $hitId,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public static function dashboard(array $filters = []): array
    {
        self::ensureSchema();
        $db = Database::getInstance();
        [$where, $params] = self::buildFilterWhere($filters);

        $today = date('Y-m-d');
        $todayWhere = $where . ' AND created_at >= :today_start AND created_at <= :today_end';
        $todayParams = array_merge($params, [
            ':today_start' => $today . ' 00:00:00',
            ':today_end' => $today . ' 23:59:59',
        ]);

        $statsToday = $db->selectOne(
            "SELECT
                COUNT(DISTINCT visitor_id) AS visitors,
                COUNT(*) AS page_views,
                COALESCE(AVG(NULLIF(duration_seconds, 0)), 0) AS avg_duration
             FROM telemetry_events
             WHERE event_type = 'page_view' AND $todayWhere",
            $todayParams
        );

        $statsRange = $db->selectOne(
            "SELECT
                COUNT(DISTINCT visitor_id) AS visitors,
                COUNT(*) AS page_views,
                COALESCE(AVG(NULLIF(duration_seconds, 0)), 0) AS avg_duration,
                COALESCE(MAX(duration_seconds), 0) AS max_duration
             FROM telemetry_events
             WHERE event_type = 'page_view' AND $where",
            $params
        );

        $topPages = $db->select(
            "SELECT page_path, page_title, business_unit,
                    COUNT(*) AS views,
                    COUNT(DISTINCT visitor_id) AS unique_visitors,
                    COALESCE(AVG(NULLIF(duration_seconds, 0)), 0) AS avg_duration
             FROM telemetry_events
             WHERE event_type = 'page_view' AND $where
             GROUP BY page_path, page_title, business_unit
             ORDER BY views DESC
             LIMIT 15",
            $params
        );

        $topVehicles = $db->select(
            "SELECT entity_type, entity_id, entity_label, business_unit,
                    COUNT(*) AS views,
                    COUNT(DISTINCT visitor_id) AS unique_visitors,
                    COALESCE(AVG(NULLIF(duration_seconds, 0)), 0) AS avg_duration
             FROM telemetry_events
             WHERE event_type = 'page_view' AND entity_type IS NOT NULL AND entity_id IS NOT NULL AND $where
             GROUP BY entity_type, entity_id, entity_label, business_unit
             ORDER BY views DESC
             LIMIT 20",
            $params
        );

        $topUnits = $db->select(
            "SELECT business_unit, COUNT(*) AS views, COUNT(DISTINCT visitor_id) AS unique_visitors
             FROM telemetry_events
             WHERE event_type = 'page_view' AND business_unit IS NOT NULL AND business_unit != '' AND $where
             GROUP BY business_unit
             ORDER BY views DESC",
            $params
        );

        [$eventWhere, $eventParams] = self::buildFilterWhere($filters, 'e');
        $topCountries = $db->select(
            "SELECT COALESCE(e.country, v.country, 'Desconocido') AS country,
                    COALESCE(v.country_code, '') AS country_code,
                    COUNT(DISTINCT e.visitor_id) AS visitors,
                    COUNT(*) AS events
             FROM telemetry_events e
             LEFT JOIN telemetry_visitors v ON v.visitor_id = e.visitor_id
             WHERE $eventWhere
             GROUP BY COALESCE(e.country, v.country, 'Desconocido'), COALESCE(v.country_code, '')
             ORDER BY visitors DESC
             LIMIT 12",
            $eventParams
        );

        $topCities = $db->select(
            "SELECT COALESCE(city, 'Desconocido') AS city, COALESCE(country, '') AS country,
                    COUNT(DISTINCT visitor_id) AS visitors
             FROM telemetry_events
             WHERE city IS NOT NULL AND city != '' AND $where
             GROUP BY city, country
             ORDER BY visitors DESC
             LIMIT 12",
            $params
        );

        $hourly = $db->select(
            "SELECT strftime('%H', created_at) AS hour, COUNT(*) AS views
             FROM telemetry_events
             WHERE event_type = 'page_view' AND $where
             GROUP BY hour
             ORDER BY hour",
            $params
        );
        if ($db->getDriverName() === 'mysql') {
            $hourly = $db->select(
                "SELECT DATE_FORMAT(created_at, '%H') AS hour, COUNT(*) AS views
                 FROM telemetry_events
                 WHERE event_type = 'page_view' AND $where
                 GROUP BY hour
                 ORDER BY hour",
                $params
            );
        }

        $deviceStats = self::visitorDeviceStats($eventWhere, $eventParams);

        return [
            'today' => [
                'visitors' => intval($statsToday['visitors'] ?? 0),
                'page_views' => intval($statsToday['page_views'] ?? 0),
                'avg_duration' => round(floatval($statsToday['avg_duration'] ?? 0), 1),
            ],
            'range' => [
                'visitors' => intval($statsRange['visitors'] ?? 0),
                'page_views' => intval($statsRange['page_views'] ?? 0),
                'avg_duration' => round(floatval($statsRange['avg_duration'] ?? 0), 1),
                'max_duration' => intval($statsRange['max_duration'] ?? 0),
            ],
            'top_pages' => $topPages,
            'top_vehicles' => $topVehicles,
            'top_units' => $topUnits,
            'top_countries' => $topCountries,
            'top_cities' => $topCities,
            'hourly' => $hourly,
            'devices' => $deviceStats,
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private static function visitorDeviceStats(string $eventWhere, array $params): array
    {
        $db = Database::getInstance();
        $join = "FROM telemetry_visitors v
                 INNER JOIN telemetry_events e ON e.visitor_id = v.visitor_id
                 WHERE $eventWhere";

        $byDevice = $db->select(
            "SELECT COALESCE(NULLIF(v.device_type, ''), 'desconocido') AS key_name,
                    COUNT(DISTINCT v.visitor_id) AS visitors,
                    COUNT(*) AS page_views
             $join
             GROUP BY COALESCE(NULLIF(v.device_type, ''), 'desconocido')
             ORDER BY visitors DESC",
            $params
        );

        $byOs = $db->select(
            "SELECT COALESCE(NULLIF(v.os, ''), 'Desconocido') AS key_name,
                    COUNT(DISTINCT v.visitor_id) AS visitors,
                    COUNT(*) AS page_views
             $join
             GROUP BY COALESCE(NULLIF(v.os, ''), 'Desconocido')
             ORDER BY visitors DESC
             LIMIT 12",
            $params
        );

        $byBrowser = $db->select(
            "SELECT COALESCE(NULLIF(v.browser, ''), 'Desconocido') AS key_name,
                    COUNT(DISTINCT v.visitor_id) AS visitors,
                    COUNT(*) AS page_views
             $join
             GROUP BY COALESCE(NULLIF(v.browser, ''), 'Desconocido')
             ORDER BY visitors DESC
             LIMIT 10",
            $params
        );

        if ($db->getDriverName() === 'mysql') {
            $byScreen = $db->select(
                "SELECT CONCAT(v.screen_width, ' × ', v.screen_height) AS key_name,
                        COUNT(DISTINCT v.visitor_id) AS visitors,
                        COUNT(*) AS page_views
                 $join AND v.screen_width IS NOT NULL AND v.screen_width > 0
                 GROUP BY v.screen_width, v.screen_height
                 ORDER BY visitors DESC
                 LIMIT 12",
                $params
            );
            $byViewport = $db->select(
                "SELECT CONCAT(v.viewport_width, ' × ', v.viewport_height) AS key_name,
                        COUNT(DISTINCT v.visitor_id) AS visitors,
                        COUNT(*) AS page_views
                 $join AND v.viewport_width IS NOT NULL AND v.viewport_width > 0
                 GROUP BY v.viewport_width, v.viewport_height
                 ORDER BY visitors DESC
                 LIMIT 12",
                $params
            );
        } else {
            $byScreen = $db->select(
                "SELECT (CAST(v.screen_width AS TEXT) || ' × ' || CAST(v.screen_height AS TEXT)) AS key_name,
                        COUNT(DISTINCT v.visitor_id) AS visitors,
                        COUNT(*) AS page_views
                 $join AND v.screen_width IS NOT NULL AND v.screen_width > 0
                 GROUP BY v.screen_width, v.screen_height
                 ORDER BY visitors DESC
                 LIMIT 12",
                $params
            );
            $byViewport = $db->select(
                "SELECT (CAST(v.viewport_width AS TEXT) || ' × ' || CAST(v.viewport_height AS TEXT)) AS key_name,
                        COUNT(DISTINCT v.visitor_id) AS visitors,
                        COUNT(*) AS page_views
                 $join AND v.viewport_width IS NOT NULL AND v.viewport_width > 0
                 GROUP BY v.viewport_width, v.viewport_height
                 ORDER BY visitors DESC
                 LIMIT 12",
                $params
            );
        }

        return [
            'by_device' => $byDevice,
            'by_os' => $byOs,
            'by_browser' => $byBrowser,
            'by_screen' => $byScreen,
            'by_viewport' => $byViewport,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{rows: array<int, array<string, mixed>>, total: int, page: int, pages: int}
     */
    public static function listEvents(array $filters = []): array
    {
        self::ensureSchema();
        $db = Database::getInstance();
        $page = max(1, intval($filters['page'] ?? 1));
        $limit = min(100, max(15, intval($filters['limit'] ?? 30)));
        $offset = ($page - 1) * $limit;

        [$where, $params] = self::buildFilterWhere($filters, 'e');

        if (!empty($filters['visitor_id'])) {
            $where .= ' AND e.visitor_id = :visitor_id';
            $params[':visitor_id'] = trim((string)$filters['visitor_id']);
        }
        if (!empty($filters['entity_id'])) {
            $where .= ' AND e.entity_id LIKE :entity_id';
            $params[':entity_id'] = '%' . trim((string)$filters['entity_id']) . '%';
        }
        if (!empty($filters['q'])) {
            $where .= ' AND (e.page_path LIKE :q1 OR e.page_title LIKE :q2 OR e.entity_label LIKE :q3 OR e.ip_address LIKE :q4 OR e.city LIKE :q5)';
            $q = '%' . trim((string)$filters['q']) . '%';
            $params[':q1'] = $q;
            $params[':q2'] = $q;
            $params[':q3'] = $q;
            $params[':q4'] = $q;
            $params[':q5'] = $q;
        }

        $countRow = $db->selectOne("SELECT COUNT(*) AS cnt FROM telemetry_events e WHERE $where", $params);
        $total = intval($countRow['cnt'] ?? 0);
        $pages = max(1, (int)ceil($total / $limit));

        $rows = $db->select(
            "SELECT e.*, v.browser, v.os, v.device_type, v.isp, v.region, v.country_code AS visitor_country_code,
                    v.screen_width, v.screen_height, v.viewport_width, v.viewport_height, v.pixel_ratio, v.language AS visitor_language
             FROM telemetry_events e
             LEFT JOIN telemetry_visitors v ON v.visitor_id = e.visitor_id
             WHERE $where
             ORDER BY e.id DESC
             LIMIT $limit OFFSET $offset",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /** @return array<string, mixed>|null */
    public static function getVisitor(string $visitorId): ?array
    {
        self::ensureSchema();
        $visitorId = self::sanitizeId($visitorId);
        if ($visitorId === '') {
            return null;
        }
        $db = Database::getInstance();
        $visitor = $db->selectOne('SELECT * FROM telemetry_visitors WHERE visitor_id = :id LIMIT 1', [':id' => $visitorId]);
        if (!$visitor) {
            return null;
        }
        $events = $db->select(
            'SELECT * FROM telemetry_events WHERE visitor_id = :id ORDER BY id DESC LIMIT 100',
            [':id' => $visitorId]
        );
        return ['visitor' => $visitor, 'events' => $events];
    }

    /** @param array<string, mixed> $payload @param array<string, string> $client */
    private static function upsertVisitor(string $visitorId, array $payload, string $ip, string $ua, array $client): void
    {
        $db = Database::getInstance();
        $existing = $db->selectOne('SELECT * FROM telemetry_visitors WHERE visitor_id = :id LIMIT 1', [':id' => $visitorId]);
        $needsGeo = !$existing || empty($existing['country']);
        $geo = $needsGeo ? self::resolveGeo($ip) : [];

        $screen = is_array($payload['screen'] ?? null) ? $payload['screen'] : [];
        $viewport = is_array($payload['viewport'] ?? null) ? $payload['viewport'] : [];
        $language = trim((string)($payload['language'] ?? ''));
        $referrer = self::truncate(trim((string)($payload['referrer'] ?? '')), 500);
        $params = self::visitorParams($visitorId, $ip, $ua, $client, $geo, $screen, $viewport, $language, $referrer, $payload);
        $nowExpr = $db->getDriverName() === 'mysql' ? 'NOW()' : "datetime('now')";

        if (!$existing) {
            $db->execute(
                "INSERT INTO telemetry_visitors
                (visitor_id, first_seen_at, last_seen_at, visit_count, ip_address, country, country_code, region, city,
                 latitude, longitude, timezone, isp, user_agent, browser, os, device_type, language, screen_width, screen_height,
                 viewport_width, viewport_height, pixel_ratio, referrer_first)
                 VALUES
                (:visitor_id, $nowExpr, $nowExpr, 1, :ip, :country, :country_code, :region, :city,
                 :lat, :lon, :timezone, :isp, :ua, :browser, :os, :device, :lang, :sw, :sh, :vw, :vh, :dpr, :ref)",
                $params
            );
            return;
        }

        $sql = "UPDATE telemetry_visitors SET
            last_seen_at = $nowExpr,
            visit_count = visit_count + 1,
            ip_address = :ip,
            user_agent = :ua,
            browser = :browser,
            os = :os,
            device_type = :device,
            language = :lang,
            screen_width = :sw,
            screen_height = :sh,
            viewport_width = :vw,
            viewport_height = :vh,
            pixel_ratio = :dpr";
        if ($geo !== []) {
            $sql .= ',
            country = :country,
            country_code = :country_code,
            region = :region,
            city = :city,
            latitude = :lat,
            longitude = :lon,
            timezone = :timezone,
            isp = :isp';
        }
        $sql .= ' WHERE visitor_id = :visitor_id';
        $db->execute($sql, $params);
    }

    /** @param array<string, mixed> $geo @param array<string, mixed> $screen @param array<string, mixed> $viewport @param array<string, mixed> $payload @return array<string, mixed> */
    private static function visitorParams(
        string $visitorId,
        string $ip,
        string $ua,
        array $client,
        array $geo,
        array $screen,
        array $viewport,
        string $language,
        string $referrer,
        array $payload = []
    ): array {
        $dpr = $payload['pixel_ratio'] ?? $payload['dpr'] ?? null;
        if ($dpr === null && is_array($payload['client_device'] ?? null)) {
            $dpr = $payload['client_device']['pixel_ratio'] ?? null;
        }

        return [
            ':visitor_id' => $visitorId,
            ':ip' => $ip !== '' ? $ip : null,
            ':country' => $geo['country'] ?? null,
            ':country_code' => $geo['country_code'] ?? null,
            ':region' => $geo['region'] ?? null,
            ':city' => $geo['city'] ?? null,
            ':lat' => $geo['latitude'] ?? null,
            ':lon' => $geo['longitude'] ?? null,
            ':timezone' => $geo['timezone'] ?? null,
            ':isp' => $geo['isp'] ?? null,
            ':ua' => $ua,
            ':browser' => $client['browser'] ?? null,
            ':os' => $client['os'] ?? null,
            ':device' => $client['device'] ?? null,
            ':lang' => $language !== '' ? $language : null,
            ':sw' => intval($screen['w'] ?? $screen['width'] ?? 0) ?: null,
            ':sh' => intval($screen['h'] ?? $screen['height'] ?? 0) ?: null,
            ':vw' => intval($viewport['w'] ?? $viewport['width'] ?? 0) ?: null,
            ':vh' => intval($viewport['h'] ?? $viewport['height'] ?? 0) ?: null,
            ':dpr' => is_numeric($dpr) ? round(floatval($dpr), 2) : null,
            ':ref' => $referrer !== '' ? $referrer : null,
        ];
    }

    /** @param array<string, mixed> $payload @param array<string, string> $parsed @return array<string, string> */
    private static function mergeClientInfo(array $payload, array $parsed): array
    {
        $client = is_array($payload['client_device'] ?? null) ? $payload['client_device'] : [];
        foreach (['browser', 'os', 'device'] as $key) {
            if (!empty($client[$key])) {
                $parsed[$key] = trim((string)$client[$key]);
            }
        }
        if (!empty($client['device_type'])) {
            $parsed['device'] = trim((string)$client['device_type']);
        }
        return $parsed;
    }

    private static function touchVisitor(string $visitorId): void
    {
        $nowExpr = Database::getInstance()->getDriverName() === 'mysql' ? 'NOW()' : "datetime('now')";
        Database::getInstance()->execute(
            "UPDATE telemetry_visitors SET last_seen_at = $nowExpr WHERE visitor_id = :id",
            [':id' => $visitorId]
        );
    }

    /** @param array<string, mixed> $payload */
    private static function insertEvent(string $visitorId, string $sessionId, string $eventType, array $payload, string $ip): int
    {
        $entity = is_array($payload['entity'] ?? null) ? $payload['entity'] : [];
        $utm = is_array($payload['utm'] ?? null) ? $payload['utm'] : [];
        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
        $geo = self::resolveGeo($ip);

        $row = [
            ':visitor_id' => $visitorId,
            ':session_id' => $sessionId,
            ':event_type' => $eventType,
            ':page_path' => self::truncate(trim((string)($payload['page_path'] ?? '/')), 500),
            ':page_title' => self::truncate(trim((string)($payload['page_title'] ?? '')), 255),
            ':page_query' => self::truncate(trim((string)($payload['page_query'] ?? '')), 500),
            ':business_unit' => self::truncate(trim((string)($payload['business_unit'] ?? '')), 32),
            ':entity_type' => self::truncate(trim((string)($entity['type'] ?? $payload['entity_type'] ?? '')), 64),
            ':entity_id' => self::truncate(trim((string)($entity['id'] ?? $payload['entity_id'] ?? '')), 120),
            ':entity_label' => self::truncate(trim((string)($entity['label'] ?? $payload['entity_label'] ?? '')), 255),
            ':duration' => max(0, intval($payload['duration'] ?? 0)),
            ':scroll' => min(100, max(0, intval($payload['scroll_depth'] ?? 0))),
            ':ip' => $ip,
            ':country' => $geo['country'] ?? null,
            ':city' => $geo['city'] ?? null,
            ':referrer' => self::truncate(trim((string)($payload['referrer'] ?? '')), 500),
            ':utm_source' => self::truncate(trim((string)($utm['source'] ?? '')), 120),
            ':utm_medium' => self::truncate(trim((string)($utm['medium'] ?? '')), 120),
            ':utm_campaign' => self::truncate(trim((string)($utm['campaign'] ?? '')), 120),
            ':meta' => self::encodeMeta($meta),
        ];

        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO telemetry_events
            (visitor_id, session_id, event_type, page_path, page_title, page_query, business_unit,
             entity_type, entity_id, entity_label, duration_seconds, scroll_depth, ip_address, country, city,
             referrer, utm_source, utm_medium, utm_campaign, meta_json, created_at)
             VALUES
            (:visitor_id, :session_id, :event_type, :page_path, :page_title, :page_query, :business_unit,
             :entity_type, :entity_id, :entity_label, :duration, :scroll, :ip, :country, :city,
             :referrer, :utm_source, :utm_medium, :utm_campaign, :meta, ' . self::nowSql() . ')',
            $row
        );
        return intval($db->lastInsertId());
    }

    /** @param array<string, mixed> $payload */
    private static function updateHit(int $hitId, string $visitorId, array $payload, bool $isExit): void
    {
        $db = Database::getInstance();
        $existing = $db->selectOne(
            'SELECT id FROM telemetry_events WHERE id = :id AND visitor_id = :vid LIMIT 1',
            [':id' => $hitId, ':vid' => $visitorId]
        );
        if (!$existing) {
            return;
        }

        $duration = max(0, intval($payload['duration'] ?? 0));
        $scroll = min(100, max(0, intval($payload['scroll_depth'] ?? 0)));
        $nowExpr = $db->getDriverName() === 'mysql' ? 'NOW()' : "datetime('now')";

        $db->execute(
            "UPDATE telemetry_events SET
                duration_seconds = CASE WHEN :duration_cmp > duration_seconds THEN :duration_set ELSE duration_seconds END,
                scroll_depth = CASE WHEN :scroll_cmp > scroll_depth THEN :scroll_set ELSE scroll_depth END,
                updated_at = $nowExpr
             WHERE id = :id AND visitor_id = :vid",
            [
                ':duration_cmp' => $duration,
                ':duration_set' => $duration,
                ':scroll_cmp' => $scroll,
                ':scroll_set' => $scroll,
                ':id' => $hitId,
                ':vid' => $visitorId,
            ]
        );

        if ($isExit && !empty($payload['meta']) && is_array($payload['meta'])) {
            $db->execute(
                'UPDATE telemetry_events SET meta_json = :meta WHERE id = :id',
                [':id' => $hitId, ':meta' => self::encodeMeta($payload['meta'])]
            );
        }
    }

    /** @param array<string, mixed> $filters @return array{0: string, 1: array<string, mixed>} */
    private static function buildFilterWhere(array $filters, string $alias = ''): array
    {
        $col = static fn(string $name) => ($alias !== '' ? $alias . '.' : '') . $name;

        $where = '1=1';
        $params = [];

        if (!empty($filters['date_from'])) {
            $where .= ' AND ' . $col('created_at') . ' >= :date_from';
            $params[':date_from'] = trim((string)$filters['date_from']) . ' 00:00:00';
        } else {
            $where .= ' AND ' . $col('created_at') . ' >= :date_from';
            $params[':date_from'] = date('Y-m-d', strtotime('-7 days')) . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where .= ' AND ' . $col('created_at') . ' <= :date_to';
            $params[':date_to'] = trim((string)$filters['date_to']) . ' 23:59:59';
        }
        if (!empty($filters['business_unit'])) {
            $where .= ' AND ' . $col('business_unit') . ' = :business_unit';
            $params[':business_unit'] = trim((string)$filters['business_unit']);
        }
        if (!empty($filters['event_type'])) {
            $where .= ' AND ' . $col('event_type') . ' = :event_type';
            $params[':event_type'] = trim((string)$filters['event_type']);
        }

        return [$where, $params];
    }

    /** @return array<string, mixed> */
    private static function resolveGeo(string $ip): array
    {
        if ($ip === '' || self::isPrivateIp($ip)) {
            return [];
        }

        static $cache = [];
        if (isset($cache[$ip])) {
            return $cache[$ip];
        }

        $url = 'http://ip-api.com/json/' . urlencode($ip)
            . '?fields=status,country,countryCode,regionName,city,lat,lon,timezone,isp,query&lang=es';
        $ctx = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            return $cache[$ip] = [];
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
            return $cache[$ip] = [];
        }

        return $cache[$ip] = [
            'country' => (string)($data['country'] ?? ''),
            'country_code' => (string)($data['countryCode'] ?? ''),
            'region' => (string)($data['regionName'] ?? ''),
            'city' => (string)($data['city'] ?? ''),
            'latitude' => $data['lat'] ?? null,
            'longitude' => $data['lon'] ?? null,
            'timezone' => (string)($data['timezone'] ?? ''),
            'isp' => (string)($data['isp'] ?? ''),
        ];
    }

    /** @return array{browser: string, os: string, device: string} */
    private static function parseUserAgent(string $ua): array
    {
        $browser = 'Desconocido';
        $os = 'Desconocido';
        $device = 'desktop';

        if (preg_match('/Mobile|Android.*Mobile|iPhone|iPod/i', $ua)) {
            $device = 'mobile';
        } elseif (preg_match('/iPad|Tablet|Android(?!.*Mobile)/i', $ua)) {
            $device = 'tablet';
        }

        if (preg_match('/iPhone|iPod/i', $ua)) {
            $os = 'iOS';
            $device = 'mobile';
        } elseif (preg_match('/iPad/i', $ua)) {
            $os = 'iPadOS';
            $device = 'tablet';
        } elseif (preg_match('/Android/i', $ua)) {
            $os = 'Android';
        } elseif (preg_match('/Windows NT/i', $ua)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X|Macintosh/i', $ua)) {
            $os = 'macOS';
        } elseif (preg_match('/CrOS/i', $ua)) {
            $os = 'Chrome OS';
        } elseif (preg_match('/Linux/i', $ua)) {
            $os = 'Linux';
        }

        if (preg_match('/Edg\//i', $ua)) {
            $browser = 'Edge';
        } elseif (preg_match('/OPR\//i', $ua)) {
            $browser = 'Opera';
        } elseif (preg_match('/CriOS/i', $ua)) {
            $browser = 'Chrome (iOS)';
        } elseif (preg_match('/FxiOS/i', $ua)) {
            $browser = 'Firefox (iOS)';
        } elseif (preg_match('/Chrome\//i', $ua) && !preg_match('/Edg\//i', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\//i', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\//i', $ua) && !preg_match('/Chrome/i', $ua)) {
            $browser = 'Safari';
        } elseif (preg_match('/SamsungBrowser/i', $ua)) {
            $browser = 'Samsung Internet';
        }

        return ['browser' => $browser, 'os' => $os, 'device' => $device];
    }

    private static function isPrivateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /** @param array<string, mixed> $meta */
    private static function encodeMeta(array $meta): ?string
    {
        if ($meta === []) {
            return null;
        }
        $json = json_encode($meta, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return null;
        }
        if (strlen($json) > self::MAX_META_JSON) {
            return substr($json, 0, self::MAX_META_JSON) . '…';
        }
        return $json;
    }

    public static function clientIp(): string
    {
        $candidates = [
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
            $_SERVER['HTTP_CLIENT_IP'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
        ];
        foreach ($candidates as $raw) {
            $raw = trim((string)$raw);
            if ($raw === '') {
                continue;
            }
            $ip = trim(explode(',', $raw)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '';
    }

    private static function sanitizeId(string $id): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $id) ?? '';
    }

    private static function generateId(): string
    {
        return bin2hex(random_bytes(16));
    }

    private static function truncate(string $value, int $max): string
    {
        if (mb_strlen($value) <= $max) {
            return $value;
        }
        return mb_substr($value, 0, $max);
    }

    private static function nowSql(): string
    {
        return Database::getInstance()->getDriverName() === 'mysql' ? 'NOW()' : "datetime('now')";
    }

    public static function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;
        if ($m < 60) {
            return $m . 'm ' . $s . 's';
        }
        $h = intdiv($m, 60);
        $m = $m % 60;
        return $h . 'h ' . $m . 'm';
    }

    /** @return array<string, string> */
    public static function businessUnitLabels(): array
    {
        return [
            'rentacar' => 'Rent A Car',
            'seminuevos' => 'Venta de Autos',
            'leasing' => 'Leasing Operativo',
            'renting' => 'Renting',
            'taller' => 'Taller',
            'sostenibilidad' => 'Sostenibilidad',
            '' => 'General',
        ];
    }

    public static function deviceTypeLabel(string $type): string
    {
        return match (strtolower($type)) {
            'mobile' => 'Teléfono móvil',
            'tablet' => 'Tablet',
            'desktop' => 'PC / Escritorio',
            'desconocido' => 'Desconocido',
            default => ucfirst($type),
        };
    }

    public static function deviceIcon(string $type): string
    {
        return match (strtolower($type)) {
            'mobile' => 'bi-phone',
            'tablet' => 'bi-tablet',
            'desktop' => 'bi-pc-display',
            default => 'bi-question-circle',
        };
    }

    public static function formatResolution(?int $w, ?int $h, ?int $vw = null, ?int $vh = null): string
    {
        $parts = [];
        if ($w && $h) {
            $parts[] = 'Pantalla ' . $w . '×' . $h;
        }
        if ($vw && $vh) {
            $parts[] = 'Ventana ' . $vw . '×' . $vh;
        }
        return $parts !== [] ? implode(' · ', $parts) : '—';
    }
}
