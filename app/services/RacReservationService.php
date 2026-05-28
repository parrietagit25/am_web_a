<?php
/**
 * Persist and list RAC reservations.
 */

require_once __DIR__ . '/RacDatabaseSchema.php';

class RacReservationService {
    public function __construct() {
        RacDatabaseSchema::ensure();
    }

    public function create(array $data): array {
        $code = $this->generateCode();
        $db = Database::getInstance();

        $sql = "INSERT INTO rac_reservations (
            reservation_code, status, customer_name, customer_email, customer_phone, customer_comments,
            location_code, return_location_code, pickup_date, pickup_time, return_date, return_time,
            driver_age, promo_code, sipp_code, vehicle_name, vehicle_category,
            vendor_rate_id, quote_token, rate_type,
            price_web, price_counter, price_total, price_total_estimated,
            coverage_code, equipment_json, vehicle_snapshot_json, search_snapshot_json
        ) VALUES (
            :reservation_code, :status, :customer_name, :customer_email, :customer_phone, :customer_comments,
            :location_code, :return_location_code, :pickup_date, :pickup_time, :return_date, :return_time,
            :driver_age, :promo_code, :sipp_code, :vehicle_name, :vehicle_category,
            :vendor_rate_id, :quote_token, :rate_type,
            :price_web, :price_counter, :price_total, :price_total_estimated,
            :coverage_code, :equipment_json, :vehicle_snapshot_json, :search_snapshot_json
        )";

        $db->execute($sql, [
            ':reservation_code' => $code,
            ':status' => 'pending',
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
            ':coverage_code' => trim($data['coverage_code'] ?? ''),
            ':equipment_json' => json_encode($data['equipment'] ?? [], JSON_UNESCAPED_UNICODE),
            ':vehicle_snapshot_json' => json_encode($data['vehicle_snapshot'] ?? [], JSON_UNESCAPED_UNICODE),
            ':search_snapshot_json' => json_encode($data['search_snapshot'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);

        $id = (int) $db->lastInsertId();
        return $this->findById($id) ?: ['id' => $id, 'reservation_code' => $code];
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
        return $db->select(
            "SELECT * FROM rac_reservations ORDER BY created_at DESC LIMIT {$limit}"
        );
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
