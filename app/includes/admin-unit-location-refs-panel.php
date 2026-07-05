<?php
/**
 * Panel reutilizable: asociar sucursales del maestro vía location_refs[].
 *
 * Variables requeridas:
 *   $ulrUnitKey     — rentacar|seminuevos|leasing|renting|taller|footer
 *   $ulrTabSlug     — slug tab para action URL (?tab=...)
 *   $ulrTitle       — título del panel
 *   $ulrSiteData    — site_data completo
 *   $ulrSaveAction  — action POST (default save_unit_location_refs)
 *
 * Opcionales:
 *   $ulrDescription — texto intro
 *   $ulrShowFooterUnit — bool, selector unit por fila (footer)
 */

require_once __DIR__ . '/admin-location-helper.php';
require_once __DIR__ . '/admin-location-select.php';

$ulrUnitKey = $ulrUnitKey ?? 'rentacar';
$ulrTabSlug = $ulrTabSlug ?? 'sucursales';
$ulrTitle = $ulrTitle ?? 'Sucursales asociadas';
$ulrSiteData = $ulrSiteData ?? [];
$ulrSaveAction = $ulrSaveAction ?? 'save_unit_location_refs';
$ulrDescription = $ulrDescription ?? 'Seleccione sucursales del maestro. Los datos públicos (nombre, dirección, teléfono) se leen desde <strong>Sucursales maestro</strong>.';
$ulrShowFooterUnit = !empty($ulrShowFooterUnit);

$sections = admin_location_unit_sections();
$ulrSection = $sections[$ulrUnitKey] ?? 'homepage';
$refs = admin_get_section_location_refs($ulrSiteData, $ulrSection);
$service = new LocationService($ulrSiteData);
$allActive = getActiveLocations($ulrSiteData, true);

$usedIds = [];
foreach ($refs as $ref) {
    $lid = trim((string) ($ref['location_id'] ?? ''));
    if ($lid !== '') {
        $usedIds[] = $lid;
    }
}

$unitLabels = [
    'grupo'      => 'Grupo Automarket',
    'rentacar'   => 'Rent A Car',
    'seminuevos' => 'Venta de Autos',
    'leasing'    => 'Leasing',
    'renting'    => 'Renting',
    'taller'     => 'Taller',
];
?>
<div class="admin-card mb-4" id="ulr-panel-<?php echo esc($ulrUnitKey); ?>">
    <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
        <i class="bi bi-pin-map-fill me-2 text-danger"></i><?php echo esc($ulrTitle); ?>
    </h5>
    <p class="text-muted small mb-3">
        <?php echo $ulrDescription; ?>
        <a href="?tab=locations-master" class="fw-semibold text-decoration-none">Ir a Sucursales maestro</a>
        para crear o editar ubicaciones.
    </p>

    <?php if (empty($allActive)): ?>
    <div class="alert alert-warning small mb-0">
        No hay sucursales activas en el maestro. Cree ubicaciones en <a href="?tab=locations-master">Sucursales maestro</a> primero.
    </div>
    <?php else: ?>
    <form method="POST" action="?tab=<?php echo esc($ulrTabSlug); ?>" id="ulrForm-<?php echo esc($ulrUnitKey); ?>">
        <input type="hidden" name="action" value="<?php echo esc($ulrSaveAction); ?>">
        <input type="hidden" name="ulr_unit_key" value="<?php echo esc($ulrUnitKey); ?>">
        <div id="ulrRows-<?php echo esc($ulrUnitKey); ?>">
            <?php if ($refs === []): ?>
            <p class="text-muted small mb-3" id="ulrEmpty-<?php echo esc($ulrUnitKey); ?>">No hay sucursales asociadas. Use el botón para agregar desde el maestro.</p>
            <?php else: ?>
            <?php foreach ($refs as $i => $ref):
                $refId = trim((string) ($ref['location_id'] ?? ''));
                $loc = $refId !== '' ? $service->getById($refId) : null;
                $isInactive = $loc !== null && ($loc['active'] ?? true) === false;
                $isMissing = $refId !== '' && $loc === null;
            ?>
            <div class="border rounded p-3 mb-3 bg-light position-relative ulr-row" data-ulr-row>
                <button type="button" class="btn btn-sm btn-outline-danger border-0 position-absolute top-0 end-0 mt-2 me-2" onclick="ulrRemoveRow(this)" title="Quitar asociación"><i class="bi bi-x-lg"></i></button>
                <div class="row g-2 align-items-end">
                    <div class="<?php echo $ulrShowFooterUnit ? 'col-md-5' : 'col-md-7'; ?>">
                        <label class="form-label fw-semibold small text-muted mb-1">Sucursal maestro *</label>
                        <?php
                        admin_render_location_select([
                            'siteData' => $ulrSiteData,
                            'name' => 'ulr_location_id[]',
                            'selected' => $refId,
                            'required' => true,
                            'allow_empty' => false,
                            'show_inactive_selected' => true,
                            'legacy_unmapped' => $isMissing ? $refId : '',
                        ]);
                        ?>
                        <?php if ($isInactive): ?>
                        <div class="form-text text-warning small">Referencia a sucursal inactiva — revise en maestro.</div>
                        <?php elseif ($isMissing): ?>
                        <div class="form-text text-danger small">location_id «<?php echo esc($refId); ?>» no existe en maestro.</div>
                        <?php endif; ?>
                    </div>
                    <?php if ($ulrShowFooterUnit): ?>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-muted mb-1">Unidad footer</label>
                        <select name="ulr_footer_unit[]" class="form-select form-control-premium">
                            <?php foreach ($unitLabels as $uk => $ul): ?>
                            <option value="<?php echo esc($uk); ?>" <?php echo (($ref['unit'] ?? 'grupo') === $uk) ? 'selected' : ''; ?>><?php echo esc($ul); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="ulr_footer_unit[]" value="">
                    <?php endif; ?>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small text-muted mb-1">Orden</label>
                        <input type="number" name="ulr_sort_order[]" class="form-control form-control-premium" value="<?php echo (int) ($ref['sort_order'] ?? ($i + 1) * 10); ?>" min="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small text-muted mb-1">Activa</label>
                        <select name="ulr_active[]" class="form-select form-control-premium">
                            <option value="1" <?php echo (($ref['active'] ?? true) !== false) ? 'selected' : ''; ?>>Sí</option>
                            <option value="0" <?php echo (($ref['active'] ?? true) === false) ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                </div>
                <?php if ($loc !== null): ?>
                <div class="small text-muted mt-2">
                    <?php echo esc(admin_location_select_label($loc)); ?>
                    <?php if (!empty($loc['address'])): ?> · <?php echo esc($loc['address']); ?><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="ulrAddRow('<?php echo esc($ulrUnitKey); ?>', <?php echo $ulrShowFooterUnit ? 'true' : 'false'; ?>)">
                <i class="bi bi-plus-lg me-1"></i> Asociar sucursal del maestro
            </button>
            <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                <i class="bi bi-save"></i> Guardar asociaciones
            </button>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
(function () {
    if (window.ulrAddRow) {
        return;
    }
    window.ulrRemoveRow = function (btn) {
        var row = btn.closest('[data-ulr-row]');
        if (row) {
            row.remove();
        }
    };
    window.ulrAddRow = function (unitKey, showFooterUnit) {
        var container = document.getElementById('ulrRows-' + unitKey);
        var empty = document.getElementById('ulrEmpty-' + unitKey);
        if (empty) {
            empty.remove();
        }
        if (!container) {
            return;
        }
        var tpl = document.getElementById('ulrRowTemplate');
        if (!tpl) {
            return;
        }
        var clone = tpl.content.cloneNode(true);
        if (!showFooterUnit) {
            var fu = clone.querySelector('[data-ulr-footer-unit-col]');
            if (fu) {
                fu.remove();
            }
        }
        container.appendChild(clone);
    };
})();
</script>

<template id="ulrRowTemplate">
    <div class="border rounded p-3 mb-3 bg-light position-relative ulr-row" data-ulr-row>
        <button type="button" class="btn btn-sm btn-outline-danger border-0 position-absolute top-0 end-0 mt-2 me-2" onclick="ulrRemoveRow(this)" title="Quitar asociación"><i class="bi bi-x-lg"></i></button>
        <div class="row g-2 align-items-end">
            <div class="col-md-7" data-ulr-select-col>
                <label class="form-label fw-semibold small text-muted mb-1">Sucursal maestro *</label>
                <select name="ulr_location_id[]" class="form-select form-control-premium" required>
                    <option value="">Seleccione sucursal…</option>
                    <?php foreach ($allActive as $loc): ?>
                    <option value="<?php echo esc($loc['id'] ?? ''); ?>"><?php echo esc(admin_location_select_label($loc)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($ulrShowFooterUnit): ?>
            <div class="col-md-3" data-ulr-footer-unit-col>
                <label class="form-label fw-semibold small text-muted mb-1">Unidad footer</label>
                <select name="ulr_footer_unit[]" class="form-select form-control-premium">
                    <?php foreach ($unitLabels as $uk => $ul): ?>
                    <option value="<?php echo esc($uk); ?>"><?php echo esc($ul); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="ulr_footer_unit[]" value="">
            <?php endif; ?>
            <div class="col-md-2">
                <label class="form-label fw-semibold small text-muted mb-1">Orden</label>
                <input type="number" name="ulr_sort_order[]" class="form-control form-control-premium" value="99" min="0">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small text-muted mb-1">Activa</label>
                <select name="ulr_active[]" class="form-select form-control-premium">
                    <option value="1" selected>Sí</option>
                    <option value="0">No</option>
                </select>
            </div>
        </div>
    </div>
</template>
