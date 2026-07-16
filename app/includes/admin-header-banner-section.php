<?php
/**
 * Admin UI — cabecera estática o slider.
 *
 * @var string $hbPrefix
 * @var string $hbDomId
 * @var array<string, mixed> $hbConfig
 */
require_once __DIR__ . '/../services/HeaderBannerService.php';

$hbPrefix = $hbPrefix ?? 'hb_default';
$hbDomId = $hbDomId ?? 'hb-default';
$hbConfig = HeaderBannerService::normalize($hbConfig ?? []);
$hbEnabled = (bool) ($hbConfig['enabled'] ?? true);
$hbMode = ($hbConfig['mode'] ?? HeaderBannerService::MODE_STATIC) === HeaderBannerService::MODE_SLIDER
    ? HeaderBannerService::MODE_SLIDER
    : HeaderBannerService::MODE_STATIC;
$hbSlides = $hbConfig['slider']['slides'] ?? [];
if (empty($hbSlides)) {
    $hbSlides = [[
        'enabled' => true,
        'image_url' => '',
        'alt' => '',
        'title' => '',
        'subtitle' => '',
        'link_text' => '',
        'link_url' => '',
    ]];
}
$hbStaticUrl = (string) ($hbConfig['image_url'] ?? '');
$hbAlt = (string) ($hbConfig['alt'] ?? '');
$hbTitle = (string) ($hbConfig['title'] ?? '');
$hbSubtitle = (string) ($hbConfig['subtitle'] ?? '');
$hbLinkText = (string) ($hbConfig['link_text'] ?? '');
$hbLinkUrl = (string) ($hbConfig['link_url'] ?? '');
$hbInterval = (int) ($hbConfig['slider']['interval_ms'] ?? 5000);
$hbTransition = (string) ($hbConfig['slider']['transition'] ?? 'fade');
?>
<div class="hb-section border rounded-3 p-4 bg-white" id="<?php echo esc($hbDomId); ?>" data-hb-prefix="<?php echo esc($hbPrefix); ?>">
    <h6 class="fw-bold text-navy-light mb-3"><i class="bi bi-images me-1"></i>Cabecera de la página</h6>

    <input type="hidden" name="<?php echo esc($hbPrefix); ?>_enabled" value="0">
    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox"
               name="<?php echo esc($hbPrefix); ?>_enabled"
               id="<?php echo esc($hbDomId); ?>-enabled"
               value="1"<?php echo $hbEnabled ? ' checked' : ''; ?>>
        <label class="form-check-label fw-semibold" for="<?php echo esc($hbDomId); ?>-enabled">Mostrar cabecera o banner</label>
        <div class="form-text">Al desactivarlo se oculta la imagen o slider; el título principal de la página se conserva.</div>
    </div>

    <div class="mb-3 d-flex flex-wrap gap-4">
        <div class="form-check">
            <input class="form-check-input hb-mode-radio" type="radio"
                   name="<?php echo esc($hbPrefix); ?>_mode"
                   id="<?php echo esc($hbDomId); ?>-mode-static"
                   value="static"<?php echo $hbMode === HeaderBannerService::MODE_STATIC ? ' checked' : ''; ?>>
            <label class="form-check-label fw-semibold" for="<?php echo esc($hbDomId); ?>-mode-static">Imagen fija</label>
        </div>
        <div class="form-check">
            <input class="form-check-input hb-mode-radio" type="radio"
                   name="<?php echo esc($hbPrefix); ?>_mode"
                   id="<?php echo esc($hbDomId); ?>-mode-slider"
                   value="slider"<?php echo $hbMode === HeaderBannerService::MODE_SLIDER ? ' checked' : ''; ?>>
            <label class="form-check-label fw-semibold" for="<?php echo esc($hbDomId); ?>-mode-slider">Slider de imágenes</label>
        </div>
    </div>

    <div class="hb-static-panel<?php echo $hbMode === HeaderBannerService::MODE_SLIDER ? ' d-none' : ''; ?>">
        <label class="form-label fw-semibold">Imagen de cabecera</label>
        <input type="hidden" name="<?php echo esc($hbPrefix); ?>_static_url" value="<?php echo esc($hbStaticUrl); ?>">
        <input type="file" name="<?php echo esc($hbPrefix); ?>_static_file" class="form-control form-control-premium hb-static-file" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
        <div class="form-text">JPG, PNG, GIF o WEBP. Máx. 12MB.</div>
        <small class="text-muted d-block mt-1">Recomendado: 1920×700 px — JPG o WebP</small>
        <?php if ($hbStaticUrl !== ''): ?>
        <div class="mt-2">
            <img src="<?php echo esc($hbStaticUrl); ?>" alt="" class="img-thumbnail hb-static-preview" style="max-height: 120px;">
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="<?php echo esc($hbPrefix); ?>_remove_static" id="<?php echo esc($hbDomId); ?>-remove-static" value="1">
                <label class="form-check-label text-danger" for="<?php echo esc($hbDomId); ?>-remove-static">Quitar imagen actual al guardar</label>
            </div>
        </div>
        <?php endif; ?>
        <div class="row g-3 mt-1">
            <div class="col-12">
                <label class="form-label fw-semibold">Texto alternativo de la imagen</label>
                <input type="text" name="<?php echo esc($hbPrefix); ?>_alt" class="form-control form-control-premium"
                       value="<?php echo esc($hbAlt); ?>" maxlength="180" placeholder="Ej: Vehículos Automarket en carretera">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Título del banner (opcional)</label>
                <input type="text" name="<?php echo esc($hbPrefix); ?>_title" class="form-control form-control-premium"
                       value="<?php echo esc($hbTitle); ?>" maxlength="180">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Subtítulo del banner (opcional)</label>
                <input type="text" name="<?php echo esc($hbPrefix); ?>_subtitle" class="form-control form-control-premium"
                       value="<?php echo esc($hbSubtitle); ?>" maxlength="300">
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold">Texto del enlace o botón</label>
                <input type="text" name="<?php echo esc($hbPrefix); ?>_link_text" class="form-control form-control-premium"
                       value="<?php echo esc($hbLinkText); ?>" maxlength="100" placeholder="Ej: Conocer más">
            </div>
            <div class="col-md-7">
                <label class="form-label fw-semibold">URL del enlace</label>
                <input type="text" name="<?php echo esc($hbPrefix); ?>_link_url" class="form-control form-control-premium"
                       value="<?php echo esc($hbLinkUrl); ?>" maxlength="500" placeholder="/leasing.php, #seccion o https://...">
            </div>
            <div class="col-12">
                <div class="form-text">Use textos breves y contraste legible. Se aceptan rutas internas, anclas y URL HTTPS.</div>
            </div>
        </div>
    </div>

    <div class="hb-slider-panel<?php echo $hbMode === HeaderBannerService::MODE_STATIC ? ' d-none' : ''; ?>">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Velocidad (segundos por imagen)</label>
                <select name="<?php echo esc($hbPrefix); ?>_interval_ms" class="form-select form-control-premium hb-interval-select">
                    <?php
                    $intervalOptions = [3000 => '3', 4000 => '4', 5000 => '5', 6000 => '6', 8000 => '8', 10000 => '10'];
                    foreach ($intervalOptions as $ms => $label):
                    ?>
                    <option value="<?php echo (int) $ms; ?>"<?php echo $hbInterval === (int) $ms ? ' selected' : ''; ?>><?php echo esc($label); ?> seg</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Transición</label>
                <select name="<?php echo esc($hbPrefix); ?>_transition" class="form-select form-control-premium">
                    <option value="fade"<?php echo $hbTransition === 'fade' ? ' selected' : ''; ?>>Fundido (fade)</option>
                    <option value="slide"<?php echo $hbTransition === 'slide' ? ' selected' : ''; ?>>Deslizar (slide)</option>
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label fw-semibold mb-0">Imágenes del slider</label>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill hb-add-slide-btn">
                <i class="bi bi-plus-lg me-1"></i>Agregar imagen
            </button>
        </div>
        <div class="hb-slides-list d-flex flex-column gap-2">
            <?php foreach ($hbSlides as $slideIndex => $slide):
                $hbSlideFieldId = $hbDomId . '-slide-' . intval($slideIndex);
            ?>
            <div class="hb-slide-row border rounded p-3 bg-light-gray">
                <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                    <input type="hidden" name="<?php echo esc($hbPrefix); ?>_slide_url[]" value="<?php echo esc($slide['image_url'] ?? ''); ?>">
                    <div class="flex-grow-1" style="min-width: 200px;">
                        <input type="file" name="<?php echo esc($hbPrefix); ?>_slide_file[]" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
                        <div class="form-text">Deje vacío para conservar la imagen actual.</div>
                        <small class="text-muted d-block mt-1">Recomendado: 1920×700 px — JPG o WebP</small>
                    </div>
                    <?php if (!empty($slide['image_url'])): ?>
                    <img src="<?php echo esc($slide['image_url']); ?>" alt="" class="img-thumbnail" style="max-height: 70px;">
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-outline-danger hb-remove-slide-btn" title="Quitar"><i class="bi bi-trash"></i></button>
                </div>
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1" for="<?php echo esc($hbSlideFieldId); ?>-enabled">Estado</label>
                        <select id="<?php echo esc($hbSlideFieldId); ?>-enabled" name="<?php echo esc($hbPrefix); ?>_slide_enabled[]" class="form-select form-select-sm">
                            <option value="1"<?php echo !isset($slide['enabled']) || $slide['enabled'] ? ' selected' : ''; ?>>Activo</option>
                            <option value="0"<?php echo isset($slide['enabled']) && !$slide['enabled'] ? ' selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label small fw-semibold mb-1" for="<?php echo esc($hbSlideFieldId); ?>-alt">Texto alternativo</label>
                        <input type="text" id="<?php echo esc($hbSlideFieldId); ?>-alt" name="<?php echo esc($hbPrefix); ?>_slide_alt[]" class="form-control form-control-sm"
                               value="<?php echo esc($slide['alt'] ?? ''); ?>" maxlength="180" placeholder="Descripción breve de la imagen">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Título del slide (opcional)</label>
                        <input type="text" name="<?php echo esc($hbPrefix); ?>_slide_title[]" class="form-control form-control-sm"
                               value="<?php echo esc($slide['title'] ?? ''); ?>" placeholder="Ej: Promoción de verano">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Subtítulo del slide (opcional)</label>
                        <input type="text" name="<?php echo esc($hbPrefix); ?>_slide_subtitle[]" class="form-control form-control-sm"
                               value="<?php echo esc($slide['subtitle'] ?? ''); ?>" placeholder="Texto breve bajo el título">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold mb-1" for="<?php echo esc($hbSlideFieldId); ?>-link-text">Texto del enlace</label>
                        <input type="text" id="<?php echo esc($hbSlideFieldId); ?>-link-text" name="<?php echo esc($hbPrefix); ?>_slide_link_text[]" class="form-control form-control-sm"
                               value="<?php echo esc($slide['link_text'] ?? ''); ?>" maxlength="100" placeholder="Ej: Ver promoción">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small fw-semibold mb-1" for="<?php echo esc($hbSlideFieldId); ?>-link-url">URL del enlace</label>
                        <input type="text" id="<?php echo esc($hbSlideFieldId); ?>-link-url" name="<?php echo esc($hbPrefix); ?>_slide_link_url[]" class="form-control form-control-sm"
                               value="<?php echo esc($slide['link_url'] ?? ''); ?>" maxlength="500" placeholder="/ruta, #seccion o https://...">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
(function () {
    const root = document.getElementById(<?php echo json_encode($hbDomId); ?>);
    if (!root || root.dataset.hbInit === '1') return;
    root.dataset.hbInit = '1';

    const prefix = (root.getAttribute('data-hb-prefix') || '').replace(/[^a-z0-9_-]/gi, '');
    const staticPanel = root.querySelector('.hb-static-panel');
    const sliderPanel = root.querySelector('.hb-slider-panel');
    const slidesList = root.querySelector('.hb-slides-list');

    function togglePanels() {
        const mode = root.querySelector('input[name="' + prefix + '_mode"]:checked')?.value || 'static';
        const isSlider = mode === 'slider';
        staticPanel?.classList.toggle('d-none', isSlider);
        sliderPanel?.classList.toggle('d-none', !isSlider);
        staticPanel?.querySelectorAll('input, select, textarea, button').forEach(function (el) {
            if (el.classList.contains('hb-mode-radio')) return;
            el.disabled = isSlider;
        });
        sliderPanel?.querySelectorAll('input, select, textarea, button').forEach(function (el) {
            if (el.classList.contains('hb-mode-radio')) return;
            el.disabled = !isSlider;
        });
    }

    function bindSlideRow(row) {
        row.querySelector('.hb-remove-slide-btn')?.addEventListener('click', function () {
            const rows = slidesList?.querySelectorAll('.hb-slide-row') || [];
            if (rows.length <= 1) {
                row.querySelector('input[type="hidden"]').value = '';
                const img = row.querySelector('img');
                if (img) img.remove();
                const file = row.querySelector('input[type="file"]');
                if (file) file.value = '';
                return;
            }
            row.remove();
        });
    }

    function addSlideRow() {
        if (!slidesList) return;
        const row = document.createElement('div');
        const rowId = root.id + '-slide-new-' + Date.now().toString(36);
        row.className = 'hb-slide-row border rounded p-3 bg-light-gray';
        row.innerHTML = ''
            + '<div class="d-flex flex-wrap align-items-center gap-3 mb-2">'
            + '<input type="hidden" name="' + prefix + '_slide_url[]" value="">'
            + '<div class="flex-grow-1" style="min-width:200px;">'
            + '<input type="file" name="' + prefix + '_slide_file[]" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">'
            + '<div class="form-text">Suba una imagen para el slider.</div>'
            + '<small class="text-muted d-block mt-1">Recomendado: 1920×700 px — JPG o WebP</small>'
            + '</div>'
            + '<button type="button" class="btn btn-sm btn-outline-danger hb-remove-slide-btn" title="Quitar"><i class="bi bi-trash"></i></button>'
            + '</div>'
            + '<div class="row g-2">'
            + '<div class="col-md-3"><label class="form-label small fw-semibold mb-1" for="' + rowId + '-enabled">Estado</label>'
            + '<select id="' + rowId + '-enabled" name="' + prefix + '_slide_enabled[]" class="form-select form-select-sm"><option value="1" selected>Activo</option><option value="0">Inactivo</option></select></div>'
            + '<div class="col-md-9"><label class="form-label small fw-semibold mb-1" for="' + rowId + '-alt">Texto alternativo</label>'
            + '<input type="text" id="' + rowId + '-alt" name="' + prefix + '_slide_alt[]" class="form-control form-control-sm" maxlength="180" placeholder="Descripción breve de la imagen"></div>'
            + '<div class="col-md-6"><label class="form-label small fw-semibold mb-1">Título del slide (opcional)</label>'
            + '<input type="text" name="' + prefix + '_slide_title[]" class="form-control form-control-sm" placeholder="Ej: Promoción de verano"></div>'
            + '<div class="col-md-6"><label class="form-label small fw-semibold mb-1">Subtítulo del slide (opcional)</label>'
            + '<input type="text" name="' + prefix + '_slide_subtitle[]" class="form-control form-control-sm" placeholder="Texto breve bajo el título"></div>'
            + '<div class="col-md-5"><label class="form-label small fw-semibold mb-1" for="' + rowId + '-link-text">Texto del enlace</label>'
            + '<input type="text" id="' + rowId + '-link-text" name="' + prefix + '_slide_link_text[]" class="form-control form-control-sm" maxlength="100" placeholder="Ej: Ver promoción"></div>'
            + '<div class="col-md-7"><label class="form-label small fw-semibold mb-1" for="' + rowId + '-link-url">URL del enlace</label>'
            + '<input type="text" id="' + rowId + '-link-url" name="' + prefix + '_slide_link_url[]" class="form-control form-control-sm" maxlength="500" placeholder="/ruta, #seccion o https://..."></div>'
            + '</div>';
        slidesList.appendChild(row);
        bindSlideRow(row);
        togglePanels();
    }

    root.querySelectorAll('.hb-mode-radio').forEach(function (radio) {
        radio.addEventListener('change', togglePanels);
    });
    root.querySelector('.hb-add-slide-btn')?.addEventListener('click', addSlideRow);
    slidesList?.querySelectorAll('.hb-slide-row').forEach(bindSlideRow);
    togglePanels();
})();
</script>
