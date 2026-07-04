<?php
/**
 * Tarifas públicas RAC desde caché BARS + reglas comerciales.
 * AM-RAC-BARS-RAC-3A
 */

declare(strict_types=1);

require_once __DIR__ . '/RacBarsDatabaseSchema.php';
require_once __DIR__ . '/BarsRateCacheService.php';
require_once __DIR__ . '/RacRateRuleService.php';
require_once __DIR__ . '/BarsRateClient.php';

class RacPublicRateService
{
    /** @var list<string> */
    private const HIDDEN_VEHICLE_CODES = ['SIMR', 'SIAR', 'SIMN'];

    private const COUNTER_MARKUP = 1.07;

    private BarsRateCacheService $cacheService;
    private RacRateRuleService $ruleService;

    public function __construct()
    {
        RacBarsDatabaseSchema::ensure();
        $this->cacheService = new BarsRateCacheService();
        $this->ruleService = new RacRateRuleService();
    }

    public static function maxAgeMinutes(): int
    {
        if (defined('RAC_PUBLIC_RATE_MAX_AGE_MINUTES')) {
            return max(1, (int) RAC_PUBLIC_RATE_MAX_AGE_MINUTES);
        }

        return 360;
    }

    public static function quoteTtlMinutes(): int
    {
        if (defined('RAC_PUBLIC_RATE_QUOTE_TTL_MINUTES')) {
            return max(5, (int) RAC_PUBLIC_RATE_QUOTE_TTL_MINUTES);
        }

        return 30;
    }

    public static function isBarsPricingEnabled(): bool
    {
        $client = new BarsRateClient();

        return $client->isConfigured();
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function normalizeSearchParams(array $input): array
    {
        $pickupLocation = strtoupper(trim((string) ($input['locationCode'] ?? $input['pickup_location'] ?? '')));
        $returnLocation = strtoupper(trim((string) ($input['returnLocationCode'] ?? $input['return_location'] ?? '')));
        if ($returnLocation === '') {
            $returnLocation = $pickupLocation;
        }

        $pickupDate = trim((string) ($input['pickupDate'] ?? ''));
        $pickupTime = trim((string) ($input['pickupTime'] ?? '10:00'));
        $returnDate = trim((string) ($input['returnDate'] ?? ''));
        $returnTime = trim((string) ($input['returnTime'] ?? '10:00'));
        $age = trim((string) ($input['age'] ?? '25'));
        $promoCode = trim((string) ($input['promoCode'] ?? ''));

        $pickupDatetime = BarsRateCacheService::normalizeOtaDatetime(
            $pickupDate !== '' ? $pickupDate . 'T' . $pickupTime : ''
        );
        $returnDatetime = BarsRateCacheService::normalizeOtaDatetime(
            $returnDate !== '' ? $returnDate . 'T' . $returnTime : ''
        );

        $cacheKey = BarsRateCacheService::buildCacheKey(
            $pickupLocation,
            $returnLocation,
            $pickupDatetime,
            $returnDatetime,
            'WEB'
        );

        return [
            'locationCode' => $pickupLocation,
            'returnLocationCode' => $returnLocation,
            'pickupDate' => $pickupDate,
            'pickupTime' => $pickupTime,
            'returnDate' => $returnDate,
            'returnTime' => $returnTime,
            'pickup_datetime' => $pickupDatetime,
            'return_datetime' => $returnDatetime,
            'pickup_location' => $pickupLocation,
            'return_location' => $returnLocation,
            'rate_qualifier' => 'WEB',
            'cache_key' => $cacheKey,
            'age' => $age,
            'promoCode' => $promoCode,
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function getPublicRates(array $params): array
    {
        $normalized = $this->normalizeSearchParams($params);
        if ($normalized['locationCode'] === '' || $normalized['pickupDate'] === '' || $normalized['returnDate'] === '') {
            return $this->errorResponse('Faltan datos de búsqueda.');
        }

        $this->expireOldQuotes();

        $freshness = $this->assessFreshness($normalized);
        $staleUsed = false;
        $refreshAttempted = false;

        if (!$freshness['fresh']) {
            $refreshAttempted = true;
            $refresh = $this->refreshRatesIfNeeded($normalized, 'public_search');
            if (!($refresh['ok'] ?? false)) {
                if ($freshness['has_data']) {
                    $staleUsed = true;
                    am_log('RAC public rates: using stale cache for ' . $normalized['cache_key'], 'WARNING');
                } else {
                    return $this->errorResponse(
                        'No pudimos consultar disponibilidad para esos datos. Intenta con otra fecha o comunícate con nosotros.',
                        'NO_AVAILABILITY'
                    );
                }
            }
        }

        $rates = $this->ruleService->getCalculatedRates([
            'cache_key' => $normalized['cache_key'],
        ]);

        if ($rates === [] && !$staleUsed) {
            $refresh = $this->refreshRatesIfNeeded($normalized, 'public_search_miss');
            $refreshAttempted = true;
            if ($refresh['ok'] ?? false) {
                $rates = $this->ruleService->getCalculatedRates(['cache_key' => $normalized['cache_key']]);
            }
        }

        if ($rates === []) {
            return $this->errorResponse(
                'No pudimos consultar disponibilidad para esos datos. Intenta con otra fecha o comunícate con nosotros.',
                'NO_AVAILABILITY'
            );
        }

        $vehicles = [];
        foreach ($rates as $rate) {
            $vehicle = $this->buildPublicRatePayload($rate);
            if ($vehicle !== null) {
                $vehicles[] = $vehicle;
            }
        }

        if ($vehicles === []) {
            return $this->errorResponse(
                'No hay vehículos disponibles para esas fechas. Pruebe otras fechas o sucursales.',
                'NO_AVAILABILITY'
            );
        }

        usort($vehicles, static function (array $a, array $b): int {
            return ((float) ($a['priceWeb'] ?? 0)) <=> ((float) ($b['priceWeb'] ?? 0));
        });

        return [
            'success' => true,
            'source' => $staleUsed ? 'BARS_CACHE_STALE' : 'BARS_CACHE',
            'xCache' => $freshness['fresh'] ? 'HIT' : ($refreshAttempted ? 'REFRESH' : 'MISS'),
            'vehicles' => $vehicles,
            'miss' => false,
            'reason' => null,
            'catalogFallback' => [],
            'rateCodes' => ['WEB'],
            'message' => null,
            'cacheKey' => $normalized['cache_key'],
            'pricingEngine' => 'bars_calculated',
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function refreshRatesIfNeeded(array $params, string $reason = 'public_search'): array
    {
        $normalized = isset($params['cache_key']) ? $params : $this->normalizeSearchParams($params);

        $barsParams = [
            'pickup_location' => $normalized['pickup_location'],
            'return_location' => $normalized['return_location'],
            'pickup_datetime' => $normalized['pickup_datetime'],
            'return_datetime' => $normalized['return_datetime'],
            'rate_qualifier' => $normalized['rate_qualifier'] ?? 'WEB',
        ];

        $result = $this->cacheService->refreshFromBars($barsParams, $reason, true);
        if (!($result['ok'] ?? false) && !($result['saved'] ?? false)) {
            am_log('RAC public BARS refresh failed: ' . ($result['message'] ?? 'unknown'), 'WARNING');
        }

        return [
            'ok' => (bool) ($result['ok'] ?? $result['saved'] ?? false),
            'message' => $result['message'] ?? null,
            'cache_key' => $normalized['cache_key'],
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @param string $vehicleCode
     * @return array<string, mixed>
     */
    public function createQuote(array $params, string $vehicleCode): array
    {
        $normalized = $this->normalizeSearchParams($params);
        $vehicleCode = strtoupper(trim($vehicleCode));
        if ($vehicleCode === '') {
            return ['ok' => false, 'message' => 'Código de vehículo inválido.'];
        }

        $freshness = $this->assessFreshness($normalized);
        if (!$freshness['fresh']) {
            $this->refreshRatesIfNeeded($normalized, 'quote_create');
        }

        $rate = $this->findCalculatedRate($normalized['cache_key'], $vehicleCode);
        if ($rate === null) {
            return ['ok' => false, 'message' => 'Tarifa no disponible. Vuelva a consultar disponibilidad.'];
        }

        if (!$this->isPubliclyBookable($rate)) {
            return ['ok' => false, 'message' => 'Este vehículo no está disponible para reserva en línea.'];
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = gmdate('Y-m-d H:i:s', time() + self::quoteTtlMinutes() * 60);
        $appliedRules = $rate['applied_rules'] ?? json_decode((string) ($rate['applied_rules_json'] ?? '[]'), true);

        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO rac_rate_quotes (
                quote_token, cache_key, calculated_rate_id, source_rate_id, snapshot_id,
                vehicle_code, vehicle_name, pickup_location, return_location,
                pickup_datetime, return_datetime, rental_days, currency,
                base_daily_rate, base_total_rate, final_daily_rate, final_total_rate,
                discount_amount_daily, discount_amount_total, applied_rules_json,
                status, expires_at, client_ip_hash, user_agent_hash
            ) VALUES (
                :quote_token, :cache_key, :calculated_rate_id, :source_rate_id, :snapshot_id,
                :vehicle_code, :vehicle_name, :pickup_location, :return_location,
                :pickup_datetime, :return_datetime, :rental_days, :currency,
                :base_daily_rate, :base_total_rate, :final_daily_rate, :final_total_rate,
                :discount_amount_daily, :discount_amount_total, :applied_rules_json,
                :status, :expires_at, :client_ip_hash, :user_agent_hash
            )',
            [
                ':quote_token' => $token,
                ':cache_key' => $normalized['cache_key'],
                ':calculated_rate_id' => $rate['id'] ?? null,
                ':source_rate_id' => $rate['source_rate_id'] ?? null,
                ':snapshot_id' => $rate['snapshot_id'] ?? null,
                ':vehicle_code' => $vehicleCode,
                ':vehicle_name' => (string) ($rate['vehicle_name'] ?? $vehicleCode),
                ':pickup_location' => $normalized['pickup_location'],
                ':return_location' => $normalized['return_location'],
                ':pickup_datetime' => $normalized['pickup_datetime'],
                ':return_datetime' => $normalized['return_datetime'],
                ':rental_days' => (int) ($rate['rental_days'] ?? 1),
                ':currency' => (string) ($rate['currency'] ?? 'USD'),
                ':base_daily_rate' => $this->decimal($rate['base_daily_rate'] ?? null),
                ':base_total_rate' => $this->decimal($rate['base_total_rate'] ?? null),
                ':final_daily_rate' => $this->decimal($rate['final_daily_rate'] ?? null),
                ':final_total_rate' => $this->decimal($rate['final_total_rate'] ?? null),
                ':discount_amount_daily' => $this->decimal($rate['discount_amount_daily'] ?? null),
                ':discount_amount_total' => $this->decimal($rate['discount_amount_total'] ?? null),
                ':applied_rules_json' => json_encode(is_array($appliedRules) ? $appliedRules : [], JSON_UNESCAPED_UNICODE),
                ':status' => 'active',
                ':expires_at' => $expiresAt,
                ':client_ip_hash' => $this->hashClientMeta($_SERVER['REMOTE_ADDR'] ?? ''),
                ':user_agent_hash' => $this->hashClientMeta($_SERVER['HTTP_USER_AGENT'] ?? ''),
            ]
        );

        $vehicle = $this->buildPublicRatePayload($rate);
        if ($vehicle === null) {
            return ['ok' => false, 'message' => 'No se pudo preparar la tarifa.'];
        }
        $vehicle['pricing']['barsQuoteToken'] = $token;
        $vehicle['pricing']['quoteExpiresAt'] = $expiresAt;

        return [
            'ok' => true,
            'quote_token' => $token,
            'expires_at' => $expiresAt,
            'vehicle' => $vehicle,
            'pricing' => $vehicle['pricing'],
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @return array{ok: bool, message?: string, quote?: array<string, mixed>}
     */
    public function validateQuote(string $quoteToken, array $context = []): array
    {
        $quoteToken = trim($quoteToken);
        if ($quoteToken === '') {
            return ['ok' => false, 'message' => 'Falta token de tarifa.'];
        }

        $this->expireOldQuotes();

        $db = Database::getInstance();
        $row = $db->selectOne(
            'SELECT * FROM rac_rate_quotes WHERE quote_token = :token LIMIT 1',
            [':token' => $quoteToken]
        );

        if (!is_array($row)) {
            return ['ok' => false, 'message' => 'Tarifa no válida. Vuelva a consultar disponibilidad.'];
        }

        if (($row['status'] ?? '') === 'used') {
            return ['ok' => false, 'message' => 'Esta tarifa ya fue utilizada. Vuelva a consultar.'];
        }

        if (($row['status'] ?? '') !== 'active') {
            return ['ok' => false, 'message' => 'Esta tarifa ya no está disponible. Vuelva a consultar.'];
        }

        $expiresAt = strtotime((string) ($row['expires_at'] ?? '') . ' UTC');
        if ($expiresAt !== false && time() > $expiresAt) {
            $this->markQuoteStatus($quoteToken, 'expired');
            return ['ok' => false, 'message' => 'Esta tarifa expiró. Por favor vuelve a consultar para obtener una tarifa actualizada.'];
        }

        if ($context !== []) {
            $normalized = $this->normalizeSearchParams($context);
            if ($normalized['cache_key'] !== ($row['cache_key'] ?? '')) {
                return ['ok' => false, 'message' => 'La tarifa no coincide con los datos de la reserva.'];
            }
            $expectedCode = strtoupper(trim((string) ($context['vehicle_code'] ?? $context['sippCode'] ?? '')));
            if ($expectedCode !== '' && $expectedCode !== strtoupper((string) ($row['vehicle_code'] ?? ''))) {
                return ['ok' => false, 'message' => 'El vehículo no coincide con la tarifa bloqueada.'];
            }
        }

        return ['ok' => true, 'quote' => $this->hydrateQuoteRow($row)];
    }

    public function markQuoteUsed(string $quoteToken, int $reservationId): void
    {
        $db = Database::getInstance();
        $usedAt = gmdate('Y-m-d H:i:s');
        $db->execute(
            "UPDATE rac_rate_quotes SET status = 'used', used_at = :used_at, reservation_id = :rid WHERE quote_token = :token AND status = 'active'",
            [':used_at' => $usedAt, ':rid' => $reservationId, ':token' => $quoteToken]
        );
    }

    public function expireOldQuotes(): int
    {
        $db = Database::getInstance();
        $now = gmdate('Y-m-d H:i:s');

        return $db->execute(
            "UPDATE rac_rate_quotes SET status = 'expired' WHERE status = 'active' AND expires_at < :now",
            [':now' => $now]
        );
    }

    /**
     * @param array<string, mixed> $calculatedRate
     * @return array<string, mixed>|null
     */
    public function buildPublicRatePayload(array $calculatedRate): ?array
    {
        if (!$this->isPubliclyBookable($calculatedRate)) {
            return null;
        }

        $code = strtoupper((string) ($calculatedRate['vehicle_code'] ?? ''));
        $name = (string) ($calculatedRate['vehicle_name'] ?? $code);
        $rentalDays = max(1, (int) ($calculatedRate['rental_days'] ?? 1));
        $finalDaily = round(max(0, (float) ($calculatedRate['final_daily_rate'] ?? 0)), 2);
        $finalTotal = round(max(0, (float) ($calculatedRate['final_total_rate'] ?? 0)), 2);
        if ($finalTotal <= 0 && $finalDaily > 0) {
            $finalTotal = round($finalDaily * $rentalDays, 2);
        }

        $counterDaily = round($finalDaily * self::COUNTER_MARKUP, 2);
        $counterTotal = round($finalTotal * self::COUNTER_MARKUP, 2);

        $appliedRules = $calculatedRate['applied_rules'] ?? json_decode((string) ($calculatedRate['applied_rules_json'] ?? '[]'), true);
        $promotionLabel = $this->resolvePromotionLabel(is_array($appliedRules) ? $appliedRules : []);
        $category = $this->inferCategory($name, $code);

        $payload = [
            'sippCode' => $code,
            'name' => $name,
            'category' => $category,
            'description' => $name,
            'passengers' => $this->inferPassengers($code, $name),
            'transmission' => 'Automática',
            'ac' => true,
            'available' => true,
            'priceWeb' => $finalDaily,
            'priceTotal' => $finalTotal,
            'priceCounter' => $counterDaily,
            'priceCounterTotal' => $counterTotal,
            'rentalDays' => $rentalDays,
            'currency' => (string) ($calculatedRate['currency'] ?? 'USD'),
            'vendorRateId' => 'bars-' . $code . '-' . substr((string) ($calculatedRate['cache_key'] ?? ''), 0, 12),
            'pricing' => [
                'rateSource' => 'bars_cache',
                'baseDailyRate' => round((float) ($calculatedRate['base_daily_rate'] ?? 0), 2),
                'baseTotalRate' => round((float) ($calculatedRate['base_total_rate'] ?? 0), 2),
                'finalDailyRate' => $finalDaily,
                'finalTotalRate' => $finalTotal,
                'discountTotal' => round((float) ($calculatedRate['discount_amount_total'] ?? 0), 2),
                'rateBase' => $finalTotal,
                'rateBaseCounter' => $counterTotal,
                'cacheKey' => (string) ($calculatedRate['cache_key'] ?? ''),
                'calculatedRateId' => (int) ($calculatedRate['id'] ?? 0),
                'snapshotId' => (int) ($calculatedRate['snapshot_id'] ?? 0),
            ],
            'mandatoryCharges' => [
                ['code' => 'SAF', 'description' => 'Cargo Administrativo (SAF)', 'amountTotal' => 0],
            ],
            'optionalCharges' => [],
            '_isFallback' => false,
        ];

        if ($promotionLabel !== '') {
            $payload['promotionLabel'] = $promotionLabel;
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $params
     * @return array{fresh: bool, has_data: bool, age_minutes: ?int}
     */
    private function assessFreshness(array $params): array
    {
        $cacheKey = (string) ($params['cache_key'] ?? '');
        if ($cacheKey === '') {
            return ['fresh' => false, 'has_data' => false, 'age_minutes' => null];
        }

        $db = Database::getInstance();
        $row = $db->selectOne(
            'SELECT calculated_at FROM rac_calculated_rates WHERE cache_key = :cache_key ORDER BY calculated_at DESC LIMIT 1',
            [':cache_key' => $cacheKey]
        );

        if (!is_array($row)) {
            return ['fresh' => false, 'has_data' => false, 'age_minutes' => null];
        }

        $calculatedAt = strtotime((string) ($row['calculated_at'] ?? ''));
        if ($calculatedAt === false) {
            return ['fresh' => false, 'has_data' => true, 'age_minutes' => null];
        }

        $ageMinutes = (int) floor((time() - $calculatedAt) / 60);

        return [
            'fresh' => $ageMinutes <= self::maxAgeMinutes(),
            'has_data' => true,
            'age_minutes' => $ageMinutes,
        ];
    }

    /**
     * @param array<string, mixed> $rate
     */
    private function isPubliclyBookable(array $rate): bool
    {
        $code = strtoupper((string) ($rate['vehicle_code'] ?? ''));
        if (in_array($code, self::HIDDEN_VEHICLE_CODES, true)) {
            return false;
        }
        if (empty($rate['available'])) {
            return false;
        }
        $finalDaily = (float) ($rate['final_daily_rate'] ?? 0);
        if ($finalDaily <= 0) {
            am_log('RAC public: skipping zero-rate vehicle ' . $code, 'WARNING');

            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findCalculatedRate(string $cacheKey, string $vehicleCode): ?array
    {
        $rates = $this->ruleService->getCalculatedRates(['cache_key' => $cacheKey]);
        foreach ($rates as $rate) {
            if (strtoupper((string) ($rate['vehicle_code'] ?? '')) === $vehicleCode) {
                return $rate;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateQuoteRow(array $row): array
    {
        $applied = json_decode((string) ($row['applied_rules_json'] ?? '[]'), true);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'quote_token' => (string) ($row['quote_token'] ?? ''),
            'cache_key' => (string) ($row['cache_key'] ?? ''),
            'calculated_rate_id' => (int) ($row['calculated_rate_id'] ?? 0),
            'snapshot_id' => (int) ($row['snapshot_id'] ?? 0),
            'vehicle_code' => (string) ($row['vehicle_code'] ?? ''),
            'vehicle_name' => (string) ($row['vehicle_name'] ?? ''),
            'rental_days' => (int) ($row['rental_days'] ?? 1),
            'currency' => (string) ($row['currency'] ?? 'USD'),
            'base_daily_rate' => (float) ($row['base_daily_rate'] ?? 0),
            'base_total_rate' => (float) ($row['base_total_rate'] ?? 0),
            'final_daily_rate' => (float) ($row['final_daily_rate'] ?? 0),
            'final_total_rate' => (float) ($row['final_total_rate'] ?? 0),
            'discount_amount_total' => (float) ($row['discount_amount_total'] ?? 0),
            'applied_rules_json' => is_array($applied) ? $applied : [],
            'expires_at' => (string) ($row['expires_at'] ?? ''),
        ];
    }

    /**
     * @param list<array<string, mixed>> $rules
     */
    private function resolvePromotionLabel(array $rules): string
    {
        foreach ($rules as $rule) {
            $type = (string) ($rule['rule_type'] ?? $rule['adjustment_type'] ?? '');
            $name = trim((string) ($rule['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            if (in_array($type, ['seasonal', 'promotion', 'percent_discount'], true)
                || stripos($name, 'descuento') !== false
                || stripos($name, 'verano') !== false
                || stripos($name, 'promo') !== false) {
                return $name;
            }
        }

        return '';
    }

    private function inferCategory(string $name, string $code): string
    {
        $hay = strtoupper($name . ' ' . $code);
        if (str_contains($hay, 'MINI BUS') || $code === 'MVMR' || $code === 'FVMR') {
            return 'Van';
        }
        if (str_contains($hay, 'SUV') || str_contains($code, 'FAR') || str_contains($code, 'IFAR')) {
            return 'SUV';
        }
        if (str_contains($hay, 'PREMIUM') || $code === 'PREMIUM') {
            return 'Premium';
        }
        if (str_contains($hay, 'ECON') || $code === 'ECAR') {
            return 'Económico';
        }
        if ($code === 'CCAR' || str_contains($hay, 'COMPACT')) {
            return 'Compacto';
        }

        return 'General';
    }

    private function inferPassengers(string $code, string $name): int
    {
        $hay = strtoupper($name . ' ' . $code);
        if (str_contains($hay, 'MINI BUS') || $code === 'MVMR') {
            return 12;
        }
        if (str_contains($hay, 'VAN') || str_contains($code, 'VMR')) {
            return 7;
        }

        return 5;
    }

    private function markQuoteStatus(string $quoteToken, string $status): void
    {
        $db = Database::getInstance();
        $db->execute(
            'UPDATE rac_rate_quotes SET status = :status WHERE quote_token = :token',
            [':status' => $status, ':token' => $quoteToken]
        );
    }

    private function hashClientMeta(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return hash('sha256', $value);
    }

    private function decimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 2);
    }

    /**
     * @return array<string, mixed>
     */
    private function errorResponse(string $message, ?string $reason = null): array
    {
        return [
            'success' => false,
            'source' => 'BARS_CACHE',
            'xCache' => 'ERROR',
            'vehicles' => [],
            'miss' => false,
            'reason' => $reason,
            'catalogFallback' => [],
            'rateCodes' => [],
            'message' => $message,
            'pricingEngine' => 'bars_calculated',
        ];
    }
}
