<?php
/**
 * Lista ordenable de enlaces del menú por unidad de negocio.
 * Requiere: $key (string), $unit (array), $buMenuTab (string).
 */
require_once __DIR__ . '/business-units-registry.php';
$buMenuItems = array_values($unit['menu'] ?? []);
$buIsCustomUnit = !am_is_builtin_business_unit($key);
$buMenuTab = trim((string) ($buMenuTab ?? 'global'));
$buMenuPublished = !array_key_exists('menu_published', $unit)
    || filter_var($unit['menu_published'], FILTER_VALIDATE_BOOLEAN);
?>
<div class="admin-card mt-4">
    <form method="POST" action="?tab=<?php echo esc($buMenuTab); ?>" class="bu-menu-form" data-unit="<?php echo esc($key); ?>">
        <input type="hidden" name="action" value="save_unit_menu">
        <input type="hidden" name="menu_unit" value="<?php echo esc($key); ?>">
        <input type="hidden" name="admin_tab" value="<?php echo esc($buMenuTab); ?>">
        <?php admin_csrf_field(); ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h5 class="fw-bold mb-0 text-navy">
                <i class="bi bi-list-nested me-2 text-danger"></i>Menú secundario — <?php echo esc($unit['label'] ?? $key); ?>
            </h5>
            <button type="button"
                    class="btn btn-sm btn-outline-danger rounded-pill px-3 bu-menu-add-btn"
                    data-unit="<?php echo esc($key); ?>">
                <i class="bi bi-plus-lg me-1"></i>Agregar enlace
            </button>
        </div>

        <input type="hidden" name="menu_published" value="0">
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="menu_published"
                   id="menu-published-<?php echo esc($key); ?>" value="1"<?php echo $buMenuPublished ? ' checked' : ''; ?>>
            <label class="form-check-label fw-semibold" for="menu-published-<?php echo esc($key); ?>">Menú publicado</label>
            <div class="form-text">Al despublicarlo se oculta únicamente este menú secundario; el selector global de unidades se conserva.</div>
        </div>

        <p class="text-muted small mb-2">
            Arrastra para cambiar el orden. Usa editar para administrar el enlace y sus submenús.
            Se aceptan rutas internas, anclas y URL HTTPS.
            <?php if ($buIsCustomUnit): ?>Los enlaces no se reescriben automáticamente.<?php endif; ?>
        </p>
        <div class="bu-menu-sortable list-group mb-2" data-unit="<?php echo esc($key); ?>"></div>
        <div class="bu-menu-fields" data-unit="<?php echo esc($key); ?>" aria-hidden="true"></div>
        <script type="application/json" class="bu-menu-initial" data-unit="<?php echo esc($key); ?>"><?php
            echo json_encode($buMenuItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        ?></script>

        <div class="text-end mt-4">
            <button type="submit" class="btn btn-premium"><i class="bi bi-save2 me-1"></i>Guardar menú</button>
        </div>
    </form>
</div>
