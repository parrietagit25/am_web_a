<?php
/**
 * Submenú lateral para unidades de negocio personalizadas.
 */
require_once __DIR__ . '/business-units-registry.php';
require_once __DIR__ . '/../services/UnitContentService.php';

$customUnitsNav = am_custom_business_units($global['business_units'] ?? []);
$showCustomUnits = admin_can('global') && !empty($customUnitsNav);

if (!$showCustomUnits) {
    return;
}

foreach ($customUnitsNav as $unitKey => $unit):
    $submenuId = 'custom-unit-submenu-' . preg_replace('/[^a-z0-9_-]/i', '-', $unitKey);
    $chevronId = 'custom-unit-chevron-' . preg_replace('/[^a-z0-9_-]/i', '-', $unitKey);
    $editablePages = am_custom_unit_editable_pages($unit, $unitKey);
    $headingLabel = trim((string) ($unit['label'] ?? strtoupper($unitKey)));
    $unitTabPrefix = 'unit-' . $unitKey;
    $unitGeneralTab = UnitContentService::generalTabSlug($unitKey);
    $unitContentTabs = UnitContentService::contentTabSlugs($unitKey);
    $unitSubmenuOpen = ($defaultAdminTab ?? '') === $unitGeneralTab
        || ($defaultAdminTab ?? '') === $unitTabPrefix
        || ($defaultAdminTab ?? '') === ('unit-' . $unitKey . '-footer')
        || strncmp((string) ($defaultAdminTab ?? ''), $unitTabPrefix . '-', strlen($unitTabPrefix) + 1) === 0
        || in_array($defaultAdminTab ?? '', $unitContentTabs, true);
?>
    <div class="sidebar-heading px-3 py-2 mt-3 text-uppercase text-white-50 fw-bold d-flex align-items-center justify-content-between"
         data-bs-toggle="collapse"
         data-bs-target="#<?php echo esc($submenuId); ?>"
         aria-expanded="<?php echo $unitSubmenuOpen ? 'true' : 'false'; ?>"
         aria-controls="<?php echo esc($submenuId); ?>"
         style="cursor: pointer; font-size: 0.75rem; letter-spacing: 0.5px;">
        <span><?php echo esc($headingLabel); ?></span>
        <i class="bi bi-chevron-down" id="<?php echo esc($chevronId); ?>"></i>
    </div>
    <div class="collapse<?php echo $unitSubmenuOpen ? ' show' : ''; ?>" id="<?php echo esc($submenuId); ?>" data-bs-parent="#admin-sidebar-accordion">
        <?php if (admin_can(UnitContentService::contentPermissionKey($unitKey))): ?>
        <button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active($unitGeneralTab, $defaultAdminTab); ?>"
                id="tab-<?php echo esc($unitGeneralTab); ?>-nav"
                data-bs-toggle="pill"
                data-bs-target="#tab-<?php echo esc($unitGeneralTab); ?>"
                type="button"
                role="tab"
                data-admin-perm="<?php echo esc(UnitContentService::contentPermissionKey($unitKey)); ?>">
            <i class="bi bi-gear-fill me-2"></i> Generales
        </button>
        <?php endif; ?>
        <?php foreach ($editablePages as $pageMeta):
            $tabSlug = (string) ($pageMeta['tab_slug'] ?? ('unit-' . $unitKey));
            $tabId = 'tab-' . $tabSlug . '-nav';
            $pageLabel = (string) ($pageMeta['label'] ?? 'Principal');
            $isMain = ($pageMeta['slug'] ?? '') === '';
        ?>
        <button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active($tabSlug, $defaultAdminTab); ?>"
                id="<?php echo esc($tabId); ?>"
                data-bs-toggle="pill"
                data-bs-target="#tab-<?php echo esc($tabSlug); ?>"
                type="button"
                role="tab"
                data-admin-perm="global">
            <i class="bi bi-<?php echo $isMain ? 'house-door-fill' : 'file-earmark-richtext'; ?> me-2"></i>
            <?php echo esc($pageLabel); ?>
        </button>
        <?php endforeach; ?>
        <?php $ucUnitKey = $unitKey; $ucSubmenuId = 'custom-unit-content-submenu-' . preg_replace('/[^a-z0-9_-]/i', '-', $unitKey); require __DIR__ . '/admin-unit-content-submenu.php'; ?>
        <?php if (admin_can('global')):
            $unitFooterTab = 'unit-' . $unitKey . '-footer';
        ?>
        <button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active($unitFooterTab, $defaultAdminTab); ?>"
                id="tab-<?php echo esc($unitFooterTab); ?>-nav"
                data-bs-toggle="pill"
                data-bs-target="#tab-<?php echo esc($unitFooterTab); ?>"
                type="button"
                role="tab"
                data-admin-perm="global">
            <i class="bi bi-layout-text-window-reverse me-2"></i> Pie de página
        </button>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
