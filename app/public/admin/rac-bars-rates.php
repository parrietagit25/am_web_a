<?php
/**
 * Admin — Consulta de tarifas BARS / RW Web (AM-RAC-BARS-ADMIN-1A).
 * Protegido por sesión admin; no expone credenciales ni conecta al frontend público.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/AdminUserService.php';
require_once __DIR__ . '/../../services/BarsRateClient.php';
require_once __DIR__ . '/../../services/BarsRateCacheService.php';
require_once __DIR__ . '/../../services/RacRateRuleService.php';
require_once __DIR__ . '/../../services/BranchDataService.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

AdminUserService::ensureSchema();
admin_require_login();

if (!admin_can('rac_reservations') && !admin_can('vehicles')) {
    http_response_code(403);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Sin permiso</title></head><body><p>No tiene permiso para consultar tarifas BARS.</p><p><a href="/admin/">Volver al admin</a></p></body></html>';
    exit;
}

/**
 * @return list<array<string, mixed>>
 */
function rac_bars_branches(): array
{
    $branches = [];
    foreach (BranchDataService::getSucursales() as $branch) {
        $code = strtoupper(trim((string) ($branch['code'] ?? '')));
        if ($code === '') {
            continue;
        }
        $branches[] = [
            'code' => $code,
            'name' => (string) ($branch['name'] ?? $code),
        ];
    }
    return $branches;
}

function rac_bars_datetime_local_default(int $daysOffset = 1): string
{
    $dt = new DateTime('now', new DateTimeZone('America/Panama'));
    $dt->modify('+' . $daysOffset . ' days');
    $dt->setTime(10, 0, 0);

    return $dt->format('Y-m-d\TH:i');
}

function rac_bars_to_ota_datetime(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
        return $value . ':00';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $value)) {
        return $value;
    }

    return '';
}

function rac_bars_format_money(?string $amount, string $currency = 'USD'): string
{
    if ($amount === null || $amount === '' || !is_numeric($amount)) {
        return '$0.00';
    }
    return '$' . number_format((float) $amount, 2, '.', ',');
}

function rac_bars_daily_display(?string $amount, string $currency = 'USD'): string
{
    return $currency . ' ' . rac_bars_format_money($amount, $currency) . ' / día';
}

function rac_bars_total_display(?string $amount, int $days, string $currency = 'USD'): string
{
    $label = $days === 1 ? '1 día' : $days . ' días';

    return 'Total ' . $label . ': ' . $currency . ' ' . rac_bars_format_money($amount, $currency);
}

function rac_bars_rental_days(string $pickupOta, string $returnOta): int
{
    if ($pickupOta === '' || $returnOta === '') {
        return 0;
    }
    try {
        $pickup = new DateTime($pickupOta);
        $return = new DateTime($returnOta);
        $days = (int) $pickup->diff($return)->days;

        return max(1, $days);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * @param list<string> $catalogWarnings
 * @return list<string>
 */
function rac_bars_invalid_catalog_codes(array $catalogWarnings): array
{
    $codes = [];
    foreach ($catalogWarnings as $warning) {
        if (preg_match('/Invalid car type ([A-Z0-9]+)/i', (string) $warning, $matches)) {
            $codes[] = strtoupper($matches[1]);
        }
    }

    return array_values(array_unique($codes));
}

function rac_bars_format_range_label(string $pickupLocal, string $returnLocal): string
{
    $pickup = str_replace('T', ' ', $pickupLocal);
    $return = str_replace('T', ' ', $returnLocal);

    return $pickup . ' → ' . $return;
}

function rac_bars_branch_label(string $code, array $branches): string
{
    foreach ($branches as $branch) {
        if (($branch['code'] ?? '') === $code) {
            return $code . ' — ' . ($branch['name'] ?? $code);
        }
    }

    return $code;
}

function rac_bars_db_row_to_vehicle(array $row): array
{
    return [
        'vehicle_code' => (string) ($row['vehicle_code'] ?? ''),
        'vehicle_name' => (string) ($row['vehicle_name'] ?? ''),
        'available' => !empty($row['available']),
        'currency' => (string) ($row['currency'] ?? 'USD'),
        'daily_rate' => (string) ($row['daily_rate'] ?? '0'),
        'total_rate' => (string) ($row['total_rate'] ?? '0'),
        'unit_name' => (string) ($row['unit_name'] ?? 'Day'),
        'raw_status' => (string) ($row['raw_status'] ?? ''),
        'fetched_at' => (string) ($row['fetched_at'] ?? ''),
    ];
}

function rac_bars_merge_calculated(array $baseRow, ?array $calc): array
{
    $vehicle = rac_bars_db_row_to_vehicle($baseRow);
    $vehicle['base_daily_rate'] = (string) ($calc['base_daily_rate'] ?? $baseRow['daily_rate'] ?? '0');
    $vehicle['base_total_rate'] = (string) ($calc['base_total_rate'] ?? $baseRow['total_rate'] ?? '0');
    $vehicle['final_daily_rate'] = (string) ($calc['final_daily_rate'] ?? $baseRow['daily_rate'] ?? '0');
    $vehicle['final_total_rate'] = (string) ($calc['final_total_rate'] ?? $baseRow['total_rate'] ?? '0');
    $vehicle['discount_amount_daily'] = (string) ($calc['discount_amount_daily'] ?? '0');
    $vehicle['applied_rules'] = is_array($calc['applied_rules'] ?? null) ? $calc['applied_rules'] : [];
    $vehicle['has_rules'] = !empty($vehicle['applied_rules']);
    $vehicle['rules_label'] = $vehicle['has_rules']
        ? implode(', ', array_column($vehicle['applied_rules'], 'name'))
        : '—';

    return $vehicle;
}

function rac_bars_ota_to_local(string $ota): string
{
    if ($ota === '') {
        return '';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $ota)) {
        return substr($ota, 0, 16);
    }

    return $ota;
}

/**
 * @param list<string> $warnings
 */
function rac_bars_has_warning175(array $warnings): bool
{
    foreach ($warnings as $warning) {
        if (preg_match('/Code=175\b/i', $warning)) {
            return true;
        }
    }

    return false;
}

/**
 * @param list<string> $warnings
 * @return list<string>
 */
function rac_bars_catalog_warnings(array $warnings): array
{
    return BarsRateClient::extractCatalogWarnings($warnings);
}

/**
 * @param list<string> $warnings
 */
function rac_bars_vehicle_warnings(string $vehicleCode, array $warnings): string
{
    $vehicleCode = strtoupper(trim($vehicleCode));
    $matched = [];
    foreach ($warnings as $warning) {
        if (stripos($warning, $vehicleCode) !== false) {
            $matched[] = $warning;
        }
    }

    return implode(' | ', $matched);
}

/**
 * @param array<string, mixed> $result
 * @return array<string, mixed>
 */
function rac_bars_sanitized_export(array $result): array
{
    $debug = is_array($result['debug'] ?? null) ? $result['debug'] : [];
    unset($debug['inner_request_preview'], $debug['soap_response_preview'], $debug['pc_message_preview']);

    return [
        'ok' => $result['ok'] ?? false,
        'auth_ok' => $result['auth_ok'] ?? false,
        'success' => $result['success'] ?? false,
        'vehicles' => $result['vehicles'] ?? [],
        'warnings' => $result['warnings'] ?? [],
        'catalog_warnings' => rac_bars_catalog_warnings(is_array($result['warnings'] ?? null) ? $result['warnings'] : []),
        'debug' => $debug,
        'error' => $result['error'] ?? null,
    ];
}

$cacheService = new BarsRateCacheService();
$ruleService = new RacRateRuleService();
$branches = rac_bars_branches();
$defaultPickupLocal = rac_bars_datetime_local_default(1);
$defaultReturnLocal = rac_bars_datetime_local_default(4);

$form = [
    'pickup_location' => strtoupper(trim((string) ($_REQUEST['pickup_location'] ?? 'PTY'))),
    'return_location' => strtoupper(trim((string) ($_REQUEST['return_location'] ?? 'PTY'))),
    'pickup_datetime_local' => trim((string) ($_REQUEST['pickup_datetime_local'] ?? $defaultPickupLocal)),
    'return_datetime_local' => trim((string) ($_REQUEST['return_datetime_local'] ?? $defaultReturnLocal)),
    'rate_qualifier' => 'WEB',
];

$successMsg = '';
$queryError = '';
$queryElapsedMs = null;
$consultedAt = null;
$dataSource = 'database';
$queryResult = null;
$cachedSnapshot = null;
$snapshotId = null;
$schedules = $cacheService->getSchedules();
$scheduleForm = [
    'id' => 0,
    'name' => 'PTY 3 días WEB',
    'enabled' => 1,
    'pickup_location' => 'PTY',
    'return_location' => 'PTY',
    'days_ahead' => 1,
    'rental_days' => 3,
    'pickup_time' => '10:00',
    'return_time' => '10:00',
    'rate_qualifier' => 'WEB',
    'scheduled_times' => '06:00, 12:00, 18:00, 23:00',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? 'load_db'));
    if ($action !== 'save_schedule' && $action !== 'run_schedule_now') {
        $form['pickup_location'] = strtoupper(trim((string) ($_POST['pickup_location'] ?? 'PTY')));
        $form['return_location'] = strtoupper(trim((string) ($_POST['return_location'] ?? 'PTY')));
        $form['pickup_datetime_local'] = trim((string) ($_POST['pickup_datetime_local'] ?? ''));
        $form['return_datetime_local'] = trim((string) ($_POST['return_datetime_local'] ?? ''));
    }

    if ($action === 'save_schedule') {
        $scheduleForm = [
            'id' => (int) ($_POST['schedule_id'] ?? 0),
            'name' => trim((string) ($_POST['schedule_name'] ?? 'Programación BARS')),
            'enabled' => !empty($_POST['schedule_enabled']) ? 1 : 0,
            'pickup_location' => strtoupper(trim((string) ($_POST['schedule_pickup_location'] ?? 'PTY'))),
            'return_location' => strtoupper(trim((string) ($_POST['schedule_return_location'] ?? 'PTY'))),
            'days_ahead' => (int) ($_POST['schedule_days_ahead'] ?? 1),
            'rental_days' => (int) ($_POST['schedule_rental_days'] ?? 3),
            'pickup_time' => trim((string) ($_POST['schedule_pickup_time'] ?? '10:00')),
            'return_time' => trim((string) ($_POST['schedule_return_time'] ?? '10:00')),
            'rate_qualifier' => 'WEB',
            'scheduled_times' => trim((string) ($_POST['schedule_times'] ?? '06:00, 12:00, 18:00, 23:00')),
        ];
        $savedId = $cacheService->saveSchedule($scheduleForm);
        $successMsg = 'Programación guardada correctamente (ID ' . $savedId . ').';
        $schedules = $cacheService->getSchedules();
    } elseif ($action === 'run_schedule_now') {
        $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
        $runResult = $cacheService->runSchedule($scheduleId, true);
        $schedules = $cacheService->getSchedules();
        if (!empty($runResult['saved'])) {
            $successMsg = 'Programación ejecutada. Tarifas actualizadas en base de datos.';
        } elseif (($runResult['status'] ?? '') === 'skipped') {
            $queryError = (string) ($runResult['message'] ?? 'Programación omitida.');
        } else {
            $queryError = 'Falló la actualización desde BARS. Se mantienen las últimas tarifas guardadas. '
                . (string) ($runResult['message'] ?? '');
        }
    } else {
        $pickupOta = rac_bars_to_ota_datetime($form['pickup_datetime_local']);
        $returnOta = rac_bars_to_ota_datetime($form['return_datetime_local']);

        if ($form['pickup_location'] === '' || $form['return_location'] === '') {
            $queryError = 'Las sucursales de retiro y devolución son obligatorias.';
        } elseif ($pickupOta === '' || $returnOta === '') {
            $queryError = 'Las fechas y horas deben tener un formato válido.';
        } elseif ($returnOta <= $pickupOta) {
            $queryError = 'La devolución debe ser posterior al retiro.';
        } elseif ($action === 'export_csv') {
            $dbRates = $cacheService->getLatestRates([
                'pickup_location' => $form['pickup_location'],
                'return_location' => $form['return_location'],
                'pickup_datetime' => $pickupOta,
                'return_datetime' => $returnOta,
                'rate_qualifier' => $form['rate_qualifier'],
            ]);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="bars-rates-db-' . date('Y-m-d_His') . '.csv"');
            header('Cache-Control: no-store');
            $out = fopen('php://output', 'w');
            if ($out !== false) {
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['vehicle_code', 'vehicle_name', 'available', 'daily_rate', 'total_rate', 'currency', 'unit_name', 'raw_status', 'fetched_at']);
                foreach ($dbRates as $rate) {
                    fputcsv($out, [
                        $rate['vehicle_code'],
                        $rate['vehicle_name'],
                        !empty($rate['available']) ? '1' : '0',
                        (string) ($rate['daily_rate'] ?? ''),
                        (string) ($rate['total_rate'] ?? ''),
                        (string) ($rate['currency'] ?? 'USD'),
                        (string) ($rate['unit_name'] ?? 'Day'),
                        (string) ($rate['raw_status'] ?? ''),
                        (string) ($rate['fetched_at'] ?? ''),
                    ]);
                }
                fclose($out);
            }
            exit;
        } elseif ($action === 'recalculate_pricing') {
            $recalc = $ruleService->recalculateCacheKey(BarsRateCacheService::buildCacheKey(
                $form['pickup_location'],
                $form['return_location'],
                $pickupOta,
                $returnOta,
                $form['rate_qualifier']
            ));
            $successMsg = 'Tarifas finales recalculadas: ' . (int) ($recalc['calculated'] ?? 0) . ' registros.';
        } elseif ($action === 'refresh_bars') {
            $force = !empty($_POST['force_refresh']);
            $refresh = $cacheService->refreshFromBars([
                'pickup_location' => $form['pickup_location'],
                'return_location' => $form['return_location'],
                'pickup_datetime' => $pickupOta,
                'return_datetime' => $returnOta,
                'rate_qualifier' => $form['rate_qualifier'],
                'veh_classes' => BarsRateClient::DEFAULT_VEH_CLASSES,
            ], 'manual', $force);

            $queryElapsedMs = (int) ($refresh['query_ms'] ?? 0);
            if (!empty($refresh['rate_limited'])) {
                $queryError = (string) $refresh['message'];
            } elseif (!empty($refresh['saved'])) {
                $successMsg = 'Tarifas actualizadas correctamente en base de datos.';
                $snapshotId = (int) ($refresh['snapshot_id'] ?? 0);
                $dataSource = 'database';
            } else {
                $queryError = 'Falló la actualización desde BARS. Se mantienen las últimas tarifas guardadas. '
                    . (string) ($refresh['message'] ?? '');
                $dataSource = 'database';
            }
        } elseif ($action === 'bars_debug') {
            $client = new BarsRateClient();
            if (!$client->isConfigured()) {
                $queryError = 'BARS/RW Web no está configurado en este ambiente.';
            } else {
                $started = microtime(true);
                $queryResult = $client->queryRates([
                    'pickup_location' => $form['pickup_location'],
                    'return_location' => $form['return_location'],
                    'pickup_datetime' => $pickupOta,
                    'return_datetime' => $returnOta,
                    'veh_classes' => BarsRateClient::DEFAULT_VEH_CLASSES,
                    'debug' => false,
                ]);
                $queryElapsedMs = (int) round((microtime(true) - $started) * 1000);
                $consultedAt = (new DateTime('now', new DateTimeZone('America/Panama')))->format('Y-m-d H:i:s T');
                $dataSource = 'bars_live';
            }
        }
    }
}

$pickupOtaDisplay = rac_bars_to_ota_datetime($form['pickup_datetime_local']);
$returnOtaDisplay = rac_bars_to_ota_datetime($form['return_datetime_local']);
$cacheFilters = [
    'pickup_location' => $form['pickup_location'],
    'return_location' => $form['return_location'],
    'pickup_datetime' => $pickupOtaDisplay,
    'return_datetime' => $returnOtaDisplay,
    'rate_qualifier' => $form['rate_qualifier'],
];

if ($dataSource !== 'bars_live' && $dataSource !== 'bars_debug') {
    $cachedSnapshot = $cacheService->getLatestSnapshot($cacheFilters);
    $dbRates = $cacheService->getLatestRates($cacheFilters);
    if ($dbRates !== []) {
        $calcRates = $ruleService->getCalculatedRates(array_merge($cacheFilters, ['cache_key' => BarsRateCacheService::buildCacheKey(
            $form['pickup_location'],
            $form['return_location'],
            $pickupOtaDisplay,
            $returnOtaDisplay,
            $form['rate_qualifier']
        )]));
        if ($calcRates === []) {
            $ruleService->recalculateCacheKey(BarsRateCacheService::buildCacheKey(
                $form['pickup_location'],
                $form['return_location'],
                $pickupOtaDisplay,
                $returnOtaDisplay,
                $form['rate_qualifier']
            ));
            $calcRates = $ruleService->getCalculatedRates($cacheFilters);
        }
        $calcMap = [];
        foreach ($calcRates as $calcRow) {
            $calcMap[strtoupper((string) $calcRow['vehicle_code'])] = $calcRow;
        }
        $vehicles = [];
        foreach ($dbRates as $rate) {
            $code = strtoupper((string) ($rate['vehicle_code'] ?? ''));
            $vehicles[] = rac_bars_merge_calculated($rate, $calcMap[$code] ?? null);
        }
        $dataSource = 'database';
        $consultedAt = (string) ($cachedSnapshot['fetched_at'] ?? ($dbRates[0]['fetched_at'] ?? ''));
        $snapshotId = (int) ($cachedSnapshot['id'] ?? ($dbRates[0]['snapshot_id'] ?? 0));
    } else {
        $vehicles = [];
    }
} else {
    $vehicles = is_array($queryResult['vehicles'] ?? null) ? $queryResult['vehicles'] : [];
}

if ($queryResult !== null && ($dataSource === 'bars_live' || $dataSource === 'bars_debug')) {
    // live/debug path already set vehicles above
} elseif (!isset($vehicles)) {
    $vehicles = [];
}

$warnings = [];
if ($dataSource === 'database' && is_array($cachedSnapshot)) {
    $warnings = is_array($cachedSnapshot['warnings'] ?? null) ? $cachedSnapshot['warnings'] : [];
    $barsHttp = (int) ($cachedSnapshot['http_code'] ?? 0);
    $success = (bool) ($cachedSnapshot['success'] ?? false);
    $hasWarning175 = !empty($cachedSnapshot['warning_175']);
    $totalCount = (int) ($cachedSnapshot['total_count'] ?? count($vehicles));
    $availableCount = (int) ($cachedSnapshot['available_count'] ?? 0);
    $unavailableCount = (int) ($cachedSnapshot['unavailable_count'] ?? 0);
    $minDailyRate = is_numeric($cachedSnapshot['min_daily_rate'] ?? null) ? (float) $cachedSnapshot['min_daily_rate'] : null;
    $maxDailyRate = is_numeric($cachedSnapshot['max_daily_rate'] ?? null) ? (float) $cachedSnapshot['max_daily_rate'] : null;
    $queryElapsedMs = $queryElapsedMs ?? ($cachedSnapshot['query_ms'] ?? null);
} elseif ($queryResult !== null) {
    $warnings = is_array($queryResult['warnings'] ?? null) ? $queryResult['warnings'] : [];
    $debug = is_array($queryResult['debug'] ?? null) ? $queryResult['debug'] : [];
    $barsHttp = (int) ($debug['http_code'] ?? 0);
    $success = (bool) ($queryResult['success'] ?? false);
    $hasWarning175 = rac_bars_has_warning175($warnings);
    $availableCount = 0;
    foreach ($vehicles as $vehicle) {
        if (is_array($vehicle) && !empty($vehicle['available'])) {
            $availableCount++;
        }
    }
    $totalCount = count($vehicles);
    $unavailableCount = $totalCount - $availableCount;
    $stats = BarsRateCacheService::computeVehicleStats($vehicles);
    $minDailyRate = $stats['min_daily_rate'];
    $maxDailyRate = $stats['max_daily_rate'];
} else {
    $barsHttp = 0;
    $success = false;
    $hasWarning175 = false;
    $totalCount = count($vehicles);
    $availableCount = 0;
    foreach ($vehicles as $vehicle) {
        if (is_array($vehicle) && !empty($vehicle['available'])) {
            $availableCount++;
        }
    }
    $unavailableCount = $totalCount - $availableCount;
    $minDailyRate = null;
    $maxDailyRate = null;
}

$catalogWarnings = rac_bars_catalog_warnings($warnings);

$statusLabel = 'Sin datos guardados';
$statusClass = 'secondary';
if ($queryError !== '') {
    $statusLabel = 'Error';
    $statusClass = 'danger';
} elseif ($hasWarning175) {
    $statusLabel = 'Error de autenticación OTA';
    $statusClass = 'danger';
} elseif ($success && $totalCount > 0) {
    $statusLabel = $dataSource === 'database' ? 'Tarifas en BD' : 'Consulta BARS';
    $statusClass = 'success';
} elseif ($success && $totalCount === 0) {
    $statusLabel = 'Sin tarifas';
    $statusClass = 'warning';
} elseif ($totalCount > 0) {
    $statusLabel = 'Datos disponibles';
    $statusClass = 'info';
}

$dataSourceLabel = match ($dataSource) {
    'bars_live' => 'BARS en vivo',
    'bars_debug' => 'BARS en vivo (sin guardar)',
    default => 'Base de datos',
};

$sanitizedJson = $queryResult !== null
    ? json_encode(rac_bars_sanitized_export($queryResult), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    : ($cachedSnapshot !== null ? json_encode([
        'source' => 'database',
        'snapshot_id' => $cachedSnapshot['id'] ?? null,
        'cache_key' => $cachedSnapshot['cache_key'] ?? null,
        'success' => $cachedSnapshot['success'] ?? false,
        'total_count' => $cachedSnapshot['total_count'] ?? 0,
        'warnings' => $cachedSnapshot['warnings'] ?? [],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '');

$pickupOtaDisplay = rac_bars_to_ota_datetime($form['pickup_datetime_local']);
$returnOtaDisplay = rac_bars_to_ota_datetime($form['return_datetime_local']);
$rentalDays = rac_bars_rental_days($pickupOtaDisplay, $returnOtaDisplay);
$invalidCatalogCodes = rac_bars_invalid_catalog_codes($catalogWarnings);
$cacheKey = BarsRateCacheService::buildCacheKey(
    $form['pickup_location'],
    $form['return_location'],
    $pickupOtaDisplay,
    $returnOtaDisplay,
    $form['rate_qualifier']
);

$availableDailyRates = [];
$hasZeroAvailableRates = false;
$operationalAvailableCount = 0;
foreach ($vehicles as $vehicle) {
    if (!is_array($vehicle) || empty($vehicle['available'])) {
        continue;
    }
    $code = strtoupper((string) ($vehicle['vehicle_code'] ?? ''));
    if (in_array($code, $invalidCatalogCodes, true)) {
        continue;
    }
    $operationalAvailableCount++;
    $dailyRate = (float) ($vehicle['final_daily_rate'] ?? $vehicle['daily_rate'] ?? 0);
    if ($dailyRate > 0) {
        $availableDailyRates[] = $dailyRate;
    } elseif ($dailyRate === 0.0) {
        $hasZeroAvailableRates = true;
    }
}

$minDailyRate = $availableDailyRates !== [] ? min($availableDailyRates) : null;
$maxDailyRate = $availableDailyRates !== [] ? max($availableDailyRates) : null;

$defaultAdminTab = 'rac-bars-rates';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Tarifas diarias Rent A Car | Admin Automarket</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --navy: #081026; --navy-light: #162447; --gray-bg: #f8f9fc; --border-color: #e3e6f0; --white: #ffffff; --primary-red: #c51f17; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--gray-bg); color: var(--navy); min-height: 100vh; }
        .admin-sidebar { background-color: var(--navy); color: var(--white); min-height: 100vh; }
        .admin-sidebar .nav-link { color: rgba(255, 255, 255, 0.7); font-weight: 500; border-radius: 8px; margin: 4px 10px; padding: 12px 16px; transition: all 0.2s ease; }
        .admin-sidebar .nav-link:hover, .admin-sidebar .nav-link.active { color: var(--white); background-color: rgba(255, 255, 255, 0.08); }
        .admin-sidebar .nav-link.active { border-left: 4px solid var(--primary-red); border-radius: 0 8px 8px 0; margin-left: 0; }
        .admin-sidebar a.admin-sidebar-page-link,
        .admin-sidebar a.admin-sidebar-page-link:hover,
        .admin-sidebar a.admin-sidebar-page-link:focus { color: rgba(255, 255, 255, 0.7); }
        .admin-sidebar a.admin-sidebar-page-link:hover { color: var(--white); background-color: rgba(255, 255, 255, 0.08); }
        #rentacar-submenu .nav-link { padding-left: 28px; font-size: 0.85rem; }
        .sidebar-heading { font-size: 0.75rem; letter-spacing: 0.5px; color: rgba(255, 255, 255, 0.4); }
        .admin-header { background-color: var(--white); border-bottom: 1px solid var(--border-color); padding: 15px 30px; }
        .admin-card { background-color: var(--white); border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 4px 12px rgba(8, 16, 38, 0.03); margin-bottom: 25px; padding: 25px; }
        .form-label { font-weight: 600; font-size: 0.9rem; color: var(--navy-light); }
        .form-control-premium { border-radius: 8px; padding: 10px 14px; border: 1px solid #d1d5db; }
        .form-control-premium:focus { border-color: var(--primary-red); box-shadow: 0 0 0 3px rgba(197, 31, 23, 0.15); }
        .btn-premium { background-color: var(--primary-red); border-color: var(--primary-red); color: var(--white); font-weight: 600; border-radius: 8px; padding: 10px 24px; }
        .btn-premium:hover { background-color: var(--navy); border-color: var(--navy); color: var(--white); }
        .text-navy { color: var(--navy) !important; }
        .text-navy-light { color: var(--navy-light) !important; }
        .stat-card { border: 1px solid var(--border-color); border-radius: 14px; padding: 1rem 1.1rem; background: #fff; height: 100%; }
        .stat-card .stat-value { font-size: 1.35rem; font-weight: 700; color: var(--navy); line-height: 1.2; }
        .stat-card .stat-label { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; color: #6c757d; font-weight: 600; }
        .daily-rate-main { font-size: 1.05rem; font-weight: 700; color: var(--navy); }
        .total-rate-sub { font-size: 0.82rem; color: #6c757d; }
        .vehicle-row.hidden-by-filter { display: none; }
        pre.json-box { max-height: 240px; overflow: auto; font-size: 0.78rem; background: #0f172a; color: #e2e8f0; border-radius: 12px; padding: 1rem; }
        .table-compact td, .table-compact th { padding-top: 0.65rem; padding-bottom: 0.65rem; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-3 col-md-4 p-0 admin-sidebar d-flex flex-column">
            <div class="p-4 text-center border-bottom border-secondary mb-3">
                <img src="/assets/img/logo.png" alt="Automarket Logo" height="32" style="filter: brightness(0) invert(1);">
                <span class="badge bg-danger mt-2 text-uppercase">Administración</span>
            </div>
            <?php require __DIR__ . '/../../includes/admin-sidebar-nav.php'; ?>
            <div class="mt-auto p-4 border-top border-secondary text-center">
                <p class="small text-white-50 mb-2">Conectado como <strong><?php echo esc(admin_current_username()); ?></strong></p>
                <a href="/admin/logout.php" class="btn btn-sm btn-outline-danger w-100 rounded-pill"><i class="bi bi-box-arrow-left me-1"></i> Cerrar Sesión</a>
            </div>
        </div>

        <div class="col-lg-9 col-md-8 p-0 d-flex flex-column">
            <div class="admin-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold mb-0 text-navy-light">Tarifas diarias Rent A Car</h4>
                    <p class="small text-muted mb-0">Consulta interna de tarifas disponibles desde BARS/RW Web. No modifica el sitio público ni reservas.</p>
                </div>
                <a href="/admin/?tab=rac-reservations" class="btn btn-sm btn-outline-dark rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i> Volver al panel</a>
            </div>

            <div class="p-4 overflow-y-auto" style="max-height: calc(100vh - 73px);">
                <div class="admin-card">
                    <form method="post" class="row g-3" id="bars-query-form">
                        <input type="hidden" name="action" value="load_db" id="bars-form-action">
                        <div class="col-md-3">
                            <label class="form-label" for="pickup_location">Retiro — sucursal</label>
                            <select name="pickup_location" id="pickup_location" class="form-select form-control-premium" required>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo esc($branch['code']); ?>"<?php echo $form['pickup_location'] === $branch['code'] ? ' selected' : ''; ?>>
                                        <?php echo esc($branch['code'] . ' — ' . $branch['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="return_location">Devolución — sucursal</label>
                            <select name="return_location" id="return_location" class="form-select form-control-premium" required>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo esc($branch['code']); ?>"<?php echo $form['return_location'] === $branch['code'] ? ' selected' : ''; ?>>
                                        <?php echo esc($branch['code'] . ' — ' . $branch['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="pickup_datetime_local">Fecha retiro</label>
                            <input type="datetime-local" name="pickup_datetime_local" id="pickup_datetime_local" class="form-control form-control-premium" value="<?php echo esc($form['pickup_datetime_local']); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="return_datetime_local">Fecha devolución</label>
                            <input type="datetime-local" name="return_datetime_local" id="return_datetime_local" class="form-control form-control-premium" value="<?php echo esc($form['return_datetime_local']); ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">RateQualifier</label>
                            <input type="text" class="form-control form-control-premium" value="WEB" readonly>
                        </div>
                        <div class="col-md-10 d-flex align-items-end gap-2 flex-wrap">
                            <button type="submit" class="btn btn-premium" onclick="document.getElementById('bars-form-action').value='refresh_bars';">
                                <i class="bi bi-cloud-download me-1"></i> Actualizar tarifas en BD desde BARS
                            </button>
                            <button type="submit" class="btn btn-outline-dark rounded-pill" onclick="document.getElementById('bars-form-action').value='load_db';">
                                <i class="bi bi-database me-1"></i> Ver tarifas guardadas
                            </button>
                            <button type="submit" class="btn btn-outline-dark rounded-pill" onclick="document.getElementById('bars-form-action').value='recalculate_pricing';">
                                <i class="bi bi-calculator me-1"></i> Recalcular reglas
                            </button>
                            <a href="/admin/rac-rate-rules.php" class="btn btn-outline-secondary rounded-pill">Reglas de tarifas</a>
                            <button type="submit" class="btn btn-outline-secondary rounded-pill" onclick="document.getElementById('bars-form-action').value='bars_debug';">
                                <i class="bi bi-bug me-1"></i> Consultar BARS sin guardar
                            </button>
                            <?php if ($vehicles !== []): ?>
                                <button type="submit" class="btn btn-outline-dark rounded-pill" onclick="document.getElementById('bars-form-action').value='export_csv';">
                                    <i class="bi bi-download me-1"></i> Exportar CSV
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <?php if ($successMsg !== ''): ?>
                    <div class="alert alert-success"><?php echo esc($successMsg); ?></div>
                <?php endif; ?>

                <?php if ($queryError !== ''): ?>
                    <div class="alert alert-danger"><?php echo esc($queryError); ?></div>
                <?php endif; ?>

                <div class="admin-card py-3 px-4 mb-4">
                    <div class="d-flex flex-wrap gap-3 align-items-center small">
                        <span><strong>Origen:</strong> <?php echo esc($dataSourceLabel); ?></span>
                        <span><strong>Última actualización BD:</strong> <?php echo esc($consultedAt !== null && $consultedAt !== '' ? $consultedAt : '—'); ?></span>
                        <?php if ($snapshotId): ?><span><strong>Snapshot:</strong> #<?php echo esc((string) $snapshotId); ?></span><?php endif; ?>
                        <span class="text-muted"><strong>cache_key:</strong> <?php echo esc(substr($cacheKey, 0, 16)); ?>…</span>
                    </div>
                </div>

                <?php if ($vehicles === [] && $queryError === '' && $successMsg === ''): ?>
                    <div class="alert alert-info">No hay tarifas guardadas para esta combinación. Usa «Actualizar tarifas en BD desde BARS».</div>
                <?php endif; ?>

                <?php if ($vehicles !== []): ?>
                    <?php if ($hasWarning175): ?>
                        <div class="alert alert-danger">Credenciales OTA inválidas: revisar RequestorID / MessagePassword.</div>
                    <?php elseif (!empty($queryResult['error']) && $dataSource !== 'database'): ?>
                        <div class="alert alert-danger"><?php echo esc((string) $queryResult['error']); ?></div>
                    <?php endif; ?>

                    <?php if ($catalogWarnings !== []): ?>
                        <div class="alert alert-warning border-0 shadow-sm">
                            <strong><i class="bi bi-exclamation-triangle me-1"></i> Advertencias de catálogo BARS (no bloquean)</strong>
                            <ul class="mb-0 mt-2 small">
                                <?php foreach ($catalogWarnings as $warning): ?>
                                    <li><?php echo esc($warning); ?> — Clase inválida o no reconocida por BARS.</li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ($invalidCatalogCodes !== []): ?>
                                <p class="small mb-0 mt-2 text-muted">Estas clases no aparecen en la vista principal de disponibles: <?php echo esc(implode(', ', $invalidCatalogCodes)); ?>.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success && $operationalAvailableCount === 0 && !$hasWarning175 && $totalCount > 0): ?>
                        <div class="alert alert-warning">BARS respondió correctamente, pero no hay tarifas disponibles para los parámetros seleccionados. Revise el filtro «No disponibles» o ajuste fechas/sucursales.</div>
                    <?php elseif ($success && $totalCount === 0 && !$hasWarning175): ?>
                        <div class="alert alert-warning">BARS respondió correctamente, pero no devolvió tarifas para los parámetros seleccionados.</div>
                    <?php endif; ?>

                    <?php if ($hasZeroAvailableRates): ?>
                        <div class="alert alert-warning py-2 small mb-3">BARS devolvió algunas tarifas en 0.00; revisar configuración de catálogo/tarifa.</div>
                    <?php endif; ?>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4 col-lg-2">
                            <div class="stat-card">
                                <div class="stat-label">Disponibles</div>
                                <div class="stat-value text-success"><?php echo esc((string) $operationalAvailableCount); ?></div>
                            </div>
                        </div>
                        <div class="col-md-4 col-lg-2">
                            <div class="stat-card">
                                <div class="stat-label">No disponibles</div>
                                <div class="stat-value"><?php echo esc((string) $unavailableCount); ?></div>
                            </div>
                        </div>
                        <div class="col-md-4 col-lg-2">
                            <div class="stat-card">
                                <div class="stat-label">Tarifa mínima diaria</div>
                                <div class="stat-value"><?php echo $minDailyRate !== null ? esc('USD ' . rac_bars_format_money((string) $minDailyRate)) : '—'; ?></div>
                            </div>
                        </div>
                        <div class="col-md-4 col-lg-2">
                            <div class="stat-card">
                                <div class="stat-label">Tarifa máxima diaria</div>
                                <div class="stat-value"><?php echo $maxDailyRate !== null ? esc('USD ' . rac_bars_format_money((string) $maxDailyRate)) : '—'; ?></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="stat-card">
                                <div class="stat-label">Sucursales</div>
                                <div class="stat-value" style="font-size:0.95rem;"><?php echo esc($form['pickup_location'] . ' → ' . $form['return_location']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="stat-card">
                                <div class="stat-label">Rango consultado</div>
                                <div class="stat-value" style="font-size:0.82rem;"><?php echo esc(rac_bars_format_range_label($form['pickup_datetime_local'], $form['return_datetime_local'])); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="admin-card">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div>
                                <h2 class="h5 text-navy fw-bold mb-1">Tarifas finales (Automarket)</h2>
                                <p class="small text-muted mb-0">Tarifa final calculada sobre base BARS. La tarifa BARS original no se modifica.</p>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <input type="search" id="bars-table-search" class="form-control form-control-sm" placeholder="Buscar categoría o código" style="min-width: 220px;">
                                <select id="bars-table-filter" class="form-select form-select-sm" style="width: auto;">
                                    <option value="available" selected>Ver disponibles</option>
                                    <option value="unavailable">Ver no disponibles</option>
                                    <option value="with_rule">Con regla aplicada</option>
                                    <option value="without_rule">Sin regla aplicada</option>
                                    <option value="all">Ver todos</option>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-compact mb-0" id="bars-rates-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Unidad / Categoría</th>
                                        <th>Código BARS</th>
                                        <th>Tarifa final diaria</th>
                                        <th>Tarifa BARS diaria</th>
                                        <th>Total final período</th>
                                        <th>Descuento / ajuste</th>
                                        <th>Reglas aplicadas</th>
                                        <th>Estado</th>
                                        <th>Última actualización</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($vehicles === []): ?>
                                    <tr><td colspan="9" class="text-muted">Sin registros para mostrar.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <?php if (!is_array($vehicle)) { continue; } ?>
                                        <?php
                                        $code = strtoupper((string) ($vehicle['vehicle_code'] ?? ''));
                                        $name = (string) ($vehicle['vehicle_name'] ?? '');
                                        $isAvailable = !empty($vehicle['available']);
                                        $isCatalogInvalid = in_array($code, $invalidCatalogCodes, true);
                                        $currency = (string) ($vehicle['currency'] ?? 'USD');
                                        $fetchedAt = (string) ($vehicle['fetched_at'] ?? $consultedAt ?? '');
                                        $baseDaily = (string) ($vehicle['base_daily_rate'] ?? $vehicle['daily_rate'] ?? '0');
                                        $finalDaily = (string) ($vehicle['final_daily_rate'] ?? $vehicle['daily_rate'] ?? '0');
                                        $finalTotal = (string) ($vehicle['final_total_rate'] ?? $vehicle['total_rate'] ?? '0');
                                        $discountDaily = (float) ($vehicle['discount_amount_daily'] ?? 0);
                                        $hasRules = !empty($vehicle['has_rules']);
                                        $rulesLabel = (string) ($vehicle['rules_label'] ?? '—');
                                        $adjustmentLabel = $discountDaily > 0
                                            ? ('−' . $currency . ' ' . rac_bars_format_money((string) $discountDaily))
                                            : '—';
                                        ?>
                                        <tr class="vehicle-row"
                                            data-available="<?php echo $isAvailable ? '1' : '0'; ?>"
                                            data-catalog-invalid="<?php echo $isCatalogInvalid ? '1' : '0'; ?>"
                                            data-has-rules="<?php echo $hasRules ? '1' : '0'; ?>"
                                            data-search="<?php echo esc(strtolower($code . ' ' . $name)); ?>">
                                            <td class="fw-semibold"><?php echo esc($name); ?></td>
                                            <td><code><?php echo esc($code); ?></code></td>
                                            <td><span class="daily-rate-main"><?php echo esc(rac_bars_daily_display($finalDaily, $currency)); ?></span></td>
                                            <td><span class="total-rate-sub"><?php echo esc(rac_bars_daily_display($baseDaily, $currency)); ?></span></td>
                                            <td><span class="total-rate-sub"><?php echo esc(rac_bars_total_display($finalTotal, $rentalDays, $currency)); ?></span></td>
                                            <td class="small"><?php echo esc($adjustmentLabel); ?></td>
                                            <td class="small"><?php echo esc($rulesLabel); ?></td>
                                            <td>
                                                <?php if ($isCatalogInvalid): ?>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis">Catálogo inválido</span>
                                                <?php elseif ($isAvailable): ?>
                                                    <span class="badge bg-success-subtle text-success">Disponible</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary">No disponible</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted"><?php echo esc($fetchedAt !== '' ? $fetchedAt : '—'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="small text-muted mt-3 mb-0" id="bars-filter-empty" style="display:none;">No hay filas que coincidan con el filtro seleccionado.</p>
                    </div>
                <?php endif; ?>

                <div class="admin-card">
                    <h2 class="h5 text-navy fw-bold mb-3">Actualización automática</h2>
                        <form method="post" class="row g-3">
                            <input type="hidden" name="action" value="save_schedule">
                            <input type="hidden" name="schedule_id" value="<?php echo esc((string) $scheduleForm['id']); ?>">
                            <div class="col-md-4">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="schedule_name" class="form-control form-control-premium" value="<?php echo esc($scheduleForm['name']); ?>" required>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="schedule_enabled" id="schedule_enabled" value="1"<?php echo !empty($scheduleForm['enabled']) ? ' checked' : ''; ?>>
                                    <label class="form-check-label" for="schedule_enabled">Activa</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Retiro — sucursal</label>
                                <select name="schedule_pickup_location" class="form-select form-control-premium">
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo esc($branch['code']); ?>"<?php echo ($scheduleForm['pickup_location'] ?? 'PTY') === $branch['code'] ? ' selected' : ''; ?>><?php echo esc($branch['code']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Devolución — sucursal</label>
                                <select name="schedule_return_location" class="form-select form-control-premium">
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo esc($branch['code']); ?>"<?php echo ($scheduleForm['return_location'] ?? 'PTY') === $branch['code'] ? ' selected' : ''; ?>><?php echo esc($branch['code']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Días hacia adelante</label>
                                <input type="number" min="0" name="schedule_days_ahead" class="form-control form-control-premium" value="<?php echo esc((string) ($scheduleForm['days_ahead'] ?? 1)); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Duración (días)</label>
                                <input type="number" min="1" name="schedule_rental_days" class="form-control form-control-premium" value="<?php echo esc((string) ($scheduleForm['rental_days'] ?? 3)); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Hora retiro</label>
                                <input type="time" name="schedule_pickup_time" class="form-control form-control-premium" value="<?php echo esc((string) ($scheduleForm['pickup_time'] ?? '10:00')); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Hora devolución</label>
                                <input type="time" name="schedule_return_time" class="form-control form-control-premium" value="<?php echo esc((string) ($scheduleForm['return_time'] ?? '10:00')); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">RateQualifier</label>
                                <input type="text" class="form-control form-control-premium" value="WEB" readonly>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Horas de actualización (HH:MM, separadas por coma)</label>
                                <input type="text" name="schedule_times" class="form-control form-control-premium" value="<?php echo esc((string) ($scheduleForm['scheduled_times'] ?? '06:00, 12:00, 18:00, 23:00')); ?>">
                            </div>
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-outline-dark rounded-pill"><i class="bi bi-save me-1"></i> Guardar programación</button>
                            </div>
                        </form>
                        <?php if ($schedules !== []): ?>
                            <div class="table-responsive mt-4">
                                <table class="table table-sm align-middle">
                                    <thead><tr><th>ID</th><th>Nombre</th><th>Estado</th><th>Próxima</th><th>Última</th><th></th></tr></thead>
                                    <tbody>
                                    <?php foreach ($schedules as $schedule): ?>
                                        <tr>
                                            <td><?php echo esc((string) ($schedule['id'] ?? '')); ?></td>
                                            <td><?php echo esc((string) ($schedule['name'] ?? '')); ?></td>
                                            <td><?php echo !empty($schedule['enabled']) ? 'Activa' : 'Inactiva'; ?> — <?php echo esc((string) ($schedule['last_status'] ?? '—')); ?></td>
                                            <td class="small"><?php echo esc((string) ($schedule['next_run_at'] ?? '—')); ?></td>
                                            <td class="small"><?php echo esc((string) ($schedule['last_run_at'] ?? '—')); ?></td>
                                            <td>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="run_schedule_now">
                                                    <input type="hidden" name="schedule_id" value="<?php echo esc((string) ($schedule['id'] ?? '')); ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Ejecutar ahora</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <p class="small text-muted mb-0 mt-3">Cron sugerido: <code>*/15 * * * * cd /home/am_web_a && php app/cron/rac-bars-rates-refresh.php --due >> app/storage/logs/rac-bars-rates-refresh.log 2>&1</code></p>
                </div>

                <?php if ($vehicles !== [] || $cachedSnapshot !== null || $queryResult !== null): ?>
                    <div class="accordion mb-4" id="barsTechnicalAccordion">
                        <div class="accordion-item border-0 shadow-sm rounded-4 overflow-hidden">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#barsTechnicalPanel">
                                    Detalle técnico BARS
                                </button>
                            </h2>
                            <div id="barsTechnicalPanel" class="accordion-collapse collapse" data-bs-parent="#barsTechnicalAccordion">
                                <div class="accordion-body">
                                    <div class="row g-3 small mb-3">
                                        <div class="col-md-3"><strong>HTTP BARS:</strong> <?php echo esc((string) $barsHttp); ?></div>
                                        <div class="col-md-3"><strong>Success:</strong> <?php echo $success ? 'Sí' : 'No'; ?></div>
                                        <div class="col-md-3"><strong>Warning 175:</strong> <?php echo $hasWarning175 ? 'Sí' : 'No'; ?></div>
                                        <div class="col-md-3"><strong>Tiempo:</strong> <?php echo esc((string) ($queryElapsedMs ?? '—')); ?> ms</div>
                                        <div class="col-md-3"><strong>Total BARS:</strong> <?php echo esc((string) $totalCount); ?></div>
                                        <div class="col-md-3"><strong>Consultado:</strong> <?php echo esc((string) ($consultedAt ?? '—')); ?></div>
                                        <div class="col-md-3"><strong>RateQualifier:</strong> WEB</div>
                                        <div class="col-md-3"><strong>Estado:</strong> <?php echo esc($statusLabel); ?></div>
                                    </div>
                                    <?php if ($sanitizedJson !== ''): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-semibold small">JSON sanitizado</span>
                                            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill" id="copy-json-btn"><i class="bi bi-clipboard me-1"></i> Copiar JSON</button>
                                        </div>
                                        <pre class="json-box mb-0" id="bars-json-box"><?php echo esc($sanitizedJson); ?></pre>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const filter = document.getElementById('bars-table-filter');
    const search = document.getElementById('bars-table-search');
    const rows = document.querySelectorAll('#bars-rates-table .vehicle-row');
    const emptyMsg = document.getElementById('bars-filter-empty');

    function applyFilters() {
        const mode = filter ? filter.value : 'available';
        const term = search ? search.value.trim().toLowerCase() : '';
        let visibleCount = 0;
        rows.forEach(function (row) {
            const available = row.getAttribute('data-available') === '1';
            const catalogInvalid = row.getAttribute('data-catalog-invalid') === '1';
            const hasRules = row.getAttribute('data-has-rules') === '1';
            const haystack = row.getAttribute('data-search') || '';
            let visible = true;
            if (mode === 'available' && (!available || catalogInvalid)) visible = false;
            if (mode === 'unavailable' && (available || catalogInvalid)) visible = false;
            if (mode === 'with_rule' && (!hasRules || catalogInvalid)) visible = false;
            if (mode === 'without_rule' && (hasRules || catalogInvalid)) visible = false;
            if (mode === 'all' && catalogInvalid) visible = false;
            if (term !== '' && haystack.indexOf(term) === -1) visible = false;
            row.classList.toggle('hidden-by-filter', !visible);
            if (visible) visibleCount++;
        });
        if (emptyMsg) {
            emptyMsg.style.display = rows.length > 0 && visibleCount === 0 ? 'block' : 'none';
        }
    }

    if (filter) filter.addEventListener('change', applyFilters);
    if (search) search.addEventListener('input', applyFilters);
    applyFilters();

    const copyBtn = document.getElementById('copy-json-btn');
    const jsonBox = document.getElementById('bars-json-box');
    if (copyBtn && jsonBox) {
        copyBtn.addEventListener('click', function () {
            navigator.clipboard.writeText(jsonBox.textContent || '').then(function () {
                copyBtn.textContent = 'Copiado';
                setTimeout(function () { copyBtn.innerHTML = '<i class="bi bi-clipboard me-1"></i> Copiar JSON'; }, 1500);
            });
        });
    }
})();
</script>
</body>
</html>
