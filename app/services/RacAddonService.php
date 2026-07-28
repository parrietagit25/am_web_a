<?php
/**
 * Protecciones y extras RAC — CRUD admin, catálogo público y cálculo server-side.
 * AM-RAC-BARS-RAC-3C
 */

require_once __DIR__ . '/RacDatabaseSchema.php';

class RacAddonService
{
    public const PRICE_TYPES = ['free', 'fixed_daily', 'fixed_total', 'percent_of_rate'];
    public const APPLIES_PER = ['rental', 'day'];

    /** @return array<string, string> */
    public static function priceTypeLabels(): array
    {
        return [
            'free' => 'Gratis',
            'fixed_daily' => 'Por día',
            'fixed_total' => 'Cargo fijo (una vez)',
            'percent_of_rate' => '% de la tarifa',
        ];
    }

    /** @return array<string, string> */
    public static function appliesPerLabels(): array
    {
        return [
            'day' => 'Por día',
            'rental' => 'Por reserva (fijo)',
        ];
    }

    public function __construct()
    {
        RacDatabaseSchema::ensure();
    }

    /**
     * @param array<string, mixed> $context
     * @return list<array<string, mixed>>
     */
    public function getPublicProtections(array $context): array
    {
        return array_map(
            fn(array $row) => $this->formatPublicProtection($row, $context),
            $this->filterProducts($this->listProtections(false), $context)
        );
    }

    /**
     * @param array<string, mixed> $context
     * @return list<array<string, mixed>>
     */
    public function getPublicExtras(array $context): array
    {
        return array_map(
            fn(array $row) => $this->formatPublicExtra($row, $context),
            $this->filterProducts($this->listExtras(false), $context)
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAdminProtections(): array
    {
        return $this->listProtections(true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAdminExtras(): array
    {
        return $this->listExtras(true);
    }

    public function getProtection(int $id): ?array
    {
        $db = Database::getInstance();
        $row = $db->selectOne('SELECT * FROM rac_protection_products WHERE id = :id', [':id' => $id]);

        return $row ? $this->normalizeProtectionRow($row) : null;
    }

    public function getExtra(int $id): ?array
    {
        $db = Database::getInstance();
        $row = $db->selectOne('SELECT * FROM rac_extra_products WHERE id = :id', [':id' => $id]);

        return $row ? $this->normalizeExtraRow($row) : null;
    }

    public function findProtectionByCodeAdmin(string $code): ?array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }
        $db = Database::getInstance();
        $row = $db->selectOne(
            'SELECT * FROM rac_protection_products WHERE UPPER(code) = :code LIMIT 1',
            [':code' => $code]
        );

        return $row ? $this->normalizeProtectionRow($row) : null;
    }

    public function findExtraByCodeAdmin(string $code): ?array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }
        $db = Database::getInstance();
        $row = $db->selectOne(
            'SELECT * FROM rac_extra_products WHERE UPPER(code) = :code LIMIT 1',
            [':code' => $code]
        );

        return $row ? $this->normalizeExtraRow($row) : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createProtection(array $data): int
    {
        $payload = $this->sanitizeProtectionPayload($data);
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO rac_protection_products (
                code, name, description, enabled, price_type, price_amount, currency, applies_per,
                vehicle_code, vehicle_name, min_rental_days, max_rental_days,
                pickup_location, return_location, sort_order, visible_public, is_default
            ) VALUES (
                :code, :name, :description, :enabled, :price_type, :price_amount, :currency, :applies_per,
                :vehicle_code, :vehicle_name, :min_rental_days, :max_rental_days,
                :pickup_location, :return_location, :sort_order, :visible_public, :is_default
            )',
            $payload
        );

        return (int) $db->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateProtection(int $id, array $data): bool
    {
        $payload = $this->sanitizeProtectionPayload($data);
        $payload[':id'] = $id;
        $db = Database::getInstance();
        $driver = $db->getDriverName();
        $updated = $driver === 'mysql' ? 'NOW()' : "datetime('now')";

        return $db->execute(
            "UPDATE rac_protection_products SET
                code = :code, name = :name, description = :description, enabled = :enabled,
                price_type = :price_type, price_amount = :price_amount, currency = :currency, applies_per = :applies_per,
                vehicle_code = :vehicle_code, vehicle_name = :vehicle_name,
                min_rental_days = :min_rental_days, max_rental_days = :max_rental_days,
                pickup_location = :pickup_location, return_location = :return_location,
                sort_order = :sort_order, visible_public = :visible_public, is_default = :is_default,
                updated_at = {$updated}
            WHERE id = :id",
            $payload
        ) > 0;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createExtra(array $data): int
    {
        $payload = $this->sanitizeExtraPayload($data);
        $this->assertExtraCodeAvailable((string) $payload[':code']);
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO rac_extra_products (
                code, name, description, enabled, price_type, price_amount, currency, applies_per, max_quantity,
                vehicle_code, vehicle_name, min_rental_days, max_rental_days,
                pickup_location, return_location, sort_order, visible_public
            ) VALUES (
                :code, :name, :description, :enabled, :price_type, :price_amount, :currency, :applies_per, :max_quantity,
                :vehicle_code, :vehicle_name, :min_rental_days, :max_rental_days,
                :pickup_location, :return_location, :sort_order, :visible_public
            )',
            $payload
        );

        return (int) $db->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateExtra(int $id, array $data): bool
    {
        $payload = $this->sanitizeExtraPayload($data);
        $this->assertExtraCodeAvailable((string) $payload[':code'], $id);
        $payload[':id'] = $id;
        $db = Database::getInstance();
        $driver = $db->getDriverName();
        $updated = $driver === 'mysql' ? 'NOW()' : "datetime('now')";

        return $db->execute(
            "UPDATE rac_extra_products SET
                code = :code, name = :name, description = :description, enabled = :enabled,
                price_type = :price_type, price_amount = :price_amount, currency = :currency, applies_per = :applies_per,
                max_quantity = :max_quantity, vehicle_code = :vehicle_code, vehicle_name = :vehicle_name,
                min_rental_days = :min_rental_days, max_rental_days = :max_rental_days,
                pickup_location = :pickup_location, return_location = :return_location,
                sort_order = :sort_order, visible_public = :visible_public, updated_at = {$updated}
            WHERE id = :id",
            $payload
        ) > 0;
    }

    private function assertExtraCodeAvailable(string $code, int $exceptId = 0): void
    {
        $existing = $this->findExtraByCodeAdmin($code);
        if ($existing !== null && (int) ($existing['id'] ?? 0) !== $exceptId) {
            throw new InvalidArgumentException(
                'Ya existe un extra con el código «' . $code . '». Usa otro código o edita el existente.'
            );
        }
    }

    public function setProtectionEnabled(int $id, bool $enabled): bool
    {
        $db = Database::getInstance();

        return $db->execute(
            'UPDATE rac_protection_products SET enabled = :enabled, visible_public = :visible_public WHERE id = :id',
            [
                ':enabled' => $enabled ? 1 : 0,
                ':visible_public' => $enabled ? 1 : 0,
                ':id' => $id,
            ]
        ) > 0;
    }

    public function setExtraEnabled(int $id, bool $enabled): bool
    {
        $db = Database::getInstance();

        return $db->execute(
            'UPDATE rac_extra_products SET enabled = :enabled, visible_public = :visible_public WHERE id = :id',
            [
                ':enabled' => $enabled ? 1 : 0,
                ':visible_public' => $enabled ? 1 : 0,
                ':id' => $id,
            ]
        ) > 0;
    }

    /**
     * @param array<string, mixed> $protection
     * @param array<string, mixed> $context
     */
    public function calculateProtectionPrice(array $protection, array $context): float
    {
        return $this->calculateProductPrice($protection, 1, $context);
    }

    /**
     * @param array<string, mixed> $extra
     * @param array<string, mixed> $context
     */
    public function calculateExtraPrice(array $extra, int $quantity, array $context): float
    {
        $qty = max(0, min((int) ($extra['max_quantity'] ?? 99), $quantity));

        return $this->calculateProductPrice($extra, $qty, $context);
    }

    /**
     * Recalcula selección del cliente (códigos/cantidades) — ignora precios del cliente.
     *
     * @param array<string, mixed> $extrasInput
     * @param array<string, mixed> $context
     * @return array{ok: bool, message?: string, protection?: array, extras?: list, items?: list, totals?: array}
     */
    public function resolveReservationAddons(array $extrasInput, array $context): array
    {
        $rentalDays = max(1, (int) ($context['rental_days'] ?? $context['billed_days'] ?? 1));
        $rateBase = (float) ($context['rental_base'] ?? $context['final_total_rate'] ?? 0);
        $ctx = array_merge($context, ['rental_days' => $rentalDays, 'billed_days' => $rentalDays, 'rental_base' => $rateBase]);

        $protectionCode = strtoupper(trim((string) ($extrasInput['protection'] ?? '')));
        if ($protectionCode === 'NONE') {
            $protectionCode = '';
        }

        $protectionRow = null;
        $protectionTotal = 0.0;
        $protectionName = '';

        // Si no viene protección y hay productos activos, forzar la más barata.
        if ($protectionCode === '') {
            $public = $this->getPublicProtections($ctx);
            $public = array_values(array_filter(
                $public,
                static fn(array $p): bool => strtoupper((string) ($p['code'] ?? '')) !== 'NONE'
                    && strtoupper((string) ($p['code'] ?? '')) !== ''
            ));
            if ($public !== []) {
                usort($public, static function (array $a, array $b): int {
                    return ((float) ($a['amountTotal'] ?? 0)) <=> ((float) ($b['amountTotal'] ?? 0));
                });
                $protectionCode = strtoupper((string) ($public[0]['code'] ?? ''));
            }
        }

        if ($protectionCode !== '') {
            $protectionRow = $this->findProtectionByCode($protectionCode, $ctx);
            if ($protectionRow === null) {
                return ['ok' => false, 'message' => 'Protección seleccionada no válida para este vehículo.'];
            }
            $protectionTotal = $this->calculateProtectionPrice($protectionRow, $ctx);
            $protectionName = (string) ($protectionRow['name'] ?? $protectionCode);
        }

        $items = [];
        $extrasTotal = 0.0;
        $seenCodes = [];

        // CONDADIC se resuelve una sola vez vía additionalDrivers (evita doble conteo con items[]).
        $additionalDrivers = max(0, (int) ($extrasInput['additionalDrivers'] ?? 0));

        $requestedItems = is_array($extrasInput['items'] ?? null) ? $extrasInput['items'] : [];
        foreach ($requestedItems as $item) {
            if (!is_array($item)) {
                return ['ok' => false, 'message' => 'Formato de extras no válido.'];
            }
            $code = strtoupper(trim((string) ($item['code'] ?? '')));
            if ($code === '' || isset($seenCodes[$code])) {
                continue;
            }
            if ($code === 'CONDADIC') {
                if ($additionalDrivers <= 0) {
                    $additionalDrivers = max(0, (int) ($item['quantity'] ?? 1));
                }
                continue;
            }
            $seenCodes[$code] = true;
            if (!array_key_exists('quantity', $item) || $item['quantity'] === '' || $item['quantity'] === null) {
                $qty = 1;
            } else {
                if (is_array($item['quantity']) || is_object($item['quantity'])) {
                    return ['ok' => false, 'message' => 'Cantidad no válida para: ' . $code];
                }
                if (!is_numeric($item['quantity']) || (float) $item['quantity'] != (int) $item['quantity']) {
                    return ['ok' => false, 'message' => 'Cantidad no válida para: ' . $code];
                }
                $qty = (int) $item['quantity'];
            }
            $extraRow = $this->findExtraByCode($code);
            if ($extraRow === null || !$this->productMatchesContext($extraRow, $ctx)) {
                return ['ok' => false, 'message' => 'Extra no válido: ' . $code];
            }
            $maxQty = max(1, (int) ($extraRow['max_quantity'] ?? 1));
            if ($qty < 1 || $qty > $maxQty) {
                return ['ok' => false, 'message' => 'Cantidad no válida para: ' . $code];
            }
            $lineTotal = $this->calculateExtraPrice($extraRow, $qty, $ctx);
            $unit = $qty > 0 ? round($lineTotal / $qty, 2) : 0.0;
            $extrasTotal += $lineTotal;
            $items[] = [
                'item_type' => 'extra',
                'item_code' => $code,
                'item_name' => (string) ($extraRow['name'] ?? $code),
                'quantity' => $qty,
                'unit_price' => $unit,
                'total_price' => $lineTotal,
                'currency' => (string) ($extraRow['currency'] ?? 'USD'),
                'pricing_json' => [
                    'price_type' => $extraRow['price_type'] ?? '',
                    'price_amount' => (float) ($extraRow['price_amount'] ?? 0),
                    'applies_per' => $extraRow['applies_per'] ?? 'rental',
                ],
            ];
        }

        $driverRow = $this->findExtraByCode('CONDADIC');
        $maxDrivers = $driverRow !== null ? max(1, (int) ($driverRow['max_quantity'] ?? 1)) : 3;
        $additionalDrivers = max(0, min($maxDrivers, $additionalDrivers));
        if ($additionalDrivers > 0) {
            if ($driverRow !== null && $this->productMatchesContext($driverRow, $ctx)) {
                $lineTotal = $this->calculateExtraPrice($driverRow, $additionalDrivers, $ctx);
                $unit = $additionalDrivers > 0 ? round($lineTotal / $additionalDrivers, 2) : 0.0;
                $extrasTotal += $lineTotal;
                $items[] = [
                    'item_type' => 'extra',
                    'item_code' => 'CONDADIC',
                    'item_name' => (string) ($driverRow['name'] ?? 'Conductor Adicional'),
                    'quantity' => $additionalDrivers,
                    'unit_price' => $unit,
                    'total_price' => $lineTotal,
                    'currency' => (string) ($driverRow['currency'] ?? 'USD'),
                    'pricing_json' => [
                        'price_type' => $driverRow['price_type'] ?? '',
                        'price_amount' => (float) ($driverRow['price_amount'] ?? 0),
                        'applies_per' => $driverRow['applies_per'] ?? 'rental',
                    ],
                ];
            }
        }

        if ($protectionRow !== null) {
            $items[] = [
                'item_type' => 'protection',
                'item_code' => $protectionCode,
                'item_name' => $protectionName,
                'quantity' => 1,
                'unit_price' => $protectionTotal,
                'total_price' => $protectionTotal,
                'currency' => (string) ($protectionRow['currency'] ?? 'USD'),
                'pricing_json' => [
                    'price_type' => $protectionRow['price_type'] ?? '',
                    'price_amount' => (float) ($protectionRow['price_amount'] ?? 0),
                    'applies_per' => $protectionRow['applies_per'] ?? 'day',
                ],
            ];
        }

        return [
            'ok' => true,
            'protection' => [
                'code' => $protectionCode,
                'name' => $protectionName,
                'amount' => round($protectionTotal, 2),
            ],
            'extras' => array_values(array_filter($items, fn($i) => ($i['item_type'] ?? '') === 'extra')),
            'items' => $items,
            'totals' => [
                'coverage' => round($protectionTotal, 2),
                'extras' => round($extrasTotal, 2),
            ],
        ];
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    public function saveReservationItems(int $reservationId, array $items): void
    {
        if ($reservationId <= 0 || $items === []) {
            return;
        }
        $db = Database::getInstance();
        $db->execute('DELETE FROM rac_reservation_items WHERE reservation_id = :id', [':id' => $reservationId]);
        foreach ($items as $item) {
            $db->execute(
                'INSERT INTO rac_reservation_items (
                    reservation_id, item_type, item_code, item_name, quantity, unit_price, total_price, currency, pricing_json
                ) VALUES (
                    :reservation_id, :item_type, :item_code, :item_name, :quantity, :unit_price, :total_price, :currency, :pricing_json
                )',
                [
                    ':reservation_id' => $reservationId,
                    ':item_type' => (string) ($item['item_type'] ?? 'extra'),
                    ':item_code' => strtoupper(trim((string) ($item['item_code'] ?? ''))),
                    ':item_name' => trim((string) ($item['item_name'] ?? '')),
                    ':quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                    ':unit_price' => round((float) ($item['unit_price'] ?? 0), 2),
                    ':total_price' => round((float) ($item['total_price'] ?? 0), 2),
                    ':currency' => trim((string) ($item['currency'] ?? 'USD')) ?: 'USD',
                    ':pricing_json' => json_encode($item['pricing_json'] ?? [], JSON_UNESCAPED_UNICODE),
                ]
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getReservationItems(int $reservationId): array
    {
        if ($reservationId <= 0) {
            return [];
        }
        $db = Database::getInstance();
        $rows = $db->select(
            'SELECT * FROM rac_reservation_items WHERE reservation_id = :id ORDER BY id ASC',
            [':id' => $reservationId]
        );
        foreach ($rows as $i => $row) {
            $rows[$i]['pricing_json'] = json_decode((string) ($row['pricing_json'] ?? ''), true) ?: [];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function formatPublicProtection(array $row, array $context): array
    {
        $days = max(1, (int) ($context['rental_days'] ?? $context['billed_days'] ?? 1));
        $total = $this->calculateProtectionPrice($row, $context);
        $perDay = ($row['applies_per'] ?? '') === 'day' || ($row['price_type'] ?? '') === 'fixed_daily'
            ? ($days > 0 ? $total / $days : $total)
            : ($days > 0 ? $total / $days : $total);

        return [
            'code' => strtoupper((string) ($row['code'] ?? '')),
            'name' => (string) ($row['name'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'amountTotal' => round($total, 2),
            'pricePerDay' => round($perDay, 2),
            'isDefault' => !empty($row['is_default']),
            'currency' => (string) ($row['currency'] ?? 'USD'),
            'source' => 'db',
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function formatPublicExtra(array $row, array $context): array
    {
        $days = max(1, (int) ($context['rental_days'] ?? $context['billed_days'] ?? 1));
        $total = $this->calculateExtraPrice($row, 1, $context);
        $perDay = ($row['applies_per'] ?? '') === 'day' || ($row['price_type'] ?? '') === 'fixed_daily'
            ? (float) ($row['price_amount'] ?? 0)
            : null;

        return [
            'code' => strtoupper((string) ($row['code'] ?? '')),
            'name' => (string) ($row['name'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'amountTotal' => round($total, 2),
            'pricePerDay' => $perDay !== null ? round($perDay, 2) : null,
            'unitName' => ($row['applies_per'] ?? '') === 'day' ? 'day' : 'rental',
            'maxQuantity' => max(1, (int) ($row['max_quantity'] ?? 1)),
            'currency' => (string) ($row['currency'] ?? 'USD'),
            'source' => 'db',
        ];
    }

    /**
     * @param array<string, mixed> $product
     * @param array<string, mixed> $context
     */
    private function calculateProductPrice(array $product, int $quantity, array $context): float
    {
        if ($quantity <= 0) {
            return 0.0;
        }
        $days = max(1, (int) ($context['rental_days'] ?? $context['billed_days'] ?? 1));
        $rateBase = (float) ($context['rental_base'] ?? $context['final_total_rate'] ?? 0);
        $type = (string) ($product['price_type'] ?? 'fixed_total');
        $amount = (float) ($product['price_amount'] ?? 0);
        $applies = (string) ($product['applies_per'] ?? 'rental');

        if ($type === 'free') {
            return 0.0;
        }
        if ($type === 'percent_of_rate') {
            $base = $rateBase * ($amount / 100.0);

            return round($base * $quantity, 2);
        }
        if ($type === 'fixed_daily' || $applies === 'day') {
            return round($amount * $days * $quantity, 2);
        }

        return round($amount * $quantity, 2);
    }

    /**
     * @param list<array<string, mixed>> $products
     * @param array<string, mixed> $context
     * @return list<array<string, mixed>>
     */
    private function filterProducts(array $products, array $context): array
    {
        $filtered = [];
        foreach ($products as $product) {
            if ($this->productMatchesContext($product, $context)) {
                $filtered[] = $product;
            }
        }

        return $filtered;
    }

    /**
     * @param array<string, mixed> $product
     * @param array<string, mixed> $context
     */
    private function productMatchesContext(array $product, array $context): bool
    {
        $days = max(1, (int) ($context['rental_days'] ?? $context['billed_days'] ?? 1));
        $vehicleCode = strtoupper(trim((string) ($context['vehicle_code'] ?? $context['sippCode'] ?? '')));
        $vehicleName = trim((string) ($context['vehicle_name'] ?? $context['vehicle_category'] ?? ''));
        $pickup = strtoupper(trim((string) ($context['pickup_location'] ?? $context['locationCode'] ?? '')));
        $ret = strtoupper(trim((string) ($context['return_location'] ?? $context['returnLocationCode'] ?? '')));

        $minDays = $product['min_rental_days'] ?? null;
        $maxDays = $product['max_rental_days'] ?? null;
        if ($minDays !== null && $minDays !== '' && (int) $minDays > 0 && $days < (int) $minDays) {
            return false;
        }
        if ($maxDays !== null && $maxDays !== '' && (int) $maxDays > 0 && $days > (int) $maxDays) {
            return false;
        }

        if (!$this->contextValueMatches($product['vehicle_code'] ?? null, $vehicleCode)) {
            return false;
        }

        if (!$this->contextNameMatches($product['vehicle_name'] ?? null, $vehicleName)) {
            return false;
        }

        if (!$this->contextLocationMatches($product['pickup_location'] ?? null, $pickup)) {
            return false;
        }

        if (!$this->contextLocationMatches($product['return_location'] ?? null, $ret)) {
            return false;
        }

        return true;
    }

    private function contextValueMatches(?string $rule, string $value): bool
    {
        $rule = strtoupper(trim((string) $rule));
        if ($rule === '' || in_array($rule, ['*', 'ALL', 'TODOS', 'TODAS'], true)) {
            return true;
        }

        return $value === '' || $rule === $value;
    }

    private function contextNameMatches(?string $rule, string $value): bool
    {
        $rule = trim((string) $rule);
        if ($rule === '' || in_array(strtoupper($rule), ['*', 'ALL', 'TODOS', 'TODAS'], true)) {
            return true;
        }

        return $value === '' || strcasecmp($rule, $value) === 0;
    }

    private function contextLocationMatches(?string $rule, string $value): bool
    {
        $rule = strtoupper(trim((string) $rule));
        if ($rule === '' || in_array($rule, ['*', 'ALL', 'TODOS', 'TODAS', 'CUALQUIERA'], true)) {
            return true;
        }

        return $value === '' || $rule === $value;
    }

    /**
     * Busca protección por código. Si hay varias con el mismo código, elige la más específica
     * que coincida con el contexto (vehículo SIPP, categoría, sucursal, etc.).
     *
     * @param array<string, mixed> $context
     */
    private function findProtectionByCode(string $code, array $context = []): ?array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }
        $db = Database::getInstance();
        $rows = $db->select(
            'SELECT * FROM rac_protection_products WHERE UPPER(code) = :code AND enabled = 1 ORDER BY sort_order ASC, id ASC',
            [':code' => $code]
        );
        if (!is_array($rows) || $rows === []) {
            return null;
        }

        $candidates = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalized = $this->normalizeProtectionRow($row);
            if ($context === [] || $this->productMatchesContext($normalized, $context)) {
                $candidates[] = $normalized;
            }
        }
        if ($candidates === []) {
            return null;
        }

        usort($candidates, function (array $a, array $b): int {
            $score = $this->productSpecificityScore($b) <=> $this->productSpecificityScore($a);
            if ($score !== 0) {
                return $score;
            }
            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });

        return $candidates[0];
    }

    /** @param array<string, mixed> $product */
    private function productSpecificityScore(array $product): int
    {
        $score = 0;
        foreach (['vehicle_code', 'vehicle_name', 'pickup_location', 'return_location'] as $field) {
            $val = strtoupper(trim((string) ($product[$field] ?? '')));
            if ($val !== '' && !in_array($val, ['*', 'ALL', 'TODOS', 'TODAS', 'CUALQUIERA'], true)) {
                $score += 10;
            }
        }
        if (!empty($product['min_rental_days']) || !empty($product['max_rental_days'])) {
            $score += 1;
        }

        return $score;
    }

    private function findExtraByCode(string $code): ?array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }
        $db = Database::getInstance();
        $row = $db->selectOne(
            'SELECT * FROM rac_extra_products WHERE UPPER(code) = :code AND enabled = 1 LIMIT 1',
            [':code' => $code]
        );

        return $row ? $this->normalizeExtraRow($row) : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listProtections(bool $includeDisabled): array
    {
        $db = Database::getInstance();
        $sql = 'SELECT * FROM rac_protection_products';
        if (!$includeDisabled) {
            $sql .= ' WHERE enabled = 1 AND visible_public = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, name ASC';
        $rows = $db->select($sql);

        return array_map(fn($r) => $this->normalizeProtectionRow($r), $rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listExtras(bool $includeDisabled): array
    {
        $db = Database::getInstance();
        $sql = 'SELECT * FROM rac_extra_products';
        if (!$includeDisabled) {
            $sql .= ' WHERE enabled = 1 AND visible_public = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, name ASC';
        $rows = $db->select($sql);

        return array_map(fn($r) => $this->normalizeExtraRow($r), $rows);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeProtectionRow(array $row): array
    {
        $row['enabled'] = !empty($row['enabled']);
        $row['visible_public'] = !empty($row['visible_public']);
        $row['is_default'] = !empty($row['is_default']);
        $row['price_amount'] = (float) ($row['price_amount'] ?? 0);

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeExtraRow(array $row): array
    {
        $row['enabled'] = !empty($row['enabled']);
        $row['visible_public'] = !empty($row['visible_public']);
        $row['price_amount'] = (float) ($row['price_amount'] ?? 0);
        $row['max_quantity'] = max(1, (int) ($row['max_quantity'] ?? 1));

        return $row;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeProtectionPayload(array $data): array
    {
        $code = strtoupper(preg_replace('/[^A-Z0-9_\-]/', '', (string) ($data['code'] ?? '')));
        if ($code === '') {
            throw new InvalidArgumentException('Código de protección obligatorio.');
        }
        $priceType = (string) ($data['price_type'] ?? 'fixed_daily');
        if (!in_array($priceType, self::PRICE_TYPES, true)) {
            $priceType = 'fixed_daily';
        }
        $applies = (string) ($data['applies_per'] ?? 'day');
        if (!in_array($applies, self::APPLIES_PER, true)) {
            $applies = 'day';
        }

        return [
            ':code' => $code,
            ':name' => trim((string) ($data['name'] ?? '')),
            ':description' => trim((string) ($data['description'] ?? '')),
            ':enabled' => !empty($data['enabled']) ? 1 : 0,
            ':price_type' => $priceType,
            ':price_amount' => round((float) ($data['price_amount'] ?? 0), 2),
            ':currency' => strtoupper(trim((string) ($data['currency'] ?? 'USD'))) ?: 'USD',
            ':applies_per' => $applies,
            ':vehicle_code' => $this->nullableUpper($data['vehicle_code'] ?? ''),
            ':vehicle_name' => trim((string) ($data['vehicle_name'] ?? '')) ?: null,
            ':min_rental_days' => $this->nullableInt($data['min_rental_days'] ?? null),
            ':max_rental_days' => $this->nullableInt($data['max_rental_days'] ?? null),
            ':pickup_location' => $this->nullableUpper($data['pickup_location'] ?? ''),
            ':return_location' => $this->nullableUpper($data['return_location'] ?? ''),
            ':sort_order' => (int) ($data['sort_order'] ?? 100),
            ':visible_public' => !empty($data['visible_public']) ? 1 : 0,
            ':is_default' => !empty($data['is_default']) ? 1 : 0,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeExtraPayload(array $data): array
    {
        $code = strtoupper(preg_replace('/[^A-Z0-9_\-]/', '', (string) ($data['code'] ?? '')));
        if ($code === '') {
            throw new InvalidArgumentException('Código de extra obligatorio.');
        }
        $priceType = (string) ($data['price_type'] ?? 'fixed_total');
        if (!in_array($priceType, self::PRICE_TYPES, true)) {
            $priceType = 'fixed_total';
        }
        $applies = (string) ($data['applies_per'] ?? 'rental');
        if (!in_array($applies, self::APPLIES_PER, true)) {
            $applies = 'rental';
        }
        // Alinear cobro diario/fijo: el tipo de precio manda.
        if ($priceType === 'fixed_daily') {
            $applies = 'day';
        } elseif ($priceType === 'fixed_total' || $priceType === 'free') {
            $applies = 'rental';
        }

        return [
            ':code' => $code,
            ':name' => trim((string) ($data['name'] ?? '')),
            ':description' => trim((string) ($data['description'] ?? '')),
            ':enabled' => !empty($data['enabled']) ? 1 : 0,
            ':price_type' => $priceType,
            ':price_amount' => round((float) ($data['price_amount'] ?? 0), 2),
            ':currency' => strtoupper(trim((string) ($data['currency'] ?? 'USD'))) ?: 'USD',
            ':applies_per' => $applies,
            ':max_quantity' => max(1, (int) ($data['max_quantity'] ?? 1)),
            ':vehicle_code' => $this->nullableUpper($data['vehicle_code'] ?? ''),
            ':vehicle_name' => trim((string) ($data['vehicle_name'] ?? '')) ?: null,
            ':min_rental_days' => $this->nullableInt($data['min_rental_days'] ?? null),
            ':max_rental_days' => $this->nullableInt($data['max_rental_days'] ?? null),
            ':pickup_location' => $this->nullableUpper($data['pickup_location'] ?? ''),
            ':return_location' => $this->nullableUpper($data['return_location'] ?? ''),
            ':sort_order' => (int) ($data['sort_order'] ?? 100),
            ':visible_public' => !empty($data['visible_public']) ? 1 : 0,
        ];
    }

    private function nullableUpper($value): ?string
    {
        $v = strtoupper(trim((string) $value));

        return $v !== '' ? $v : null;
    }

    private function nullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
