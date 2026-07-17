<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/services/InventoryHighlightService.php';

function adj04_semi_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$catalog = InventoryHighlightService::catalog();
foreach (['promo', 'featured', 'recommended', 'popular', 'custom'] as $key) {
    adj04_semi_assert(isset($catalog[$key]), "Catalog contains {$key}");
}
adj04_semi_assert(InventoryHighlightService::normalizeKey('bg-danger') === '', 'Arbitrary classes are rejected');

$legacyNode = [
    'inventory_highlights' => [
        'assignments' => ['vin:ABC123' => 'oferta'],
    ],
];
$legacyAssignments = InventoryHighlightService::getAssignments($legacyNode);
$legacyMeta = InventoryHighlightService::getMetadata($legacyNode);
$vehicle = ['id' => 1, 'VIN' => 'abc123', 'LicensePlate' => 'AA-123'];
$legacyBadge = InventoryHighlightService::resolveBadge($vehicle, $legacyAssignments, $legacyMeta);
adj04_semi_assert(($legacyBadge['label'] ?? '') === 'Oferta', 'Legacy string assignment keeps its badge');

$siteData = ['seminuevos' => ['inventory_highlights' => ['assignments' => [], 'meta' => []]]];
InventoryHighlightService::setAssignment(
    $siteData,
    $vehicle,
    'popular',
    ['enabled' => true, 'text' => '  Más buscado  ']
);
$assignments = InventoryHighlightService::getAssignments($siteData['seminuevos']);
$metadata = InventoryHighlightService::getMetadata($siteData['seminuevos']);
$badge = InventoryHighlightService::resolveBadge($vehicle, $assignments, $metadata);
adj04_semi_assert(($badge['key'] ?? '') === 'popular', 'Popular type persists');
adj04_semi_assert(($badge['label'] ?? '') === 'Más buscado', 'Custom visible text persists');
adj04_semi_assert(($badge['class'] ?? '') === 'inv-highlight--popular', 'Class comes from closed catalog');

InventoryHighlightService::setAssignment(
    $siteData,
    $vehicle,
    'featured',
    ['enabled' => false, 'text' => 'Destacado oculto']
);
$assignments = InventoryHighlightService::getAssignments($siteData['seminuevos']);
$metadata = InventoryHighlightService::getMetadata($siteData['seminuevos']);
adj04_semi_assert(
    InventoryHighlightService::resolveBadge($vehicle, $assignments, $metadata) === null,
    'Disabled visual badge does not render'
);
adj04_semi_assert(
    InventoryHighlightService::resolveBadgeKey($vehicle, $assignments) === 'featured',
    'Disabling the visual badge does not remove its assignment'
);

$defaultLabelData = ['seminuevos' => ['inventory_highlights' => ['assignments' => [], 'meta' => []]]];
InventoryHighlightService::setAssignment(
    $defaultLabelData,
    $vehicle,
    'recommended',
    ['enabled' => true, 'text' => '']
);
$defaultBadge = InventoryHighlightService::resolveBadge(
    $vehicle,
    InventoryHighlightService::getAssignments($defaultLabelData['seminuevos']),
    InventoryHighlightService::getMetadata($defaultLabelData['seminuevos'])
);
adj04_semi_assert(($defaultBadge['label'] ?? '') === 'Recomendado', 'Empty text uses type default');

$invalidPayloads = [
    str_repeat('x', 61),
    '<script>alert(1)</script>',
    '<img src=x onerror=alert(1)>',
    'javascript:alert(1)',
    "\" onclick=\"alert(1)",
    "Promo\nNueva",
];
foreach ($invalidPayloads as $payload) {
    $rejected = false;
    try {
        InventoryHighlightService::normalizeVisualText($payload);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
    }
    adj04_semi_assert($rejected, 'Unsafe or oversized Seminuevos badge text is rejected');
}

$otherVehicle = ['id' => 2, 'VIN' => 'XYZ999', 'LicensePlate' => 'BB-999'];
InventoryHighlightService::setAssignment(
    $siteData,
    $otherVehicle,
    'promo',
    ['enabled' => true, 'text' => 'Promo']
);
InventoryHighlightService::setAssignment($siteData, $vehicle, '', []);
adj04_semi_assert(
    InventoryHighlightService::resolveBadgeKey(
        $otherVehicle,
        InventoryHighlightService::getAssignments($siteData['seminuevos'])
    ) === 'promo',
    'Removing one vehicle assignment preserves other vehicles'
);

echo "AM-ADJ-04 Seminuevos highlight tests: OK\n";
