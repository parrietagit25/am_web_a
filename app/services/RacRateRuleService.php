<?php
/**
 * Reglas comerciales RAC sobre tarifas base BARS.
 * AM-RAC-BARS-PRICING-2A
 */

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/RacBarsDatabaseSchema.php';
require_once __DIR__ . '/AdminUserService.php';

class RacRateRuleService
{
    private const TZ = 'America/Panama';

    /** @var list<string> */
    public const BADGE_TYPES = ['promo', 'featured', 'recommended', 'popular', 'custom'];

    /** @var array<string, string> */
    public const BADGE_DEFAULT_LABELS = [
        'promo' => 'Promo',
        'featured' => 'Destacado',
        'recommended' => 'Recomendado',
        'popular' => 'Más buscado',
        'custom' => 'Personalizado',
    ];

    /** @var list<string> */
    public const RULE_TYPES = ['seasonal', 'promotion', 'category_override', 'long_rental', 'manual', 'corporate', 'other'];

    /** @var list<string> */
    public const ADJUSTMENT_TYPES = [
        'percent_discount',
        'percent_surcharge',
        'fixed_daily_rate',
        'fixed_total_rate',
        'amount_discount_daily',
        'amount_surcharge_daily',
    ];

    public function __construct()
    {
        RacBarsDatabaseSchema::ensure();
    }

    public static function calculateRentalDays(string $pickupDateTime, string $returnDateTime): int
    {
        if ($pickupDateTime === '' || $returnDateTime === '') {
            return 1;
        }
        try {
            $pickup = new DateTime($pickupDateTime);
            $return = new DateTime($returnDateTime);
            $seconds = max(0, $return->getTimestamp() - $pickup->getTimestamp());
            $hours = $seconds / 3600;
            $days = (int) ceil($hours / 24);

            return max(1, $days);
        } catch (Exception $e) {
            return 1;
        }
    }

    /**
     * @param array<string, mixed> $context
     * @return list<array<string, mixed>>
     */
    public function getActiveRules(array $context = []): array
    {
        $db = Database::getInstance();
        $rows = $db->select(
            'SELECT * FROM rac_rate_rules WHERE enabled = 1 ORDER BY priority ASC, id ASC'
        );

        $rules = [];
        foreach ($rows as $row) {
            $rule = $this->hydrateRuleRow($row);
            $rule['targets'] = $this->getRuleTargets((int) $rule['id']);
            $rules[] = $rule;
        }

        return $rules;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRules(bool $includeDisabled = true): array
    {
        $db = Database::getInstance();
        $sql = 'SELECT * FROM rac_rate_rules';
        if (!$includeDisabled) {
            $sql .= ' WHERE enabled = 1';
        }
        $sql .= ' ORDER BY priority ASC, id ASC';
        $rows = $db->select($sql);
        $rules = [];
        foreach ($rows as $row) {
            $rule = $this->hydrateRuleRow($row);
            $rule['targets'] = $this->getRuleTargets((int) $rule['id']);
            $rules[] = $rule;
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRule(int $id): ?array
    {
        $db = Database::getInstance();
        $row = $db->selectOne('SELECT * FROM rac_rate_rules WHERE id = :id', [':id' => $id]);
        if (!is_array($row)) {
            return null;
        }
        $rule = $this->hydrateRuleRow($row);
        $rule['targets'] = $this->getRuleTargets($id);

        return $rule;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{enabled: bool, type: string, text: string}
     */
    public static function normalizeVisualBadgeConfig(array $data): array
    {
        $enabled = filter_var($data['badge_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $type = strtolower(trim((string) ($data['badge_type'] ?? 'promo')));
        if (!in_array($type, self::BADGE_TYPES, true)) {
            throw new InvalidArgumentException('El tipo visual de la etiqueta no es válido.');
        }

        $text = self::normalizeVisualBadgeText((string) ($data['badge_text'] ?? ''));
        if ($enabled && $text === '') {
            $text = self::BADGE_DEFAULT_LABELS[$type];
        }

        return [
            'enabled' => $enabled,
            'type' => $type,
            'text' => $text,
        ];
    }

    public static function normalizeVisualBadgeText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        $length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
        if ($length > 60) {
            throw new InvalidArgumentException('El texto de la etiqueta no puede superar 60 caracteres.');
        }
        if (preg_match('/[\r\n]/u', $text)
            || strip_tags($text) !== $text
            || preg_match('/javascript\s*:|(?:^|\s)on[a-z]+\s*=/iu', $text)) {
            throw new InvalidArgumentException('El texto de la etiqueta contiene contenido no permitido.');
        }

        return $text;
    }

    /**
     * Solo resuelve metadata visual de reglas que ya fueron aplicadas por el motor.
     *
     * @param list<int> $ruleIds
     * @return array<int, array{enabled: bool, type: string, text: string}>
     */
    public function getVisualBadgeMap(array $ruleIds, string $pickupDateTime = ''): array
    {
        $ruleIds = array_values(array_unique(array_filter(array_map('intval', $ruleIds), static fn (int $id): bool => $id > 0)));
        if ($ruleIds === []) {
            return [];
        }

        $params = [];
        $placeholders = [];
        foreach ($ruleIds as $index => $ruleId) {
            $placeholder = ':rule_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $ruleId;
        }

        $rows = Database::getInstance()->select(
            'SELECT * FROM rac_rate_rules WHERE id IN (' . implode(', ', $placeholders) . ')',
            $params
        );
        $map = [];
        foreach ($rows as $row) {
            $rule = $this->hydrateRuleRow($row);
            if (!$rule['enabled']
                || !$rule['badge_enabled']
                || !$this->dateInValidity($pickupDateTime, $rule['valid_from'], $rule['valid_to'])) {
                continue;
            }
            $map[$rule['id']] = [
                'enabled' => true,
                'type' => $rule['badge_type'],
                'text' => $rule['badge_text'],
            ];
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $data
     * @param list<array<string, string>> $targets
     */
    public function createRule(array $data, array $targets): int
    {
        $this->validateRuleData($data, $targets);
        $db = Database::getInstance();
        $userId = $this->currentAdminUserId();
        $now = self::nowString();

        $db->execute(
            'INSERT INTO rac_rate_rules (
                name, description, enabled, priority, stackable, stop_processing, rule_type,
                adjustment_type, adjustment_value, currency, valid_from, valid_to, days_of_week_json,
                min_rental_days, max_rental_days, pickup_location, return_location, rate_qualifier,
                applies_to, badge_enabled, badge_text, badge_type, created_by, updated_by, updated_at
            ) VALUES (
                :name, :description, :enabled, :priority, :stackable, :stop_processing, :rule_type,
                :adjustment_type, :adjustment_value, :currency, :valid_from, :valid_to, :days_of_week_json,
                :min_rental_days, :max_rental_days, :pickup_location, :return_location, :rate_qualifier,
                :applies_to, :badge_enabled, :badge_text, :badge_type, :created_by, :updated_by, :updated_at
            )',
            $this->ruleBindParams($data, $userId, $now, true)
        );

        $ruleId = (int) $db->lastInsertId();
        $this->replaceRuleTargets($ruleId, $targets);
        $rule = $this->getRule($ruleId);
        $this->auditLog($ruleId, 'create', null, $rule, $userId);

        return $ruleId;
    }

    /**
     * @param array<string, mixed> $data
     * @param list<array<string, string>> $targets
     */
    public function updateRule(int $id, array $data, array $targets): void
    {
        $before = $this->getRule($id);
        if ($before === null) {
            throw new InvalidArgumentException('Regla no encontrada.');
        }
        $this->validateRuleData($data, $targets);
        $db = Database::getInstance();
        $userId = $this->currentAdminUserId();
        $now = self::nowString();

        $params = $this->ruleBindParams($data, $userId, $now, false);
        $params[':id'] = $id;
        $db->execute(
            'UPDATE rac_rate_rules SET
                name = :name, description = :description, enabled = :enabled, priority = :priority,
                stackable = :stackable, stop_processing = :stop_processing, rule_type = :rule_type,
                adjustment_type = :adjustment_type, adjustment_value = :adjustment_value, currency = :currency,
                valid_from = :valid_from, valid_to = :valid_to, days_of_week_json = :days_of_week_json,
                min_rental_days = :min_rental_days, max_rental_days = :max_rental_days,
                pickup_location = :pickup_location, return_location = :return_location,
                rate_qualifier = :rate_qualifier, applies_to = :applies_to,
                badge_enabled = :badge_enabled, badge_text = :badge_text, badge_type = :badge_type,
                updated_by = :updated_by, updated_at = :updated_at
             WHERE id = :id',
            $params
        );

        $this->replaceRuleTargets($id, $targets);
        $after = $this->getRule($id);
        $this->auditLog($id, 'update', $before, $after, $userId);
    }

    public function enableRule(int $id): void
    {
        $this->setRuleEnabled($id, true);
    }

    public function disableRule(int $id): void
    {
        $this->setRuleEnabled($id, false);
    }

    /**
     * @param array<string, mixed> $rate
     * @param array<string, mixed> $context
     */
    public function ruleAppliesToRate(array $rule, array $rate, array $context): bool
    {
        if (empty($rule['enabled'])) {
            return false;
        }

        $rentalDays = (int) ($context['rental_days'] ?? self::calculateRentalDays(
            (string) ($rate['pickup_datetime'] ?? $context['pickup_datetime'] ?? ''),
            (string) ($rate['return_datetime'] ?? $context['return_datetime'] ?? '')
        ));

        if ($rule['min_rental_days'] !== null && $rentalDays < (int) $rule['min_rental_days']) {
            return false;
        }
        if ($rule['max_rental_days'] !== null && $rentalDays > (int) $rule['max_rental_days']) {
            return false;
        }

        $pickupLocation = strtoupper(trim((string) ($rate['pickup_location'] ?? $context['pickup_location'] ?? '')));
        $returnLocation = strtoupper(trim((string) ($rate['return_location'] ?? $context['return_location'] ?? '')));
        $rateQualifier = strtoupper(trim((string) ($rate['rate_qualifier'] ?? $context['rate_qualifier'] ?? 'WEB')));

        if ($rule['pickup_location'] !== null && $rule['pickup_location'] !== '' && strtoupper($rule['pickup_location']) !== $pickupLocation) {
            return false;
        }
        if ($rule['return_location'] !== null && $rule['return_location'] !== '' && strtoupper($rule['return_location']) !== $returnLocation) {
            return false;
        }
        if ($rule['rate_qualifier'] !== null && $rule['rate_qualifier'] !== '' && strtoupper($rule['rate_qualifier']) !== $rateQualifier) {
            return false;
        }

        $pickupDt = (string) ($rate['pickup_datetime'] ?? $context['pickup_datetime'] ?? '');
        if (!$this->dateInValidity($pickupDt, $rule['valid_from'], $rule['valid_to'])) {
            return false;
        }

        if (!$this->dayOfWeekMatches($pickupDt, $rule['days_of_week'])) {
            return false;
        }

        return $this->targetMatches($rule, $rate);
    }

    /**
     * @param array<string, mixed> $rate
     * @param list<array<string, mixed>> $rules
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function applyRulesToRate(array $rate, array $rules, array $context): array
    {
        $baseDaily = max(0, (float) ($rate['daily_rate'] ?? $rate['base_daily_rate'] ?? 0));
        $baseTotal = max(0, (float) ($rate['total_rate'] ?? $rate['base_total_rate'] ?? 0));
        $rentalDays = (int) ($context['rental_days'] ?? self::calculateRentalDays(
            (string) ($rate['pickup_datetime'] ?? ''),
            (string) ($rate['return_datetime'] ?? '')
        ));

        $currentDaily = $baseDaily;
        $currentTotal = $baseTotal;
        /** @var list<array<string, mixed>> $appliedRules */
        $appliedRules = [];
        $notes = [];
        $stopped = false;

        if ($baseDaily === 0.0 && !empty($rate['available'])) {
            $notes[] = 'BARS devolvió tarifa base 0.00.';
        }

        foreach ($rules as $rule) {
            if ($stopped) {
                break;
            }
            if (!$this->ruleAppliesToRate($rule, $rate, $context)) {
                continue;
            }

            $beforeDaily = $currentDaily;
            $inputDaily = !empty($rule['stackable']) ? $currentDaily : $baseDaily;
            $adjusted = $this->applyAdjustment($inputDaily, $baseDaily, $baseTotal, $rentalDays, $rule);
            $currentDaily = $adjusted['daily'];
            $currentTotal = $adjusted['total'];

            $discountDaily = max(0, round($beforeDaily - $currentDaily, 2));
            $surchargeDaily = max(0, round($currentDaily - $beforeDaily, 2));

            $appliedRules[] = [
                'rule_id' => (int) $rule['id'],
                'name' => (string) $rule['name'],
                'adjustment_type' => (string) $rule['adjustment_type'],
                'adjustment_value' => (float) $rule['adjustment_value'],
                'before_daily_rate' => round($beforeDaily, 2),
                'after_daily_rate' => round($currentDaily, 2),
                'discount_amount' => $discountDaily,
                'surcharge_amount' => $surchargeDaily,
            ];

            if (empty($rule['stackable']) || !empty($rule['stop_processing'])) {
                if (!empty($rule['stop_processing'])) {
                    $stopped = true;
                }
            }
        }

        $finalDaily = round(max(0, $currentDaily), 2);
        $finalTotal = round(max(0, $currentTotal), 2);

        return [
            'base_daily_rate' => round($baseDaily, 2),
            'base_total_rate' => round($baseTotal, 2),
            'final_daily_rate' => $finalDaily,
            'final_total_rate' => $finalTotal,
            'rental_days' => $rentalDays,
            'discount_amount_daily' => round(max(0, $baseDaily - $finalDaily), 2),
            'discount_amount_total' => round(max(0, $baseTotal - $finalTotal), 2),
            'surcharge_amount_daily' => round(max(0, $finalDaily - $baseDaily), 2),
            'surcharge_amount_total' => round(max(0, $finalTotal - $baseTotal), 2),
            'applied_rules' => $appliedRules,
            'calculation_notes' => implode(' ', $notes),
        ];
    }

    /**
     * @return array{ok: bool, cache_key: string, calculated: int, errors: list<string>}
     */
    public function recalculateCacheKey(string $cacheKey): array
    {
        $db = Database::getInstance();
        $rates = $db->select('SELECT * FROM rac_bars_rates WHERE cache_key = :cache_key ORDER BY vehicle_code ASC', [
            ':cache_key' => $cacheKey,
        ]);

        if ($rates === []) {
            return ['ok' => true, 'cache_key' => $cacheKey, 'calculated' => 0, 'errors' => []];
        }

        $first = $rates[0];
        $context = $this->buildContextFromRateRow($first);
        $rules = $this->getActiveRules($context);
        $calculated = 0;
        $errors = [];

        foreach ($rates as $row) {
            try {
                $rate = $this->hydrateBaseRateRow($row);
                $result = $this->applyRulesToRate($rate, $rules, $context);
                $this->upsertCalculatedRate($row, $result);
                $calculated++;
            } catch (Exception $e) {
                $errors[] = (string) ($row['vehicle_code'] ?? '') . ': ' . $e->getMessage();
            }
        }

        $this->auditLog(null, 'recalculate', null, ['cache_key' => $cacheKey, 'calculated' => $calculated], $this->currentAdminUserId());

        return ['ok' => $errors === [], 'cache_key' => $cacheKey, 'calculated' => $calculated, 'errors' => $errors];
    }

    /**
     * @return array{ok: bool, calculated: int, cache_keys: list<string>}
     */
    public function recalculateAllActive(): array
    {
        $db = Database::getInstance();
        $rows = $db->select('SELECT DISTINCT cache_key FROM rac_bars_rates ORDER BY cache_key ASC');
        $total = 0;
        $keys = [];
        foreach ($rows as $row) {
            $key = (string) ($row['cache_key'] ?? '');
            if ($key === '') {
                continue;
            }
            $result = $this->recalculateCacheKey($key);
            $total += (int) ($result['calculated'] ?? 0);
            $keys[] = $key;
        }

        return ['ok' => true, 'calculated' => $total, 'cache_keys' => $keys];
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function getCalculatedRates(array $filters): array
    {
        $cacheKey = (string) ($filters['cache_key'] ?? '');
        if ($cacheKey === '' && !empty($filters['pickup_datetime'])) {
            require_once __DIR__ . '/BarsRateCacheService.php';
            $cacheKey = BarsRateCacheService::buildCacheKey(
                (string) ($filters['pickup_location'] ?? 'PTY'),
                (string) ($filters['return_location'] ?? 'PTY'),
                BarsRateCacheService::normalizeOtaDatetime((string) $filters['pickup_datetime']),
                BarsRateCacheService::normalizeOtaDatetime((string) ($filters['return_datetime'] ?? '')),
                (string) ($filters['rate_qualifier'] ?? 'WEB')
            );
        }
        if ($cacheKey === '') {
            return [];
        }

        $db = Database::getInstance();
        $rows = $db->select(
            'SELECT * FROM rac_calculated_rates WHERE cache_key = :cache_key ORDER BY available DESC, vehicle_name ASC, vehicle_code ASC',
            [':cache_key' => $cacheKey]
        );

        return array_map([$this, 'hydrateCalculatedRow'], $rows);
    }

    /**
     * @param array<string, mixed> $ruleData
     * @param list<array<string, string>> $targets
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function previewRule(array $ruleData, array $targets, array $filters): array
    {
        $rule = array_merge([
            'id' => 0,
            'enabled' => 1,
            'targets' => $targets,
            'days_of_week' => self::parseDaysOfWeek($ruleData['days_of_week'] ?? $ruleData['days_of_week_json'] ?? null),
        ], $ruleData);

        require_once __DIR__ . '/BarsRateCacheService.php';
        $cache = new BarsRateCacheService();
        $baseRates = $cache->getLatestRates($filters);
        if ($baseRates === []) {
            return [];
        }

        $context = $this->buildContextFromRateRow([
            'pickup_location' => $filters['pickup_location'] ?? 'PTY',
            'return_location' => $filters['return_location'] ?? 'PTY',
            'pickup_datetime' => BarsRateCacheService::normalizeOtaDatetime((string) ($filters['pickup_datetime'] ?? '')),
            'return_datetime' => BarsRateCacheService::normalizeOtaDatetime((string) ($filters['return_datetime'] ?? '')),
            'rate_qualifier' => $filters['rate_qualifier'] ?? 'WEB',
        ]);

        $preview = [];
        foreach ($baseRates as $rate) {
            if (!$this->ruleAppliesToRate(array_merge($rule, ['targets' => $targets]), $rate, $context)) {
                continue;
            }
            $calc = $this->applyRulesToRate($rate, [array_merge($rule, ['targets' => $targets])], $context);
            $preview[] = [
                'vehicle_code' => $rate['vehicle_code'],
                'vehicle_name' => $rate['vehicle_name'],
                'base_daily_rate' => $calc['base_daily_rate'],
                'final_daily_rate' => $calc['final_daily_rate'],
                'difference_daily' => round($calc['final_daily_rate'] - $calc['base_daily_rate'], 2),
                'applied_rules' => $calc['applied_rules'],
            ];
        }

        return $preview;
    }

    /**
     * @param array<string, mixed> $rule
     * @return array{daily: float, total: float}
     */
    private function applyAdjustment(float $inputDaily, float $baseDaily, float $baseTotal, int $rentalDays, array $rule): array
    {
        $value = (float) ($rule['adjustment_value'] ?? 0);
        $type = (string) ($rule['adjustment_type'] ?? '');
        $daily = $inputDaily;
        $total = $inputDaily * max(1, $rentalDays);

        switch ($type) {
            case 'percent_discount':
                $daily = $inputDaily * (1 - ($value / 100));
                $total = $daily * max(1, $rentalDays);
                break;
            case 'percent_surcharge':
                $daily = $inputDaily * (1 + ($value / 100));
                $total = $daily * max(1, $rentalDays);
                break;
            case 'fixed_daily_rate':
                $daily = $value;
                $total = $daily * max(1, $rentalDays);
                break;
            case 'fixed_total_rate':
                $total = $value;
                $daily = $total / max(1, $rentalDays);
                break;
            case 'amount_discount_daily':
                $daily = $inputDaily - $value;
                $total = $daily * max(1, $rentalDays);
                break;
            case 'amount_surcharge_daily':
                $daily = $inputDaily + $value;
                $total = $daily * max(1, $rentalDays);
                break;
        }

        return [
            'daily' => round(max(0, $daily), 2),
            'total' => round(max(0, $total), 2),
        ];
    }

    /**
     * @param array<string, mixed> $rule
     * @param array<string, mixed> $rate
     */
    private function targetMatches(array $rule, array $rate): bool
    {
        $targets = $rule['targets'] ?? [];
        if ($targets === []) {
            return ($rule['applies_to'] ?? 'all') === 'all';
        }

        $code = strtoupper(trim((string) ($rate['vehicle_code'] ?? '')));
        $name = strtoupper(trim((string) ($rate['vehicle_name'] ?? '')));

        foreach ($targets as $target) {
            $type = (string) ($target['target_type'] ?? '');
            $value = strtoupper(trim((string) ($target['target_value'] ?? '')));
            if ($type === 'all' || $value === '*' || $value === '') {
                return true;
            }
            if ($type === 'vehicle_code' && $value === $code) {
                return true;
            }
            if ($type === 'vehicle_name' && ($value === $name || str_contains($name, $value))) {
                return true;
            }
            if ($type === 'location' && ($value === strtoupper((string) ($rate['pickup_location'] ?? '')) || $value === strtoupper((string) ($rate['return_location'] ?? '')))) {
                return true;
            }
        }

        return false;
    }

    private function dateInValidity(string $pickupDateTime, ?string $validFrom, ?string $validTo): bool
    {
        if ($pickupDateTime === '') {
            return true;
        }
        try {
            $pickupDate = (new DateTime($pickupDateTime))->format('Y-m-d');
        } catch (Exception $e) {
            return true;
        }
        if ($validFrom !== null && $validFrom !== '' && $pickupDate < $validFrom) {
            return false;
        }
        if ($validTo !== null && $validTo !== '' && $pickupDate > $validTo) {
            return false;
        }

        return true;
    }

    /**
     * @param list<int|string>|null $daysOfWeek
     */
    private function dayOfWeekMatches(string $pickupDateTime, ?array $daysOfWeek): bool
    {
        if ($daysOfWeek === null || $daysOfWeek === []) {
            return true;
        }
        try {
            $dow = (int) (new DateTime($pickupDateTime))->format('w');
        } catch (Exception $e) {
            return true;
        }
        $normalized = array_map(static fn($d) => (int) $d, $daysOfWeek);

        return in_array($dow, $normalized, true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getRuleTargets(int $ruleId): array
    {
        $db = Database::getInstance();
        $rows = $db->select('SELECT * FROM rac_rate_rule_targets WHERE rule_id = :rule_id ORDER BY id ASC', [
            ':rule_id' => $ruleId,
        ]);

        return array_map(static fn(array $row): array => [
            'id' => (int) ($row['id'] ?? 0),
            'target_type' => (string) ($row['target_type'] ?? ''),
            'target_value' => (string) ($row['target_value'] ?? ''),
        ], $rows);
    }

    /**
     * @param list<array<string, string>> $targets
     */
    private function replaceRuleTargets(int $ruleId, array $targets): void
    {
        $db = Database::getInstance();
        $db->execute('DELETE FROM rac_rate_rule_targets WHERE rule_id = :rule_id', [':rule_id' => $ruleId]);
        foreach ($targets as $target) {
            $type = trim((string) ($target['target_type'] ?? ''));
            $value = trim((string) ($target['target_value'] ?? ''));
            if ($type === '') {
                continue;
            }
            if ($type === 'all') {
                $value = '*';
            }
            $db->execute(
                'INSERT INTO rac_rate_rule_targets (rule_id, target_type, target_value) VALUES (:rule_id, :target_type, :target_value)',
                [':rule_id' => $ruleId, ':target_type' => $type, ':target_value' => $value]
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param list<array<string, string>> $targets
     */
    private function validateRuleData(array $data, array $targets): void
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('El nombre de la regla es obligatorio.');
        }

        $adjustmentType = (string) ($data['adjustment_type'] ?? '');
        if (!in_array($adjustmentType, self::ADJUSTMENT_TYPES, true)) {
            throw new InvalidArgumentException('Tipo de ajuste inválido.');
        }

        $value = (float) ($data['adjustment_value'] ?? 0);
        if ($value < 0) {
            throw new InvalidArgumentException('El valor de ajuste no puede ser negativo.');
        }
        if (in_array($adjustmentType, ['percent_discount', 'percent_surcharge'], true) && $value > 100) {
            throw new InvalidArgumentException('El porcentaje debe estar entre 0 y 100.');
        }

        $validFrom = trim((string) ($data['valid_from'] ?? ''));
        $validTo = trim((string) ($data['valid_to'] ?? ''));
        if ($validFrom !== '' && $validTo !== '' && $validTo < $validFrom) {
            throw new InvalidArgumentException('valid_to debe ser mayor o igual a valid_from.');
        }

        $minDays = $data['min_rental_days'] ?? null;
        $maxDays = $data['max_rental_days'] ?? null;
        if ($minDays !== null && $minDays !== '' && $maxDays !== null && $maxDays !== '' && (int) $maxDays < (int) $minDays) {
            throw new InvalidArgumentException('max_rental_days debe ser mayor o igual a min_rental_days.');
        }

        if ($targets === []) {
            throw new InvalidArgumentException('Debe definir al menos un target.');
        }
    }

    private function setRuleEnabled(int $id, bool $enabled): void
    {
        $before = $this->getRule($id);
        if ($before === null) {
            throw new InvalidArgumentException('Regla no encontrada.');
        }
        $db = Database::getInstance();
        $userId = $this->currentAdminUserId();
        $db->execute(
            'UPDATE rac_rate_rules SET enabled = :enabled, updated_by = :updated_by, updated_at = :updated_at WHERE id = :id',
            [':enabled' => $enabled ? 1 : 0, ':updated_by' => $userId, ':updated_at' => self::nowString(), ':id' => $id]
        );
        $after = $this->getRule($id);
        $this->auditLog($id, $enabled ? 'enable' : 'disable', $before, $after, $userId);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $result
     */
    private function upsertCalculatedRate(array $row, array $result): void
    {
        $db = Database::getInstance();
        $driver = $db->getDriverName();
        $now = self::nowString();
        $params = [
            ':source_rate_id' => (int) ($row['id'] ?? 0),
            ':snapshot_id' => isset($row['snapshot_id']) ? (int) $row['snapshot_id'] : null,
            ':cache_key' => (string) ($row['cache_key'] ?? ''),
            ':vehicle_code' => strtoupper((string) ($row['vehicle_code'] ?? '')),
            ':vehicle_name' => (string) ($row['vehicle_name'] ?? ''),
            ':available' => !empty($row['available']) ? 1 : 0,
            ':currency' => (string) ($row['currency'] ?? 'USD'),
            ':base_daily_rate' => $result['base_daily_rate'],
            ':base_total_rate' => $result['base_total_rate'],
            ':final_daily_rate' => $result['final_daily_rate'],
            ':final_total_rate' => $result['final_total_rate'],
            ':rental_days' => (int) $result['rental_days'],
            ':discount_amount_daily' => $result['discount_amount_daily'],
            ':discount_amount_total' => $result['discount_amount_total'],
            ':surcharge_amount_daily' => $result['surcharge_amount_daily'],
            ':surcharge_amount_total' => $result['surcharge_amount_total'],
            ':applied_rules_json' => json_encode($result['applied_rules'], JSON_UNESCAPED_UNICODE),
            ':calculation_notes' => (string) ($result['calculation_notes'] ?? ''),
            ':pickup_location' => (string) ($row['pickup_location'] ?? ''),
            ':return_location' => (string) ($row['return_location'] ?? ''),
            ':pickup_datetime' => (string) ($row['pickup_datetime'] ?? ''),
            ':return_datetime' => (string) ($row['return_datetime'] ?? ''),
            ':rate_qualifier' => (string) ($row['rate_qualifier'] ?? 'WEB'),
            ':calculated_at' => $now,
            ':updated_at' => $now,
        ];

        if ($driver === 'mysql') {
            $db->execute(
                'INSERT INTO rac_calculated_rates (
                    source_rate_id, snapshot_id, cache_key, vehicle_code, vehicle_name, available, currency,
                    base_daily_rate, base_total_rate, final_daily_rate, final_total_rate, rental_days,
                    discount_amount_daily, discount_amount_total, surcharge_amount_daily, surcharge_amount_total,
                    applied_rules_json, calculation_notes, pickup_location, return_location,
                    pickup_datetime, return_datetime, rate_qualifier, calculated_at, updated_at
                ) VALUES (
                    :source_rate_id, :snapshot_id, :cache_key, :vehicle_code, :vehicle_name, :available, :currency,
                    :base_daily_rate, :base_total_rate, :final_daily_rate, :final_total_rate, :rental_days,
                    :discount_amount_daily, :discount_amount_total, :surcharge_amount_daily, :surcharge_amount_total,
                    :applied_rules_json, :calculation_notes, :pickup_location, :return_location,
                    :pickup_datetime, :return_datetime, :rate_qualifier, :calculated_at, :updated_at
                ) ON DUPLICATE KEY UPDATE
                    source_rate_id = VALUES(source_rate_id),
                    snapshot_id = VALUES(snapshot_id),
                    vehicle_name = VALUES(vehicle_name),
                    available = VALUES(available),
                    currency = VALUES(currency),
                    base_daily_rate = VALUES(base_daily_rate),
                    base_total_rate = VALUES(base_total_rate),
                    final_daily_rate = VALUES(final_daily_rate),
                    final_total_rate = VALUES(final_total_rate),
                    rental_days = VALUES(rental_days),
                    discount_amount_daily = VALUES(discount_amount_daily),
                    discount_amount_total = VALUES(discount_amount_total),
                    surcharge_amount_daily = VALUES(surcharge_amount_daily),
                    surcharge_amount_total = VALUES(surcharge_amount_total),
                    applied_rules_json = VALUES(applied_rules_json),
                    calculation_notes = VALUES(calculation_notes),
                    pickup_location = VALUES(pickup_location),
                    return_location = VALUES(return_location),
                    pickup_datetime = VALUES(pickup_datetime),
                    return_datetime = VALUES(return_datetime),
                    rate_qualifier = VALUES(rate_qualifier),
                    calculated_at = VALUES(calculated_at),
                    updated_at = VALUES(updated_at)',
                $params
            );

            return;
        }

        $db->execute(
            'INSERT INTO rac_calculated_rates (
                source_rate_id, snapshot_id, cache_key, vehicle_code, vehicle_name, available, currency,
                base_daily_rate, base_total_rate, final_daily_rate, final_total_rate, rental_days,
                discount_amount_daily, discount_amount_total, surcharge_amount_daily, surcharge_amount_total,
                applied_rules_json, calculation_notes, pickup_location, return_location,
                pickup_datetime, return_datetime, rate_qualifier, calculated_at, updated_at
            ) VALUES (
                :source_rate_id, :snapshot_id, :cache_key, :vehicle_code, :vehicle_name, :available, :currency,
                :base_daily_rate, :base_total_rate, :final_daily_rate, :final_total_rate, :rental_days,
                :discount_amount_daily, :discount_amount_total, :surcharge_amount_daily, :surcharge_amount_total,
                :applied_rules_json, :calculation_notes, :pickup_location, :return_location,
                :pickup_datetime, :return_datetime, :rate_qualifier, :calculated_at, :updated_at
            ) ON CONFLICT(cache_key, vehicle_code) DO UPDATE SET
                source_rate_id = excluded.source_rate_id,
                snapshot_id = excluded.snapshot_id,
                vehicle_name = excluded.vehicle_name,
                available = excluded.available,
                currency = excluded.currency,
                base_daily_rate = excluded.base_daily_rate,
                base_total_rate = excluded.base_total_rate,
                final_daily_rate = excluded.final_daily_rate,
                final_total_rate = excluded.final_total_rate,
                rental_days = excluded.rental_days,
                discount_amount_daily = excluded.discount_amount_daily,
                discount_amount_total = excluded.discount_amount_total,
                surcharge_amount_daily = excluded.surcharge_amount_daily,
                surcharge_amount_total = excluded.surcharge_amount_total,
                applied_rules_json = excluded.applied_rules_json,
                calculation_notes = excluded.calculation_notes,
                pickup_location = excluded.pickup_location,
                return_location = excluded.return_location,
                pickup_datetime = excluded.pickup_datetime,
                return_datetime = excluded.return_datetime,
                rate_qualifier = excluded.rate_qualifier,
                calculated_at = excluded.calculated_at,
                updated_at = excluded.updated_at',
            $params
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function buildContextFromRateRow(array $row): array
    {
        $pickup = (string) ($row['pickup_datetime'] ?? '');
        $return = (string) ($row['return_datetime'] ?? '');

        return [
            'pickup_location' => (string) ($row['pickup_location'] ?? ''),
            'return_location' => (string) ($row['return_location'] ?? ''),
            'pickup_datetime' => $pickup,
            'return_datetime' => $return,
            'rate_qualifier' => (string) ($row['rate_qualifier'] ?? 'WEB'),
            'rental_days' => self::calculateRentalDays($pickup, $return),
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateBaseRateRow(array $row): array
    {
        return [
            'vehicle_code' => (string) ($row['vehicle_code'] ?? ''),
            'vehicle_name' => (string) ($row['vehicle_name'] ?? ''),
            'available' => !empty($row['available']),
            'currency' => (string) ($row['currency'] ?? 'USD'),
            'daily_rate' => $row['daily_rate'],
            'total_rate' => $row['total_rate'],
            'pickup_location' => (string) ($row['pickup_location'] ?? ''),
            'return_location' => (string) ($row['return_location'] ?? ''),
            'pickup_datetime' => (string) ($row['pickup_datetime'] ?? ''),
            'return_datetime' => (string) ($row['return_datetime'] ?? ''),
            'rate_qualifier' => (string) ($row['rate_qualifier'] ?? 'WEB'),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateRuleRow(array $row): array
    {
        try {
            $badge = self::normalizeVisualBadgeConfig([
                'badge_enabled' => $row['badge_enabled'] ?? false,
                'badge_text' => $row['badge_text'] ?? '',
                'badge_type' => $row['badge_type'] ?? 'promo',
            ]);
        } catch (InvalidArgumentException $e) {
            $badge = ['enabled' => false, 'type' => 'promo', 'text' => ''];
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'enabled' => !empty($row['enabled']),
            'priority' => (int) ($row['priority'] ?? 100),
            'stackable' => !empty($row['stackable']),
            'stop_processing' => !empty($row['stop_processing']),
            'rule_type' => (string) ($row['rule_type'] ?? 'promotion'),
            'adjustment_type' => (string) ($row['adjustment_type'] ?? ''),
            'adjustment_value' => (float) ($row['adjustment_value'] ?? 0),
            'currency' => (string) ($row['currency'] ?? 'USD'),
            'valid_from' => $row['valid_from'] ?? null,
            'valid_to' => $row['valid_to'] ?? null,
            'days_of_week' => self::parseDaysOfWeek($row['days_of_week_json'] ?? null),
            'min_rental_days' => isset($row['min_rental_days']) && $row['min_rental_days'] !== '' ? (int) $row['min_rental_days'] : null,
            'max_rental_days' => isset($row['max_rental_days']) && $row['max_rental_days'] !== '' ? (int) $row['max_rental_days'] : null,
            'pickup_location' => $row['pickup_location'] ?? null,
            'return_location' => $row['return_location'] ?? null,
            'rate_qualifier' => $row['rate_qualifier'] ?? null,
            'applies_to' => (string) ($row['applies_to'] ?? 'all'),
            'badge_enabled' => $badge['enabled'],
            'badge_text' => $badge['text'],
            'badge_type' => $badge['type'],
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function hydrateCalculatedRow(array $row): array
    {
        $applied = json_decode((string) ($row['applied_rules_json'] ?? '[]'), true);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'source_rate_id' => isset($row['source_rate_id']) ? (int) $row['source_rate_id'] : null,
            'snapshot_id' => isset($row['snapshot_id']) ? (int) $row['snapshot_id'] : null,
            'cache_key' => (string) ($row['cache_key'] ?? ''),
            'vehicle_code' => (string) ($row['vehicle_code'] ?? ''),
            'vehicle_name' => (string) ($row['vehicle_name'] ?? ''),
            'available' => !empty($row['available']),
            'currency' => (string) ($row['currency'] ?? 'USD'),
            'base_daily_rate' => $row['base_daily_rate'],
            'base_total_rate' => $row['base_total_rate'],
            'final_daily_rate' => $row['final_daily_rate'],
            'final_total_rate' => $row['final_total_rate'],
            'rental_days' => (int) ($row['rental_days'] ?? 1),
            'discount_amount_daily' => $row['discount_amount_daily'],
            'discount_amount_total' => $row['discount_amount_total'],
            'applied_rules' => is_array($applied) ? $applied : [],
            'has_rules' => is_array($applied) && $applied !== [],
            'calculation_notes' => (string) ($row['calculation_notes'] ?? ''),
            'calculated_at' => (string) ($row['calculated_at'] ?? ''),
            'pickup_location' => (string) ($row['pickup_location'] ?? ''),
            'return_location' => (string) ($row['return_location'] ?? ''),
            'pickup_datetime' => (string) ($row['pickup_datetime'] ?? ''),
            'return_datetime' => (string) ($row['return_datetime'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function ruleBindParams(array $data, ?int $userId, string $now, bool $isCreate): array
    {
        $daysJson = $data['days_of_week'] ?? $data['days_of_week_json'] ?? null;
        if (is_array($daysJson)) {
            $daysJson = json_encode($daysJson, JSON_UNESCAPED_UNICODE);
        }
        $badge = self::normalizeVisualBadgeConfig($data);

        $params = [
            ':name' => trim((string) ($data['name'] ?? '')),
            ':description' => trim((string) ($data['description'] ?? '')),
            ':enabled' => !empty($data['enabled']) ? 1 : 0,
            ':priority' => (int) ($data['priority'] ?? 100),
            ':stackable' => !empty($data['stackable']) ? 1 : 0,
            ':stop_processing' => !empty($data['stop_processing']) ? 1 : 0,
            ':rule_type' => (string) ($data['rule_type'] ?? 'promotion'),
            ':adjustment_type' => (string) ($data['adjustment_type'] ?? 'percent_discount'),
            ':adjustment_value' => (float) ($data['adjustment_value'] ?? 0),
            ':currency' => strtoupper(trim((string) ($data['currency'] ?? 'USD'))),
            ':valid_from' => self::nullableDate($data['valid_from'] ?? null),
            ':valid_to' => self::nullableDate($data['valid_to'] ?? null),
            ':days_of_week_json' => $daysJson ?: null,
            ':min_rental_days' => self::nullableInt($data['min_rental_days'] ?? null),
            ':max_rental_days' => self::nullableInt($data['max_rental_days'] ?? null),
            ':pickup_location' => self::nullableStr($data['pickup_location'] ?? null),
            ':return_location' => self::nullableStr($data['return_location'] ?? null),
            ':rate_qualifier' => self::nullableStr($data['rate_qualifier'] ?? null),
            ':applies_to' => (string) ($data['applies_to'] ?? 'all'),
            ':badge_enabled' => $badge['enabled'] ? 1 : 0,
            ':badge_text' => $badge['text'] !== '' ? $badge['text'] : null,
            ':badge_type' => $badge['type'],
            ':updated_by' => $userId,
            ':updated_at' => $now,
        ];
        if ($isCreate) {
            $params[':created_by'] = $userId;
        }

        return $params;
    }

    /**
     * @param mixed $value
     * @return list<int>
     */
    private static function parseDaysOfWeek($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = preg_split('/[\s,;]+/', $value) ?: [];
            }
        }
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_map(static fn($d) => (int) $d, $value));
    }

    private static function nullableDate($value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }

    private static function nullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private static function nullableStr($value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? strtoupper($value) : null;
    }

    private function currentAdminUserId(): ?int
    {
        $user = AdminUserService::current();
        if ($user === null) {
            return null;
        }
        $id = (int) ($user['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     */
    private function auditLog(?int $ruleId, string $action, ?array $before, ?array $after, ?int $adminUserId): void
    {
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO rac_rate_rule_audit_log (rule_id, action, before_json, after_json, admin_user_id) VALUES (:rule_id, :action, :before_json, :after_json, :admin_user_id)',
            [
                ':rule_id' => $ruleId,
                ':action' => $action,
                ':before_json' => $before !== null ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
                ':after_json' => $after !== null ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
                ':admin_user_id' => $adminUserId,
            ]
        );
    }

    private static function nowString(): string
    {
        return (new DateTime('now', new DateTimeZone(self::TZ)))->format('Y-m-d H:i:s');
    }
}
