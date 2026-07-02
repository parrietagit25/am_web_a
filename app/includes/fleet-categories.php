<?php
/**
 * Categorías de flota Rent A Car (carrusel home + filtros en /flota.php).
 */

function am_fleet_category_display_label(string $category, string $label = ''): string
{
    $label = trim($label);
    if ($label !== '') {
        return $label;
    }

    $aliases = [
        'SUV Mini' => 'SUV compacto',
        'SUV mini' => 'SUV compacto',
    ];

    return $aliases[$category] ?? $category;
}

/**
 * @param array<int, array<string, mixed>> $items
 * @return list<array<string, mixed>>
 */
function am_fleet_categories_sorted(array $items): array
{
    $normalized = [];

    foreach (array_values($items) as $idx => $item) {
        if (!is_array($item)) {
            continue;
        }

        $label = trim((string) ($item['label'] ?? $item['category'] ?? ''));
        if ($label === '') {
            continue;
        }

        $category = trim((string) ($item['category'] ?? $label)) ?: $label;
        $label = am_fleet_category_display_label($category, $label);

        $normalized[] = [
            'id' => intval($item['id'] ?? ($idx + 1)),
            'category' => $category,
            'label' => $label,
            'image_url' => trim((string) ($item['image_url'] ?? '')),
            'sort_order' => intval($item['sort_order'] ?? (($idx + 1) * 10)),
        ];
    }

    usort($normalized, static function (array $a, array $b): int {
        $order = ($a['sort_order'] ?? 999) <=> ($b['sort_order'] ?? 999);
        if ($order !== 0) {
            return $order;
        }

        return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
    });

    return $normalized;
}

/** @return list<string> */
function am_fleet_category_names(array $items): array
{
    $names = [];
    foreach (am_fleet_categories_sorted($items) as $item) {
        $names[] = (string) $item['category'];
    }

    return $names;
}

function am_rename_fleet_vehicle_category(array &$siteData, string $oldCategory, string $newCategory): void
{
    if ($oldCategory === '' || $newCategory === '' || $oldCategory === $newCategory) {
        return;
    }

    foreach ($siteData['homepage']['vehicles'] ?? [] as $idx => $vehicle) {
        if (!is_array($vehicle)) {
            continue;
        }
        if (trim((string) ($vehicle['category'] ?? '')) === $oldCategory) {
            $siteData['homepage']['vehicles'][$idx]['category'] = $newCategory;
        }
    }
}

/**
 * Guarda nombre y orden de categorías desde el tab Vehículos / Flota.
 *
 * @param array<string, array<string, mixed>> $postedCategories
 * @return string|null
 */
function am_apply_fleet_categories_from_post(array &$siteData, array $postedCategories): ?string
{
    if (!isset($siteData['homepage']['fleet_carousel']['items']) || !is_array($siteData['homepage']['fleet_carousel']['items'])) {
        $siteData['homepage']['fleet_carousel']['items'] = [];
    }

    $existingById = [];
    foreach ($siteData['homepage']['fleet_carousel']['items'] as $item) {
        if (!is_array($item)) {
            continue;
        }
        $existingById[intval($item['id'] ?? 0)] = $item;
    }

    $updated = [];

    foreach ($postedCategories as $rawId => $data) {
        if (!is_array($data)) {
            continue;
        }

        $id = intval($rawId);
        if ($id <= 0 || !isset($existingById[$id])) {
            continue;
        }

        $existing = $existingById[$id];
        $oldCategory = trim((string) ($existing['category'] ?? $existing['label'] ?? ''));
        $newLabel = trim((string) ($data['label'] ?? ''));
        $sortOrder = intval($data['sort_order'] ?? 0);

        if ($newLabel === '') {
            return 'Todas las categorías deben tener un nombre.';
        }

        if ($oldCategory !== '' && $oldCategory !== $newLabel) {
            am_rename_fleet_vehicle_category($siteData, $oldCategory, $newLabel);
        }

        $updated[] = array_merge($existing, [
            'id' => $id,
            'label' => $newLabel,
            'category' => $newLabel,
            'sort_order' => $sortOrder > 0 ? $sortOrder : intval($existing['sort_order'] ?? 999),
        ]);
    }

    if ($updated === []) {
        return 'No se recibieron categorías válidas para guardar.';
    }

    $siteData['homepage']['fleet_carousel']['items'] = am_fleet_categories_sorted($updated);

    return null;
}
