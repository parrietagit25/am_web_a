<?php
/**
 * Métricas de reservas RAC para el dashboard admin.
 * Solo lectura de rac_reservations. No toca RentWorks ni el cobro.
 */
declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/RacDatabaseSchema.php';
require_once __DIR__ . '/RacReservationService.php';

class RacReservationDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function build(int $days, ?string $from = null, ?string $to = null): array
    {
        RacDatabaseSchema::ensure();
        $tz = new DateTimeZone('America/Panama');
        $now = new DateTimeImmutable('now', $tz);
        $range = $this->resolveRange($days, $from, $to, $tz, $now);
        $rows = $this->loadRows($range['start'], $range['end'], $range['bounded']);

        $paid = 0;
        $unpaid = 0;
        $cancelled = 0;
        $confirmed = 0;
        $pending = 0;
        $picked = 0;
        $card = 0;
        $counter = 0;
        $revenue = 0.0;
        $revenuePaid = 0.0;
        $byDay = [];
        $byWeek = [];
        $byLocation = [];
        $byVehicle = [];
        $byPromo = [];
        $byStatus = [];

        $periodStart = $range['start'];
        $firstCreated = null;
        $lastCreated = null;

        foreach ($rows as $row) {
            $created = $this->parseDate((string) ($row['created_at'] ?? ''), $tz);
            if ($created === null) {
                continue;
            }
            if ($firstCreated === null || $created < $firstCreated) {
                $firstCreated = $created;
            }
            if ($lastCreated === null || $created > $lastCreated) {
                $lastCreated = $created;
            }

            $pay = RacReservationService::paymentSummary($row);
            $status = strtolower(trim((string) ($row['status'] ?? 'pending')));
            $amount = $this->amount($row);
            $isPaid = ($pay['payment_status'] ?? '') === 'paid';
            $isCancelled = $status === 'cancelled';

            if ($isCancelled) {
                $cancelled++;
            } elseif ($status === 'confirmed') {
                $confirmed++;
            } else {
                $pending++;
            }

            if ($isPaid) {
                $paid++;
                $revenuePaid += $amount;
            } else {
                $unpaid++;
            }
            if (($pay['channel'] ?? '') === 'card') {
                $card++;
            } else {
                $counter++;
            }
            if (($row['pickup_status'] ?? '') === 'picked_up') {
                $picked++;
            }

            $revenue += $amount;

            $dayKey = $created->format('Y-m-d');
            if (!isset($byDay[$dayKey])) {
                $byDay[$dayKey] = ['total' => 0, 'paid' => 0, 'unpaid' => 0, 'revenue' => 0.0];
            }
            $byDay[$dayKey]['total']++;
            $byDay[$dayKey][$isPaid ? 'paid' : 'unpaid']++;
            $byDay[$dayKey]['revenue'] += $amount;

            $weekKey = $created->modify('monday this week')->format('Y-m-d');
            if (!isset($byWeek[$weekKey])) {
                $byWeek[$weekKey] = ['total' => 0, 'paid' => 0, 'revenue' => 0.0];
            }
            $byWeek[$weekKey]['total']++;
            if ($isPaid) {
                $byWeek[$weekKey]['paid']++;
            }
            $byWeek[$weekKey]['revenue'] += $amount;

            $loc = strtoupper(trim((string) ($row['location_code'] ?? ''))) ?: '—';
            $byLocation[$loc] = ($byLocation[$loc] ?? 0) + 1;

            $veh = strtoupper(trim((string) ($row['sipp_code'] ?? '')));
            if ($veh === '') {
                $veh = trim((string) ($row['vehicle_name'] ?? '')) ?: '—';
            }
            $byVehicle[$veh] = ($byVehicle[$veh] ?? 0) + 1;

            $promo = strtoupper(trim((string) ($row['promo_code'] ?? '')));
            if ($promo !== '') {
                $byPromo[$promo] = ($byPromo[$promo] ?? 0) + 1;
            }

            $byStatus[$status !== '' ? $status : 'pending'] = ($byStatus[$status !== '' ? $status : 'pending'] ?? 0) + 1;
        }

        $total = count($rows);
        $spanStart = $periodStart ?? $firstCreated ?? $now;
        $spanEnd = $range['bounded'] ? $range['end'] : $now;
        $spanDays = max(1, (int) $spanStart->setTime(0, 0)->diff($spanEnd->setTime(0, 0))->days + 1);
        if (!$range['custom'] && $days > 0) {
            $spanDays = $days;
        }
        $spanWeeks = max(1, (int) ceil($spanDays / 7));

        ksort($byDay);
        ksort($byWeek);
        arsort($byLocation);
        arsort($byVehicle);
        arsort($byPromo);

        $maxPoints = $range['custom']
            ? min(400, $spanDays)
            : ($days > 0 ? $days : min(90, $spanDays));
        $timeline = $this->fillDays($byDay, $spanStart, $spanEnd, $maxPoints);

        return [
            'days' => $days,
            'range_custom' => $range['custom'],
            'range_from' => $range['from'],
            'range_to' => $range['to'],
            'total' => $total,
            'paid' => $paid,
            'unpaid' => $unpaid,
            'cancelled' => $cancelled,
            'confirmed' => $confirmed,
            'pending' => $pending,
            'picked_up' => $picked,
            'card' => $card,
            'counter' => $counter,
            'revenue' => round($revenue, 2),
            'revenue_paid' => round($revenuePaid, 2),
            'avg_ticket' => $total > 0 ? round($revenue / $total, 2) : 0.0,
            'avg_per_day' => round($total / $spanDays, 2),
            'avg_per_week' => round($total / $spanWeeks, 2),
            'paid_rate' => $total > 0 ? round(($paid / $total) * 100, 1) : 0.0,
            'span_days' => $spanDays,
            'timeline' => $timeline,
            'weeks' => $this->topPairs($byWeek, 12, true),
            'locations' => $this->topPairs($byLocation, 8),
            'vehicles' => $this->topPairs($byVehicle, 8),
            'promos' => $this->topPairs($byPromo, 8),
            'statuses' => $byStatus,
        ];
    }

    /**
     * @return array{custom:bool,bounded:bool,start:?DateTimeImmutable,end:DateTimeImmutable,from:string,to:string}
     */
    private function resolveRange(int $days, ?string $from, ?string $to, DateTimeZone $tz, DateTimeImmutable $now): array
    {
        $fromDay = $this->parseDay($from, $tz);
        $toDay = $this->parseDay($to, $tz);
        if ($fromDay instanceof DateTimeImmutable && $toDay instanceof DateTimeImmutable) {
            if ($fromDay > $toDay) {
                [$fromDay, $toDay] = [$toDay, $fromDay];
            }

            return [
                'custom' => true,
                'bounded' => true,
                'start' => $fromDay->setTime(0, 0, 0),
                'end' => $toDay->setTime(23, 59, 59),
                'from' => $fromDay->format('Y-m-d'),
                'to' => $toDay->format('Y-m-d'),
            ];
        }

        $start = $days > 0
            ? $now->modify('-' . ($days - 1) . ' days')->setTime(0, 0, 0)
            : null;

        return [
            'custom' => false,
            'bounded' => false,
            'start' => $start,
            'end' => $now,
            'from' => $start instanceof DateTimeImmutable ? $start->format('Y-m-d') : '',
            'to' => $now->format('Y-m-d'),
        ];
    }

    private function parseDay(?string $value, DateTimeZone $tz): ?DateTimeImmutable
    {
        $value = trim((string) $value);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $tz);

        return $dt instanceof DateTimeImmutable ? $dt : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadRows(?DateTimeImmutable $start, DateTimeImmutable $end, bool $bounded): array
    {
        $db = Database::getInstance();
        $sql = 'SELECT id, status, location_code, sipp_code, vehicle_name, promo_code, rate_type,
                       price_total, price_total_estimated, extras_snapshot_json, customer_comments,
                       pickup_status, created_at
                FROM rac_reservations';
        $params = [];
        if ($start instanceof DateTimeImmutable) {
            $sql .= ' WHERE created_at >= :since';
            $params[':since'] = $start->format('Y-m-d H:i:s');
            if ($bounded) {
                $sql .= ' AND created_at <= :until';
                $params[':until'] = $end->format('Y-m-d H:i:s');
            }
        }
        $sql .= ' ORDER BY created_at ASC';

        return $db->select($sql, $params);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function amount(array $row): float
    {
        $est = (float) ($row['price_total_estimated'] ?? 0);
        if ($est > 0) {
            return $est;
        }

        return max(0.0, (float) ($row['price_total'] ?? 0));
    }

    private function parseDate(string $value, DateTimeZone $tz): ?DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value, $tz);
        if ($dt instanceof DateTimeImmutable) {
            return $dt;
        }
        try {
            return new DateTimeImmutable($value, $tz);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * @param array<string, array{total:int,paid:int,unpaid:int,revenue:float}> $byDay
     * @return list<array{date:string,label:string,total:int,paid:int,unpaid:int,revenue:float}>
     */
    private function fillDays(array $byDay, DateTimeImmutable $start, DateTimeImmutable $end, int $maxPoints): array
    {
        $cursor = $end->setTime(0, 0)->modify('-' . max(0, $maxPoints - 1) . ' days');
        if ($cursor < $start->setTime(0, 0) && $maxPoints >= 365) {
            $cursor = $start->setTime(0, 0);
        }
        $out = [];
        $guard = 0;
        while ($cursor <= $end->setTime(0, 0) && $guard < 400) {
            $key = $cursor->format('Y-m-d');
            $bucket = $byDay[$key] ?? ['total' => 0, 'paid' => 0, 'unpaid' => 0, 'revenue' => 0.0];
            $out[] = [
                'date' => $key,
                'label' => $cursor->format('d/m'),
                'total' => (int) $bucket['total'],
                'paid' => (int) $bucket['paid'],
                'unpaid' => (int) $bucket['unpaid'],
                'revenue' => round((float) $bucket['revenue'], 2),
            ];
            $cursor = $cursor->modify('+1 day');
            $guard++;
        }

        return $out;
    }

    /**
     * @param array<string, int|array<string, mixed>> $map
     * @return list<array{label:string,value:int,extra?:float}>
     */
    private function topPairs(array $map, int $limit, bool $keepOrder = false): array
    {
        if (!$keepOrder) {
            arsort($map);
        }
        $out = [];
        $i = 0;
        foreach ($map as $label => $value) {
            if ($i >= $limit) {
                break;
            }
            if (is_array($value)) {
                $out[] = [
                    'label' => (string) $label,
                    'value' => (int) ($value['total'] ?? 0),
                    'paid' => (int) ($value['paid'] ?? 0),
                    'revenue' => round((float) ($value['revenue'] ?? 0), 2),
                ];
            } else {
                $out[] = [
                    'label' => (string) $label,
                    'value' => (int) $value,
                ];
            }
            $i++;
        }

        return $out;
    }
}
