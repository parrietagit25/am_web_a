<?php
/**
 * Pestaña Generales por unidad de negocio.
 *
 * @var string $ucUnitKey
 * @var string $defaultAdminTab
 * @var array<string, mixed> $siteData
 */
require_once __DIR__ . '/../services/UnitContentService.php';
require_once __DIR__ . '/business-units-registry.php';

$ucGeneralTab = UnitContentService::generalTabSlug($ucUnitKey);
$ucGeneralActive = ($defaultAdminTab ?? '') === $ucGeneralTab;
$ucBusinessUnits = am_merge_business_units($siteData['global']['business_units'] ?? []);

if (!isset($ucBusinessUnits[$ucUnitKey])) {
    return;
}

$key = $ucUnitKey;
$unit = $ucBusinessUnits[$ucUnitKey];
$buMenuTab = $ucGeneralTab;
$ucNavMenuSettings = UnitContentService::getNavMenuSettings($siteData, $ucUnitKey);
$ucNavDomUnit = preg_replace('/[^a-z0-9_-]/i', '-', $ucUnitKey);
?>
<div class="tab-pane fade<?php echo $ucGeneralActive ? ' show active' : ''; ?>"
     id="tab-<?php echo esc($ucGeneralTab); ?>"
     role="tabpanel"
     aria-labelledby="tab-<?php echo esc($ucGeneralTab); ?>-nav">
    <div class="admin-card mb-4">
        <h5 class="fw-bold mb-2 font-montserrat text-navy">
            <i class="bi bi-gear-fill me-2 text-danger"></i>Generales — <?php echo esc($unit['label'] ?? $ucUnitKey); ?>
        </h5>
        <p class="text-muted small mb-0">
            Administra las opciones del menú público de esta unidad de negocio.
        </p>
    </div>

    <?php require __DIR__ . '/admin-business-units-menu-list.php'; ?>

    <div class="admin-card mt-4">
        <form method="POST" action="?tab=<?php echo esc($ucGeneralTab); ?>">
            <input type="hidden" name="action" value="save_unit_nav_content_menu">
            <input type="hidden" name="nav_menu_unit" value="<?php echo esc($ucUnitKey); ?>">
            <?php admin_csrf_field(); ?>

            <h5 class="fw-bold mb-1 text-navy">
                <i class="bi bi-collection me-2 text-danger"></i>Menú «CONTENIDO» — <?php echo esc($unit['label'] ?? $ucUnitKey); ?>
            </h5>
            <p class="text-muted small mb-3">
                Esta opción se agrega automáticamente al menú público de la unidad, antes de CONTACTOS.
                Aquí puedes ocultarla por completo o elegir qué opciones internas mostrar.
            </p>

            <input type="hidden" name="nav_menu_enabled" value="0">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="nav_menu_enabled"
                       id="nav-menu-enabled-<?php echo esc($ucNavDomUnit); ?>" value="1"<?php echo $ucNavMenuSettings['enabled'] ? ' checked' : ''; ?>>
                <label class="form-check-label fw-semibold" for="nav-menu-enabled-<?php echo esc($ucNavDomUnit); ?>">
                    Mostrar menú «CONTENIDO» en el sitio público
                </label>
                <div class="form-text">Al desactivarlo, no se mostrará el desplegable Contenido en el navbar de esta unidad.</div>
            </div>

            <div class="border rounded-3 p-3 bg-light">
                <div class="fw-semibold mb-2 text-navy">Opciones internas</div>
                <?php
                $ucNavItemLabels = [
                    'news' => 'Noticias',
                    'blog' => 'Blog',
                    'latest' => 'Novedades',
                ];
                foreach ($ucNavItemLabels as $ucNavItemKey => $ucNavItemLabel):
                ?>
                <input type="hidden" name="nav_menu_item_<?php echo esc($ucNavItemKey); ?>" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox"
                           name="nav_menu_item_<?php echo esc($ucNavItemKey); ?>"
                           id="nav-menu-item-<?php echo esc($ucNavDomUnit . '-' . $ucNavItemKey); ?>"
                           value="1"<?php echo !empty($ucNavMenuSettings['items'][$ucNavItemKey]) ? ' checked' : ''; ?>>
                    <label class="form-check-label" for="nav-menu-item-<?php echo esc($ucNavDomUnit . '-' . $ucNavItemKey); ?>">
                        <?php echo esc($ucNavItemLabel); ?>
                    </label>
                </div>
                <?php endforeach; ?>
                <div class="form-text mt-2">Si desactivas todas las opciones, el menú «CONTENIDO» tampoco se mostrará.</div>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-premium"><i class="bi bi-save2 me-1"></i>Guardar menú Contenido</button>
            </div>
        </form>
    </div>
</div>
<?php
unset($ucBusinessUnits, $key, $unit, $buMenuTab, $ucGeneralTab, $ucGeneralActive, $ucNavMenuSettings, $ucNavDomUnit, $ucNavItemLabels);
