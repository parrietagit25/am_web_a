<?php
require_once __DIR__ . '/../services/UnitContentService.php';

$ucUnitKey = 'rentacar';
$ucUnitLabel = 'Rent A Car';
if (UnitContentService::ensureMigrated($siteData, $ucUnitKey)) {
    $contentService->saveAll($siteData);
}
$ucContent = UnitContentService::getContentNode($siteData, $ucUnitKey);
$ucSettings = $ucContent['settings'] ?? [];
$ucTaxonomy = $ucContent['taxonomy'] ?? ['categories' => [], 'tags' => [], 'topics' => []];
$ucPickerItems = UnitContentService::getAllPublishedForPicker($siteData, $ucUnitKey);
$ucRotation = $ucSettings['home_rotation'] ?? [];
$ucSingle = $ucSettings['home_single'] ?? ['source_type' => 'news', 'item_id' => 0];
$ucConfigTab = 'rentacar-content-config';
$ucConfigActive = ($defaultAdminTab ?? '') === $ucConfigTab;
?>
<div class="tab-pane fade<?php echo $ucConfigActive ? ' show active' : ''; ?>" id="tab-rentacar-content-config" role="tabpanel" aria-labelledby="tab-rentacar-content-config-nav">
    <div class="admin-card mb-4">
        <h5 class="fw-bold mb-3 font-montserrat text-navy">
            <i class="bi bi-sliders me-2 text-danger"></i>Configuración — Contenido <?php echo esc($ucUnitLabel); ?>
        </h5>
        <p class="text-muted small mb-3">
            En <code>rent-a-car.php</code> hay <strong>dos bloques independientes</strong>. Configúralos por separado:
        </p>
        <ul class="text-muted small mb-0">
            <li><strong>Destacados</strong> — carrusel o pieza grande arriba (usa la rotación o el destacado único de abajo).</li>
            <li><strong>Contenido más reciente</strong> — grilla de tarjetas (solo ítems creados en esa sección del menú, con el ojo <i class="bi bi-eye-fill"></i> activo).</li>
        </ul>
    </div>

    <div class="admin-card">
        <form method="POST" action="?tab=<?php echo esc($ucConfigTab); ?>">
            <input type="hidden" name="action" value="save_unit_content_settings">
            <input type="hidden" name="content_unit" value="<?php echo esc($ucUnitKey); ?>">

            <div class="row g-3">
                <div class="col-12">
                    <div class="alert alert-light border small mb-0 py-2">
                        <i class="bi bi-info-circle text-danger me-1"></i>
                        La <strong>rotación</strong> de abajo solo aplica al bloque <strong>Destacados</strong>. No llena la grilla «Contenido más reciente».
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="home_block_enabled" id="uc_home_block_enabled" value="1" <?php echo !empty($ucSettings['home_block_enabled']) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold" for="uc_home_block_enabled">Mostrar bloque destacado en <code>rent-a-car.php</code></label>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Modo de visualización</label>
                    <select name="home_display_mode" id="uc_home_display_mode" class="form-select form-control-premium">
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

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="latest_show_on_home" id="uc_latest_show_on_home" value="1" <?php echo !empty($ucSettings['latest_show_on_home']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="uc_latest_show_on_home">Mostrar bloque adicional de «contenido más reciente» en el home</label>
                    </div>
                </div>

                <div class="col-12" id="uc-single-wrap">
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

                <div class="col-12" id="uc-rotation-wrap">
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-semibold">Rotación (puede mezclar tipos)</div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addUnitContentRotationRow()"><i class="bi bi-plus-lg"></i> Agregar</button>
                        </div>
                        <div id="uc-rotation-rows">
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
</div>

<?php
foreach (UnitContentService::TYPES as $ucType) {
    $ucItems = UnitContentService::getItems($siteData, $ucUnitKey, $ucType);
    $ucTabSlug = 'rentacar-content-' . $ucType;
    require __DIR__ . '/admin-unit-content-type-panel.php';
}
?>

<template id="uc-rotation-row-template">
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

<script>
function toggleUnitContentHomeMode() {
    const mode = document.getElementById('uc_home_display_mode');
    const singleWrap = document.getElementById('uc-single-wrap');
    const rotationWrap = document.getElementById('uc-rotation-wrap');
    if (!mode || !singleWrap || !rotationWrap) return;
    const isSingle = mode.value === 'single';
    singleWrap.style.display = isSingle ? '' : 'none';
    rotationWrap.style.display = isSingle ? 'none' : '';
}

function addUnitContentRotationRow() {
    const tpl = document.getElementById('uc-rotation-row-template');
    const container = document.getElementById('uc-rotation-rows');
    if (!tpl || !container) return;
    container.appendChild(tpl.content.cloneNode(true));
}

function unitContentSetBody(prefix, html) {
    const el = document.getElementById(prefix + '-body');
    if (!el) return;
    if (window.jQuery && jQuery(el).next('.note-editor').length) {
        jQuery(el).summernote('code', html || '');
    } else {
        el.value = html || '';
    }
}

function initUnitContentEditors() {
    if (!window.jQuery || !jQuery.fn.summernote) return;
    jQuery('.js-unit-content-editor').each(function () {
        const $ta = jQuery(this);
        if ($ta.next('.note-editor').length) return;
        $ta.summernote({
            height: 300,
            placeholder: 'Escriba el contenido (acepta HTML)...',
            toolbar: [
                ['style', ['style', 'bold', 'italic', 'underline', 'clear']],
                ['font', ['fontsize', 'color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture', 'video', 'table', 'hr']],
                ['view', ['codeview', 'fullscreen']]
            ]
        });
    });
}

function resetUnitContentForm(prefix) {
    const form = document.getElementById(prefix + '-form');
    if (!form) return;
    form.reset();
    document.getElementById(prefix + '-form-action').value = 'add_unit_content_item';
    document.getElementById(prefix + '-id').value = '';
    document.getElementById(prefix + '-form-title').innerHTML = '<i class="bi bi-file-plus me-2 text-danger"></i>Agregar contenido';
    document.getElementById(prefix + '-cancel').classList.add('d-none');
    document.getElementById(prefix + '-submit-text').textContent = 'Publicar';
    document.getElementById(prefix + '-thumb-help').textContent = '';
    document.getElementById(prefix + '-banner-help').textContent = '';
    unitContentSetBody(prefix, '');
    const thumb = document.getElementById(prefix + '-thumbnail');
    if (thumb) thumb.required = true;
}

function initEditUnitContent(prefix, item) {
    document.getElementById(prefix + '-form-action').value = 'edit_unit_content_item';
    document.getElementById(prefix + '-id').value = item.id || '';
    document.getElementById(prefix + '-date').value = item.date || '';
    document.getElementById(prefix + '-title').value = item.title || '';
    document.getElementById(prefix + '-slug').value = item.slug || '';
    document.getElementById(prefix + '-excerpt').value = item.excerpt || '';
    document.getElementById(prefix + '-link-text').value = item.link_text || 'Ver Más';
    document.getElementById(prefix + '-subheading').value = item.subheading || '';
    document.getElementById(prefix + '-description').value = item.description || '';
    unitContentSetBody(prefix, item.content || '');
    document.getElementById(prefix + '-sort').value = item.sort_order || 0;
    document.getElementById(prefix + '-published').checked = (item.published === true || item.published === 'true' || item.published == 1);
    const showHome = document.getElementById(prefix + '-show-home');
    if (showHome) showHome.checked = (item.show_on_home === true || item.show_on_home === 'true' || item.show_on_home == 1);
    const subtype = document.getElementById(prefix + '-subtype');
    if (subtype) subtype.value = item.subtype || 'promotion';
    const from = document.getElementById(prefix + '-from');
    if (from) from.value = (item.publish_from || '').replace(' ', 'T').slice(0, 16);
    const until = document.getElementById(prefix + '-until');
    if (until) until.value = (item.publish_until || '').replace(' ', 'T').slice(0, 16);
    ['categories', 'tags', 'topics'].forEach(function (kind) {
        const el = document.getElementById(prefix + '-' + kind);
        if (!el) return;
        const ids = item[kind === 'categories' ? 'category_ids' : (kind === 'tags' ? 'tag_ids' : 'topic_ids')] || [];
        Array.from(el.options).forEach(function (opt) {
            opt.selected = ids.map(String).includes(String(opt.value));
        });
    });
    document.getElementById(prefix + '-thumb-help').innerHTML = item.thumbnail ? 'Actual: <code>' + item.thumbnail + '</code>' : '';
    document.getElementById(prefix + '-banner-help').innerHTML = item.banner ? 'Actual: <code>' + item.banner + '</code>' : '';
    const thumb = document.getElementById(prefix + '-thumbnail');
    if (thumb) thumb.required = false;
    document.getElementById(prefix + '-form-title').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar contenido';
    document.getElementById(prefix + '-cancel').classList.remove('d-none');
    document.getElementById(prefix + '-submit-text').textContent = 'Guardar cambios';
    document.getElementById(prefix + '-form').scrollIntoView({ behavior: 'smooth' });
}

document.addEventListener('DOMContentLoaded', function () {
    const mode = document.getElementById('uc_home_display_mode');
    if (mode) {
        mode.addEventListener('change', toggleUnitContentHomeMode);
        toggleUnitContentHomeMode();
    }
    initUnitContentEditors();
});
</script>
