<?php
/**
 * Campo de upload: logo de unidad en navbar.
 *
 * @var string $navLogoUnitKey
 * @var array $global
 */
require_once __DIR__ . '/business-units-registry.php';

$navLogoUnitKey = (string) ($navLogoUnitKey ?? '');
$mergedUnits = am_merge_business_units($global['business_units'] ?? []);
$navLogoUrl = trim((string) ($global['business_units'][$navLogoUnitKey]['nav_logo_url'] ?? ''));
$unitSubtitle = trim((string) ($mergedUnits[$navLogoUnitKey]['logo_subtitle'] ?? $mergedUnits[$navLogoUnitKey]['label'] ?? ''));
$fallbackLabel = $unitSubtitle !== '' ? $unitSubtitle : 'nombre de la unidad';
?>
<div class="col-12">
    <div class="border rounded-3 p-3 mb-2 bg-light">
        <h6 class="fw-bold mb-2 font-montserrat text-navy">
            <i class="bi bi-badge-ad me-2 text-danger"></i>Logo en header (junto a Automarket)
        </h6>
        <p class="form-text mb-3">
            Reemplaza el texto «<?php echo esc($fallbackLabel); ?>» en la barra superior del sitio.
            Si no subes un logo, se muestra ese texto como hasta ahora.
        </p>
        <label for="unit_nav_logo_<?php echo esc($navLogoUnitKey); ?>" class="form-label fw-semibold">Imagen del logo</label>
        <input type="file"
               id="unit_nav_logo_<?php echo esc($navLogoUnitKey); ?>"
               name="unit_nav_logo"
               class="form-control form-control-premium"
               accept="image/*">
        <div class="form-text">Formatos: JPG, PNG, GIF, WEBP. Altura recomendada ~32px. Máx: 5MB.</div>
        <small class="text-muted d-block mt-1">Recomendado: 300×120 px (o según diseño) — PNG con fondo transparente</small>
        <?php if ($navLogoUrl !== ''): ?>
            <div class="mt-3 d-flex flex-wrap align-items-center gap-3">
                <img src="<?php echo esc($navLogoUrl); ?>"
                     alt="Logo header actual"
                     class="img-thumbnail unit-nav-logo-preview">
                <div class="form-check mb-0">
                    <input class="form-check-input"
                           type="checkbox"
                           name="remove_unit_nav_logo"
                           value="1"
                           id="remove_unit_nav_logo_<?php echo esc($navLogoUnitKey); ?>">
                    <label class="form-check-label" for="remove_unit_nav_logo_<?php echo esc($navLogoUnitKey); ?>">
                        Quitar logo y volver al texto
                    </label>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
