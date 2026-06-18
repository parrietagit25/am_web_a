<?php
/**
 * Lista ordenable de enlaces del menú por unidad de negocio.
 * Requiere: $key (string), $unit (array).
 */
$buMenuItems = array_values($unit['menu'] ?? []);
?>
<div class="col-12 mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h6 class="fw-bold mb-0 text-navy-light">
            <i class="bi bi-link-45deg me-1"></i>Enlaces del Menú Secundario
        </h6>
        <button type="button"
                class="btn btn-sm btn-outline-danger rounded-pill px-3 bu-menu-add-btn"
                data-unit="<?php echo esc($key); ?>">
            <i class="bi bi-plus-lg me-1"></i>Agregar enlace
        </button>
    </div>
    <p class="text-muted small mb-2">Arrastra para cambiar el orden. Usa editar para agregar submenús. Para páginas internas use <code>unidad.php?u=<?php echo esc($key); ?>&amp;p=slug</code> (ej. <code>p=musica</code>).</p>
    <div class="bu-menu-sortable list-group mb-2" data-unit="<?php echo esc($key); ?>"></div>
    <div class="bu-menu-fields" data-unit="<?php echo esc($key); ?>" aria-hidden="true"></div>
    <script type="application/json" class="bu-menu-initial" data-unit="<?php echo esc($key); ?>"><?php
        echo json_encode($buMenuItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    ?></script>
</div>
