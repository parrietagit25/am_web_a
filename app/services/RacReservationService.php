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
            bars_confirmation_code, extras_snapshot_json,
            bars_cache_key, bars_snapshot_id, calculated_rate_id, vehicle_code, rental_days, currency,
            base_daily_rate, base_total_rate, final_daily_rate, final_total_rate,
            discount_amount_total, applied_rules_json, rate_source, rate_locked_at
        ) VALUES (
            :reservation_code, :status, :customer_name, :customer_email, :customer_phone, :customer_comments,
            :location_code, :return_location_code, :pickup_date, :pickup_time, :return_date, :return_time,
            :driver_age, :promo_code, :sipp_code, :vehicle_name, :vehicle_category,
            :vendor_rate_id, :quote_token, :rate_type,
            :price_web, :price_counter, :price_total, :price_total_estimated,
            :coverage_code, :coverage_name, :coverage_amount, :coverage_deductible,
            :price_rental_base, :price_saf, :price_itbms,
            :equipment_json, :vehicle_snapshot_json, :search_snapshot_json,
            :bars_confirmation_code, :extras_snapshot_json,
            :bars_cache_key, :bars_snapshot_id, :calculated_rate_id, :vehicle_code, :rental_days, :currency,
            :base_daily_rate, :base_total_rate, :final_daily_rate, :final_total_rate,
            :discount_amount_total, :applied_rules_json, :rate_source, :rate_locked_at
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
            ':bars_cache_key' => trim((string) ($data['bars_cache_key'] ?? '')) ?: null,
            ':bars_snapshot_id' => isset($data['bars_snapshot_id']) ? (int) $data['bars_snapshot_id'] : null,
            ':calculated_rate_id' => isset($data['calculated_rate_id']) ? (int) $data['calculated_rate_id'] : null,
            ':vehicle_code' => trim((string) ($data['vehicle_code'] ?? '')) ?: null,
            ':rental_days' => isset($data['rental_days']) ? (int) $data['rental_days'] : null,
            ':currency' => trim((string) ($data['currency'] ?? 'USD')) ?: null,
            ':base_daily_rate' => $this->decimal($data['base_daily_rate'] ?? null),
            ':base_total_rate' => $this->decimal($data['base_total_rate'] ?? null),
            ':final_daily_rate' => $this->decimal($data['final_daily_rate'] ?? null),
            ':final_total_rate' => $this->decimal($data['final_total_rate'] ?? null),
            ':discount_amount_total' => $this->decimal($data['discount_amount_total'] ?? null),
            ':applied_rules_json' => json_encode($data['applied_rules_json'] ?? [], JSON_UNESCAPED_UNICODE),
            ':rate_source' => trim((string) ($data['rate_source'] ?? '')) ?: null,
            ':rate_locked_at' => trim((string) ($data['rate_locked_at'] ?? '')) ?: null,
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

    /**
     * Crea (o reutiliza) una fila local espejo de una reserva que solo existe en RentWorks/BARS.
     * Permite cobro en «Paga tu reserva» y marcar pagado sin volver a crear en RentWorks.
     *
     * @param array<string, mixed> $barsReservation Resultado de BarsReservationClient / AutomarketReservationApiService
     * @return array<string, mixed>|null
     */
    public function ensureFromBarsLookup(string $code, array $barsReservation, float $amountDue): ?array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }

        $existing = $this->findByBarsCode($code);
        if ($existing !== null) {
            return $existing;
        }

        $pickupDt = trim((string) ($barsReservation['pickupDateTime'] ?? ''));
        $returnDt = trim((string) ($barsReservation['returnDateTime'] ?? ''));
        $pickupDate = $pickupDt !== '' ? substr($pickupDt, 0, 10) : date('Y-m-d');
        $pickupTime = $pickupDt !== '' && strlen($pickupDt) >= 16 ? substr($pickupDt, 11, 5) : '10:00';
        $returnDate = $returnDt !== '' ? substr($returnDt, 0, 10) : $pickupDate;
        $returnTime = $returnDt !== '' && strlen($returnDt) >= 16 ? substr($returnDt, 11, 5) : '10:00';

        $statusRaw = strtolower(trim((string) ($barsReservation['status'] ?? 'confirmed')));
        $status = (str_contains($statusRaw, 'cancel')) ? 'cancelled' : 'confirmed';
        $amount = max(0, round($amountDue, 2));

        return $this->create([
            'status' => $status,
            'customer_name' => trim((string) ($barsReservation['customerName'] ?? '')),
            'customer_email' => trim((string) ($barsReservation['customerEmail'] ?? '')),
            'customer_phone' => '',
            'customer_comments' => '[Origen: RentWorks / pago en línea vía Paga tu reserva]',
            'location_code' => strtoupper(trim((string) ($barsReservation['pickupLocation'] ?? ''))),
            'return_location_code' => strtoupper(trim((string) ($barsReservation['returnLocation'] ?? $barsReservation['pickupLocation'] ?? ''))),
            'pickup_date' => $pickupDate,
            'pickup_time' => $pickupTime,
            'return_date' => $returnDate,
            'return_time' => $returnTime,
            'driver_age' => '25',
            'sipp_code' => trim((string) ($barsReservation['sippCode'] ?? '')),
            'vehicle_code' => trim((string) ($barsReservation['sippCode'] ?? '')),
            'vehicle_name' => trim((string) ($barsReservation['vehicleName'] ?? '')),
            'rate_type' => 'web',
            'price_total' => $amount,
            'price_total_estimated' => $amount,
            'final_total_rate' => $amount,
            'bars_confirmation_code' => $code,
            'currency' => 'USD',
            'rate_source' => 'rentworks_lookup',
            // unpaid_counter: paymentSummary() marca pendiente (pay_online/web se interpretan como pagado).
            'extras_snapshot' => [
                'payment_choice' => 'pay_at_counter',
                'payment_status' => 'unpaid_counter',
                'source' => 'rentworks_desktop',
            ],
        ]);
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

    /**
     * Tipo de cobro y estado de pago (no modifica RentWorks).
     *
     * @param array<string, mixed> $row
     * @return array{channel: string, channel_label: string, payment_status: string, payment_status_label: string}
     */
    public static function paymentSummary(array $row): array
    {
        $rawExtras = $row['extras_snapshot_json'] ?? $row['extras_snapshot'] ?? $row['_extras'] ?? [];
        if (is_array($rawExtras)) {
            $extras = $rawExtras;
        } else {
            $extras = json_decode((string) $rawExtras, true);
            if (!is_array($extras)) {
                $extras = [];
            }
        }
        $choice = strtolower(trim((string) ($extras['payment_choice'] ?? '')));
        $payStatus = strtolower(trim((string) ($extras['payment_status'] ?? '')));
        $comments = (string) ($row['customer_comments'] ?? '');
        $rateType = strtolower(trim((string) ($row['rate_type'] ?? '')));

        $isCounter = in_array($choice, ['pay_at_counter', 'counter', 'pay_later'], true)
            || $payStatus === 'unpaid_counter'
            || str_contains($comments, '[Pago: en sucursal')
            || ($choice === '' && $rateType === 'counter');

        $isPaid = in_array($payStatus, ['paid', 'pagado', 'completed'], true)
            || $choice === 'pay_online'
            || $rateType === 'web';

        if (in_array($payStatus, ['paid', 'pagado', 'completed'], true)) {
            return [
                'channel' => 'card',
                'channel_label' => 'Pago con tarjeta',
                'payment_status' => 'paid',
                'payment_status_label' => 'Pagado',
            ];
        }

        if ($isCounter) {
            return [
                'channel' => 'counter',
                'channel_label' => 'Solo reserva',
                'payment_status' => 'pending',
                'payment_status_label' => 'Pendiente por pagar',
            ];
        }

        if ($isPaid) {
            return [
                'channel' => 'card',
                'channel_label' => 'Pago con tarjeta',
                'payment_status' => 'paid',
                'payment_status_label' => 'Pagado',
            ];
        }

        return [
            'channel' => 'counter',
            'channel_label' => 'Solo reserva',
            'payment_status' => 'pending',
            'payment_status_label' => 'Pendiente por pagar',
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function decorateForUi(array $row): array
    {
        $row = self::enrichCoverageFields($row);
        $pay = self::paymentSummary($row);
        $row['payment_channel'] = $pay['channel'];
        $row['payment_channel_label'] = $pay['channel_label'];
        $row['payment_status'] = $pay['payment_status'];
        $row['payment_status_label'] = $pay['payment_status_label'];
        $picked = ($row['pickup_status'] ?? '') === 'picked_up';
        $row['pickup_status'] = $picked ? 'picked_up' : 'pending';
        $row['pickup_status_label'] = $picked ? 'Cliente retiró' : 'Pendiente de retiro';
        $row['picked_up_at_label'] = self::formatDateTime($row['picked_up_at'] ?? null);
        $row['pickup_logs'] = is_array($row['pickup_logs'] ?? null) ? $row['pickup_logs'] : [];

        return $row;
    }

    public function listAll(int $limit = 200): array {
        $db = Database::getInstance();
        $limit = max(1, min(500, $limit));
        $rows = $db->select(
            "SELECT * FROM rac_reservations ORDER BY created_at DESC LIMIT {$limit}"
        );
        foreach ($rows as $i => $row) {
            $rows[$i] = self::decorateForUi($row);
        }
        return $this->attachPickupLogs($rows);
    }

    /**
     * Listado para export CSV/Excel (sin logs de retiro; límite más alto).
     *
     * @return list<array<string, mixed>>
     */
    public function listForExport(int $limit = 10000): array
    {
        $db = Database::getInstance();
        $limit = max(1, min(20000, $limit));
        $rows = $db->select(
            "SELECT * FROM rac_reservations ORDER BY created_at DESC LIMIT {$limit}"
        );
        foreach ($rows as $i => $row) {
            $rows[$i] = self::decorateForUi($row);
        }
        return $rows;
    }

    /**
     * Promo code aplicado a la reserva (columna o snapshot de búsqueda).
     */
    public static function promoCodeOf(array $row): string
    {
        $promo = strtoupper(trim((string) ($row['promo_code'] ?? '')));
        if ($promo !== '') {
            return $promo;
        }

        $raw = $row['search_snapshot_json'] ?? $row['search_snapshot'] ?? null;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : null;
        }
        if (is_array($raw)) {
            $promo = strtoupper(trim((string) ($raw['promoCode'] ?? $raw['promo_code'] ?? '')));
        }

        return $promo;
    }

    /**
     * @param array{q?: string, date_from?: string, date_to?: string, pickup?: string} $filters
     * @return list<array<string, mixed>>
     */
    public function search(array $filters, int $limit = 150): array
    {
        $db = Database::getInstance();
        $limit = max(1, min(300, $limit));
        $where = ['1=1'];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        $q = preg_replace('/[^\p{L}\p{N}@.\+\-\s]/u', '', $q) ?? '';
        $q = trim($q);
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(reservation_code LIKE :q1 OR COALESCE(bars_confirmation_code, \'\') LIKE :q2
                OR customer_name LIKE :q3 OR customer_email LIKE :q4 OR customer_phone LIKE :q5)';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
            $params[':q4'] = $like;
            $params[':q5'] = $like;
        }

        $from = trim((string) ($filters['date_from'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $where[] = 'pickup_date >= :date_from';
            $params[':date_from'] = $from;
        }
        $to = trim((string) ($filters['date_to'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $where[] = 'pickup_date <= :date_to';
            $params[':date_to'] = $to;
        }

        $pickup = trim((string) ($filters['pickup'] ?? ''));
        if ($pickup === 'picked_up') {
            $where[] = "COALESCE(pickup_status, 'pending') = 'picked_up'";
        } elseif ($pickup === 'pending') {
            $where[] = "COALESCE(pickup_status, 'pending') <> 'picked_up'";
        }

        $sql = 'SELECT * FROM rac_reservations WHERE ' . implode(' AND ', $where)
            . " ORDER BY pickup_date DESC, pickup_time DESC, created_at DESC LIMIT {$limit}";
        $rows = $db->select($sql, $params);
        foreach ($rows as $i => $row) {
            $rows[$i] = self::decorateForUi($row);
        }

        return $this->attachPickupLogs($rows);
    }

    /**
     * @param array{user_id?: int, username?: string, display_name?: string, ip?: string, user_agent?: string} $actor
     */
    public function markPickedUp(int $id, bool $pickedUp, string $byUser, array $actor = []): bool
    {
        if ($id <= 0) {
            return false;
        }
        $db = Database::getInstance();
        $byUser = self::clip($byUser, 120);
        $now = self::nowPanama();
        if ($pickedUp) {
            $ok = $db->execute(
                "UPDATE rac_reservations
                 SET pickup_status = 'picked_up', picked_up_at = :now_at, picked_up_by = :by_user, updated_at = :now_upd
                 WHERE id = :id",
                [':id' => $id, ':by_user' => $byUser, ':now_at' => $now, ':now_upd' => $now]
            ) > 0;
        } else {
            $ok = $db->execute(
                "UPDATE rac_reservations
                 SET pickup_status = 'pending', picked_up_at = NULL, picked_up_by = NULL, updated_at = :now_upd
                 WHERE id = :id",
                [':id' => $id, ':now_upd' => $now]
            ) > 0;
        }
        if (!$ok) {
            return false;
        }
        $this->insertPickupLog($id, $pickedUp ? 'picked_up' : 'undone', $byUser, $actor, $now);

        return true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRecentPickupLogs(int $limit = 150): array
    {
        $db = Database::getInstance();
        $limit = max(1, min(300, $limit));
        $rows = $db->select(
            "SELECT l.*, r.reservation_code, r.bars_confirmation_code, r.customer_name, r.vehicle_name
             FROM rac_reservation_pickup_logs l
             LEFT JOIN rac_reservations r ON r.id = l.reservation_id
             ORDER BY l.id DESC
             LIMIT {$limit}"
        );
        foreach ($rows as $i => $row) {
            $rows[$i] = $this->decoratePickupLogRow($row);
        }

        return $rows;
    }

    /**
     * Marca una reserva existente como pagada en línea (BD local).
     * El posteo a RentWorks (tarjeta / últimos 4) lo hace RacCheckoutFulfillment / RacRentworksPaymentSync.
     *
     * @param array<string, mixed> $meta
     */
    public function markPaidOnline(int $id, array $meta = []): bool
    {
        $row = $this->findById($id);
        if (!$row) {
            return false;
        }
        $extras = json_decode((string) ($row['extras_snapshot_json'] ?? ''), true);
        if (!is_array($extras)) {
            $extras = [];
        }
        $extras['payment_choice'] = 'pay_online';
        $extras['payment_status'] = 'paid';
        $extras['paid_online_at'] = date('c');
        if (!empty($meta['payment_id'])) {
            $extras['powertranz_payment_id'] = (int) $meta['payment_id'];
        }
        if (isset($meta['amount'])) {
            $extras['paid_amount'] = round((float) $meta['amount'], 2);
        }
        if (!empty($meta['card_suffix']) && preg_match('/^\d{4}$/', (string) $meta['card_suffix'])) {
            $extras['card_suffix'] = (string) $meta['card_suffix'];
        }
        if (isset($meta['rentworks_payment']) && is_array($meta['rentworks_payment'])) {
            $extras['rentworks_payment'] = [
                'ok' => !empty($meta['rentworks_payment']['ok']),
                'posted_amount' => $meta['rentworks_payment']['posted_amount'] ?? null,
                'card_suffix' => $meta['rentworks_payment']['card_suffix'] ?? null,
                'error' => $meta['rentworks_payment']['error'] ?? null,
                'at' => date('c'),
            ];
        }
        $comments = (string) ($row['customer_comments'] ?? '');
        $comments = str_replace(
            '[Pago: en sucursal / sin cobro en línea]',
            '[Pago: en línea / tarjeta]',
            $comments
        );

        $db = Database::getInstance();
        $driver = $db->getDriverName();
        $updated = $driver === 'mysql' ? 'NOW()' : "datetime('now')";
        return $db->execute(
            "UPDATE rac_reservations
             SET extras_snapshot_json = :extras, customer_comments = :comments, updated_at = {$updated}
             WHERE id = :id",
            [
                ':extras' => json_encode($extras, JSON_UNESCAPED_UNICODE),
                ':comments' => $comments,
                ':id' => $id,
            ]
        ) > 0;
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

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function attachPickupLogs(array $rows): array
    {
        if ($rows === []) {
            return $rows;
        }
        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return $rows;
        }

        $params = [];
        $placeholders = [];
        foreach ($ids as $index => $id) {
            $placeholder = ':pid_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }
        $logRows = Database::getInstance()->select(
            'SELECT * FROM rac_reservation_pickup_logs WHERE reservation_id IN ('
            . implode(', ', $placeholders)
            . ') ORDER BY id DESC',
            $params
        );
        $byRes = [];
        foreach ($logRows as $log) {
            $resId = (int) ($log['reservation_id'] ?? 0);
            $byRes[$resId][] = $this->decoratePickupLogRow($log);
        }
        foreach ($rows as $i => $row) {
            $resId = (int) ($row['id'] ?? 0);
            $rows[$i]['pickup_logs'] = $byRes[$resId] ?? [];
        }

        return $rows;
    }

    /**
     * @param array{user_id?: int, username?: string, display_name?: string, ip?: string, user_agent?: string} $actor
     */
    private function insertPickupLog(int $reservationId, string $event, string $byUser, array $actor, string $createdAt): void
    {
        try {
            $display = trim((string) ($actor['display_name'] ?? $byUser));
            if ($display === '') {
                $display = $byUser;
            }
            $ua = self::clip((string) ($actor['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '')), 255);
            $ip = trim((string) ($actor['ip'] ?? self::clientIp()));
            Database::getInstance()->execute(
                'INSERT INTO rac_reservation_pickup_logs
                    (reservation_id, event, user_id, username, display_name, ip_address, user_agent, created_at)
                 VALUES
                    (:reservation_id, :event, :user_id, :username, :display_name, :ip_address, :user_agent, :created_at)',
                [
                    ':reservation_id' => $reservationId,
                    ':event' => $event === 'undone' ? 'undone' : 'picked_up',
                    ':user_id' => ((int) ($actor['user_id'] ?? 0)) > 0 ? (int) $actor['user_id'] : null,
                    ':username' => self::clip((string) ($actor['username'] ?? ''), 80),
                    ':display_name' => self::clip($display, 120),
                    ':ip_address' => self::clip($ip, 45),
                    ':user_agent' => $ua,
                    ':created_at' => $createdAt,
                ]
            );
        } catch (Throwable $e) {
            if (function_exists('am_log')) {
                am_log('RAC pickup log failed: ' . $e->getMessage(), 'ERROR');
            }
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function decoratePickupLogRow(array $row): array
    {
        $event = (($row['event'] ?? '') === 'undone') ? 'undone' : 'picked_up';
        $row['event'] = $event;
        $row['event_label'] = $event === 'undone' ? 'Deshizo retiro' : 'Marcó retiro';
        $row['created_at_label'] = self::formatDateTime($row['created_at'] ?? null);
        $who = trim((string) ($row['display_name'] ?? ''));
        if ($who === '') {
            $who = trim((string) ($row['username'] ?? ''));
        }
        $row['actor_label'] = $who !== '' ? $who : 'Mostrador';
        $row['confirmation_code'] = self::displayConfirmationCode($row);

        return $row;
    }

    /** Timestamps de retiro se guardan ya en America/Panama; solo se reformatea. */
    public static function formatDateTime(?string $raw): string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }
        try {
            return (new DateTime($raw))->format('d/m/Y H:i');
        } catch (Exception $e) {
            return $raw;
        }
    }

    private static function nowPanama(): string
    {
        return (new DateTime('now', new DateTimeZone('America/Panama')))->format('Y-m-d H:i:s');
    }

    private static function clip(string $value, int $max): string
    {
        $value = trim($value);
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max);
        }

        return substr($value, 0, $max);
    }

    private static function clientIp(): string
    {
        $candidates = [
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
            $_SERVER['HTTP_CLIENT_IP'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
        ];
        foreach ($candidates as $raw) {
            $raw = trim((string) $raw);
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

    private function decimal($value): ?float {
        if ($value === null || $value === '') {
            return null;
        }
        return round((float) $value, 2);
    }
}
