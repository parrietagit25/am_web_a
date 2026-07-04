<?php
/**
 * CLI — Actualización programada de tarifas BARS en base de datos.
 * AM-RAC-BARS-CACHE-2A
 *
 * Cron sugerido (no instalar sin autorización):
 * Cada 15 min: cd /home/am_web_a && php app/cron/rac-bars-rates-refresh.php --due >> app/storage/logs/rac-bars-rates-refresh.log 2>&1
 *
 * Uso:
 *   php app/cron/rac-bars-rates-refresh.php --due
 *   php app/cron/rac-bars-rates-refresh.php --schedule-id=1 --force
 *   php app/cron/rac-bars-rates-refresh.php --all --force
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/app/config/config.php';
require_once $root . '/app/services/BarsRateCacheService.php';

$options = getopt('', ['due', 'all', 'force', 'schedule-id::']);
$force = array_key_exists('force', $options);
$cacheService = new BarsRateCacheService();

if (!empty($options['schedule-id'])) {
    $scheduleId = (int) $options['schedule-id'];
    $results = [$cacheService->runSchedule($scheduleId, $force)];
} elseif (array_key_exists('all', $options)) {
    $results = $cacheService->runDueSchedules(true);
} elseif (array_key_exists('due', $options)) {
    $results = $cacheService->runDueSchedules(false);
} else {
    fwrite(STDERR, "Uso: php app/cron/rac-bars-rates-refresh.php [--due|--all] [--force] [--schedule-id=N]\n");
    exit(1);
}

if ($results === []) {
    echo "No hay programaciones para ejecutar.\n";
    exit(0);
}

foreach ($results as $result) {
    $line = sprintf(
        'schedule_id=%s status=%s count=%s available=%s unavailable=%s snapshot_id=%s message=%s',
        (string) ($result['schedule_id'] ?? 'manual'),
        (string) ($result['status'] ?? 'unknown'),
        (string) ($result['total_count'] ?? 0),
        (string) ($result['available_count'] ?? 0),
        (string) ($result['unavailable_count'] ?? 0),
        (string) ($result['snapshot_id'] ?? ''),
        str_replace(["\r", "\n"], ' ', (string) ($result['message'] ?? ''))
    );
    echo $line . PHP_EOL;
}

exit(0);
