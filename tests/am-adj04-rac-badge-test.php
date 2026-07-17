<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/services/RacRateRuleService.php';
require_once __DIR__ . '/../app/services/RacPublicRateService.php';

function adj04_rac_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$defaults = RacRateRuleService::normalizeVisualBadgeConfig([]);
adj04_rac_assert($defaults['enabled'] === false, 'Existing rules default to no visual badge');
adj04_rac_assert($defaults['type'] === 'promo', 'Default badge type is promo');
adj04_rac_assert($defaults['text'] === '', 'Disabled badge has no public text');

$popular = RacRateRuleService::normalizeVisualBadgeConfig([
    'badge_enabled' => 1,
    'badge_type' => 'popular',
    'badge_text' => '  Más buscado  ',
]);
adj04_rac_assert($popular === [
    'enabled' => true,
    'type' => 'popular',
    'text' => 'Más buscado',
], 'Configured visual badge is normalized');

$defaultText = RacRateRuleService::normalizeVisualBadgeConfig([
    'badge_enabled' => 1,
    'badge_type' => 'recommended',
    'badge_text' => '',
]);
adj04_rac_assert($defaultText['text'] === 'Recomendado', 'Enabled badge uses the selected type default');

$sixtyChars = str_repeat('Á', 60);
$boundary = RacRateRuleService::normalizeVisualBadgeConfig([
    'badge_enabled' => 1,
    'badge_type' => 'custom',
    'badge_text' => $sixtyChars,
]);
adj04_rac_assert(mb_strlen($boundary['text'], 'UTF-8') === 60, 'Sixty-character text is accepted');

$invalidPayloads = [
    str_repeat('x', 61),
    "<script>alert(1)</script>",
    '<img src=x onerror=alert(1)>',
    'javascript:alert(1)',
    "\" onclick=\"alert(1)",
    "Promo\nNueva",
];
foreach ($invalidPayloads as $payload) {
    $rejected = false;
    try {
        RacRateRuleService::normalizeVisualBadgeConfig([
            'badge_enabled' => 1,
            'badge_type' => 'custom',
            'badge_text' => $payload,
        ]);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
    }
    adj04_rac_assert($rejected, 'Unsafe or oversized RAC badge text is rejected');
}

$invalidTypeRejected = false;
try {
    RacRateRuleService::normalizeVisualBadgeConfig([
        'badge_enabled' => 1,
        'badge_type' => 'bg-danger onclick',
        'badge_text' => 'Promo',
    ]);
} catch (InvalidArgumentException $e) {
    $invalidTypeRejected = true;
}
adj04_rac_assert($invalidTypeRejected, 'Arbitrary RAC badge types are rejected');

$appliedRules = [
    ['rule_id' => 20, 'name' => 'Second rule'],
    ['rule_id' => 10, 'name' => 'First rule'],
];
$visualRules = [
    20 => ['enabled' => false, 'type' => 'promo', 'text' => 'Hidden'],
    10 => ['enabled' => true, 'type' => 'popular', 'text' => 'Más buscado'],
];
$resolved = RacPublicRateService::resolvePromotionBadgeFromRules($appliedRules, $visualRules);
adj04_rac_assert($resolved === ['text' => 'Más buscado', 'type' => 'popular'], 'First enabled badge follows applied-rule order');

$withoutBadge = RacPublicRateService::resolvePromotionBadgeFromRules(
    [['rule_id' => 10, 'name' => 'Promoción automática antigua']],
    []
);
adj04_rac_assert($withoutBadge === null, 'Legacy applied rules do not show unexpected labels');

$rateService = new RacRateRuleService();
$rate = [
    'daily_rate' => 100.00,
    'total_rate' => 300.00,
    'pickup_datetime' => '2026-08-10 10:00:00',
    'return_datetime' => '2026-08-13 10:00:00',
    'pickup_location' => 'PTY',
    'return_location' => 'PTY',
    'rate_qualifier' => 'WEB',
    'vehicle_code' => 'ECAR',
    'available' => true,
];
$rule = [
    'id' => 99,
    'name' => 'Descuento visualmente etiquetado',
    'enabled' => true,
    'stackable' => false,
    'stop_processing' => false,
    'adjustment_type' => 'percent_discount',
    'adjustment_value' => 10,
    'min_rental_days' => null,
    'max_rental_days' => null,
    'pickup_location' => null,
    'return_location' => null,
    'rate_qualifier' => null,
    'valid_from' => null,
    'valid_to' => null,
    'days_of_week' => [],
    'targets' => [],
    'applies_to' => 'all',
    'badge_enabled' => false,
    'badge_type' => 'promo',
    'badge_text' => '',
];
$withoutVisualMetadata = $rateService->applyRulesToRate($rate, [$rule], ['rental_days' => 3]);
$rule['badge_enabled'] = true;
$rule['badge_type'] = 'popular';
$rule['badge_text'] = 'Más buscado';
$withVisualMetadata = $rateService->applyRulesToRate($rate, [$rule], ['rental_days' => 3]);
adj04_rac_assert(
    $withoutVisualMetadata === $withVisualMetadata,
    'Changing only badge metadata leaves every calculated amount bit-for-bit identical'
);

echo "AM-ADJ-04 RAC badge tests: OK\n";
