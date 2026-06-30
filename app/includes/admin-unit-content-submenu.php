<?php
/**
 * Submenú lateral reutilizable — Contenido por unidad.
 *
 * @var string $ucUnitKey
 * @var string $ucSubmenuId
 */
require_once __DIR__ . '/../services/UnitContentService.php';

$ucContentPerm = UnitContentService::contentPermissionKey($ucUnitKey);
$ucContentTabs = UnitContentService::contentTabSlugs($ucUnitKey);

if (!admin_can($ucContentPerm)) {
    return;
}
?>
<div class="px-3 py-2 text-white-50 d-flex align-items-center justify-content-between"
     data-bs-toggle="collapse"
     data-bs-target="#<?php echo esc($ucSubmenuId); ?>"
     aria-expanded="<?php echo admin_submenu_aria_expanded($ucContentTabs, $defaultAdminTab ?? ''); ?>"
     aria-controls="<?php echo esc($ucSubmenuId); ?>"
     style="cursor: pointer; font-size: 0.82rem;">
    <span><i class="bi bi-collection me-2"></i> Contenido</span>
    <i class="bi bi-chevron-down small"></i>
</div>
<div class="<?php echo admin_submenu_collapse_class($ucContentTabs, $defaultAdminTab ?? ''); ?> pb-1" id="<?php echo esc($ucSubmenuId); ?>">
    <button class="nav-link text-start w-100 border-0 bg-transparent ps-4<?php echo admin_nav_active($ucUnitKey . '-content-config', $defaultAdminTab ?? ''); ?>" id="tab-<?php echo esc($ucUnitKey); ?>-content-config-nav" data-bs-toggle="pill" data-bs-target="#tab-<?php echo esc($ucUnitKey); ?>-content-config" type="button" role="tab" data-admin-perm="<?php echo esc($ucContentPerm); ?>"><i class="bi bi-sliders me-2"></i> Configuración</button>
    <button class="nav-link text-start w-100 border-0 bg-transparent ps-4<?php echo admin_nav_active($ucUnitKey . '-content-latest', $defaultAdminTab ?? ''); ?>" id="tab-<?php echo esc($ucUnitKey); ?>-content-latest-nav" data-bs-toggle="pill" data-bs-target="#tab-<?php echo esc($ucUnitKey); ?>-content-latest" type="button" role="tab" data-admin-perm="<?php echo esc($ucContentPerm); ?>"><i class="bi bi-lightning-charge me-2"></i> Novedades</button>
    <button class="nav-link text-start w-100 border-0 bg-transparent ps-4<?php echo admin_nav_active($ucUnitKey . '-content-news', $defaultAdminTab ?? ''); ?>" id="tab-<?php echo esc($ucUnitKey); ?>-content-news-nav" data-bs-toggle="pill" data-bs-target="#tab-<?php echo esc($ucUnitKey); ?>-content-news" type="button" role="tab" data-admin-perm="<?php echo esc($ucContentPerm); ?>"><i class="bi bi-newspaper me-2"></i> Noticias</button>
    <button class="nav-link text-start w-100 border-0 bg-transparent ps-4<?php echo admin_nav_active($ucUnitKey . '-content-blog', $defaultAdminTab ?? ''); ?>" id="tab-<?php echo esc($ucUnitKey); ?>-content-blog-nav" data-bs-toggle="pill" data-bs-target="#tab-<?php echo esc($ucUnitKey); ?>-content-blog" type="button" role="tab" data-admin-perm="<?php echo esc($ucContentPerm); ?>"><i class="bi bi-journal-text me-2"></i> Blog</button>
</div>
