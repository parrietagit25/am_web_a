<?php
/**
 * Persistencia y caché de tarifas BARS/RW Web.
 * AM-RAC-BARS-CACHE-2A
 */

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/RacBarsDatabaseSchema.php';
require_once __DIR__ . '/BarsRateClient.php';

class BarsRateCacheService
{
    public const MIN_MANUAL_REFRESH_SECONDS = 60;

    private const TZ = 'America/Panama';

    public function __construct()
    {
        RacBarsDatabaseSchema::ensure();
    }

    public static function buildCacheKey(
        string $pickupLocation,
        string $returnLocation,
        string $pickupDateTime,
        string $returnDateTime,
        string $rateQualifier = 'WEB'
    ): string {
        $parts = [
            strtoupper(trim($pickupLocation)),
            strtoupper(trim($returnLocation)),
            self::normalizeOtaDatetime($pickupDateTime),
            self::normalizeOtaDatetime($returnDateTime),
            strtoupper(trim($rateQualifier)),
        ];

        return hash('sha256', implode('|', $parts));
    }

    public static function normalizeOtaDatetime(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
            return $value . ':00';
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $barsResult
     * @return array<string, mixed>
     */
    public function saveBarsResponse(array $params, array $barsResult, string $source = 'manual', ?int $queryMs = null): array
    {
        $pickupLocation = strtoupper(trim((string) ($params['pickup_location'] ?? 'PTY')));
        $returnLocation = strtoupper(trim((string) ($params['return_location'] ?? $pickupLocation)));
        $pickupDatetime = self::normalizeOtaDatetime((string) ($params['pickup_datetime'] ?? ''));
        $returnDatetime = self::normalizeOtaDatetime((string) ($params['return_datetime'] ?? ''));
        $rateQualifier = strtoupper(trim((string) ($params['rate_qualifier'] ?? 'WEB')));
        $cacheKey = self::buildCacheKey($pickupLocation, $returnLocation, $pickupDatetime, $returnDatetime, $rateQualifier);

        $warnings = is_array($barsResult['warnings'] ?? null) ? $barsResult['warnings'] : [];
        $vehicles = is_array($barsResult['vehicles'] ?? null) ? $barsResult['vehicles'] : [];
        $debug = is_array($barsResult['debug'] ?? null) ? $barsResult['debug'] : [];
        $httpCode = (int) ($debug['http_code'] ?? 0);
        $success = (bool) ($barsResult['success'] ?? false);
        $warning175 = self::hasWarning175($warnings);

        if ($warning175 || (!$success && !($barsResult['ok'] ?? false))) {
            return [
                'saved' => false,
                'reason' => $warning175 ? 'auth_ota' : 'bars_error',
                'message' => $warning175
                    ? 'Credenciales OTA inválidas: revisar RequestorID / MessagePassword.'
                    : (string) ($barsResult['error'] ?? 'Error al consultar BARS.'),
                'cache_key' => $cacheKey,
            ];
        }

        $stats = self::computeVehicleStats($vehicles);
        $fetchedAt = self::nowString();
        $requestedClasses = $params['veh_classes'] ?? BarsRateClient::DEFAULT_VEH_CLASSES;

        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO rac_bars_rate_snapshots (
                cache_key, pickup_location, return_location, pickup_datetime, return_datetime, rate_qualifier,
                http_code, success, warning_175, total_count, available_count, unavailable_count,
                min_daily_rate, max_daily_rate, query_ms, warnings_json, requested_classes_json, source, fetched_at
            ) VALUES (
                :cache_key, :pickup_location, :return_location, :pickup_datetime, :return_datetime, :rate_qualifier,
                :http_code, :success, :warning_175, :total_count, :available_count, :unavailable_count,
                :min_daily_rate, :max_daily_rate, :query_ms, :warnings_json, :requested_classes_json, :source, :fetched_at
            )',
            [
                ':cache_key' => $cacheKey,
                ':pickup_location' => $pickupLocation,
                ':return_location' => $returnLocation,
                ':pickup_datetime' => $pickupDatetime,
                ':return_datetime' => $returnDatetime,
                ':rate_qualifier' => $rateQualifier,
                ':http_code' => $httpCode,
                ':success' => $success ? 1 : 0,
                ':warning_175' => $warning175 ? 1 : 0,
                ':total_count' => $stats['total_count'],
                ':available_count' => $stats['available_count'],
                ':unavailable_count' => $stats['unavailable_count'],
                ':min_daily_rate' => $stats['min_daily_rate'],
                ':max_daily_rate' => $stats['max_daily_rate'],
                ':query_ms' => $queryMs,
                ':warnings_json' => json_encode($warnings, JSON_UNESCAPED_UNICODE),
                ':requested_classes_json' => json_encode($requestedClasses, JSON_UNESCAPED_UNICODE),
                ':source' => $source,
                ':fetched_at' => $fetchedAt,
            ]
        );

        $snapshotId = (int) $db->lastInsertId();
        $upserted = 0;

        foreach ($vehicles as $vehicle) {
            if (!is_array($vehicle)) {
                continue;
            }
            $code = strtoupper(trim((string) ($vehicle['vehicle_code'] ?? '')));
            if ($code === '') {
                continue;
            }
            $vehWarnings = self::vehicleWarnings($code, $warnings);
            $this->upsertRateRow(
                $cacheKey,
                $snapshotId,
                $vehicle,
                $vehWarnings,
                $pickupLocation,
                $returnLocation,
                $pickupDatetime,
                $returnDatetime,
                $rateQualifier,
                $fetchedAt
            );
            $upserted++;
        }

        $pricingResult = $this->recalculatePricing($cacheKey);

        return [
            'saved' => true,
            'snapshot_id' => $snapshotId,
            'cache_key' => $cacheKey,
            'upserted' => $upserted,
            'total_count' => $stats['total_count'],
            'available_count' => $stats['available_count'],
            'unavailable_count' => $stats['unavailable_count'],
            'min_daily_rate' => $stats['min_daily_rate'],
            'max_daily_rate' => $stats['max_daily_rate'],
            'fetched_at' => $fetchedAt,
            'http_code' => $httpCode,
            'success' => $success,
            'warning_175' => $warning175,
            'warnings' => $warnings,
            'pricing_recalculated' => $pricingResult,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function recalculatePricing(string $cacheKey): array
    {
        try {
            require_once __DIR__ . '/RacRateRuleService.php';
            $ruleService = new RacRateRuleService();

            return $ruleService->recalculateCacheKey($cacheKey);
        } catch (Throwable $e) {
            if (function_exists('am_log')) {
                am_log('RAC pricing recalculate failed for ' . $cacheKey . ': ' . $e->getMessage(), 'ERROR');
            }

            return ['ok' => false, 'cache_key' => $cacheKey, 'calculated' => 0, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function refreshFromBars(array $params, string $source = 'manual', bool $force = false): array
    {
        $pickupDatetime = self::normalizeOtaDatetime((string) ($params['pickup_datetime'] ?? ''));
        $returnDatetime = self::normalizeOtaDatetime((string) ($params['return_datetime'] ?? ''));
        $rateQualifier = strtoupper(trim((string) ($params['rate_qualifier'] ?? 'WEB')));
        $cacheKey = self::buildCacheKey(
            (string) ($params['pickup_location'] ?? 'PTY'),
            (string) ($params['return_location'] ?? 'PTY'),
            $pickupDatetime,
            $returnDatetime,
            $rateQualifier
        );

        if (!$force && !$this->canRefreshManually($cacheKey, false)) {
            return [
                'ok' => false,
                'rate_limited' => true,
                'message' => 'Espere al menos ' . self::MIN_MANUAL_REFRESH_SECONDS . ' segundos entre actualizaciones manuales.',
                'cache_key' => $cacheKey,
            ];
        }

        $client = new BarsRateClient();
        if (!$client->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'BARS/RW Web no está configurado en este ambiente.',
                'cache_key' => $cacheKey,
            ];
        }

        $started = microtime(true);
        $barsResult = $client->queryRates([
            'pickup_location' => $params['pickup_location'] ?? 'PTY',
            'return_location' => $params['return_location'] ?? 'PTY',
            'pickup_datetime' => $pickupDatetime,
            'return_datetime' => $returnDatetime,
            'veh_classes' => $params['veh_classes'] ?? BarsRateClient::DEFAULT_VEH_CLASSES,
            'debug' => false,
        ]);
        $queryMs = (int) round((microtime(true) - $started) * 1000);

        $saveResult = $this->saveBarsResponse(array_merge($params, [
            'pickup_datetime' => $pickupDatetime,
            'return_datetime' => $returnDatetime,
            'rate_qualifier' => $rateQualifier,
        ]), $barsResult, $source, $queryMs);

        return array_merge([
            'ok' => (bool) ($saveResult['saved'] ?? false),
            'bars_result' => $barsResult,
            'query_ms' => $queryMs,
        ], $saveResult);
    }

    public function canRefreshManually(string $cacheKey, bool $force): bool
    {
        if ($force) {
            return true;
        }
        $snapshot = $this->getLatestSnapshot(['cache_key' => $cacheKey]);
        if ($snapshot === null) {
            return true;
        }
        $fetchedAt = strtotime((string) ($snapshot['fetched_at'] ?? ''));
        if ($fetchedAt === false) {
            return true;
        }

        return (time() - $fetchedAt) >= self::MIN_MANUAL_REFRESH_SECONDS;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>|null
     */
    public function getLatestSnapshot(array $filters): ?array
    {
        $cacheKey = $this->resolveCacheKey($filters);
        if ($cacheKey === null) {
            return null;
        }

        $db = Database::getInstance();
        $row = $db->selectOne(
            'SELECT * FROM rac_bars_rate_snapshots WHERE cache_key = :cache_key ORDER BY fetched_at DESC, id DESC LIMIT 1',
            [':cache_key' => $cacheKey]
        );

        return is_array($row) ? $this->hydrateSnapshotRow($row) : null;
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function getLatestRates(array $filters): array
    {
        $cacheKey = $this->resolveCacheKey($filters);
        if ($cacheKey === null) {
            return [];
        }

        $db = Database::getInstance();
        $rows = $db->select(
            'SELECT * FROM rac_bars_rates WHERE cache_key = :cache_key ORDER BY available DESC, vehicle_name ASC, vehicle_code ASC',
            [':cache_key' => $cacheKey]
        );

        return array_map([$this, 'hydrateRateRow'], $rows);
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function getAvailableRates(array $filters): array
    {
        $rates = $this->getLatestRates($filters);

        return array_values(array_filter($rates, static fn(array $row): bool => !empty($row['available'])));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getSchedules(): array
    {
        $db = Database::getInstance();
        $rows = $db->select('SELECT * FROM rac_bars_rate_refresh_schedules ORDER BY name ASC, id ASC');

        return array_map([$this, 'hydrateScheduleRow'], $rows);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSchedule(int $scheduleId): ?array
    {
        $db = Database::getInstance();
        $row = $db->selectOne('SELECT * FROM rac_bars_rate_refresh_schedules WHERE id = :id', [':id' => $scheduleId]);

        return is_array($row) ? $this->hydrateScheduleRow($row) : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveSchedule(array $data): int
    {
        $id = (int) ($data['id'] ?? 0);
        $scheduledTimes = self::normalizeScheduledTimes($data['scheduled_times'] ?? $data['scheduled_times_json'] ?? []);
        $payload = [
            ':name' => trim((string) ($data['name'] ?? 'Programación BARS')),
            ':enabled' => !empty($data['enabled']) ? 1 : 0,
            ':pickup_location' => strtoupper(trim((string) ($data['pickup_location'] ?? 'PTY'))),
            ':return_location' => strtoupper(trim((string) ($data['return_location'] ?? 'PTY'))),
            ':days_ahead' => max(0, (int) ($data['days_ahead'] ?? 1)),
            ':rental_days' => max(1, (int) ($data['rental_days'] ?? 3)),
            ':pickup_time' => self::normalizeTime((string) ($data['pickup_time'] ?? '10:00')),
            ':return_time' => self::normalizeTime((string) ($data['return_time'] ?? '10:00')),
            ':rate_qualifier' => strtoupper(trim((string) ($data['rate_qualifier'] ?? 'WEB'))),
            ':scheduled_times_json' => json_encode($scheduledTimes, JSON_UNESCAPED_UNICODE),
            ':updated_at' => self::nowString(),
        ];

        $db = Database::getInstance();
        if ($id > 0) {
            $db->execute(
                'UPDATE rac_bars_rate_refresh_schedules SET
                    name = :name, enabled = :enabled, pickup_location = :pickup_location, return_location = :return_location,
                    days_ahead = :days_ahead, rental_days = :rental_days, pickup_time = :pickup_time, return_time = :return_time,
                    rate_qualifier = :rate_qualifier, scheduled_times_json = :scheduled_times_json, updated_at = :updated_at
                 WHERE id = :id',
                array_merge($payload, [':id' => $id])
            );
        } else {
            $db->execute(
                'INSERT INTO rac_bars_rate_refresh_schedules (
                    name, enabled, pickup_location, return_location, days_ahead, rental_days,
                    pickup_time, return_time, rate_qualifier, scheduled_times_json, updated_at
                ) VALUES (
                    :name, :enabled, :pickup_location, :return_location, :days_ahead, :rental_days,
                    :pickup_time, :return_time, :rate_qualifier, :scheduled_times_json, :updated_at
                )',
                $payload
            );
            $id = (int) $db->lastInsertId();
        }

        $schedule = $this->getSchedule($id);
        if ($schedule !== null) {
            $nextRunAt = $this->computeNextRunAt($schedule);
            $db->execute(
                'UPDATE rac_bars_rate_refresh_schedules SET next_run_at = :next_run_at WHERE id = :id',
                [':next_run_at' => $nextRunAt, ':id' => $id]
            );
        }

        return $id;
    }

    /**
     * @return array<string, mixed>
     */
    public function runSchedule(int $scheduleId, bool $force = false): array
    {
        $schedule = $this->getSchedule($scheduleId);
        if ($schedule === null) {
            return ['ok' => false, 'message' => 'Programación no encontrada.', 'schedule_id' => $scheduleId];
        }
        if (empty($schedule['enabled']) && !$force) {
            return ['ok' => false, 'message' => 'Programación desactivada.', 'schedule_id' => $scheduleId];
        }

        $startedAt = self::nowString();
        $runId = $this->startRefreshRun($scheduleId, $force ? 'manual' : 'scheduled', $startedAt);
        $datetimes = $this->computeScheduleDatetimes($schedule);
        $params = [
            'pickup_location' => $schedule['pickup_location'],
            'return_location' => $schedule['return_location'],
            'pickup_datetime' => $datetimes['pickup_datetime'],
            'return_datetime' => $datetimes['return_datetime'],
            'rate_qualifier' => $schedule['rate_qualifier'],
            'veh_classes' => BarsRateClient::DEFAULT_VEH_CLASSES,
        ];

        $refresh = $this->refreshFromBars($params, 'scheduled', $force);
        $finishedAt = self::nowString();
        $status = 'error';
        $message = (string) ($refresh['message'] ?? 'Error desconocido');

        if (!empty($refresh['rate_limited'])) {
            $status = 'skipped';
            $message = (string) $refresh['message'];
        } elseif (!empty($refresh['saved'])) {
            $status = ((int) ($refresh['total_count'] ?? 0)) > 0 ? 'success' : 'no_rates';
            $message = $status === 'success'
                ? 'Tarifas actualizadas correctamente.'
                : 'BARS respondió sin tarifas.';
        } elseif (($refresh['reason'] ?? '') === 'auth_ota') {
            $status = 'auth_ota';
        }

        $this->finishRefreshRun($runId, [
            'finished_at' => $finishedAt,
            'status' => $status,
            'message' => $message,
            'http_code' => (int) ($refresh['http_code'] ?? ($refresh['bars_result']['debug']['http_code'] ?? 0)),
            'warning_175' => !empty($refresh['warning_175']) ? 1 : 0,
            'total_count' => (int) ($refresh['total_count'] ?? 0),
            'available_count' => (int) ($refresh['available_count'] ?? 0),
            'unavailable_count' => (int) ($refresh['unavailable_count'] ?? 0),
            'snapshot_id' => $refresh['snapshot_id'] ?? null,
        ]);

        $db = Database::getInstance();
        $nextRunAt = $this->computeNextRunAt($schedule);
        $db->execute(
            'UPDATE rac_bars_rate_refresh_schedules SET last_run_at = :last_run_at, next_run_at = :next_run_at, last_status = :last_status, last_message = :last_message, updated_at = :updated_at WHERE id = :id',
            [
                ':last_run_at' => $finishedAt,
                ':next_run_at' => $nextRunAt,
                ':last_status' => $status,
                ':last_message' => $message,
                ':updated_at' => $finishedAt,
                ':id' => $scheduleId,
            ]
        );

        return array_merge([
            'ok' => in_array($status, ['success', 'no_rates'], true),
            'schedule_id' => $scheduleId,
            'run_id' => $runId,
            'status' => $status,
            'message' => $message,
        ], $refresh);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function runDueSchedules(bool $forceAll = false): array
    {
        $db = Database::getInstance();
        $now = self::nowString();
        if ($forceAll) {
            $rows = $db->select('SELECT id FROM rac_bars_rate_refresh_schedules WHERE enabled = 1 ORDER BY id ASC');
        } else {
            $rows = $db->select(
                'SELECT id FROM rac_bars_rate_refresh_schedules WHERE enabled = 1 AND (next_run_at IS NULL OR next_run_at <= :now) ORDER BY id ASC',
                [':now' => $now]
            );
        }

        $results = [];
        foreach ($rows as $row) {
            $results[] = $this->runSchedule((int) ($row['id'] ?? 0), $forceAll);
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $schedule
     * @return array{pickup_datetime: string, return_datetime: string}
     */
    public function computeScheduleDatetimes(array $schedule): array
    {
        $tz = new DateTimeZone(self::TZ);
        $pickup = new DateTime('now', $tz);
        $pickup->modify('+' . max(0, (int) ($schedule['days_ahead'] ?? 1)) . ' days');
        self::applyTimeToDate($pickup, (string) ($schedule['pickup_time'] ?? '10:00'));

        $return = clone $pickup;
        $return->modify('+' . max(1, (int) ($schedule['rental_days'] ?? 3)) . ' days');
        self::applyTimeToDate($return, (string) ($schedule['return_time'] ?? '10:00'));

        return [
            'pickup_datetime' => $pickup->format('Y-m-d\TH:i:s'),
            'return_datetime' => $return->format('Y-m-d\TH:i:s'),
        ];
    }

    /**
     * @param array<string, mixed> $schedule
     */
    public function computeNextRunAt(array $schedule, ?DateTimeInterface $after = null): ?string
    {
        if (empty($schedule['enabled'])) {
            return null;
        }

        $times = $schedule['scheduled_times'] ?? [];
        if ($times === []) {
            return null;
        }

        $tz = new DateTimeZone(self::TZ);
        $base = $after !== null
            ? DateTimeImmutable::createFromInterface($after)->setTimezone($tz)
            : new DateTimeImmutable('now', $tz);

        for ($dayOffset = 0; $dayOffset <= 1; $dayOffset++) {
            foreach ($times as $time) {
                $candidate = DateTimeImmutable::createFromFormat('Y-m-d H:i', $base->format('Y-m-d') . ' ' . $time, $tz);
                if ($candidate === false) {
                    continue;
                }
                if ($dayOffset > 0) {
                    $candidate = $candidate->modify('+' . $dayOffset . ' day');
                }
                if ($candidate <= $base) {
                    continue;
                }
                if ($this->scheduleSlotAlreadyRanToday($schedule, $candidate)) {
                    continue;
                }

                return $candidate->format('Y-m-d H:i:s');
            }
        }

        $firstTime = $times[0];
        $tomorrow = $base->modify('+1 day')->format('Y-m-d');

        return $tomorrow . ' ' . $firstTime . ':00';
    }

    /**
     * @param list<string> $warnings
     */
    public static function hasWarning175(array $warnings): bool
    {
        foreach ($warnings as $warning) {
            if (preg_match('/Code=175\b/i', (string) $warning)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array<string, mixed>> $vehicles
     * @return array{total_count: int, available_count: int, unavailable_count: int, min_daily_rate: ?float, max_daily_rate: ?float}
     */
    public static function computeVehicleStats(array $vehicles): array
    {
        $availableCount = 0;
        $rates = [];
        foreach ($vehicles as $vehicle) {
            if (!is_array($vehicle)) {
                continue;
            }
            if (!empty($vehicle['available'])) {
                $availableCount++;
                $daily = (float) ($vehicle['daily_rate'] ?? 0);
                if ($daily > 0) {
                    $rates[] = $daily;
                }
            }
        }
        $total = count(array_filter($vehicles, 'is_array'));

        return [
            'total_count' => $total,
            'available_count' => $availableCount,
            'unavailable_count' => $total - $availableCount,
            'min_daily_rate' => $rates !== [] ? min($rates) : null,
            'max_daily_rate' => $rates !== [] ? max($rates) : null,
        ];
    }

    /**
     * @param array<string, mixed> $vehicle
     * @param list<string> $warnings
     */
    public static function sanitizeVehicleRaw(array $vehicle, array $warnings = []): array
    {
        unset($vehicle['raw']);

        return [
            'vehicle_code' => $vehicle['vehicle_code'] ?? '',
            'vehicle_name' => $vehicle['vehicle_name'] ?? '',
            'available' => !empty($vehicle['available']),
            'currency' => $vehicle['currency'] ?? 'USD',
            'daily_rate' => $vehicle['daily_rate'] ?? null,
            'total_rate' => $vehicle['total_rate'] ?? null,
            'unit_name' => $vehicle['unit_name'] ?? 'Day',
            'raw_status' => $vehicle['raw_status'] ?? '',
            'warnings' => self::vehicleWarnings((string) ($vehicle['vehicle_code'] ?? ''), $warnings),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function hydrateRateRow(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'snapshot_id' => isset($row['snapshot_id']) ? (int) $row['snapshot_id'] : null,
            'vehicle_code' => (string) ($row['vehicle_code'] ?? ''),
            'vehicle_name' => (string) ($row['vehicle_name'] ?? ''),
            'available' => !empty($row['available']),
            'currency' => (string) ($row['currency'] ?? 'USD'),
            'daily_rate' => $row['daily_rate'],
            'total_rate' => $row['total_rate'],
            'unit_name' => (string) ($row['unit_name'] ?? 'Day'),
            'raw_status' => (string) ($row['raw_status'] ?? ''),
            'warnings' => json_decode((string) ($row['warnings_json'] ?? '[]'), true) ?: [],
            'fetched_at' => (string) ($row['fetched_at'] ?? ''),
            'pickup_location' => (string) ($row['pickup_location'] ?? ''),
            'return_location' => (string) ($row['return_location'] ?? ''),
            'pickup_datetime' => (string) ($row['pickup_datetime'] ?? ''),
            'return_datetime' => (string) ($row['return_datetime'] ?? ''),
            'rate_qualifier' => (string) ($row['rate_qualifier'] ?? 'WEB'),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function resolveCacheKey(array $filters): ?string
    {
        if (!empty($filters['cache_key'])) {
            return (string) $filters['cache_key'];
        }

        $pickup = self::normalizeOtaDatetime((string) ($filters['pickup_datetime'] ?? ''));
        $return = self::normalizeOtaDatetime((string) ($filters['return_datetime'] ?? ''));
        if ($pickup === '' || $return === '') {
            return null;
        }

        return self::buildCacheKey(
            (string) ($filters['pickup_location'] ?? 'PTY'),
            (string) ($filters['return_location'] ?? 'PTY'),
            $pickup,
            $return,
            (string) ($filters['rate_qualifier'] ?? 'WEB')
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateSnapshotRow(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'cache_key' => (string) ($row['cache_key'] ?? ''),
            'pickup_location' => (string) ($row['pickup_location'] ?? ''),
            'return_location' => (string) ($row['return_location'] ?? ''),
            'pickup_datetime' => (string) ($row['pickup_datetime'] ?? ''),
            'return_datetime' => (string) ($row['return_datetime'] ?? ''),
            'rate_qualifier' => (string) ($row['rate_qualifier'] ?? 'WEB'),
            'http_code' => (int) ($row['http_code'] ?? 0),
            'success' => !empty($row['success']),
            'warning_175' => !empty($row['warning_175']),
            'total_count' => (int) ($row['total_count'] ?? 0),
            'available_count' => (int) ($row['available_count'] ?? 0),
            'unavailable_count' => (int) ($row['unavailable_count'] ?? 0),
            'min_daily_rate' => $row['min_daily_rate'],
            'max_daily_rate' => $row['max_daily_rate'],
            'query_ms' => isset($row['query_ms']) ? (int) $row['query_ms'] : null,
            'warnings' => json_decode((string) ($row['warnings_json'] ?? '[]'), true) ?: [],
            'source' => (string) ($row['source'] ?? 'manual'),
            'fetched_at' => (string) ($row['fetched_at'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateScheduleRow(array $row): array
    {
        $times = json_decode((string) ($row['scheduled_times_json'] ?? '[]'), true);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'enabled' => !empty($row['enabled']),
            'pickup_location' => (string) ($row['pickup_location'] ?? 'PTY'),
            'return_location' => (string) ($row['return_location'] ?? 'PTY'),
            'days_ahead' => (int) ($row['days_ahead'] ?? 1),
            'rental_days' => (int) ($row['rental_days'] ?? 3),
            'pickup_time' => (string) ($row['pickup_time'] ?? '10:00'),
            'return_time' => (string) ($row['return_time'] ?? '10:00'),
            'rate_qualifier' => (string) ($row['rate_qualifier'] ?? 'WEB'),
            'scheduled_times' => self::normalizeScheduledTimes(is_array($times) ? $times : []),
            'last_run_at' => $row['last_run_at'] ?? null,
            'next_run_at' => $row['next_run_at'] ?? null,
            'last_status' => $row['last_status'] ?? null,
            'last_message' => $row['last_message'] ?? null,
        ];
    }

    /**
     * @param list<string> $warnings
     * @return list<string>
     */
    private static function vehicleWarnings(string $vehicleCode, array $warnings): array
    {
        $vehicleCode = strtoupper(trim($vehicleCode));
        $matched = [];
        foreach ($warnings as $warning) {
            if (stripos((string) $warning, $vehicleCode) !== false) {
                $matched[] = (string) $warning;
            }
        }

        return $matched;
    }

    /**
     * @param array<string, mixed> $vehicle
     * @param list<string> $vehWarnings
     */
    private function upsertRateRow(
        string $cacheKey,
        int $snapshotId,
        array $vehicle,
        array $vehWarnings,
        string $pickupLocation,
        string $returnLocation,
        string $pickupDatetime,
        string $returnDatetime,
        string $rateQualifier,
        string $fetchedAt
    ): void {
        $db = Database::getInstance();
        $driver = $db->getDriverName();
        $code = strtoupper(trim((string) ($vehicle['vehicle_code'] ?? '')));
        $params = [
            ':snapshot_id' => $snapshotId,
            ':cache_key' => $cacheKey,
            ':vehicle_code' => $code,
            ':vehicle_name' => (string) ($vehicle['vehicle_name'] ?? $code),
            ':available' => !empty($vehicle['available']) ? 1 : 0,
            ':currency' => (string) ($vehicle['currency'] ?? 'USD'),
            ':daily_rate' => is_numeric($vehicle['daily_rate'] ?? null) ? (float) $vehicle['daily_rate'] : null,
            ':total_rate' => is_numeric($vehicle['total_rate'] ?? null) ? (float) $vehicle['total_rate'] : null,
            ':unit_name' => (string) ($vehicle['unit_name'] ?? 'Day'),
            ':raw_status' => (string) ($vehicle['raw_status'] ?? ''),
            ':warnings_json' => json_encode($vehWarnings, JSON_UNESCAPED_UNICODE),
            ':raw_json_sanitized' => json_encode(self::sanitizeVehicleRaw($vehicle, $vehWarnings), JSON_UNESCAPED_UNICODE),
            ':pickup_location' => $pickupLocation,
            ':return_location' => $returnLocation,
            ':pickup_datetime' => $pickupDatetime,
            ':return_datetime' => $returnDatetime,
            ':rate_qualifier' => $rateQualifier,
            ':fetched_at' => $fetchedAt,
            ':updated_at' => $fetchedAt,
        ];

        if ($driver === 'mysql') {
            $db->execute(
                'INSERT INTO rac_bars_rates (
                    snapshot_id, cache_key, vehicle_code, vehicle_name, available, currency, daily_rate, total_rate,
                    unit_name, raw_status, warnings_json, raw_json_sanitized, pickup_location, return_location,
                    pickup_datetime, return_datetime, rate_qualifier, fetched_at, updated_at
                ) VALUES (
                    :snapshot_id, :cache_key, :vehicle_code, :vehicle_name, :available, :currency, :daily_rate, :total_rate,
                    :unit_name, :raw_status, :warnings_json, :raw_json_sanitized, :pickup_location, :return_location,
                    :pickup_datetime, :return_datetime, :rate_qualifier, :fetched_at, :updated_at
                ) ON DUPLICATE KEY UPDATE
                    snapshot_id = VALUES(snapshot_id),
                    vehicle_name = VALUES(vehicle_name),
                    available = VALUES(available),
                    currency = VALUES(currency),
                    daily_rate = VALUES(daily_rate),
                    total_rate = VALUES(total_rate),
                    unit_name = VALUES(unit_name),
                    raw_status = VALUES(raw_status),
                    warnings_json = VALUES(warnings_json),
                    raw_json_sanitized = VALUES(raw_json_sanitized),
                    pickup_location = VALUES(pickup_location),
                    return_location = VALUES(return_location),
                    pickup_datetime = VALUES(pickup_datetime),
                    return_datetime = VALUES(return_datetime),
                    rate_qualifier = VALUES(rate_qualifier),
                    fetched_at = VALUES(fetched_at),
                    updated_at = VALUES(updated_at)',
                $params
            );

            return;
        }

        $db->execute(
            'INSERT INTO rac_bars_rates (
                snapshot_id, cache_key, vehicle_code, vehicle_name, available, currency, daily_rate, total_rate,
                unit_name, raw_status, warnings_json, raw_json_sanitized, pickup_location, return_location,
                pickup_datetime, return_datetime, rate_qualifier, fetched_at, updated_at
            ) VALUES (
                :snapshot_id, :cache_key, :vehicle_code, :vehicle_name, :available, :currency, :daily_rate, :total_rate,
                :unit_name, :raw_status, :warnings_json, :raw_json_sanitized, :pickup_location, :return_location,
                :pickup_datetime, :return_datetime, :rate_qualifier, :fetched_at, :updated_at
            ) ON CONFLICT(cache_key, vehicle_code) DO UPDATE SET
                snapshot_id = excluded.snapshot_id,
                vehicle_name = excluded.vehicle_name,
                available = excluded.available,
                currency = excluded.currency,
                daily_rate = excluded.daily_rate,
                total_rate = excluded.total_rate,
                unit_name = excluded.unit_name,
                raw_status = excluded.raw_status,
                warnings_json = excluded.warnings_json,
                raw_json_sanitized = excluded.raw_json_sanitized,
                pickup_location = excluded.pickup_location,
                return_location = excluded.return_location,
                pickup_datetime = excluded.pickup_datetime,
                return_datetime = excluded.return_datetime,
                rate_qualifier = excluded.rate_qualifier,
                fetched_at = excluded.fetched_at,
                updated_at = excluded.updated_at',
            $params
        );
    }

    private function startRefreshRun(?int $scheduleId, string $runType, string $startedAt): int
    {
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO rac_bars_rate_refresh_runs (schedule_id, run_type, started_at, status) VALUES (:schedule_id, :run_type, :started_at, :status)',
            [
                ':schedule_id' => $scheduleId,
                ':run_type' => $runType,
                ':started_at' => $startedAt,
                ':status' => 'running',
            ]
        );

        return (int) $db->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function finishRefreshRun(int $runId, array $data): void
    {
        $db = Database::getInstance();
        $db->execute(
            'UPDATE rac_bars_rate_refresh_runs SET
                finished_at = :finished_at,
                status = :status,
                message = :message,
                http_code = :http_code,
                warning_175 = :warning_175,
                total_count = :total_count,
                available_count = :available_count,
                unavailable_count = :unavailable_count,
                snapshot_id = :snapshot_id
             WHERE id = :id',
            [
                ':finished_at' => $data['finished_at'] ?? null,
                ':status' => $data['status'] ?? 'error',
                ':message' => $data['message'] ?? null,
                ':http_code' => $data['http_code'] ?? null,
                ':warning_175' => !empty($data['warning_175']) ? 1 : 0,
                ':total_count' => $data['total_count'] ?? null,
                ':available_count' => $data['available_count'] ?? null,
                ':unavailable_count' => $data['unavailable_count'] ?? null,
                ':snapshot_id' => $data['snapshot_id'] ?? null,
                ':id' => $runId,
            ]
        );
    }

    /**
     * @param array<string, mixed> $schedule
     */
    private function scheduleSlotAlreadyRanToday(array $schedule, DateTimeInterface $candidate): bool
    {
        $lastRunAt = $schedule['last_run_at'] ?? null;
        if ($lastRunAt === null || $lastRunAt === '') {
            return false;
        }

        $tz = new DateTimeZone(self::TZ);
        $lastRun = new DateTimeImmutable((string) $lastRunAt, $tz);
        $candidateImmutable = DateTimeImmutable::createFromInterface($candidate)->setTimezone($tz);

        if ($lastRun->format('Y-m-d') !== $candidateImmutable->format('Y-m-d')) {
            return false;
        }

        return $lastRun->format('H:i') === $candidateImmutable->format('H:i');
    }

    /**
     * @param mixed $times
     * @return list<string>
     */
    private static function normalizeScheduledTimes($times): array
    {
        if (is_string($times)) {
            $times = preg_split('/[\s,;]+/', $times) ?: [];
        }
        if (!is_array($times)) {
            return ['06:00', '12:00', '18:00', '23:00'];
        }

        $normalized = [];
        foreach ($times as $time) {
            $time = self::normalizeTime((string) $time);
            if ($time !== '') {
                $normalized[] = $time;
            }
        }
        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized !== [] ? $normalized : ['06:00', '12:00', '18:00', '23:00'];
    }

    private static function normalizeTime(string $time): string
    {
        $time = trim($time);
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            [$h, $m] = explode(':', $time);

            return sprintf('%02d:%02d', (int) $h, (int) $m);
        }
        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $time)) {
            return substr($time, 0, 5);
        }

        return '10:00';
    }

    private static function applyTimeToDate(DateTime $date, string $time): void
    {
        $time = self::normalizeTime($time);
        [$h, $m] = explode(':', $time);
        $date->setTime((int) $h, (int) $m, 0);
    }

    private static function nowString(): string
    {
        return (new DateTime('now', new DateTimeZone(self::TZ)))->format('Y-m-d H:i:s');
    }
}
