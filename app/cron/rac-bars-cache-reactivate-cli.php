<?php
/**
 * Reactiva cache BARS: schedules de refresh + modo tablas (no live-only).
 *
 * Uso:
 *   php app/cron/rac-bars-cache-reactivate-cli.php
 *   php app/cron/rac-bars-cache-reactivate-cli.php --seed-only
 *   php app/cron/rac-bars-cache-reactivate-cli.php --refresh
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Solo CLI.\n");
    exit(1);
}

$appDir = dirname(__DIR__);
require_once $appDir . '/config/config.php';
require_once $appDir . '/services/BarsRateCacheService.php';
require_once $appDir . '/services/RacPublicRateService.php';
require_once $appDir . '/services/RacBarsDatabaseSchema.php';

RacBarsDatabaseSchema::ensure();

$opts = getopt('', ['seed-only', 'refresh', 'force-seed']);
$seedOnly = array_key_exists('seed-only', $opts);
$doRefresh = array_key_exists('refresh', $opts) || !$seedOnly;
$forceSeed = array_key_exists('force-seed', $opts);

$cache = new BarsRateCacheService();
$existing = $cache->getSchedules();

echo 'RAC_BARS_LIVE_ONLY=';
if (defined('RAC_BARS_LIVE_ONLY')) {
    echo (RAC_BARS_LIVE_ONLY ? 'true' : 'false');
} else {
    echo 'undefined (default live=true)';
}
echo PHP_EOL;
echo 'isBarsLiveOnly=' . (RacPublicRateService::isBarsLiveOnly() ? '1' : '0') . PHP_EOL;
echo 'schedules_before=' . count($existing) . PHP_EOL;

if ($existing === [] || $forceSeed) {
    // Ventanas típicas de búsqueda + sucursales principales.
    $templates = [
        ['loc' => 'PTY', 'ahead' => 1, 'days' => 3],
        ['loc' => 'PTY', 'ahead' => 3, 'days' => 3],
        ['loc' => 'PTY', 'ahead' => 7, 'days' => 3],
        ['loc' => 'PTY', 'ahead' => 14, 'days' => 3],
        ['loc' => 'TCP', 'ahead' => 1, 'days' => 3],
        ['loc' => 'TCP', 'ahead' => 7, 'days' => 3],
        ['loc' => 'TBM', 'ahead' => 1, 'days' => 3],
        ['loc' => 'TBM', 'ahead' => 7, 'days' => 3],
        ['loc' => 'VENAO', 'ahead' => 1, 'days' => 3],
        ['loc' => 'VENAO', 'ahead' => 7, 'days' => 3],
    ];
    $times = ['06:00', '09:00', '12:00', '15:00', '18:00', '21:00', '23:00'];

    foreach ($templates as $t) {
        $loc = $t['loc'];
        $name = sprintf('%s +%dd / %dd WEB', $loc, $t['ahead'], $t['days']);
        // Evitar duplicar por nombre si --force-seed
        $dup = false;
        foreach ($cache->getSchedules() as $s) {
            if (($s['name'] ?? '') === $name) {
                $dup = true;
                break;
            }
        }
        if ($dup) {
            echo "skip existing: {$name}\n";
            continue;
        }
        $id = $cache->saveSchedule([
            'name' => $name,
            'enabled' => 1,
            'pickup_location' => $loc,
            'return_location' => $loc,
            'days_ahead' => $t['ahead'],
            'rental_days' => $t['days'],
            'pickup_time' => '10:00',
            'return_time' => '10:00',
            'rate_qualifier' => 'WEB',
            'scheduled_times' => $times,
        ]);
        echo "seeded id={$id} {$name}\n";
    }
} else {
    echo "Schedules ya existen (" . count($existing) . "). Usa --force-seed para agregar faltantes.\n";
}

$after = $cache->getSchedules();
echo 'schedules_after=' . count($after) . PHP_EOL;
foreach ($after as $s) {
    echo sprintf(
        "  #%s %s enabled=%s next=%s last=%s status=%s\n",
        $s['id'] ?? '',
        $s['name'] ?? '',
        !empty($s['enabled']) ? '1' : '0',
        $s['next_run_at'] ?? '—',
        $s['last_run_at'] ?? '—',
        $s['last_status'] ?? '—'
    );
}

if (!$doRefresh) {
    echo "OK seed-only.\n";
    exit(0);
}

if (!defined('RAC_BARS_LIVE_ONLY') || RAC_BARS_LIVE_ONLY) {
    echo "WARNING: RAC_BARS_LIVE_ONLY sigue en live. Define false en config.php para servir tablas.\n";
}

echo "=== Running --all --force ===\n";
$results = $cache->runDueSchedules(true);
if ($results === []) {
    echo "No hay programaciones para ejecutar.\n";
    exit(1);
}

$ok = 0;
$fail = 0;
foreach ($results as $r) {
    $status = (string) ($r['status'] ?? ($r['ok'] ? 'ok' : 'error'));
    $sid = $r['schedule_id'] ?? $r['id'] ?? '';
    $msg = (string) ($r['message'] ?? '');
    $total = $r['total_count'] ?? '';
    echo "schedule={$sid} status={$status} total={$total} {$msg}\n";
    if (in_array($status, ['success', 'ok', 'no_rates'], true) || !empty($r['ok']) || !empty($r['saved'])) {
        $ok++;
    } else {
        $fail++;
    }
}

$db = Database::getInstance();
$snap = $db->selectOne('SELECT COUNT(*) AS c, MAX(fetched_at) AS last_fetch FROM rac_bars_rate_snapshots');
$rates = $db->selectOne('SELECT COUNT(*) AS c FROM rac_bars_rates');
$calc = $db->selectOne('SELECT COUNT(*) AS c, MAX(calculated_at) AS last_calc FROM rac_calculated_rates');
echo 'snapshots=' . ($snap['c'] ?? 0) . ' last_fetch=' . ($snap['last_fetch'] ?? '') . PHP_EOL;
echo 'rates=' . ($rates['c'] ?? 0) . ' calculated=' . ($calc['c'] ?? 0) . ' last_calc=' . ($calc['last_calc'] ?? '') . PHP_EOL;
echo "done ok={$ok} fail={$fail}\n";
echo 'isBarsLiveOnly_now=' . (RacPublicRateService::isBarsLiveOnly() ? '1' : '0') . PHP_EOL;

exit($fail > 0 && $ok === 0 ? 1 : 0);
