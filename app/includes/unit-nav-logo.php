<?php
/**
 * Logo de unidad en navbar (junto al logo Automarket).
 */

function am_ensure_global_business_unit_node(array &$siteData, string $unitKey): void
{
    if (!isset($siteData['global']) || !is_array($siteData['global'])) {
        $siteData['global'] = [];
    }
    if (!isset($siteData['global']['business_units']) || !is_array($siteData['global']['business_units'])) {
        $siteData['global']['business_units'] = [];
    }
    if (!isset($siteData['global']['business_units'][$unitKey]) || !is_array($siteData['global']['business_units'][$unitKey])) {
        $siteData['global']['business_units'][$unitKey] = [];
    }
}

function am_unit_nav_logo_url(array $unit): string
{
    return trim((string) ($unit['nav_logo_url'] ?? ''));
}

/**
 * @return string|null Mensaje de error o null si todo OK
 */
function am_apply_unit_nav_logo_from_post(
    array &$siteData,
    string $unitKey,
    ContentService $contentService,
    string $fileField = 'unit_nav_logo',
    string $removeField = 'remove_unit_nav_logo'
): ?string {
    require_once __DIR__ . '/business-units-registry.php';
    am_ensure_global_business_unit_node($siteData, $unitKey);

    if (isset($_POST[$removeField]) && (string) $_POST[$removeField] === '1') {
        $siteData['global']['business_units'][$unitKey]['nav_logo_url'] = '';
        return null;
    }

    if (
        isset($_FILES[$fileField])
        && is_array($_FILES[$fileField])
        && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK
    ) {
        $uploaded = $contentService->uploadImage($_FILES[$fileField], 'nav_logo_' . $unitKey . '_');
        if (!$uploaded) {
            return 'No se pudo subir el logo del header (formato inválido o supera los 5MB).';
        }
        $siteData['global']['business_units'][$unitKey]['nav_logo_url'] = $uploaded;
    }

    return null;
}
