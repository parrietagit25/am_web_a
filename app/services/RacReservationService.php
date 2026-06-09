<?php
/**
 * Persist and list RAC reservations.
 */

require_once __DIR__ . '/RacDatabaseSchema.php';

class RacReservationService {
    public function __construct() {
        RacDatabaseSchema::ensure();
    }

    /**
     * @return array{name: string, amount: ?float, deductible: ?float}
     */
    public static function resolveCoverageFromSnapshot(string $code, ?array $vehicle): array {
        if ($code === '') {
            return ['name' => '—', 'amount' => null, 'deductible' => null];
        }
        $name = $code;
        $amount = null;
        $deductible = null;
        if (!is_array($vehicle)) {
            return compact('name', 'amount', 'deductible');
        }
        $packages = $vehicle['pricing']['coveragePackages'] ?? $vehicle['availableCoverages'] ?? [];
        foreach ($packages as $pkg) {
            $pkgCode = $pkg['code'] ?? $pkg['coverageType'] ?? '';
            if ($pkgCode === $code) {
                $name = $pkg['name'] ?? $pkg['description'] ?? $code;
                $amount = isset($pkg['amountTotal']) ? (float) $pkg['amountTotal'] : null;
                $deductible = isset($pkg['deductible']) ? (float) $pkg['deductible'] : null;
                break;
            }
        }
        return compact('name', 'amount', 'deductible');
    }

    /**
     * Enriquecer fila de reserva con datos de póliza (registros antiguos).
     */
    public static function enrichCoverageFields(array $row): array {
        $code = trim($row['coverage_code'] ?? '');
        if ($code === '') {
            return $row;
        }
        if (!empty($row['coverage_name']) && $row['coverage_amount'] !== null && $row['coverage_amount'] !== '') {
            return $row;
        }
        $vehicle = json_decode($row['vehicle_snapshot_json'] ?? '', true);
        $resolved = self::resolveCoverageFromSnapshot($code, is_array($vehicle) ? $vehicle : null);
        if (empty($row['coverage_name'])) {
            $row['coverage_name'] = $resolved['name'];
        }
        if (($row['coverage_amount'] ?? '') === '' || $row['coverage_amount'] === null) {
            $row['coverage_amount'] = $resolved['amount'];
        }
        if (($row['coverage_deductible'] ?? '') === '' || $row['coverage_deductible'] === null) {
            $row['coverage_deductible'] = $resolved['deductible'];
        }
        return $row;
    }

    public function create(array $data): array {
        $code = $this->generateCode();
        $db = Database::getInstance();

        $vehicleSnap = $data['vehicle_snapshot'] ?? [];
        $coverageCode = trim($data['coverage_code'] ?? '');
        $coverageResolved = self::resolveCoverageFromSnapshot($coverageCode, $vehicleSnap);
        $barsCode = trim($data['bars_confirmation_code'] ?? '');
        $status = trim($data['status'] ?? 'pending');
        if (!in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
            $status = 'pending';
        }

        $sql = "INSERT INTO rac_reservations (
            reservation_code, status, customer_name, customer_email, customer_phone, customer_comments,
            location_code, return_location_code, pickup_date, pickup_time, return_date, return_time,
            driver_age, promo_code, sipp_code, vehicle_name, vehicle_category,
            vendor_rate_id, quote_token, rate_type,
            price_web, price_counter, price_total, price_total_estimated,
            coverage_code, coverage_name, coverage_amount, coverage_deductible,
            price_rental_base, price_saf, price_itbms,
            equipment_json, vehicle_snapshot_json, search_snapshot_json,
            bars_confirmation_code, extras_snapshot_json
        ) VALUES (
            :reservation_code, :status, :customer_name, :customer_email, :customer_phone, :customer_comments,
            :location_code, :return_location_code, :pickup_date, :pickup_time, :return_date, :return_time,
            :driver_age, :promo_code, :sipp_code, :vehicle_name, :vehicle_category,
            :vendor_rate_id, :quote_token, :rate_type,
            :price_web, :price_counter, :price_total, :price_total_estimated,
            :coverage_code, :coverage_name, :coverage_amount, :coverage_deductible,
            :price_rental_base, :price_saf, :price_itbms,
            :equipment_json, :vehicle_snapshot_json, :search_snapshot_json,
            :bars_confirmation_code, :extras_snapshot_json
        )";

        $db->execute($sql, [
            ':reservation_code' => $code,
            ':status' => $status,
            ':customer_name' => trim($data['customer_name'] ?? ''),
            ':customer_email' => trim($data['customer_email'] ?? ''),
            ':customer_phone' => trim($data['customer_phone'] ?? ''),
            ':customer_comments' => trim($data['customer_comments'] ?? ''),
            ':location_code' => strtoupper(trim($data['location_code'] ?? '')),
            ':return_location_code' => strtoupper(trim($data['return_location_code'] ?? '')),
            ':pickup_date' => $data['pickup_date'] ?? '',
            ':pickup_time' => $data['pickup_time'] ?? '10:00',
            ':return_date' => $data['return_date'] ?? '',
            ':return_time' => $data['return_time'] ?? '10:00',
            ':driver_age' => (string) ($data['driver_age'] ?? '25'),
            ':promo_code' => trim($data['promo_code'] ?? ''),
            ':sipp_code' => trim($data['sipp_code'] ?? ''),
            ':vehicle_name' => trim($data['vehicle_name'] ?? ''),
            ':vehicle_category' => trim($data['vehicle_category'] ?? ''),
            ':vendor_rate_id' => trim($data['vendor_rate_id'] ?? ''),
            ':quote_token' => trim($data['quote_token'] ?? ''),
            ':rate_type' => in_array($data['rate_type'] ?? 'web', ['web', 'counter'], true) ? $data['rate_type'] : 'web',
            ':price_web' => $this->decimal($data['price_web'] ?? null),
            ':price_counter' => $this->decimal($data['price_counter'] ?? null),
            ':price_total' => $this->decimal($data['price_total'] ?? null),
            ':price_total_estimated' => $this->decimal($data['price_total_estimated'] ?? null),
            ':coverage_code' => $coverageCode,
            ':coverage_name' => trim($data['coverage_name'] ?? '') ?: $coverageResolved['name'],
            ':coverage_amount' => $this->decimal($data['coverage_amount'] ?? $coverageResolved['amount']),
            ':coverage_deductible' => $this->decimal($data['coverage_deductible'] ?? $coverageResolved['deductible']),
            ':price_rental_base' => $this->decimal($data['price_rental_base'] ?? null),
            ':price_saf' => $this->decimal($data['price_saf'] ?? null),
            ':price_itbms' => $this->decimal($data['price_itbms'] ?? null),
            ':equipment_json' => json_encode($data['equipment'] ?? [], JSON_UNESCAPED_UNICODE),
            ':vehicle_snapshot_json' => json_encode($data['vehicle_snapshot'] ?? [], JSON_UNESCAPED_UNICODE),
            ':search_snapshot_json' => json_encode($data['search_snapshot'] ?? [], JSON_UNESCAPED_UNICODE),
            ':bars_confirmation_code' => $barsCode !== '' ? $barsCode : null,
            ':extras_snapshot_json' => json_encode($data['extras_snapshot'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);

        $id = (int) $db->lastInsertId();
        $row = $this->findById($id) ?: ['id' => $id, 'reservation_code' => $code];
        if ($barsCode !== '') {
            $row['bars_confirmation_code'] = $barsCode;
        }
        return $row;
    }

    public function findByBarsCode(string $code): ?array {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }
        $db = Database::getInstance();
        return $db->selectOne(
            'SELECT * FROM rac_reservations WHERE bars_confirmation_code = :code OR reservation_code = :code2',
            [':code' => $code, ':code2' => $code]
        ) ?: null;
    }

    public static function displayConfirmationCode(array $row): string {
        $bars = strtoupper(trim($row['bars_confirmation_code'] ?? ''));
        if ($bars !== '' && $bars !== 'PENDING') {
            return $bars;
        }
        return $row['reservation_code'] ?? '';
    }

    public function findById(int $id): ?array {
        $db = Database::getInstance();
        return $db->selectOne('SELECT * FROM rac_reservations WHERE id = :id', [':id' => $id]) ?: null;
    }

    public function findByCode(string $code): ?array {
        $db = Database::getInstance();
        return $db->selectOne(
            'SELECT * FROM rac_reservations WHERE reservation_code = :code',
            [':code' => trim($code)]
        ) ?: null;
    }

    public function listAll(int $limit = 200): array {
        $db = Database::getInstance();
        $limit = max(1, min(500, $limit));
        $rows = $db->select(
            "SELECT * FROM rac_reservations ORDER BY created_at DESC LIMIT {$limit}"
        );
        foreach ($rows as $i => $row) {
            $rows[$i] = self::enrichCoverageFields($row);
        }
        return $rows;
    }

    public function updateStatus(int $id, string $status): bool {
        $allowed = ['pending', 'confirmed', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $db = Database::getInstance();
        $driver = $db->getDriverName();
        $updated = $driver === 'mysql' ? 'NOW()' : "datetime('now')";
        return $db->execute(
            "UPDATE rac_reservations SET status = :status, updated_at = {$updated} WHERE id = :id",
            [':status' => $status, ':id' => $id]
        ) > 0;
    }

    private function generateCode(): string {
        return 'AM-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    private function decimal($value): ?float {
        if ($value === null || $value === '') {
            return null;
        }
        return round((float) $value, 2);
    }
}
