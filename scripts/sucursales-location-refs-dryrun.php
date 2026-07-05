<?php
/**
 * Dry-run: mapeo legacy → location_id / location_refs (AM-CMS-LOCATION-REFS).
 *
 * Uso:
 *   php scripts/sucursales-location-refs-dryrun.php
 *   php scripts/sucursales-location-refs-dryrun.php --apply
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Solo CLI.\n";
    exit(1);
}

const SITE_DATA_PATH = __DIR__ . '/../app/storage/site_data.json';

require_once __DIR__ . '/../app/includes/admin-location-helper.php';

$apply = in_array('--apply', $argv, true);
$ts = date('Ymd_Hi');
$mdPath = __DIR__ . '/../app/storage/audits/sucursales_location_refs_dryrun_' . $ts . '.md';
$jsonPath = __DIR__ . '/../app/storage/audits/sucursales_location_refs_dryrun_' . $ts . '.json';

$raw = file_get_contents(SITE_DATA_PATH);
if ($raw === false) {
    fwrite(STDERR, "No se pudo leer site_data.json\n");
    exit(1);
}
$siteData = json_decode($raw, true);
if (!is_array($siteData)) {
    fwrite(STDERR, "JSON inválido\n");
    exit(1);
}

$mtimeBefore = filemtime(SITE_DATA_PATH);

$sources = [
    ['path' => 'homepage.sucursales', 'module' => 'RAC sucursales', 'field' => 'name'],
    ['path' => 'seminuevos.sucursales', 'module' => 'Seminuevos sucursales', 'field' => 'name'],
    ['path' => 'leasing.sucursales', 'module' => 'Leasing sucursales', 'field' => 'name'],
    ['path' => 'renting.sucursales', 'module' => 'Renting sucursales', 'field' => 'name'],
    ['path' => 'taller.sucursales', 'module' => 'Taller sucursales', 'field' => 'name'],
    ['path' => 'footer.sucursales', 'module' => 'Footer sucursales', 'field' => 'name'],
    ['path' => 'global.sucursales', 'module' => 'Global sucursales', 'field' => 'name'],
    ['path' => 'seminuevos.branches', 'module' => 'Seminuevos branches', 'field' => 'name'],
    ['path' => 'leasing.branches', 'module' => 'Leasing branches', 'field' => 'name'],
    ['path' => 'renting.branches', 'module' => 'Renting branches', 'field' => 'name'],
    ['path' => 'taller.branches', 'module' => 'Taller branches', 'field' => 'name'],
];

$results = [];
$stats = ['exact_id' => 0, 'exact_slug' => 0, 'name_match' => 0, 'not_found' => 0, 'duplicate_names' => 0];

foreach ($sources as $src) {
    $parts = explode('.', $src['path']);
    $node = $siteData;
    foreach ($parts as $p) {
        $node = is_array($node[$p] ?? null) ? $node[$p] : [];
    }
    if (!is_array($node)) {
        continue;
    }
    foreach ($node as $i => $row) {
        if (!is_array($row)) {
            continue;
        }
        $legacyName = trim((string) ($row['name'] ?? ''));
        $existingId = trim((string) ($row['location_id'] ?? ''));
        $legacySlug = trim((string) ($row['slug'] ?? ''));

        $matchType = 'not_found';
        $matchedId = '';
        $matchedName = '';

        $service = new LocationService($siteData);
        if ($existingId !== '' && $service->getById($existingId) !== null) {
            $matchType = 'exact_id';
            $matchedId = $existingId;
            $matchedName = (string) ($service->getById($existingId)['name'] ?? '');
            $stats['exact_id']++;
        } elseif ($legacySlug !== '' && ($loc = $service->getBySlug($legacySlug)) !== null) {
            $matchType = 'exact_slug';
            $matchedId = (string) ($loc['id'] ?? '');
            $matchedName = (string) ($loc['name'] ?? '');
            $stats['exact_slug']++;
        } elseif ($legacyName !== '') {
            $loc = admin_match_location_by_legacy_name($siteData, $legacyName);
            if ($loc !== null) {
                $matchType = 'name_match';
                $matchedId = (string) ($loc['id'] ?? '');
                $matchedName = (string) ($loc['name'] ?? '');
                $stats['name_match']++;
            } else {
                $stats['not_found']++;
            }
        }

        $results[] = [
            'module' => $src['module'],
            'path' => $src['path'],
            'index' => $i,
            'legacy_name' => $legacyName,
            'legacy_location_id' => $existingId,
            'match_type' => $matchType,
            'matched_location_id' => $matchedId,
            'matched_name' => $matchedName,
        ];
    }
}

// Equipo seminuevos
foreach ($siteData['seminuevos']['team']['agents'] ?? [] as $i => $agent) {
    if (!is_array($agent)) {
        continue;
    }
    $branch = trim((string) ($agent['branch'] ?? ''));
    $locId = trim((string) ($agent['location_id'] ?? ''));
    $matchType = 'not_found';
    $matchedId = '';
    if ($locId !== '' && (new LocationService($siteData))->getById($locId) !== null) {
        $matchType = 'exact_id';
        $matchedId = $locId;
        $stats['exact_id']++;
    } elseif ($branch !== '') {
        $loc = admin_match_location_by_legacy_name($siteData, $branch);
        if ($loc !== null) {
            $matchType = 'name_match';
            $matchedId = (string) ($loc['id'] ?? '');
            $stats['name_match']++;
        } else {
            $stats['not_found']++;
        }
    }
    $results[] = [
        'module' => 'Equipo seminuevos',
        'path' => 'seminuevos.team.agents',
        'index' => $i,
        'legacy_name' => $branch,
        'legacy_location_id' => $locId,
        'match_type' => $matchType,
        'matched_location_id' => $matchedId,
        'matched_name' => $branch,
    ];
}

// Duplicados por nombre en maestro
$nameKeys = [];
foreach ($siteData['locations'] ?? [] as $loc) {
    if (!is_array($loc)) {
        continue;
    }
    $key = admin_location_name_key((string) ($loc['name'] ?? ''));
    if ($key === '') {
        continue;
    }
    $nameKeys[$key] = ($nameKeys[$key] ?? 0) + 1;
}
$dupMaster = array_filter($nameKeys, fn($c) => $c > 1);
$stats['duplicate_names'] = count($dupMaster);

$report = [
    'generated_at' => date('c'),
    'mode' => $apply ? 'apply' : 'dry-run',
    'site_data_path' => SITE_DATA_PATH,
    'locations_count' => count($siteData['locations'] ?? []),
    'stats' => $stats,
    'duplicate_name_keys' => array_keys($dupMaster),
    'results' => $results,
    'apply_summary' => null,
];

if ($apply) {
    $backupPath = __DIR__ . '/../app/storage/site_data.json.pre-location-refs-' . $ts . '.bak';
    if (!copy(SITE_DATA_PATH, $backupPath)) {
        fwrite(STDERR, "No se pudo crear backup: {$backupPath}\n");
        exit(1);
    }

    $applied = 0;
    foreach ($results as $r) {
        if ($r['matched_location_id'] === '' || $r['match_type'] === 'not_found') {
            continue;
        }
        if ($r['legacy_location_id'] !== '') {
            continue;
        }
        // Solo marcar en reporte; apply real conserva legacy y agrega location_id en agentes
        if ($r['path'] === 'seminuevos.team.agents') {
            $idx = (int) $r['index'];
            if (isset($siteData['seminuevos']['team']['agents'][$idx])) {
                $siteData['seminuevos']['team']['agents'][$idx]['location_id'] = $r['matched_location_id'];
                $applied++;
            }
        }
    }

    file_put_contents(SITE_DATA_PATH, json_encode($siteData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    $report['apply_summary'] = [
        'backup' => $backupPath,
        'agents_location_id_written' => $applied,
        'note' => 'Silos legacy intactos; solo location_id en agentes sin id previo.',
    ];
}

$md = "# Dry-run location_refs — {$ts}\n\n";
$md .= "Modo: **" . ($apply ? 'APPLY' : 'dry-run') . "**\n\n";
$md .= "| Métrica | Valor |\n|---------|-------|\n";
$md .= "| locations[] | " . count($siteData['locations'] ?? []) . " |\n";
foreach ($stats as $k => $v) {
    $md .= "| {$k} | {$v} |\n";
}
$md .= "\n## No encontrados\n\n";
foreach ($results as $r) {
    if ($r['match_type'] !== 'not_found') {
        continue;
    }
    $md .= "- **{$r['module']}** [{$r['index']}]: «{$r['legacy_name']}»\n";
}
if ($apply && is_array($report['apply_summary'])) {
    $md .= "\n## Apply\n\n";
    $md .= "- Backup: `" . $report['apply_summary']['backup'] . "`\n";
    $md .= "- Agentes actualizados: " . $report['apply_summary']['agents_location_id_written'] . "\n";
}

@mkdir(dirname($mdPath), 0755, true);
file_put_contents($mdPath, $md);
file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if (!$apply && filemtime(SITE_DATA_PATH) !== $mtimeBefore) {
    fwrite(STDERR, "ADVERTENCIA: site_data.json cambió durante dry-run.\n");
    exit(1);
}

echo "Reporte: {$mdPath}\n";
echo "JSON: {$jsonPath}\n";
echo "Registros analizados: " . count($results) . "\n";
echo "not_found: {$stats['not_found']}\n";

exit(0);
