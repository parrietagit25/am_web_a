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
$hbConfig = $hbConfig ?? HeaderBannerService::defaults();
$hbMode = ($hbConfig['mode'] ?? HeaderBannerService::MODE_STATIC) === HeaderBannerService::MODE_SLIDER
    ? HeaderBannerService::MODE_SLIDER
    : HeaderBannerService::MODE_STATIC;
$hbSlides = $hbConfig['slider']['slides'] ?? [];
if (empty($hbSlides)) {
    $hbSlides = [['image_url' => '', 'alt' => '']];
}
$hbStaticUrl = (string) ($hbConfig['image_url'] ?? '');
$hbInterval = (int) ($hbConfig['slider']['interval_ms'] ?? 5000);
$hbTransition = (string) ($hbConfig['slider']['transition'] ?? 'fade');
?>
<div class="hb-section border rounded-3 p-4 bg-white" id="<?php echo esc($hbDomId); ?>" data-hb-prefix="<?php echo esc($hbPrefix); ?>">
    <h6 class="fw-bold text-navy-light mb-3"><i class="bi bi-images me-1"></i>Cabecera de la página</h6>

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
        <input type="file" name="<?php echo esc($hbPrefix); ?>_static_file" class="form-control form-control-premium hb-static-file" accept="image/*">
        <div class="form-text">JPG, PNG, GIF o WEBP. Máx. 5MB.</div>
        <?php if ($hbStaticUrl !== ''): ?>
        <div class="mt-2">
            <img src="<?php echo esc($hbStaticUrl); ?>" alt="" class="img-thumbnail hb-static-preview" style="max-height: 120px;">
        </div>
        <?php endif; ?>
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
            <?php foreach ($hbSlides as $slide): ?>
            <div class="hb-slide-row border rounded p-3 bg-light-gray d-flex flex-wrap align-items-center gap-3">
                <input type="hidden" name="<?php echo esc($hbPrefix); ?>_slide_url[]" value="<?php echo esc($slide['image_url'] ?? ''); ?>">
                <div class="flex-grow-1" style="min-width: 200px;">
                    <input type="file" name="<?php echo esc($hbPrefix); ?>_slide_file[]" class="form-control form-control-sm" accept="image/*">
                    <div class="form-text">Deje vacío para conservar la imagen actual.</div>
                </div>
                <?php if (!empty($slide['image_url'])): ?>
                <img src="<?php echo esc($slide['image_url']); ?>" alt="" class="img-thumbnail" style="max-height: 70px;">
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-outline-danger hb-remove-slide-btn" title="Quitar"><i class="bi bi-trash"></i></button>
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

    const prefix = root.getAttribute('data-hb-prefix') || '';
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
        row.className = 'hb-slide-row border rounded p-3 bg-light-gray d-flex flex-wrap align-items-center gap-3';
        row.innerHTML = ''
            + '<input type="hidden" name="' + prefix + '_slide_url[]" value="">'
            + '<div class="flex-grow-1" style="min-width:200px;">'
            + '<input type="file" name="' + prefix + '_slide_file[]" class="form-control form-control-sm" accept="image/*">'
            + '<div class="form-text">Suba una imagen para el slider.</div>'
            + '</div>'
            + '<button type="button" class="btn btn-sm btn-outline-danger hb-remove-slide-btn" title="Quitar"><i class="bi bi-trash"></i></button>';
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
