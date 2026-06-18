<?php
/**
 * Sección admin: unidades de negocio (orden, alta y edición).
 * Requiere: $global['business_units'] ya fusionado y ordenado.
 */
$businessUnitsForAdmin = $global['business_units'] ?? [];
$businessUnitsOrder = array_keys($businessUnitsForAdmin);
?>
<h5 class="fw-bold mt-5 mb-2 font-montserrat border-bottom pb-2 text-navy">
    <i class="bi bi-list-stars me-2 text-danger"></i>Menú y Sub-títulos de Unidades de Negocio
</h5>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <p class="text-muted small mb-0">Arrastra las unidades para cambiar el orden en el menú superior del sitio.</p>
    <button type="button" class="btn btn-sm btn-danger rounded-pill px-3" id="buUnitAddBtn">
        <i class="bi bi-plus-lg me-1"></i>Nueva unidad de negocio
    </button>
</div>

<input type="hidden" name="business_units_order" id="businessUnitsOrderInput" value="<?php echo esc(json_encode($businessUnitsOrder, JSON_UNESCAPED_UNICODE)); ?>">

<div class="accordion" id="businessUnitsAccordion">
    <?php foreach ($businessUnitsForAdmin as $key => $unit): ?>
        <?php require __DIR__ . '/admin-business-units-accordion-item.php'; ?>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/admin-business-units-menu-modal.php'; ?>
<?php require __DIR__ . '/admin-business-units-unit-modal.php'; ?>
