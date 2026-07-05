<?php
/**
 * Helpers admin + resolución de sucursales desde maestro locations[] (AM-CMS-LOCATION-REFS).
 */

require_once __DIR__ . '/../services/LocationService.php';

/**
 * Sucursales activas del maestro, ordenadas.
 *
 * @param array<string, mixed> $siteData
 * @return list<array<string, mixed>>
 */
function getActiveLocations(array $siteData, bool $activeOnly = true): array
{
    $locations = $siteData['locations'] ?? [];
    if (!is_array($locations)) {
        return [];
    }

    $list = [];
    foreach ($locations as $loc) {
        if (!is_array($loc)) {
            continue;
        }
        $id = trim((string) ($loc['id'] ?? ''));
        if ($id === '') {
            continue;
        }
        if ($activeOnly && ($loc['active'] ?? true) === false) {
            continue;
        }
        $list[] = $loc;
    }

    usort($list, function (array $a, array $b): int {
        $oa = (int) ($a['sort_order'] ?? 99);
        $ob = (int) ($b['sort_order'] ?? 99);

        return $oa !== $ob ? $oa - $ob : strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
    });

    return $list;
}

/**
 * Clave normalizada para matching de nombres legacy.
 */
function admin_location_name_key(string $name): string
{
    $key = mb_strtolower(trim($name));
    $key = preg_replace('/\s+/', ' ', $key) ?? $key;
    if (function_exists('iconv')) {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $key);
        if ($ascii !== false) {
            $key = $ascii;
        }
    }

    return preg_replace('/[^a-z0-9]+/', '', $key) ?? '';
}

/**
 * Busca location_id por nombre legacy normalizado.
 *
 * @param array<string, mixed> $siteData
 */
function admin_match_location_by_legacy_name(array $siteData, string $legacyName): ?array
{
    $legacyName = trim($legacyName);
    if ($legacyName === '') {
        return null;
    }

    $key = admin_location_name_key($legacyName);
    if ($key === '') {
        return null;
    }

    $service = new LocationService($siteData);
    foreach ($service->getAll() as $loc) {
        if (admin_location_name_key((string) ($loc['name'] ?? '')) === $key) {
            return $loc;
        }
        $label = trim((string) ($loc['location_label'] ?? ''));
        if ($label !== '' && admin_location_name_key($label) === $key) {
            return $loc;
        }
    }

    return null;
}

/**
 * Resuelve referencia a sucursal: id, slug o nombre legacy.
 * Devuelve datos planos para render; fallback legacy si no hay match en maestro.
 *
 * @param array<string, mixed> $siteData
 * @param array<string, mixed>|null $legacyRow  Datos legacy opcionales (name, address, phone, …)
 * @return array<string, mixed>|null
 */
function resolveLocationRef(array $siteData, ?string $locationIdOrSlug, ?string $legacyName = null, ?array $legacyRow = null): ?array
{
    $service = new LocationService($siteData);
    $location = null;

    $ref = trim((string) ($locationIdOrSlug ?? ''));
    if ($ref !== '') {
        $location = $service->getById($ref) ?? $service->getBySlug($ref);
    }

    if ($location === null && $legacyName !== null && trim($legacyName) !== '') {
        $location = admin_match_location_by_legacy_name($siteData, $legacyName);
    }

    if ($location !== null) {
        $phones = $location['phones'] ?? [];
        $primaryPhone = is_array($phones) && $phones !== [] ? (string) $phones[0] : '';

        return [
            'id'             => (string) ($location['id'] ?? ''),
            'slug'           => (string) ($location['slug'] ?? ''),
            'name'           => (string) ($location['name'] ?? ''),
            'location_label' => (string) ($location['location_label'] ?? ''),
            'address'        => (string) ($location['address'] ?? ''),
            'city'           => (string) ($location['city'] ?? ''),
            'phone'          => $primaryPhone,
            'whatsapp'       => (string) ($location['whatsapp'] ?? ''),
            'email'          => (string) ($location['email'] ?? ''),
            'schedule'       => (string) ($location['hours']['display'] ?? ''),
            'lat'            => (string) ($location['lat'] ?? ''),
            'lng'            => (string) ($location['lng'] ?? ''),
            'map_url'        => (string) ($location['map_url'] ?? ''),
            'map_embed_url'  => (string) ($location['map_embed_url'] ?? ''),
            'rac_code'       => (string) ($location['rac_code'] ?? ''),
            'active'         => ($location['active'] ?? true) !== false,
            'from_master'    => true,
            'legacy_fallback'=> false,
        ];
    }

    if ($legacyRow !== null && is_array($legacyRow)) {
        $name = trim((string) ($legacyRow['name'] ?? $legacyName ?? ''));
        if ($name === '') {
            return null;
        }

        return [
            'id'             => trim((string) ($legacyRow['location_id'] ?? '')),
            'slug'           => trim((string) ($legacyRow['slug'] ?? '')),
            'name'           => $name,
            'location_label' => trim((string) ($legacyRow['location'] ?? $legacyRow['location_label'] ?? '')),
            'address'        => trim((string) ($legacyRow['address'] ?? '')),
            'city'           => trim((string) ($legacyRow['city'] ?? '')),
            'phone'          => trim((string) ($legacyRow['phone'] ?? '')),
            'whatsapp'       => trim((string) ($legacyRow['whatsapp'] ?? '')),
            'email'          => trim((string) ($legacyRow['email'] ?? '')),
            'schedule'       => trim((string) ($legacyRow['schedule'] ?? '')),
            'lat'            => trim((string) ($legacyRow['lat'] ?? '')),
            'lng'            => trim((string) ($legacyRow['lng'] ?? '')),
            'map_url'        => trim((string) ($legacyRow['map_url'] ?? '')),
            'map_embed_url'  => '',
            'rac_code'       => '',
            'active'         => ($legacyRow['active'] ?? true) !== false,
            'from_master'    => false,
            'legacy_fallback'=> true,
        ];
    }

    if ($legacyName !== null && trim($legacyName) !== '') {
        return [
            'id'             => '',
            'slug'           => '',
            'name'           => trim($legacyName),
            'location_label' => '',
            'address'        => '',
            'city'           => '',
            'phone'          => '',
            'whatsapp'       => '',
            'email'          => '',
            'schedule'       => '',
            'lat'            => '',
            'lng'            => '',
            'map_url'        => '',
            'map_embed_url'  => '',
            'rac_code'       => '',
            'active'         => true,
            'from_master'    => false,
            'legacy_fallback'=> true,
        ];
    }

    return null;
}

/**
 * @return array<string, string> unitKey => section key
 */
function admin_location_unit_sections(): array
{
    return [
        'rentacar'   => 'homepage',
        'seminuevos' => 'seminuevos',
        'leasing'    => 'leasing',
        'renting'    => 'renting',
        'taller'     => 'taller',
        'footer'     => 'footer',
    ];
}

/**
 * @param array<string, mixed> $siteData
 * @return list<array<string, mixed>>
 */
function admin_get_section_location_refs(array $siteData, string $section): array
{
    $refs = $siteData[$section]['location_refs'] ?? [];

    return is_array($refs) ? array_values(array_filter($refs, 'is_array')) : [];
}

/**
 * Valida location_id contra maestro activo.
 *
 * @param array<string, mixed> $siteData
 */
function admin_is_valid_active_location_id(array $siteData, string $locationId): bool
{
    $locationId = trim($locationId);
    if ($locationId === '') {
        return false;
    }
    $service = new LocationService($siteData);
    $loc = $service->getById($locationId);

    return $loc !== null && ($loc['active'] ?? true) !== false;
}

/**
 * @param list<string> $locationIds
 */
function admin_validate_no_duplicate_location_ids(array $locationIds): ?string
{
    $seen = [];
    foreach ($locationIds as $id) {
        $id = trim((string) $id);
        if ($id === '') {
            continue;
        }
        if (isset($seen[$id])) {
            return 'No puede repetir la misma sucursal en el mismo bloque.';
        }
        $seen[$id] = true;
    }

    return null;
}

/**
 * Guarda location_refs para una sección de unidad.
 *
 * @param array<string, mixed> $siteData
 * @param list<array{location_id: string, sort_order?: int, active?: bool, unit?: string}> $refs
 * @return list<string> errores
 */
function admin_save_section_location_refs(array &$siteData, string $section, array $refs): array
{
    $errors = [];
    $locationIds = [];
    $normalized = [];

    foreach ($refs as $ref) {
        if (!is_array($ref)) {
            continue;
        }
        $locationId = trim((string) ($ref['location_id'] ?? ''));
        if ($locationId === '') {
            continue;
        }

        if (!admin_is_valid_active_location_id($siteData, $locationId)) {
            $service = new LocationService($siteData);
            $existing = $service->getById($locationId);
            if ($existing === null) {
                $errors[] = 'La sucursal «' . $locationId . '» no existe en el maestro.';
                continue;
            }
            if (($existing['active'] ?? true) === false) {
                $errors[] = 'La sucursal «' . ($existing['name'] ?? $locationId) . '» está inactiva; no puede agregarse en nuevos selects.';
                continue;
            }
        }

        $locationIds[] = $locationId;
        $row = [
            'location_id' => $locationId,
            'sort_order'  => (int) ($ref['sort_order'] ?? 99),
            'active'      => ($ref['active'] ?? true) !== false,
        ];
        if ($section === 'footer' && isset($ref['unit'])) {
            $row['unit'] = trim((string) $ref['unit']) ?: 'grupo';
        }
        $normalized[] = $row;
    }

    $dupErr = admin_validate_no_duplicate_location_ids($locationIds);
    if ($dupErr !== null) {
        $errors[] = $dupErr;
    }

    if ($errors !== []) {
        return $errors;
    }

    if (!isset($siteData[$section]) || !is_array($siteData[$section])) {
        $siteData[$section] = [];
    }
    $siteData[$section]['location_refs'] = $normalized;

    return [];
}

/**
 * Etiqueta legible para option de select.
 *
 * @param array<string, mixed> $location
 */
function admin_location_select_label(array $location): string
{
    $name = trim((string) ($location['name'] ?? ''));
    $label = trim((string) ($location['location_label'] ?? ''));
    if ($label !== '' && stripos($name, $label) === false) {
        return $name . ' — ' . $label;
    }

    return $name;
}

/**
 * Resuelve agent/lead branch: valida location_id y devuelve branch label + location_id.
 *
 * @param array<string, mixed> $siteData
 * @return array{ok: bool, location_id: string, branch_label: string, error: string, legacy_warning: bool}
 */
function admin_resolve_agent_location_post(array $siteData, string $locationId, string $legacyBranch = ''): array
{
    $locationId = trim($locationId);
    $legacyBranch = trim($legacyBranch);

    if ($locationId !== '') {
        $resolved = resolveLocationRef($siteData, $locationId);
        if ($resolved === null || ($resolved['from_master'] ?? false) !== true) {
            return [
                'ok' => false,
                'location_id' => '',
                'branch_label' => '',
                'error' => 'La sucursal seleccionada no existe en el maestro o está inactiva.',
                'legacy_warning' => false,
            ];
        }
        if (($resolved['active'] ?? true) === false) {
            return [
                'ok' => false,
                'location_id' => $locationId,
                'branch_label' => (string) ($resolved['name'] ?? ''),
                'error' => 'La sucursal seleccionada está inactiva.',
                'legacy_warning' => true,
            ];
        }

        return [
            'ok' => true,
            'location_id' => $locationId,
            'branch_label' => (string) ($resolved['name'] ?? ''),
            'error' => '',
            'legacy_warning' => false,
        ];
    }

    if ($legacyBranch !== '') {
        $matched = admin_match_location_by_legacy_name($siteData, $legacyBranch);
        if ($matched !== null) {
            return [
                'ok' => true,
                'location_id' => (string) ($matched['id'] ?? ''),
                'branch_label' => (string) ($matched['name'] ?? $legacyBranch),
                'error' => '',
                'legacy_warning' => false,
            ];
        }

        return [
            'ok' => true,
            'location_id' => '',
            'branch_label' => $legacyBranch,
            'error' => '',
            'legacy_warning' => true,
        ];
    }

    return [
        'ok' => false,
        'location_id' => '',
        'branch_label' => '',
        'error' => 'Seleccione una sucursal del maestro.',
        'legacy_warning' => false,
    ];
}

/**
 * Procesa POST ulr_* y escribe location_refs en siteData.
 *
 * @param array<string, mixed> $siteData
 * @param array<string, mixed> $post
 * @return string|null  Mensaje de error o null si OK
 */
function admin_apply_unit_location_refs_post(array &$siteData, array $post): ?string
{
    $unitKey = trim((string) ($post['ulr_unit_key'] ?? ''));
    $sections = admin_location_unit_sections();
    $section = $sections[$unitKey] ?? null;

    if ($section === null) {
        return 'Unidad no válida para asociar sucursales.';
    }

    $locationIds = $post['ulr_location_id'] ?? [];
    $sortOrders = $post['ulr_sort_order'] ?? [];
    $actives = $post['ulr_active'] ?? [];
    $footerUnits = $post['ulr_footer_unit'] ?? [];

    if (!is_array($locationIds)) {
        $locationIds = [];
    }

    $refs = [];
    foreach ($locationIds as $i => $locationId) {
        $locationId = trim((string) $locationId);
        if ($locationId === '') {
            continue;
        }
        $ref = [
            'location_id' => $locationId,
            'sort_order'  => (int) ($sortOrders[$i] ?? 99),
            'active'      => (string) ($actives[$i] ?? '1') === '1',
        ];
        if ($section === 'footer') {
            $ref['unit'] = trim((string) ($footerUnits[$i] ?? 'grupo')) ?: 'grupo';
        }
        $refs[] = $ref;
    }

    $errors = admin_save_section_location_refs($siteData, $section, $refs);

    return $errors !== [] ? implode(' ', $errors) : null;
}
