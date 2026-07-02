<?php
/**
 * AM-SEO-3C — Migración de sucursales a locations[] master.
 *
 * Por defecto: dry-run (solo lectura + reporte).
 * Con --execute: backup + escribe locations[] y location_refs (silos legacy intactos).
 *
 * Uso:
 *   php scripts/location-migration-dry-run.php
 *   php scripts/location-migration-dry-run.php --output=docs/AM-SEO-3C-A0-location-migration-dry-run.md
 *   php scripts/location-migration-dry-run.php --execute
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Este script solo puede ejecutarse por CLI.\n";
    exit(1);
}

const SITE_DATA_PATH = __DIR__ . '/../app/storage/site_data.json';
const RAC_JSON_PATH = __DIR__ . '/../app/data/sucursales.json';
const DEFAULT_OUTPUT = __DIR__ . '/../docs/AM-SEO-3C-A0-location-migration-dry-run.md';
const BACKUP_DIR = __DIR__ . '/../app/storage/backups';
const COORD_MATCH_METERS = 200;
const NAME_SIMILAR_THRESHOLD = 0.82;

function main(array $argv): int
{
    require_once __DIR__ . '/../app/services/LocationService.php';

    $outputPath = DEFAULT_OUTPUT;
    $execute = false;
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--execute') {
            $execute = true;
            continue;
        }
        if (str_starts_with($arg, '--output=')) {
            $outputPath = substr($arg, 9);
            if (!str_starts_with($outputPath, '/') && !preg_match('#^[A-Za-z]:\\\\#', $outputPath)) {
                $outputPath = getcwd() . DIRECTORY_SEPARATOR . $outputPath;
            }
        }
    }

    $siteMtimeBefore = is_file(SITE_DATA_PATH) ? filemtime(SITE_DATA_PATH) : null;
    $siteData = loadJsonFile(SITE_DATA_PATH);
    if ($siteData === null) {
        fwrite(STDERR, "No se pudo leer site_data.json en " . SITE_DATA_PATH . "\n");
        return 1;
    }

    $legacyCounts = LocationMigrationWriter::snapshotLegacyCounts($siteData);

    $racRows = loadJsonFile(RAC_JSON_PATH);
    if (!is_array($racRows)) {
        fwrite(STDERR, "No se pudo leer sucursales.json en " . RAC_JSON_PATH . "\n");
        return 1;
    }

    $collector = new LocationDryRunCollector();
    $collector->collectFromSiteData($siteData);
    $collector->collectFromRacJson($racRows);
    $analysis = $collector->analyze();

    $phoneConflicts = LocationMigrationWriter::filterPhoneConflicts($analysis['conflicts']);

    $markdown = LocationDryRunReporter::toMarkdown($analysis, [
        'generated_at' => date('Y-m-d H:i:s'),
        'site_data_path' => SITE_DATA_PATH,
        'rac_json_path' => RAC_JSON_PATH,
        'site_data_mtime' => $siteMtimeBefore,
        'execute_mode' => $execute,
        'phone_conflicts' => $phoneConflicts,
    ]);

    $dir = dirname($outputPath);
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        fwrite(STDERR, "No se pudo crear directorio: {$dir}\n");
        return 1;
    }

    if (file_put_contents($outputPath, "\xEF\xBB\xBF" . $markdown) === false) {
        fwrite(STDERR, "No se pudo escribir reporte: {$outputPath}\n");
        return 1;
    }

    if (!$execute) {
        $siteMtimeAfter = is_file(SITE_DATA_PATH) ? filemtime(SITE_DATA_PATH) : null;
        if ($siteMtimeBefore !== $siteMtimeAfter) {
            fwrite(STDERR, "ADVERTENCIA: site_data.json cambió durante el dry-run.\n");
            return 1;
        }

        echo "Dry-run completado.\n";
        echo "Reporte: {$outputPath}\n";
        echo "Registros por fuente: " . array_sum($analysis['counts_by_source']) . "\n";
        echo "Locations candidatas: " . count($analysis['candidate_locations']) . "\n";
        echo "Conflictos: " . count($analysis['conflicts']) . "\n";
        echo "Conflictos de teléfono (revisión manual): " . count($phoneConflicts) . "\n";
        echo "Huérfanos: " . count($analysis['orphans']) . "\n";
        echo "site_data.json: sin cambios (mtime intacto)\n";

        return 0;
    }

    $backupPath = LocationMigrationWriter::createBackup(SITE_DATA_PATH);
    if ($backupPath === null) {
        fwrite(STDERR, "No se pudo crear backup de site_data.json.\n");
        return 1;
    }

    $writeResult = LocationMigrationWriter::apply($siteData, $analysis);
    if (!$writeResult['ok']) {
        fwrite(STDERR, "Migración abortada: " . ($writeResult['error'] ?? 'error desconocido') . "\n");
        return 1;
    }

    if (!LocationMigrationWriter::saveSiteData($writeResult['site_data'], SITE_DATA_PATH)) {
        fwrite(STDERR, "No se pudo escribir site_data.json. Backup disponible en: {$backupPath}\n");
        return 1;
    }

  $after = loadJsonFile(SITE_DATA_PATH);
    if ($after === null) {
        fwrite(STDERR, "site_data.json ilegible tras escritura. Restaure desde: {$backupPath}\n");
        return 1;
    }

    $legacyAfter = LocationMigrationWriter::snapshotLegacyCounts($after);
    if ($legacyCounts !== $legacyAfter) {
        fwrite(STDERR, "ADVERTENCIA: conteos de silos legacy cambiaron. Restaure desde: {$backupPath}\n");
        fwrite(STDERR, "Antes: " . json_encode($legacyCounts) . "\n");
        fwrite(STDERR, "Después: " . json_encode($legacyAfter) . "\n");
        return 1;
    }

    $refs = $writeResult['refs_counts'];
    echo "Migración --execute completada.\n";
    echo "Reporte: {$outputPath}\n";
    echo "Backup: {$backupPath}\n";
    echo "locations[] escritas: " . count($after['locations'] ?? []) . "\n";
    echo "location_refs homepage: " . ($refs['homepage'] ?? 0) . "\n";
    echo "location_refs footer: " . ($refs['footer'] ?? 0) . "\n";
    echo "location_refs seminuevos: " . ($refs['seminuevos'] ?? 0) . "\n";
    echo "location_refs leasing: " . ($refs['leasing'] ?? 0) . "\n";
    echo "location_refs renting: " . ($refs['renting'] ?? 0) . "\n";
    echo "location_refs taller: " . ($refs['taller'] ?? 0) . "\n";
    echo "Conflictos de teléfono pendientes: " . count($phoneConflicts) . "\n";
    foreach ($phoneConflicts as $pc) {
        echo "  - [{$pc['location_id']}] {$pc['name']}: " . implode(', ', $pc['values'] ?? []) . "\n";
    }
    echo "Silos legacy: intactos (conteos verificados)\n";

    return 0;
}

/** @return array<string, mixed>|null */
function loadJsonFile(string $path): ?array
{
    if (!is_readable($path)) {
        return null;
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        return null;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

final class LocationDryRunCollector
{
    /** @var list<array<string, mixed>> */
    private array $records = [];

    /** @param array<string, mixed> $siteData */
    public function collectFromSiteData(array $siteData): void
    {
        $this->collectList('homepage.sucursales', $siteData['homepage']['sucursales'] ?? [], 'rentacar', 'sucursales');
        $this->collectList('footer.sucursales', $siteData['footer']['sucursales'] ?? [], null, 'sucursales');
        $this->collectList('global.sucursales', $siteData['global']['sucursales'] ?? [], null, 'global');
        $this->collectList('seminuevos.sucursales', $siteData['seminuevos']['sucursales'] ?? [], 'seminuevos', 'sucursales');
        $this->collectList('leasing.sucursales', $siteData['leasing']['sucursales'] ?? [], 'leasing', 'sucursales');
        $this->collectList('renting.sucursales', $siteData['renting']['sucursales'] ?? [], 'renting', 'sucursales');
        $this->collectList('taller.sucursales', $siteData['taller']['sucursales'] ?? [], 'taller', 'sucursales');
        $this->collectList('seminuevos.branches', $siteData['seminuevos']['branches'] ?? [], 'seminuevos', 'branches');
        $this->collectList('leasing.branches', $siteData['leasing']['branches'] ?? [], 'leasing', 'branches');
        $this->collectList('renting.branches', $siteData['renting']['branches'] ?? [], 'renting', 'branches');
        $this->collectList('taller.branches', $siteData['taller']['branches'] ?? [], 'taller', 'branches');
    }

    /** @param list<mixed> $list */
    private function collectList(string $source, array $list, ?string $defaultUnit, string $kind): void
    {
        foreach ($list as $idx => $row) {
            if (!is_array($row)) {
                continue;
            }
            $unit = $defaultUnit;
            if ($source === 'footer.sucursales') {
                $unit = trim((string) ($row['unit'] ?? 'grupo'));
            }
            $this->records[] = $this->normalizeRecord($source, $idx, $row, $unit, $kind);
        }
    }

    /** @param list<mixed> $racRows */
    public function collectFromRacJson(array $racRows): void
    {
        foreach ($racRows as $idx => $row) {
            if (!is_array($row)) {
                continue;
            }
            $mapped = [
                'name' => $row['name'] ?? '',
                'location_label' => $row['city'] ?? '',
                'address' => $row['address'] ?? '',
                'schedule' => $row['hours'] ?? '',
                'phone' => $row['phone'] ?? '',
                'lat' => isset($row['lat']) ? (string) $row['lat'] : '',
                'lng' => isset($row['lng']) ? (string) $row['lng'] : '',
                'rac_code' => strtoupper(trim((string) ($row['code'] ?? ''))),
                'hours_structured' => $row['dailyHours'] ?? null,
            ];
            $this->records[] = $this->normalizeRecord('app/data/sucursales.json', $idx, $mapped, 'rentacar', 'rac_json');
        }
    }

    /** @param array<string, mixed> $row */
    private function normalizeRecord(string $source, int $index, array $row, ?string $unit, string $kind): array
    {
        $name = trim((string) ($row['name'] ?? ''));
        $address = trim((string) ($row['address'] ?? ''));
        $locationLabel = trim((string) ($row['location'] ?? $row['location_label'] ?? ''));
        $phone = trim((string) ($row['phone'] ?? ''));
        $whatsapp = trim((string) ($row['whatsapp'] ?? ''));
        $email = trim((string) ($row['email'] ?? ''));
        $schedule = trim((string) ($row['schedule'] ?? ''));
        $lat = trim((string) ($row['lat'] ?? $row['latitude'] ?? ''));
        $lng = trim((string) ($row['lng'] ?? $row['longitude'] ?? ''));
        $racCode = strtoupper(trim((string) ($row['rac_code'] ?? $row['code'] ?? '')));
        $imageUrl = trim((string) ($row['image_url'] ?? $row['photo_url'] ?? $row['image'] ?? ''));
        $mapUrl = trim((string) ($row['map_url'] ?? ''));

        if ($lat === '' && $lng === '' && $mapUrl !== '') {
            [$latFromMap, $lngFromMap] = self::parseCoordsFromMapUrl($mapUrl);
            if ($lat === '') {
                $lat = $latFromMap;
            }
            if ($lng === '') {
                $lng = $lngFromMap;
            }
        }

        return [
            'record_id' => $source . '#' . $index,
            'source' => $source,
            'source_index' => $index,
            'kind' => $kind,
            'unit' => $unit,
            'name' => $name,
            'name_key' => self::nameKey($name),
            'location_label' => $locationLabel,
            'address' => $address,
            'phone' => $phone,
            'whatsapp' => $whatsapp,
            'email' => $email,
            'schedule' => $schedule,
            'lat' => $lat,
            'lng' => $lng,
            'rac_code' => $racCode,
            'image_url' => $imageUrl,
            'map_url' => $mapUrl,
            'hours_structured' => $row['hours_structured'] ?? ($row['dailyHours'] ?? null),
        ];
    }

    /** @return array<string, mixed> */
    public function analyze(): array
    {
        $countsBySource = [];
        foreach ($this->records as $record) {
            $src = $record['source'];
            $countsBySource[$src] = ($countsBySource[$src] ?? 0) + 1;
        }

        $parent = [];
        foreach ($this->records as $record) {
            $parent[$record['record_id']] = $record['record_id'];
        }

        $strongMatches = [];
        $mediumMatches = [];

        $byNameKey = [];
        $byRacCode = [];
        foreach ($this->records as $record) {
            if ($record['name_key'] !== '') {
                $byNameKey[$record['name_key']][] = $record['record_id'];
            }
            if ($record['rac_code'] !== '') {
                $byRacCode[$record['rac_code']][] = $record['record_id'];
            }
        }

        foreach ($byRacCode as $code => $ids) {
            if (count($ids) < 2) {
                continue;
            }
            for ($i = 1; $i < count($ids); $i++) {
                self::union($parent, $ids[0], $ids[$i]);
                $strongMatches[] = [
                    'type' => 'rac_code',
                    'detail' => $code,
                    'records' => [$ids[0], $ids[$i]],
                ];
            }
        }

        foreach ($byNameKey as $nameKey => $ids) {
            if ($nameKey === '' || count($ids) < 2) {
                continue;
            }
            for ($i = 1; $i < count($ids); $i++) {
                self::union($parent, $ids[0], $ids[$i]);
                $strongMatches[] = [
                    'type' => 'normalized_name',
                    'detail' => $nameKey,
                    'records' => [$ids[0], $ids[$i]],
                ];
            }
        }

        $recordMap = [];
        foreach ($this->records as $record) {
            $recordMap[$record['record_id']] = $record;
        }

        $ids = array_keys($recordMap);
        $n = count($ids);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $a = $recordMap[$ids[$i]];
                $b = $recordMap[$ids[$j]];
                if (self::find($parent, $a['record_id']) === self::find($parent, $b['record_id'])) {
                    continue;
                }

                $nameSim = self::nameSimilarity($a['name_key'], $b['name_key']);
                $dist = self::distanceMeters($a['lat'], $a['lng'], $b['lat'], $b['lng']);

                $medium = false;
                $detail = [];
                if ($nameSim >= NAME_SIMILAR_THRESHOLD && $a['name_key'] !== '' && $b['name_key'] !== '') {
                    $medium = true;
                    $detail[] = 'name_similarity=' . round($nameSim, 2);
                }
                if ($dist !== null && $dist <= COORD_MATCH_METERS) {
                    $medium = true;
                    $detail[] = 'distance_m=' . (int) round($dist);
                }

                if ($medium) {
                    self::union($parent, $a['record_id'], $b['record_id']);
                    $mediumMatches[] = [
                        'type' => 'similar_name_or_coords',
                        'detail' => implode(', ', $detail),
                        'records' => [$a['record_id'], $b['record_id']],
                        'names' => [$a['name'], $b['name']],
                    ];
                }
            }
        }

        $clusters = [];
        foreach ($this->records as $record) {
            $root = self::find($parent, $record['record_id']);
            $clusters[$root][] = $record;
        }

        $candidateLocations = [];
        $conflicts = [];
        $orphans = [];
        $locationRefs = [
            'homepage' => [],
            'footer' => [],
            'seminuevos' => [],
            'leasing' => [],
            'renting' => [],
            'taller' => [],
        ];

        $locNum = 0;
        foreach ($clusters as $root => $members) {
            $locNum++;
            $locationId = sprintf('loc_%03d', $locNum);
            $merged = self::mergeCluster($members);
            $merged['id'] = $locationId;
            $merged['slug'] = LocationService::normalizeSlug($merged['name'] !== '' ? $merged['name'] : ('ubicacion-' . $locNum));
            $merged['sources'] = array_values(array_unique(array_map(fn ($m) => $m['source'], $members)));
            $merged['source_records'] = array_map(fn ($m) => $m['record_id'], $members);
            $merged['units'] = self::unitsFromMembers($members);
            $merged['missing_fields'] = self::missingFields($merged);
            $candidateLocations[] = $merged;

            $sourceSet = $merged['sources'];
            if (count($sourceSet) === 1 && count($members) === 1) {
                $orphans[] = [
                    'record_id' => $members[0]['record_id'],
                    'source' => $members[0]['source'],
                    'name' => $members[0]['name'],
                    'unit' => $members[0]['unit'],
                ];
            }

            $clusterConflicts = self::detectConflicts($members, $locationId, $merged['name']);
            $conflicts = array_merge($conflicts, $clusterConflicts);

            foreach ($members as $member) {
                $ref = [
                    'location_id' => $locationId,
                    'location_slug' => $merged['slug'],
                    'sort_order' => 99,
                    'active' => true,
                    'source' => $member['source'],
                ];
                switch ($member['source']) {
                    case 'homepage.sucursales':
                        $locationRefs['homepage'][] = $ref;
                        break;
                    case 'footer.sucursales':
                        $ref['unit'] = $member['unit'] ?? 'grupo';
                        $locationRefs['footer'][] = $ref;
                        break;
                    case 'seminuevos.sucursales':
                    case 'seminuevos.branches':
                        $locationRefs['seminuevos'][] = $ref;
                        break;
                    case 'leasing.sucursales':
                    case 'leasing.branches':
                        $locationRefs['leasing'][] = $ref;
                        break;
                    case 'renting.sucursales':
                    case 'renting.branches':
                        $locationRefs['renting'][] = $ref;
                        break;
                    case 'taller.sucursales':
                    case 'taller.branches':
                        $locationRefs['taller'][] = $ref;
                        break;
                }
            }
        }

        usort($candidateLocations, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return [
            'counts_by_source' => $countsBySource,
            'total_records' => count($this->records),
            'strong_matches' => $strongMatches,
            'medium_matches' => $mediumMatches,
            'candidate_locations' => $candidateLocations,
            'conflicts' => $conflicts,
            'orphans' => $orphans,
            'proposed_location_refs' => $locationRefs,
        ];
    }

    /** @param list<array<string, mixed>> $members */
    private static function mergeCluster(array $members): array
    {
        $merged = [
            'name' => '',
            'location_label' => '',
            'address' => '',
            'phone' => '',
            'whatsapp' => '',
            'email' => '',
            'schedule' => '',
            'lat' => '',
            'lng' => '',
            'rac_code' => '',
            'image_url' => '',
            'hours_structured' => null,
        ];

        usort($members, function ($a, $b) {
            return self::sourcePriority($b['source']) <=> self::sourcePriority($a['source']);
        });

        foreach ($members as $member) {
            foreach (['name', 'location_label', 'address', 'phone', 'whatsapp', 'email', 'schedule', 'lat', 'lng', 'rac_code', 'image_url'] as $field) {
                if ($merged[$field] === '' && $member[$field] !== '') {
                    $merged[$field] = $member[$field];
                }
            }
            if ($merged['hours_structured'] === null && !empty($member['hours_structured'])) {
                $merged['hours_structured'] = $member['hours_structured'];
            }
        }

        $racMember = null;
        foreach ($members as $member) {
            if ($member['kind'] === 'rac_json') {
                $racMember = $member;
                break;
            }
        }
        if ($racMember !== null && $racMember['phone'] !== '') {
            $merged['phone'] = $racMember['phone'];
        }

        return $merged;
    }

    private static function sourcePriority(string $source): int
    {
        return match ($source) {
            'app/data/sucursales.json' => 100,
            'homepage.sucursales' => 90,
            'seminuevos.sucursales', 'leasing.sucursales', 'renting.sucursales', 'taller.sucursales' => 70,
            'seminuevos.branches', 'leasing.branches', 'renting.branches', 'taller.branches' => 65,
            'footer.sucursales' => 50,
            'global.sucursales' => 20,
            default => 0,
        };
    }

    /** @param list<array<string, mixed>> $members */
    private static function unitsFromMembers(array $members): array
    {
        $units = [];
        foreach ($members as $member) {
            $unit = $member['unit'] ?? null;
            if ($unit === null || $unit === 'grupo' || $unit === 'global') {
                continue;
            }
            if (!isset($units[$unit])) {
                $units[$unit] = ['active' => true];
            }
            foreach (['phone', 'whatsapp', 'email', 'schedule'] as $field) {
                if (($member[$field] ?? '') !== '') {
                    $units[$unit][$field . '_override'] = $member[$field];
                }
            }
        }
        return $units;
    }

    /** @param array<string, mixed> $merged */
    private static function missingFields(array $merged): array
    {
        $missing = [];
        foreach ([
            'address' => 'dirección',
            'phone' => 'teléfono',
            'whatsapp' => 'WhatsApp',
            'email' => 'email',
            'schedule' => 'horarios',
            'lat' => 'coordenadas (lat)',
            'lng' => 'coordenadas (lng)',
            'rac_code' => 'rac_code',
        ] as $key => $label) {
            if (trim((string) ($merged[$key] ?? '')) === '') {
                $missing[] = $label;
            }
        }
        if (empty($merged['units'])) {
            $missing[] = 'unidad asociada';
        }
        return $missing;
    }

    /** @param list<array<string, mixed>> $members */
    private static function detectConflicts(array $members, string $locationId, string $name): array
    {
        $conflicts = [];
        if (count($members) < 2) {
            return $conflicts;
        }

        $addresses = array_values(array_unique(array_filter(array_map(fn ($m) => self::normalizeText($m['address']), $members))));
        if (count($addresses) > 1) {
            $conflicts[] = [
                'location_id' => $locationId,
                'name' => $name,
                'type' => 'same_cluster_different_address',
                'values' => $addresses,
                'records' => array_map(fn ($m) => $m['record_id'], $members),
            ];
        }

        $phones = array_values(array_unique(array_filter(array_map(fn ($m) => preg_replace('/\D/', '', $m['phone']), $members))));
        $phones = array_values(array_filter($phones));
        if (count($phones) > 1) {
            $conflicts[] = [
                'location_id' => $locationId,
                'name' => $name,
                'type' => 'same_cluster_different_phone',
                'values' => $phones,
                'records' => array_map(fn ($m) => $m['record_id'], $members),
            ];
        }

        $schedules = array_values(array_unique(array_filter(array_map(fn ($m) => self::normalizeText($m['schedule']), $members))));
        if (count($schedules) > 1) {
            $conflicts = array_merge($conflicts, [[
                'location_id' => $locationId,
                'name' => $name,
                'type' => 'same_cluster_different_schedule',
                'values' => $schedules,
                'records' => array_map(fn ($m) => $m['record_id'], $members),
            ]]);
        }

        $units = array_values(array_unique(array_filter(array_map(fn ($m) => (string) ($m['unit'] ?? ''), $members))));
        $units = array_values(array_filter($units, fn ($u) => $u !== '' && $u !== 'global'));
        if (count($units) > 1) {
            $conflicts[] = [
                'location_id' => $locationId,
                'name' => $name,
                'type' => 'same_location_multiple_units',
                'values' => $units,
                'records' => array_map(fn ($m) => $m['record_id'], $members),
                'note' => 'Esperado en ubicaciones compartidas; revisar overrides por unidad.',
            ];
        }

        $nameGroups = [];
        foreach ($members as $member) {
            if ($member['name_key'] === '') {
                continue;
            }
            $nameGroups[$member['name_key']][] = $member;
        }
        foreach ($nameGroups as $nameKey => $group) {
            if (count($group) < 2) {
                continue;
            }
            $addrs = array_values(array_unique(array_filter(array_map(fn ($m) => self::normalizeText($m['address']), $group))));
            if (count($addrs) > 1) {
                $conflicts[] = [
                    'location_id' => $locationId,
                    'name' => $name,
                    'type' => 'same_name_different_address',
                    'values' => $addrs,
                    'records' => array_map(fn ($m) => $m['record_id'], $group),
                ];
            }
            $phones = array_values(array_unique(array_filter(array_map(fn ($m) => preg_replace('/\D/', '', $m['phone']), $group))));
            $phones = array_values(array_filter($phones));
            if (count($phones) > 1) {
                $conflicts[] = [
                    'location_id' => $locationId,
                    'name' => $name,
                    'type' => 'same_name_different_phone',
                    'values' => $phones,
                    'records' => array_map(fn ($m) => $m['record_id'], $group),
                ];
            }
        }

        return $conflicts;
    }

    private static function nameKey(string $name): string
    {
        $key = mb_strtolower(trim($name));
        $key = preg_replace('/\s+/', ' ', $key) ?? $key;
        if (function_exists('iconv')) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $key);
            if ($ascii !== false) {
                $key = $ascii;
            }
        }
        return preg_replace('/[^a-z0-9]+/', '', $key) ?? '';
    }

    private static function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        if (function_exists('iconv')) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($ascii !== false) {
                $text = $ascii;
            }
        }
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? $text;
        return trim($text, '-') ?: 'ubicacion';
    }

    private static function normalizeText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    /** @return array{0:string,1:string} */
    private static function parseCoordsFromMapUrl(string $mapUrl): array
    {
        if (preg_match('/!3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)/', $mapUrl, $m)) {
            return [$m[1], $m[2]];
        }
        if (preg_match('/@(-?\d+\.?\d*),(-?\d+\.?\d*)/', $mapUrl, $m)) {
            return [$m[1], $m[2]];
        }
        return ['', ''];
    }

    private static function nameSimilarity(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }
        if ($a === $b) {
            return 1.0;
        }
        similar_text($a, $b, $pct);
        return $pct / 100;
    }

    private static function distanceMeters(string $lat1, string $lng1, string $lat2, string $lng2): ?float
    {
        if ($lat1 === '' || $lng1 === '' || $lat2 === '' || $lng2 === '') {
            return null;
        }
        $la1 = (float) $lat1;
        $lo1 = (float) $lng1;
        $la2 = (float) $lat2;
        $lo2 = (float) $lng2;
        $earth = 6371000;
        $dLat = deg2rad($la2 - $la1);
        $dLng = deg2rad($lo2 - $lo1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($la1)) * cos(deg2rad($la2)) * sin($dLng / 2) ** 2;
        return 2 * $earth * asin(min(1, sqrt($a)));
    }

    /** @param array<string, string> $parent */
    private static function find(array &$parent, string $id): string
    {
        if ($parent[$id] !== $id) {
            $parent[$id] = self::find($parent, $parent[$id]);
        }
        return $parent[$id];
    }

    /** @param array<string, string> $parent */
    private static function union(array &$parent, string $a, string $b): void
    {
        $ra = self::find($parent, $a);
        $rb = self::find($parent, $b);
        if ($ra !== $rb) {
            $parent[$rb] = $ra;
        }
    }
}

final class LocationDryRunReporter
{
    /** @param array<string, mixed> $analysis */
    /** @param array<string, mixed> $meta */
    public static function toMarkdown(array $analysis, array $meta): string
    {
        $lines = [];
        $lines[] = '# AM-SEO-3C-A0 - Dry-run migracion locations master';
        $lines[] = '';
        $lines[] = '- **Generado:** ' . ($meta['generated_at'] ?? '');
        $lines[] = '- **site_data.json:** `' . ($meta['site_data_path'] ?? '') . '`';
        $lines[] = '- **RAC JSON:** `' . ($meta['rac_json_path'] ?? '') . '`';
        $lines[] = '- **Modo:** solo lectura (sin escritura en CMS)';
        $lines[] = '';

        $lines[] = '## 1. Registros por fuente';
        $lines[] = '';
        $lines[] = '| Fuente | Registros |';
        $lines[] = '|--------|-----------:|';
        foreach ($analysis['counts_by_source'] as $source => $count) {
            $lines[] = '| `' . $source . '` | ' . $count . ' |';
        }
        $lines[] = '| **Total** | **' . ($analysis['total_records'] ?? 0) . '** |';
        $lines[] = '';

        $lines[] = '## 2. Resumen de clustering';
        $lines[] = '';
        $lines[] = '- **Locations candidatas:** ' . count($analysis['candidate_locations']);
        $lines[] = '- **Matches fuertes:** ' . count($analysis['strong_matches']);
        $lines[] = '- **Matches medios:** ' . count($analysis['medium_matches']);
        $lines[] = '- **Conflictos detectados:** ' . count($analysis['conflicts']);
        $lines[] = '- **Huérfanos (1 fuente / 1 registro):** ' . count($analysis['orphans']);
        $lines[] = '';

        $lines[] = '## 3. Matches fuertes (muestra)';
        $lines[] = '';
        self::appendMatchTable($lines, array_slice($analysis['strong_matches'], 0, 40));
        if (count($analysis['strong_matches']) > 40) {
            $lines[] = '';
            $lines[] = '_(' . (count($analysis['strong_matches']) - 40) . ' matches fuertes adicionales omitidos)_';
        }
        $lines[] = '';

        $lines[] = '## 4. Matches medios (muestra)';
        $lines[] = '';
        self::appendMatchTable($lines, array_slice($analysis['medium_matches'], 0, 40), true);
        if (count($analysis['medium_matches']) > 40) {
            $lines[] = '';
            $lines[] = '_(' . (count($analysis['medium_matches']) - 40) . ' matches medios adicionales omitidos)_';
        }
        $lines[] = '';

        $lines[] = '## 5. Conflictos';
        $lines[] = '';
        if ($analysis['conflicts'] === []) {
            $lines[] = '_Sin conflictos._';
        } else {
            $lines[] = '| Location | Tipo | Detalle |';
            $lines[] = '|----------|------|---------|';
            foreach (array_slice($analysis['conflicts'], 0, 60) as $conflict) {
                $detail = implode('; ', array_map('strval', $conflict['values'] ?? []));
                if (strlen($detail) > 120) {
                    $detail = substr($detail, 0, 117) . '...';
                }
                $lines[] = '| `' . ($conflict['location_id'] ?? '') . '` | `' . ($conflict['type'] ?? '') . '` | ' . self::esc($detail) . ' |';
            }
            if (count($analysis['conflicts']) > 60) {
                $lines[] = '';
                $lines[] = '_(' . (count($analysis['conflicts']) - 60) . ' conflictos adicionales omitidos)_';
            }
        }
        $lines[] = '';

        $phoneConflicts = $meta['phone_conflicts'] ?? [];
        $lines[] = '## 5b. Conflictos de teléfono (revisión manual)';
        $lines[] = '';
        if ($phoneConflicts === []) {
            $lines[] = '_Sin conflictos de teléfono._';
        } else {
            $lines[] = '| Location | Nombre | Teléfonos en conflicto |';
            $lines[] = '|----------|--------|----------------------|';
            foreach ($phoneConflicts as $pc) {
                $phones = implode(', ', array_map('strval', $pc['values'] ?? []));
                $lines[] = '| `' . ($pc['location_id'] ?? '') . '` | ' . self::esc($pc['name'] ?? '') . ' | ' . self::esc($phones) . ' |';
            }
        }
        $lines[] = '';

        $lines[] = '## 6. Huérfanos (muestra)';
        $lines[] = '';
        if ($analysis['orphans'] === []) {
            $lines[] = '_Sin huérfanos._';
        } else {
            $lines[] = '| Fuente | Nombre | Unidad |';
            $lines[] = '|--------|--------|--------|';
            foreach (array_slice($analysis['orphans'], 0, 50) as $orphan) {
                $lines[] = '| `' . ($orphan['source'] ?? '') . '` | ' . self::esc($orphan['name'] ?? '') . ' | `' . ($orphan['unit'] ?? '') . '` |';
            }
        }
        $lines[] = '';

        $lines[] = '## 7. Campos faltantes por location candidata';
        $lines[] = '';
        $lines[] = '| ID | Nombre | Faltantes |';
        $lines[] = '|----|--------|-----------|';
        foreach ($analysis['candidate_locations'] as $loc) {
            $missing = implode(', ', $loc['missing_fields'] ?? []);
            $lines[] = '| `' . ($loc['id'] ?? '') . '` | ' . self::esc($loc['name'] ?? '') . ' | ' . self::esc($missing) . ' |';
        }
        $lines[] = '';

        $lines[] = '## 8. Propuesta `locations[]` (JSON)';
        $lines[] = '';
        $proposed = array_map(function ($loc) {
            return [
                'id' => $loc['id'],
                'slug' => $loc['slug'],
                'name' => $loc['name'],
                'location_label' => $loc['location_label'],
                'address' => $loc['address'],
                'lat' => $loc['lat'],
                'lng' => $loc['lng'],
                'phones' => $loc['phone'] !== '' ? [$loc['phone']] : [],
                'whatsapp' => $loc['whatsapp'],
                'email' => $loc['email'],
                'hours' => [
                    'display' => $loc['schedule'],
                    'structured' => $loc['hours_structured'],
                ],
                'rac_code' => $loc['rac_code'],
                'active' => true,
                'sort_order' => 99,
                'units' => $loc['units'],
                'meta' => ['sources' => $loc['sources']],
            ];
        }, $analysis['candidate_locations']);
        $lines[] = '```json';
        $lines[] = json_encode($proposed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $lines[] = '```';
        $lines[] = '';

        $lines[] = '## 9. Propuesta `location_refs` por unidad';
        $lines[] = '';
        $lines[] = '```json';
        $lines[] = json_encode($analysis['proposed_location_refs'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $lines[] = '```';
        $lines[] = '';

        $lines[] = '## 10. Recomendación';
        $lines[] = '';
        $conflictCount = count($analysis['conflicts']);
        $candidateCount = count($analysis['candidate_locations']);
        $orphanCount = count($analysis['orphans']);
        if ($conflictCount <= 15 && $candidateCount > 0) {
            $lines[] = '**Pasar a AM-SEO-3C-A1** con revisión manual de conflictos listados arriba.';
        } elseif ($conflictCount > 15 && $conflictCount <= 40) {
            $lines[] = '**Pasar a AM-SEO-3C-A1 con precaución:** resolver conflictos de dirección/teléfono antes de escribir `locations[]`.';
        } else {
            $lines[] = '**No pasar a escritura automática todavía:** alto volumen de conflictos; requiere limpieza editorial en admin.';
        }
        $lines[] = '';
        $lines[] = '- Locations candidatas: **' . $candidateCount . '**';
        $lines[] = '- Conflictos: **' . $conflictCount . '**';
        $lines[] = '- Huérfanos: **' . $orphanCount . '**';
        $lines[] = '';

        return implode("\n", $lines) . "\n";
    }

    /** @param list<array<string, mixed>> $matches */
    private static function appendMatchTable(array &$lines, array $matches, bool $withNames = false): void
    {
        if ($matches === []) {
            $lines[] = '_Sin matches en esta categoría._';
            return;
        }
        $lines[] = '| Tipo | Detalle | Registros |';
        $lines[] = '|------|---------|-----------|';
        foreach ($matches as $match) {
            $detail = (string) ($match['detail'] ?? '');
            if ($withNames && !empty($match['names'])) {
                $detail .= ' — ' . implode(' / ', $match['names']);
            }
            $records = implode(', ', $match['records'] ?? []);
            $lines[] = '| `' . ($match['type'] ?? '') . '` | ' . self::esc($detail) . ' | `' . self::esc($records) . '` |';
        }
    }

    private static function esc(string $value): string
    {
        return str_replace(['|', "\n"], ['\\|', ' '], $value);
    }
}

final class LocationMigrationWriter
{
    /** @param array<string, mixed> $siteData */
    public static function snapshotLegacyCounts(array $siteData): array
    {
        return [
            'homepage.sucursales' => count($siteData['homepage']['sucursales'] ?? []),
            'footer.sucursales' => count($siteData['footer']['sucursales'] ?? []),
            'global.sucursales' => count($siteData['global']['sucursales'] ?? []),
            'seminuevos.sucursales' => count($siteData['seminuevos']['sucursales'] ?? []),
            'leasing.sucursales' => count($siteData['leasing']['sucursales'] ?? []),
            'renting.sucursales' => count($siteData['renting']['sucursales'] ?? []),
            'taller.sucursales' => count($siteData['taller']['sucursales'] ?? []),
            'seminuevos.branches' => count($siteData['seminuevos']['branches'] ?? []),
            'leasing.branches' => count($siteData['leasing']['branches'] ?? []),
            'renting.branches' => count($siteData['renting']['branches'] ?? []),
            'taller.branches' => count($siteData['taller']['branches'] ?? []),
        ];
    }

    /** @param list<array<string, mixed>> $conflicts */
    /** @return list<array<string, mixed>> */
    public static function filterPhoneConflicts(array $conflicts): array
    {
        return array_values(array_filter($conflicts, function (array $c): bool {
            return in_array($c['type'] ?? '', ['same_cluster_different_phone', 'same_name_different_phone'], true);
        }));
    }

    public static function createBackup(string $siteDataPath): ?string
    {
        if (!is_readable($siteDataPath)) {
            return null;
        }
        if (!is_dir(BACKUP_DIR) && !mkdir(BACKUP_DIR, 0755, true)) {
            return null;
        }

        $stamp = date('Ymd-His');
        $backupPath = BACKUP_DIR . '/site_data-before-locations-' . $stamp . '.json';
        if (!copy($siteDataPath, $backupPath)) {
            return null;
        }

        return $backupPath;
    }

    /**
     * @param array<string, mixed> $siteData
     * @param array<string, mixed> $analysis
     * @return array{ok:bool, site_data?:array<string,mixed>, refs_counts?:array<string,int>, error?:string}
     */
    public static function apply(array $siteData, array $analysis): array
    {
        $candidates = $analysis['candidate_locations'] ?? [];
        if (!is_array($candidates) || $candidates === []) {
            return ['ok' => false, 'error' => 'No hay locations candidatas para escribir.'];
        }

        $locations = [];
        $usedSlugs = [];
        $usedRacCodes = [];

        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $id = (string) ($candidate['id'] ?? '');
            if ($id === '') {
                continue;
            }

            $baseSlug = LocationService::normalizeSlug((string) ($candidate['slug'] ?? $candidate['name'] ?? $id));
            $slug = $baseSlug;
            $suffix = 2;
            while (isset($usedSlugs[$slug])) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }
            $usedSlugs[$slug] = true;

            $racCode = strtoupper(trim((string) ($candidate['rac_code'] ?? '')));
            if ($racCode !== '') {
                if (isset($usedRacCodes[$racCode])) {
                    return ['ok' => false, 'error' => "rac_code duplicado en migración: {$racCode}"];
                }
                $usedRacCodes[$racCode] = true;
            }

            $phone = trim((string) ($candidate['phone'] ?? ''));
            $locations[] = [
                'id' => $id,
                'slug' => $slug,
                'name' => (string) ($candidate['name'] ?? ''),
                'location_label' => (string) ($candidate['location_label'] ?? ''),
                'address' => (string) ($candidate['address'] ?? ''),
                'city' => 'Ciudad de Panamá',
                'country' => 'PA',
                'lat' => (string) ($candidate['lat'] ?? ''),
                'lng' => (string) ($candidate['lng'] ?? ''),
                'image_url' => (string) ($candidate['image_url'] ?? ''),
                'map_url' => '',
                'phones' => $phone !== '' ? [$phone] : [],
                'whatsapp' => (string) ($candidate['whatsapp'] ?? ''),
                'email' => (string) ($candidate['email'] ?? ''),
                'hours' => [
                    'display' => (string) ($candidate['schedule'] ?? ''),
                    'structured' => $candidate['hours_structured'] ?? null,
                ],
                'rac_code' => $racCode,
                'active' => true,
                'sort_order' => 99,
                'units' => self::normalizeUnitsForStorage($candidate['units'] ?? []),
                'meta' => [
                    'migrated_at' => date('c'),
                    'sources' => $candidate['sources'] ?? [],
                ],
            ];
        }

        $service = new LocationService($siteData);
        foreach ($locations as $location) {
            if (!$service->isSlugUnique((string) $location['slug'], (string) $location['id'])) {
                return ['ok' => false, 'error' => 'slug duplicado: ' . $location['slug']];
            }
            $rac = (string) ($location['rac_code'] ?? '');
            if ($rac !== '' && !$service->isRacCodeUnique($rac, (string) $location['id'])) {
                return ['ok' => false, 'error' => 'rac_code duplicado existente: ' . $rac];
            }
        }

        $siteData['locations'] = $locations;

        $refs = $analysis['proposed_location_refs'] ?? [];
        $siteData['homepage']['location_refs'] = self::dedupeRefs($refs['homepage'] ?? [], false);
        $siteData['footer']['location_refs'] = self::dedupeRefs($refs['footer'] ?? [], true);
        $siteData['seminuevos']['location_refs'] = self::dedupeRefs($refs['seminuevos'] ?? [], false);
        $siteData['leasing']['location_refs'] = self::dedupeRefs($refs['leasing'] ?? [], false);
        $siteData['renting']['location_refs'] = self::dedupeRefs($refs['renting'] ?? [], false);
        $siteData['taller']['location_refs'] = self::dedupeRefs($refs['taller'] ?? [], false);

        return [
            'ok' => true,
            'site_data' => $siteData,
            'refs_counts' => [
                'homepage' => count($siteData['homepage']['location_refs'] ?? []),
                'footer' => count($siteData['footer']['location_refs'] ?? []),
                'seminuevos' => count($siteData['seminuevos']['location_refs'] ?? []),
                'leasing' => count($siteData['leasing']['location_refs'] ?? []),
                'renting' => count($siteData['renting']['location_refs'] ?? []),
                'taller' => count($siteData['taller']['location_refs'] ?? []),
            ],
        ];
    }

    /** @param array<string, mixed> $siteData */
    public static function saveSiteData(array $siteData, string $path): bool
    {
        $json = json_encode($siteData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }

        $tmp = $path . '.tmp.' . getmypid();
        if (file_put_contents($tmp, $json) === false) {
            @unlink($tmp);
            return false;
        }

        if (!rename($tmp, $path)) {
            @unlink($tmp);
            return false;
        }

        return true;
    }

    /** @param list<mixed> $refs */
    /** @return list<array<string, mixed>> */
    private static function dedupeRefs(array $refs, bool $withUnit): array
    {
        $seen = [];
        $out = [];
        foreach ($refs as $ref) {
            if (!is_array($ref)) {
                continue;
            }
            $locationId = trim((string) ($ref['location_id'] ?? ''));
            if ($locationId === '') {
                continue;
            }
            $unit = $withUnit ? trim((string) ($ref['unit'] ?? 'grupo')) : '';
            $key = $locationId . '|' . $unit;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $row = [
                'location_id' => $locationId,
                'sort_order' => (int) ($ref['sort_order'] ?? 99),
                'active' => ($ref['active'] ?? true) !== false,
            ];
            if ($withUnit) {
                $row['unit'] = $unit !== '' ? $unit : 'grupo';
            }
            $out[] = $row;
        }

        return $out;
    }

    /** @param array<string, mixed> $units */
    /** @return array<string, array<string, mixed>> */
    private static function normalizeUnitsForStorage(array $units): array
    {
        $out = [];
        foreach ($units as $unitKey => $unitData) {
            if (!is_string($unitKey) || !is_array($unitData)) {
                continue;
            }
            $row = ['active' => ($unitData['active'] ?? true) !== false];
            foreach (['phone', 'whatsapp', 'email', 'schedule'] as $field) {
                $overrideKey = $field . '_override';
                if (isset($unitData[$overrideKey]) && trim((string) $unitData[$overrideKey]) !== '') {
                    $row[$field] = trim((string) $unitData[$overrideKey]);
                } elseif (isset($unitData[$field]) && trim((string) $unitData[$field]) !== '') {
                    $row[$field] = trim((string) $unitData[$field]);
                }
            }
            if (isset($unitData['sort_order'])) {
                $row['sort_order'] = (int) $unitData['sort_order'];
            }
            $out[$unitKey] = $row;
        }

        return $out;
    }
}

exit(main($argv));
