<?php
/**
 * Aviso legacy — sucursales públicas migradas al maestro locations[] (AM-SEO-3C-D2).
 *
 * @var string $_legacyLocationsNoticeMb Clase margin-bottom (opcional, default mb-3).
 */
$_legacyLocationsNoticeMb = $_legacyLocationsNoticeMb ?? 'mb-3';
?>
<div class="alert alert-light border small py-2 <?php echo htmlspecialchars($_legacyLocationsNoticeMb, ENT_QUOTES, 'UTF-8'); ?>" role="note">
    <i class="bi bi-info-circle me-1 text-danger"></i>
    Las sucursales públicas ahora se administran desde
    <a href="?tab=locations-master" class="fw-semibold text-decoration-none">Sucursales maestro</a>.
    Esta sección se mantiene temporalmente como respaldo/legacy.
</div>
<?php unset($_legacyLocationsNoticeMb); ?>
