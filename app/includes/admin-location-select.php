<?php
/**
 * Render de &lt;select&gt; de sucursales desde maestro locations[].
 */

require_once __DIR__ . '/admin-location-helper.php';

/**
 * @param array<string, mixed> $opts
 *   siteData (required), name, id, selected (location_id), required, disabled,
 *   class, placeholder, exclude_ids[], allow_empty, legacy_unmapped (string),
 *   show_inactive_selected (bool)
 */
function admin_render_location_select(array $opts): void
{
    $siteData = $opts['siteData'] ?? [];
    if (!is_array($siteData)) {
        $siteData = [];
    }

    $name = (string) ($opts['name'] ?? 'location_id');
    $id = (string) ($opts['id'] ?? $name);
    $selected = trim((string) ($opts['selected'] ?? ''));
    $required = !empty($opts['required']);
    $disabled = !empty($opts['disabled']);
    $class = (string) ($opts['class'] ?? 'form-select form-control-premium');
    $placeholder = (string) ($opts['placeholder'] ?? 'Seleccione sucursal…');
    $excludeIds = is_array($opts['exclude_ids'] ?? null) ? $opts['exclude_ids'] : [];
    $allowEmpty = array_key_exists('allow_empty', $opts) ? (bool) $opts['allow_empty'] : true;
    $legacyUnmapped = trim((string) ($opts['legacy_unmapped'] ?? ''));
    $showInactiveSelected = !empty($opts['show_inactive_selected']);

    $locations = getActiveLocations($siteData, true);
    $service = new LocationService($siteData);

    $selectedValid = $selected !== '' && admin_is_valid_active_location_id($siteData, $selected);
    $selectedInactive = false;
    if ($selected !== '' && !$selectedValid) {
        $selLoc = $service->getById($selected);
        if ($selLoc !== null && ($selLoc['active'] ?? true) === false) {
            $selectedInactive = true;
        }
    }

    echo '<select';
    echo ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"';
    echo ' name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"';
    echo ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"';
    if ($required) {
        echo ' required';
    }
    if ($disabled) {
        echo ' disabled';
    }
    echo '>';

    if ($allowEmpty) {
        echo '<option value="">' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '</option>';
    }

    foreach ($locations as $loc) {
        if (!is_array($loc)) {
            continue;
        }
        $locId = trim((string) ($loc['id'] ?? ''));
        if ($locId === '' || in_array($locId, $excludeIds, true)) {
            continue;
        }
        $label = admin_location_select_label($loc);
        echo '<option value="' . htmlspecialchars($locId, ENT_QUOTES, 'UTF-8') . '"';
        if ($selected === $locId) {
            echo ' selected';
        }
        echo '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
    }

    if ($selected !== '' && !$selectedValid && !$selectedInactive) {
        $resolved = resolveLocationRef($siteData, $selected, $legacyUnmapped);
        $fallbackLabel = $legacyUnmapped !== ''
            ? $legacyUnmapped . ' (legacy sin mapear)'
            : ($resolved['name'] ?? $selected) . ' (no encontrada en maestro)';
        echo '<option value="' . htmlspecialchars($selected, ENT_QUOTES, 'UTF-8') . '" selected>';
        echo htmlspecialchars($fallbackLabel, ENT_QUOTES, 'UTF-8');
        echo '</option>';
    }

    if ($selectedInactive && $showInactiveSelected) {
        $selLoc = $service->getById($selected);
        $label = admin_location_select_label(is_array($selLoc) ? $selLoc : []) . ' (inactiva)';
        echo '<option value="' . htmlspecialchars($selected, ENT_QUOTES, 'UTF-8') . '" selected>';
        echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        echo '</option>';
    }

    echo '</select>';

    if ($legacyUnmapped !== '' && $selected === '' && !admin_match_location_by_legacy_name($siteData, $legacyUnmapped)) {
        echo '<div class="form-text text-warning"><i class="bi bi-exclamation-triangle me-1"></i>';
        echo 'Valor legacy «' . htmlspecialchars($legacyUnmapped, ENT_QUOTES, 'UTF-8') . '» no coincide con el maestro. Seleccione una sucursal válida.';
        echo '</div>';
    } elseif ($selectedInactive) {
        echo '<div class="form-text text-warning"><i class="bi bi-exclamation-triangle me-1"></i>';
        echo 'Esta sucursal está inactiva en el maestro. El registro se conserva pero no aparecerá en nuevos formularios públicos.';
        echo '</div>';
    }
}
