<?php
/**
 * Partial: enlace a ficha canónica de sucursal (solo si hay slug).
 *
 * Variables de entrada:
 *   $_locSlug (string) — slug de locations[]
 */
$_locSlug = trim((string) ($_locSlug ?? ''));
if ($_locSlug === '') {
    unset($_locSlug);
    return;
}

require_once __DIR__ . '/location-public-helper.php';
$_locDetailPath = am_location_detail_path($_locSlug);
unset($_locSlug);
?>
<p class="mb-3">
    <a href="<?php echo esc($_locDetailPath); ?>" class="small fw-semibold text-danger text-decoration-none">
        Ver ficha de sucursal <i class="bi bi-arrow-right-circle"></i>
    </a>
</p>
<?php unset($_locDetailPath); ?>
