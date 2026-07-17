<?php
/**
 * Pestañas admin de contenido por unidad.
 *
 * @var string $ucUnitKey
 */
require_once __DIR__ . '/../services/UnitContentService.php';

$ucUnitLabel = UnitContentService::unitLabel($siteData, $ucUnitKey);
$ucHomePath = UnitContentService::unitHomePath($siteData, $ucUnitKey);
$ucDomUnit = preg_replace('/[^a-z0-9_-]/i', '-', $ucUnitKey);
$ucContent = UnitContentService::getContentNode($siteData, $ucUnitKey);
$ucSettings = $ucContent['settings'] ?? [];
$ucPageHeaders = UnitContentService::normalizePageHeaders($ucSettings['page_headers'] ?? [], $ucUnitLabel);
$ucTaxonomy = $ucContent['taxonomy'] ?? ['categories' => [], 'tags' => [], 'topics' => []];
$ucPickerItems = UnitContentService::getAllPublishedForPicker($siteData, $ucUnitKey);
$ucRotation = $ucSettings['home_rotation'] ?? [];
$ucSingle = $ucSettings['home_single'] ?? ['source_type' => 'news', 'item_id' => 0];
$ucConfigTab = $ucUnitKey . '-content-config';
$ucConfigActive = ($defaultAdminTab ?? '') === $ucConfigTab;
?>
<div class="tab-pane fade<?php echo $ucConfigActive ? ' show active' : ''; ?>" id="tab-<?php echo esc($ucUnitKey); ?>-content-config" role="tabpanel" aria-labelledby="tab-<?php echo esc($ucUnitKey); ?>-content-config-nav">
    <div class="admin-card mb-4">
        <h5 class="fw-bold mb-3 font-montserrat text-navy">
            <i class="bi bi-sliders me-2 text-danger"></i>Configuración — Contenido <?php echo esc($ucUnitLabel); ?>
        </h5>
        <p class="text-muted small mb-3">
            En <code><?php echo esc(ltrim($ucHomePath, '/')); ?></code> hay <strong>dos bloques independientes</strong>. Configúralos por separado:
        </p>
        <ul class="text-muted small mb-0">
            <li><strong>Destacados</strong> — carrusel o pieza grande arriba (usa la rotación o el destacado único de abajo).</li>
            <li><strong>Novedades</strong> — grilla de tarjetas (ítems de novedades, blog o noticias con el ojo <i class="bi bi-eye-fill"></i> activo).</li>
        </ul>
    </div>

    <div class="admin-card">
        <form method="POST" action="?tab=<?php echo esc($ucConfigTab); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_unit_content_settings">
            <input type="hidden" name="content_unit" value="<?php echo esc($ucUnitKey); ?>">

            <h6 class="fw-bold text-navy mb-3 font-montserrat border-bottom pb-2">
                <i class="bi bi-image me-2 text-danger"></i>Cabeceras — Noticias, Blog y Novedades
            </h6>
            <p class="text-muted small mb-4">
                Imagen de fondo, textos y alineación del banner superior en <code>noticias.php</code>, <code>blog.php</code> y <code>contenido-reciente.php</code> de esta unidad.
            </p>

            <?php
            $ucPageHeaderLabels = ['news' => 'Noticias', 'blog' => 'Blog', 'latest' => 'Novedades'];
            foreach (UnitContentService::TYPES as $phType):
                $ph = $ucPageHeaders[$phType] ?? [];
                $phLabel = $ucPageHeaderLabels[$phType] ?? $phType;
            ?>
            <div class="border rounded-3 p-3 p-md-4 mb-3 bg-light">
                <div class="fw-semibold mb-3 text-navy"><i class="bi bi-layout-text-window me-2"></i><?php echo esc($phLabel); ?></div>
                <input type="hidden" name="content_page_enabled_<?php echo esc($phType); ?>" value="0">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox"
                           name="content_page_enabled_<?php echo esc($phType); ?>"
                           id="content-page-enabled-<?php echo esc($ucDomUnit . '-' . $phType); ?>"
                           value="1"<?php echo !isset($ph['enabled']) || $ph['enabled'] ? ' checked' : ''; ?>>
                    <label class="form-check-label fw-semibold" for="content-page-enabled-<?php echo esc($ucDomUnit . '-' . $phType); ?>">Mostrar cabecera</label>
                    <div class="form-text">Al desactivarla se conserva el título principal de la página sin imagen de banner.</div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Imagen de cabecera</label>
                        <input type="file" name="content_page_banner_<?php echo esc($phType); ?>" class="form-control form-control-premium" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
                        <small class="text-muted d-block mt-1">Recomendado: 1920×700 px — JPG o WebP. Máx. 12 MB.</small>
                        <?php if (!empty($ph['banner'])): ?>
                            <div class="small text-muted mt-2">Actual: <code><?php echo esc($ph['banner']); ?></code></div>
                            <img src="<?php echo esc($ph['banner']); ?>" alt="" class="img-fluid rounded mt-2 border" style="max-height:120px;object-fit:cover;">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="content_page_remove_<?php echo esc($phType); ?>" id="content-page-remove-<?php echo esc($ucDomUnit . '-' . $phType); ?>" value="1">
                                <label class="form-check-label text-danger" for="content-page-remove-<?php echo esc($ucDomUnit . '-' . $phType); ?>">Quitar imagen actual</label>
                            </div>
                        <?php endif; ?>
                        <label class="form-label mt-3">Texto alternativo de la imagen</label>
                        <input type="text" name="content_page_alt_<?php echo esc($phType); ?>" class="form-control form-control-premium"
                               value="<?php echo esc($ph['alt'] ?? ''); ?>" maxlength="180" placeholder="Descripción breve de la imagen">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Etiqueta superior (kicker)</label>
                        <input type="text" name="content_page_kicker_<?php echo esc($phType); ?>" class="form-control form-control-premium" value="<?php echo esc($ph['kicker'] ?? ''); ?>" placeholder="Ej: Actualidad">
                        <label class="form-label mt-3">Título principal</label>
                        <input type="text" name="content_page_title_<?php echo esc($phType); ?>" class="form-control form-control-premium" value="<?php echo esc($ph['title'] ?? ''); ?>" placeholder="<?php echo esc($phLabel); ?>">
                        <label class="form-label mt-3">Subtítulo / descripción</label>
                        <textarea name="content_page_subtitle_<?php echo esc($phType); ?>" class="form-control form-control-premium" rows="2" placeholder="Texto bajo el título"><?php echo esc($ph['subtitle'] ?? ''); ?></textarea>
                        <label class="form-label mt-3">Alineación del texto</label>
                        <select name="content_page_align_<?php echo esc($phType); ?>" class="form-select form-control-premium">
                            <option value="left" <?php echo ($ph['align'] ?? '') === 'left' ? 'selected' : ''; ?>>Izquierda</option>
                            <option value="center" <?php echo ($ph['align'] ?? '') === 'center' ? 'selected' : ''; ?>>Centro</option>
                            <option value="right" <?php echo ($ph['align'] ?? '') === 'right' ? 'selected' : ''; ?>>Derecha</option>
                        </select>
                        <label class="form-label mt-3">Texto del enlace o botón</label>
                        <input type="text" name="content_page_button_text_<?php echo esc($phType); ?>" class="form-control form-control-premium"
                               value="<?php echo esc($ph['button_text'] ?? ''); ?>" maxlength="100" placeholder="Ej: Conocer más">
                        <label class="form-label mt-3">URL del enlace</label>
                        <input type="text" name="content_page_button_url_<?php echo esc($phType); ?>" class="form-control form-control-premium"
                               value="<?php echo esc($ph['button_url'] ?? ''); ?>" maxlength="500" placeholder="/ruta, #seccion o https://...">
                        <div class="form-text">Se aceptan rutas internas, anclas y URL HTTPS.</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <hr class="my-4">

            <h6 class="fw-bold text-navy mb-3 font-montserrat">
                <i class="bi bi-house me-2 text-danger"></i>Bloques en la página principal
            </h6>

            <div class="row g-3">
                <div class="col-12">
                    <div class="alert alert-light border small mb-0 py-2">
                        <i class="bi bi-info-circle text-danger me-1"></i>
                        La <strong>rotación</strong> de abajo solo aplica al bloque <strong>Destacados</strong>. No llena la grilla «Novedades».
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="home_block_enabled" id="uc-<?php echo esc($ucDomUnit); ?>-home_block_enabled" value="1" <?php echo !empty($ucSettings['home_block_enabled']) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold" for="uc-<?php echo esc($ucDomUnit); ?>-home_block_enabled">Mostrar bloque destacado en el home de la unidad</label>
                        <div class="form-text">Desactívalo para ocultar el carrusel o pieza destacada en la página principal de la unidad.</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Modo de visualización</label>
                    <select name="home_display_mode" id="uc-<?php echo esc($ucDomUnit); ?>-home_display_mode" class="form-select form-control-premium uc-home-display-mode" data-uc-unit="<?php echo esc($ucDomUnit); ?>">
                        <option value="single" <?php echo ($ucSettings['home_display_mode'] ?? '') === 'single' ? 'selected' : ''; ?>>Un solo destacado</option>
                        <option value="rotation" <?php echo ($ucSettings['home_display_mode'] ?? 'rotation') === 'rotation' ? 'selected' : ''; ?>>Rotación (varios)</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Intervalo rotación (ms)</label>
                    <input type="number" name="home_rotation_interval_ms" class="form-control form-control-premium" min="3000" step="500" value="<?php echo intval($ucSettings['home_rotation_interval_ms'] ?? 6000); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Límite bloque «más reciente»</label>
                    <input type="number" name="latest_home_limit" class="form-control form-control-premium" min="1" max="12" value="<?php echo intval($ucSettings['latest_home_limit'] ?? 4); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Título bloque Destacados (home)</label>
                    <input type="text" name="home_spotlight_title" class="form-control form-control-premium" value="<?php echo esc($ucSettings['home_spotlight_title'] ?? ''); ?>" placeholder="Destacados">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Título bloque Novedades (home)</label>
                    <input type="text" name="home_latest_title" class="form-control form-control-premium" value="<?php echo esc($ucSettings['home_latest_title'] ?? ''); ?>" placeholder="Novedades">
                </div>
                <div class="col-12">
                    <label class="form-label">Subtítulo bloque Novedades (home)</label>
                    <input type="text" name="home_latest_subtitle" class="form-control form-control-premium" value="<?php echo esc($ucSettings['home_latest_subtitle'] ?? ''); ?>" placeholder="Promociones, eventos e información de interés.">
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="latest_show_on_home" id="uc-<?php echo esc($ucDomUnit); ?>-latest_show_on_home" value="1" <?php echo !empty($ucSettings['latest_show_on_home']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="uc-<?php echo esc($ucDomUnit); ?>-latest_show_on_home">Mostrar bloque adicional de «Novedades» en el home</label>
                    </div>
                </div>

                <div class="col-12 uc-single-wrap" id="uc-<?php echo esc($ucDomUnit); ?>-single-wrap">
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="fw-semibold mb-2">Destacado único</div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <select name="home_single_type" class="form-select form-control-premium">
                                    <?php foreach (UnitContentService::TYPES as $t): ?>
                                    <option value="<?php echo esc($t); ?>" <?php echo ($ucSingle['source_type'] ?? '') === $t ? 'selected' : ''; ?>><?php echo esc(UnitContentService::TYPE_LABELS[$t]); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <select name="home_single_id" class="form-select form-control-premium">
                                    <option value="0">— Seleccione publicación —</option>
                                    <?php foreach ($ucPickerItems as $pick): ?>
                                    <option value="<?php echo intval($pick['item_id']); ?>" <?php echo (intval($ucSingle['item_id'] ?? 0) === intval($pick['item_id']) && ($ucSingle['source_type'] ?? '') === ($pick['source_type'] ?? '')) ? 'selected' : ''; ?>>
                                        [<?php echo esc($pick['type_label']); ?>] <?php echo esc($pick['title']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 uc-rotation-wrap" id="uc-<?php echo esc($ucDomUnit); ?>-rotation-wrap">
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-semibold">Rotación (puede mezclar tipos)</div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addUnitContentRotationRow('<?php echo esc($ucDomUnit); ?>')"><i class="bi bi-plus-lg"></i> Agregar</button>
                        </div>
                        <div id="uc-<?php echo esc($ucDomUnit); ?>-rotation-rows">
                            <?php if (empty($ucRotation)): ?>
                            <div class="row g-2 mb-2 uc-rotation-row">
                                <div class="col-md-4">
                                    <select name="home_rotation_type[]" class="form-select form-control-premium">
                                        <?php foreach (UnitContentService::TYPES as $t): ?>
                                        <option value="<?php echo esc($t); ?>"><?php echo esc(UnitContentService::TYPE_LABELS[$t]); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-7">
                                    <select name="home_rotation_id[]" class="form-select form-control-premium">
                                        <option value="0">— Seleccione —</option>
                                        <?php foreach ($ucPickerItems as $pick): ?>
                                        <option value="<?php echo intval($pick['item_id']); ?>">[<?php echo esc($pick['type_label']); ?>] <?php echo esc($pick['title']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.uc-rotation-row').remove()"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                            <?php else: ?>
                            <?php foreach ($ucRotation as $rot): ?>
                            <div class="row g-2 mb-2 uc-rotation-row">
                                <div class="col-md-4">
                                    <select name="home_rotation_type[]" class="form-select form-control-premium">
                                        <?php foreach (UnitContentService::TYPES as $t): ?>
                                        <option value="<?php echo esc($t); ?>" <?php echo ($rot['source_type'] ?? '') === $t ? 'selected' : ''; ?>><?php echo esc(UnitContentService::TYPE_LABELS[$t]); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-7">
                                    <select name="home_rotation_id[]" class="form-select form-control-premium">
                                        <option value="0">— Seleccione —</option>
                                        <?php foreach ($ucPickerItems as $pick): ?>
                                        <option value="<?php echo intval($pick['item_id']); ?>" <?php echo (intval($rot['item_id'] ?? 0) === intval($pick['item_id']) && ($rot['source_type'] ?? '') === ($pick['source_type'] ?? '')) ? 'selected' : ''; ?>>
                                            [<?php echo esc($pick['type_label']); ?>] <?php echo esc($pick['title']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.uc-rotation-row').remove()"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-premium"><i class="bi bi-save2"></i> Guardar configuración</button>
            </div>
        </form>
    </div>
    <?php if (in_array($ucUnitKey, ['rentacar', 'seminuevos', 'leasing'], true) || UnitContentService::isCustomUnit($ucUnitKey)): ?>
        <?php require __DIR__ . '/admin-unit-about-section.php'; ?>
    <?php endif; ?>
    <?php if ($ucUnitKey !== 'rentacar'): ?>
        <?php require __DIR__ . '/admin-unit-terms-section.php'; ?>
    <?php endif; ?>
    <?php
    require_once __DIR__ . '/business-units-registry.php';
    $ucBusinessUnits = am_merge_business_units($siteData['global']['business_units'] ?? []);
    $key = $ucUnitKey;
    $unit = $ucBusinessUnits[$ucUnitKey];
    $buMenuTab = $ucConfigTab;
    require __DIR__ . '/admin-business-units-menu-list.php';
    unset($ucBusinessUnits, $key, $unit, $buMenuTab);
    ?>
</div>

<?php
foreach (UnitContentService::TYPES as $ucType) {
    $ucItems = UnitContentService::getItems($siteData, $ucUnitKey, $ucType);
    $ucTabSlug = $ucUnitKey . '-content-' . $ucType;
    require __DIR__ . '/admin-unit-content-type-panel.php';
}
?>

<template id="uc-rotation-row-template-<?php echo esc($ucDomUnit); ?>">
    <div class="row g-2 mb-2 uc-rotation-row">
        <div class="col-md-4">
            <select name="home_rotation_type[]" class="form-select form-control-premium">
                <?php foreach (UnitContentService::TYPES as $t): ?>
                <option value="<?php echo esc($t); ?>"><?php echo esc(UnitContentService::TYPE_LABELS[$t]); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-7">
            <select name="home_rotation_id[]" class="form-select form-control-premium">
                <option value="0">— Seleccione —</option>
                <?php foreach ($ucPickerItems as $pick): ?>
                <option value="<?php echo intval($pick['item_id']); ?>">[<?php echo esc($pick['type_label']); ?>] <?php echo esc($pick['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.uc-rotation-row').remove()"><i class="bi bi-trash"></i></button>
        </div>
    </div>
</template>
