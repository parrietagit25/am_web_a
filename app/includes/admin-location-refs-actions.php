<?php
/**
 * Acciones POST comunes para location_refs[] desde módulos secundarios.
 *
 * Espera variables del admin: $action, $siteData, $contentService, &$successMsg, &$errorMsg
 */

require_once __DIR__ . '/admin-location-helper.php';

if (($action ?? '') === 'save_unit_location_refs') {
    require_once __DIR__ . '/admin-location-helper.php';
    $applyError = admin_apply_unit_location_refs_post($siteData, $_POST);
    if ($applyError !== null) {
        $errorMsg = $applyError;
    } elseif ($contentService->saveAll($siteData)) {
        $successMsg = 'Asociaciones de sucursales guardadas correctamente.';
    } else {
        $errorMsg = 'Error al guardar las asociaciones de sucursales.';
    }
}

if (($action ?? '') === 'sync_global_from_master') {
    require_once __DIR__ . '/../services/GlobalSucursalesService.php';

    $locations = getActiveLocations($siteData, true);
    if ($locations === []) {
        $errorMsg = 'No hay sucursales activas en el maestro para sincronizar.';
    } else {
        if (!isset($siteData['global']) || !is_array($siteData['global'])) {
            $siteData['global'] = [];
        }

        $existing = [];
        foreach ($siteData['global']['sucursales'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $key = admin_location_name_key((string) ($row['name'] ?? ''));
            if ($key !== '') {
                $existing[$key] = $row;
            }
        }

        $list = [];
        $nextId = 1;
        foreach ($locations as $loc) {
            $name = trim((string) ($loc['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $key = admin_location_name_key($name);
            $row = $existing[$key] ?? [
                'name' => $name,
                'image_url' => (string) ($loc['image_url'] ?? ''),
                'lat' => (string) ($loc['lat'] ?? ''),
                'lng' => (string) ($loc['lng'] ?? ''),
            ];
            $row['id'] = $nextId++;
            $row['name'] = $name;
            $row['location_id'] = (string) ($loc['id'] ?? '');
            if (trim((string) ($row['image_url'] ?? '')) === '' && !empty($loc['image_url'])) {
                $row['image_url'] = (string) $loc['image_url'];
            }
            if (trim((string) ($row['lat'] ?? '')) === '' && !empty($loc['lat'])) {
                $row['lat'] = (string) $loc['lat'];
            }
            if (trim((string) ($row['lng'] ?? '')) === '' && !empty($loc['lng'])) {
                $row['lng'] = (string) $loc['lng'];
            }
            $list[] = $row;
        }

        $siteData['global']['sucursales'] = $list;

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Catálogo global sincronizado desde maestro (' . count($list) . ' sucursales).';
        } else {
            $errorMsg = 'Error al sincronizar sucursales globales.';
        }
    }
}

if (($action ?? '') === 'block_legacy_sucursal_create') {
    $errorMsg = 'La creación manual de sucursales está deshabilitada. Use Generales → Sucursales maestro y asocie la ubicación desde el panel de asociaciones.';
}
