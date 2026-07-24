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
                                        $hbSourceNode = $hbNode;
                                        $hbConfig = HeaderBannerService::normalizeFromNode($hbNode, 'hero_image_url');
                                        $hbPrefix = 'hb_unit_' . $unitKey . '_' . preg_replace('/[^a-z0-9_]/', '_', $hbSlugPart);
                                        $hbDomId = 'hb-unit-' . preg_replace('/[^a-z0-9-]/', '-', $unitKey) . '-' . preg_replace('/[^a-z0-9-]/', '-', $hbSlugPart);
                                        require __DIR__ . '/admin-header-banner-section.php';
                                        unset($hbSourceNode);
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
                                    <?php
                                    $htcTitleName = 'hero_title_color';
                                    $htcSubtitleName = 'hero_subtitle_color';
                                    $htcTitleId = 'hero_title_color_' . preg_replace('/[^a-z0-9_]/', '_', $unitKey . '_' . ($pageSlug !== '' ? $pageSlug : 'main'));
                                    $htcSubtitleId = 'hero_subtitle_color_' . preg_replace('/[^a-z0-9_]/', '_', $unitKey . '_' . ($pageSlug !== '' ? $pageSlug : 'main'));
                                    $htcTitleValue = $content['heroTitleColor'] ?? '';
                                    $htcSubtitleValue = $content['heroSubtitleColor'] ?? '';
                                    require __DIR__ . '/admin-hero-text-colors-fields.php';
                                    ?>
                                    <?php if ($isMain): ?>
                                    <?php
                                    $customWhatsappContact = is_array($unit['footer_contact'] ?? null) ? $unit['footer_contact'] : [];
                                    $customWhatsappEnabled = !array_key_exists('whatsapp_enabled', $customWhatsappContact)
                                        || !empty($customWhatsappContact['whatsapp_enabled']);
                                    $customWhatsappFieldId = preg_replace('/[^a-z0-9_]/', '_', $unitKey);
                                    ?>
                                    <div class="col-12"><hr><h6 class="fw-bold">WhatsApp de la unidad</h6></div>
                                    <div class="col-md-4">
                                        <label for="custom_whatsapp_<?php echo esc($customWhatsappFieldId); ?>" class="form-label fw-semibold">Número (con código de país)</label>
                                        <input type="text" id="custom_whatsapp_<?php echo esc($customWhatsappFieldId); ?>" name="custom_whatsapp_number"
                                               class="form-control form-control-premium"
                                               value="<?php echo esc($customWhatsappContact['whatsapp_number'] ?? ''); ?>" placeholder="50760000000">
                                    </div>
                                    <div class="col-md-5">
                                        <label for="custom_whatsapp_message_<?php echo esc($customWhatsappFieldId); ?>" class="form-label fw-semibold">Mensaje inicial</label>
                                        <input type="text" maxlength="200" id="custom_whatsapp_message_<?php echo esc($customWhatsappFieldId); ?>"
                                               name="custom_whatsapp_message" class="form-control form-control-premium"
                                               value="<?php echo esc($customWhatsappContact['whatsapp_message'] ?? ''); ?>"
                                               placeholder="Vacío = saludo neutral con el nombre de la unidad">
                                    </div>
                                    <div class="col-md-3 d-flex align-items-center">
                                        <div class="form-check form-switch mt-3">
                                            <input class="form-check-input" type="checkbox" id="custom_whatsapp_enabled_<?php echo esc($customWhatsappFieldId); ?>"
                                                   name="custom_whatsapp_enabled" value="1"<?php echo $customWhatsappEnabled ? ' checked' : ''; ?>>
                                            <label class="form-check-label" for="custom_whatsapp_enabled_<?php echo esc($customWhatsappFieldId); ?>">Mostrar WhatsApp</label>
                                        </div>
                                    </div>
                                    <?php endif; ?>
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
                        <?php if ($isMain): ?>
                        <div class="mt-4">
                            <?php
                            $allyUnitKey = $unitKey;
                            require __DIR__ . '/admin-unit-allies-panel.php';
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
<?php
    endforeach;
endforeach;
