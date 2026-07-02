<?php
/**
 * Dual-read público: locations[] maestro + fallback silos legacy (AM-SEO-3C-C).
 */

require_once __DIR__ . '/../services/LocationService.php';

/**
 * @return string|null
 */
function am_location_section_for_unit(string $unitKey): ?string
{
    static $map = [
        'rentacar'   => 'homepage',
        'seminuevos' => 'seminuevos',
        'leasing'    => 'leasing',
        'renting'    => 'renting',
        'taller'     => 'taller',
        'footer'     => 'footer',
    ];

    return $map[$unitKey] ?? null;
}

function am_unit_has_location_refs(array $siteData, string $unitKey): bool
{
    $section = am_location_section_for_unit($unitKey);
    if ($section === null) {
        return false;
    }

    $refs = $siteData[$section]['location_refs'] ?? [];
    if (!is_array($refs)) {
        return false;
    }

    foreach ($refs as $ref) {
        if (!is_array($ref)) {
            continue;
        }
        if (($ref['active'] ?? true) === false) {
            continue;
        }
        if (trim((string) ($ref['location_id'] ?? '')) !== '') {
            return true;
        }
    }

    return false;
}

/**
 * @param array<int|string, mixed> $raw
 * @return list<array<string, mixed>>
 */
function am_filter_legacy_sucursales(array $raw): array
{
    $sucursales = array_values(array_filter($raw, function ($s) {
        if (!is_array($s)) {
            return false;
        }

        return !isset($s['active']) || $s['active'] === true || $s['active'] === 'true' || $s['active'] == 1;
    }));

    usort($sucursales, function ($a, $b) {
        $oa = intval($a['sort_order'] ?? 0);
        $ob = intval($b['sort_order'] ?? 0);

        return $oa !== $ob ? $oa - $ob : strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
    });

    return $sucursales;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function am_map_master_to_sucursales_card(array $row, int $index): array
{
    $rawId = $row['id'] ?? '';
    $numericId = is_numeric($rawId) ? (int) $rawId : ($index + 1);

    $mapUrl = trim((string) ($row['map_url'] ?? ''));
    if ($mapUrl === '') {
        $mapUrl = trim((string) ($row['map_embed_url'] ?? ''));
    }

    return [
        'id'         => $numericId,
        'name'       => trim((string) ($row['name'] ?? '')),
        'location'   => trim((string) ($row['location_label'] ?? ($row['location'] ?? ''))),
        'address'    => trim((string) ($row['address'] ?? '')),
        'schedule'   => trim((string) ($row['schedule'] ?? '')),
        'phone'      => trim((string) ($row['phone'] ?? '')),
        'whatsapp'   => trim((string) ($row['whatsapp'] ?? '')),
        'email'      => trim((string) ($row['email'] ?? '')),
        'lat'        => trim((string) ($row['lat'] ?? '')),
        'lng'        => trim((string) ($row['lng'] ?? '')),
        'image_url'  => trim((string) ($row['image_url'] ?? '')),
        'map_url'    => $mapUrl,
        'active'     => ($row['active'] ?? true) !== false,
        'sort_order' => (int) ($row['sort_order'] ?? 99),
    ];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function am_map_master_to_branch_card(array $row, int $index): array
{
    $card = am_map_master_to_sucursales_card($row, $index);
    unset($card['id'], $card['location'], $card['lat'], $card['lng'], $card['active']);

    return $card;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function am_map_master_to_footer_card(array $row, string $unit): array
{
    return [
        'name'       => trim((string) ($row['name'] ?? '')),
        'location'   => trim((string) ($row['location_label'] ?? ($row['location'] ?? ''))),
        'address'    => trim((string) ($row['address'] ?? '')),
        'schedule'   => trim((string) ($row['schedule'] ?? '')),
        'phone'      => trim((string) ($row['phone'] ?? '')),
        'lat'        => trim((string) ($row['lat'] ?? '')),
        'lng'        => trim((string) ($row['lng'] ?? '')),
        'unit'       => $unit,
        'sort_order' => (int) ($row['sort_order'] ?? 99),
        'active'     => ($row['active'] ?? true) !== false,
    ];
}

/**
 * @param array<int|string, mixed> $legacyRaw
 * @return list<array<string, mixed>>
 */
function am_list_sucursales_for_unit(ContentService $contentService, string $unitKey, array $legacyRaw): array
{
    $siteData = $contentService->getAll();

    if (am_unit_has_location_refs($siteData, $unitKey)) {
        $locationService = new LocationService($siteData);
        $fromMaster = $locationService->listForUnit($unitKey, true);

        if ($fromMaster !== []) {
            $mapped = [];
            foreach ($fromMaster as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }
                $card = am_map_master_to_sucursales_card($row, (int) $i);
                if ($card['name'] !== '') {
                    $mapped[] = $card;
                }
            }
            if ($mapped !== []) {
                return $mapped;
            }
        }
    }

    return am_filter_legacy_sucursales($legacyRaw);
}

/**
 * @param array<int|string, mixed> $legacyBranches
 * @return list<array<string, mixed>>
 */
function am_list_branches_for_unit(ContentService $contentService, string $unitKey, array $legacyBranches): array
{
    $siteData = $contentService->getAll();

    if (am_unit_has_location_refs($siteData, $unitKey)) {
        $locationService = new LocationService($siteData);
        $fromMaster = $locationService->listForUnit($unitKey, true);

        if ($fromMaster !== []) {
            $mapped = [];
            foreach ($fromMaster as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }
                $card = am_map_master_to_branch_card($row, (int) $i);
                if ($card['name'] !== '') {
                    $mapped[] = $card;
                }
            }
            if ($mapped !== []) {
                return $mapped;
            }
        }
    }

    return array_values(array_filter($legacyBranches, function ($item) {
        if (!is_array($item)) {
            return false;
        }

        return trim((string) ($item['name'] ?? '')) !== '';
    }));
}

/**
 * @return list<array<string, mixed>>
 */
function am_list_footer_sucursales(ContentService $contentService, FooterService $footerService): array
{
    $siteData = $contentService->getAll();

    if (!am_unit_has_location_refs($siteData, 'footer')) {
        return $footerService->getActiveSucursales();
    }

    $refs = $siteData['footer']['location_refs'] ?? [];
    if (!is_array($refs)) {
        return $footerService->getActiveSucursales();
    }

    $locationService = new LocationService($siteData);
    $out = [];

    foreach ($refs as $ref) {
        if (!is_array($ref)) {
            continue;
        }
        if (($ref['active'] ?? true) === false) {
            continue;
        }

        $locationId = trim((string) ($ref['location_id'] ?? ''));
        if ($locationId === '') {
            continue;
        }

        $location = $locationService->getById($locationId);
        if ($location === null || ($location['active'] ?? true) === false) {
            continue;
        }

        $displayUnit = trim((string) ($ref['unit'] ?? 'grupo'));
        if ($displayUnit === '') {
            $displayUnit = 'grupo';
        }

        $resolveUnit = $displayUnit === 'grupo' ? 'rentacar' : $displayUnit;
        $unitOverride = is_array($location['units'][$resolveUnit] ?? null)
            ? $location['units'][$resolveUnit]
            : [];

        $resolved = $locationService->resolveForUnit($resolveUnit, $location, $unitOverride);
        $card = am_map_master_to_footer_card($resolved, $displayUnit);
        $card['sort_order'] = (int) ($ref['sort_order'] ?? $card['sort_order'] ?? 99);

        if ($card['name'] !== '') {
            $out[] = $card;
        }
    }

    if ($out === []) {
        return $footerService->getActiveSucursales();
    }

    usort($out, function ($a, $b) {
        return intval($a['sort_order'] ?? 99) - intval($b['sort_order'] ?? 99);
    });

    return array_values($out);
}
