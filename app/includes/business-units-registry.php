<?php
/**
 * Claves oficiales de unidades de negocio (config/business-units.php).
 */

/** @return list<string> */
function am_builtin_business_unit_keys(): array
{
    static $keys = null;
    if ($keys === null) {
        $keys = array_keys(require __DIR__ . '/../config/business-units.php');
    }

    return $keys;
}

/** @param array<string, mixed> $units */
function am_filter_builtin_business_units(array $units): array
{
    $filtered = [];
    foreach (am_builtin_business_unit_keys() as $key) {
        if (isset($units[$key]) && is_array($units[$key])) {
            $filtered[$key] = $units[$key];
        }
    }

    return $filtered;
}

/** @param array<string, mixed> $siteData */
function am_strip_custom_business_units(array &$siteData): bool
{
    if (!isset($siteData['global']['business_units']) || !is_array($siteData['global']['business_units'])) {
        return false;
    }

    $filtered = am_filter_builtin_business_units($siteData['global']['business_units']);
    if ($filtered === $siteData['global']['business_units']) {
        return false;
    }

    $siteData['global']['business_units'] = $filtered;

    return true;
}
