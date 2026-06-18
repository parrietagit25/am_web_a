<?php
/**
 * Pestañas de contenido para unidades de negocio personalizadas.
 * Requiere: $global['business_units'] fusionado.
 */
require_once __DIR__ . '/business-units-registry.php';
require_once __DIR__ . '/article-content.php';

$customUnitsForTabs = am_custom_business_units($global['business_units'] ?? []);

foreach ($customUnitsForTabs as $unitKey => $unit):
    $editablePages = am_custom_unit_editable_pages($unit, $unitKey);
    foreach ($editablePages as $pageMeta):
        $pageSlug = (string) ($pageMeta['slug'] ?? '');
        $tabSlug = (string) ($pageMeta['tab_slug'] ?? ('unit-' . $unitKey));
        $tabId = 'tab-' . $tabSlug;
        $navId = $tabId . '-nav';
        $content = am_custom_unit_page_content($unit, $pageSlug);
        $isMain = $pageSlug === '';
        $pageLabel = (string) ($pageMeta['label'] ?? ($isMain ? 'Principal' : $pageSlug));
        $publicUrl = '/unidad.php?u=' . rawurlencode($unitKey) . ($pageSlug !== '' ? '&p=' . rawurlencode($pageSlug) : '');
?>
                    <div class="tab-pane fade<?php echo ($defaultAdminTab ?? '') === $tabSlug ? ' show active' : ''; ?>"
                         id="<?php echo esc($tabId); ?>"
                         role="tabpanel"
                         aria-labelledby="<?php echo esc($navId); ?>">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-building me-2 text-danger"></i><?php echo esc($unit['label'] ?? $unitKey); ?> — <?php echo esc($pageLabel); ?>
                            </h5>
                            <p class="text-muted small mb-4">
                                Página pública:
                                <a href="<?php echo esc($publicUrl); ?>" target="_blank" rel="noopener" class="text-danger fw-semibold"><?php echo esc($publicUrl); ?></a>
                                <?php if (!$isMain): ?>
                                <span class="d-block mt-1">El enlace del menú debe apuntar a esta URL (configúrelo en Configuración global).</span>
                                <?php endif; ?>
                            </p>

                            <form method="POST" action="?tab=<?php echo esc($tabSlug); ?>" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="save_custom_unit_content">
                                <input type="hidden" name="unit_key" value="<?php echo esc($unitKey); ?>">
                                <input type="hidden" name="page_slug" value="<?php echo esc($pageSlug); ?>">
                                <input type="hidden" name="tab_slug" value="<?php echo esc($tabSlug); ?>">

                                <div class="row g-3">
                                    <?php if ($isMain): ?>
                                        <?php
                                        $navLogoUnitKey = $unitKey;
                                        require __DIR__ . '/admin-unit-nav-logo-field.php';
                                        ?>
                                    <?php endif; ?>
                                    <div class="col-12">
                                        <?php
                                        require_once __DIR__ . '/../services/HeaderBannerService.php';
                                        $hbSlugPart = $pageSlug !== '' ? $pageSlug : 'main';
                                        $hbNode = $pageSlug === ''
                                            ? $unit
                                            : (is_array($unit['pages'][$pageSlug] ?? null) ? $unit['pages'][$pageSlug] : []);
                                        $hbConfig = HeaderBannerService::normalizeFromNode($hbNode, 'hero_image_url');
                                        $hbPrefix = 'hb_unit_' . $unitKey . '_' . preg_replace('/[^a-z0-9_]/', '_', $hbSlugPart);
                                        $hbDomId = 'hb-unit-' . preg_replace('/[^a-z0-9-]/', '-', $unitKey) . '-' . preg_replace('/[^a-z0-9-]/', '-', $hbSlugPart);
                                        require __DIR__ . '/admin-header-banner-section.php';
                                        ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Título del hero</label>
                                        <input type="text" name="hero_title" class="form-control form-control-premium" value="<?php echo esc($content['heroTitle']); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Subtítulo del hero</label>
                                        <input type="text" name="hero_subtitle" class="form-control form-control-premium" value="<?php echo esc($content['heroSubtitle']); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Contenido HTML de la página</label>
                                        <textarea name="body_html" rows="18" class="form-control form-control-premium font-monospace" style="font-size:13px;line-height:1.45;" placeholder="<section>...</section>"><?php echo esc($content['body_html']); ?></textarea>
                                        <div class="form-text">HTML permitido (sin scripts). Se muestra debajo del banner en el sitio público.</div>
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save2"></i> Guardar contenido
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
<?php
    endforeach;
endforeach;
