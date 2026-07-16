<?php
/**
 * Campos admin: colores de título y subtítulo del hero.
 *
 * @var string $htcTitleName     name del input título (POST)
 * @var string $htcSubtitleName  name del input subtítulo (POST)
 * @var string $htcTitleId       id HTML título
 * @var string $htcSubtitleId    id HTML subtítulo
 * @var string $htcTitleValue    valor guardado (hex o vacío)
 * @var string $htcSubtitleValue valor guardado (hex o vacío)
 * @var string $htcColClass      clase columna Bootstrap (default col-md-6)
 */
require_once __DIR__ . '/hero-text-colors.php';

$htcTitleName = $htcTitleName ?? 'hero_title_color';
$htcSubtitleName = $htcSubtitleName ?? 'hero_subtitle_color';
$htcTitleId = $htcTitleId ?? preg_replace('/[^a-zA-Z0-9_-]/', '_', $htcTitleName);
$htcSubtitleId = $htcSubtitleId ?? preg_replace('/[^a-zA-Z0-9_-]/', '_', $htcSubtitleName);
$htcTitleValue = am_normalize_hex_color($htcTitleValue ?? '');
$htcSubtitleValue = am_normalize_hex_color($htcSubtitleValue ?? '');
$htcColClass = $htcColClass ?? 'col-md-6';
$htcPickerTitle = $htcTitleValue !== '' ? $htcTitleValue : '#FFFFFF';
$htcPickerSubtitle = $htcSubtitleValue !== '' ? $htcSubtitleValue : '#FFFFFF';
?>
<div class="<?php echo esc($htcColClass); ?>">
    <label for="<?php echo esc($htcTitleId); ?>" class="form-label fw-semibold">Color del título</label>
    <div class="d-flex align-items-center gap-2">
        <input type="color"
               id="<?php echo esc($htcTitleId); ?>_picker"
               class="form-control form-control-color"
               value="<?php echo esc($htcPickerTitle); ?>"
               title="Elegir color del título"
               data-htc-sync="<?php echo esc($htcTitleId); ?>">
        <input type="text"
               id="<?php echo esc($htcTitleId); ?>"
               name="<?php echo esc($htcTitleName); ?>"
               class="form-control form-control-premium"
               value="<?php echo esc($htcTitleValue); ?>"
               placeholder="#FFFFFF"
               pattern="^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$"
               maxlength="7"
               autocomplete="off"
               data-htc-hex>
    </div>
    <div class="form-text">Vacío = diseño original. Elija un color legible sobre la imagen del hero.</div>
</div>
<div class="<?php echo esc($htcColClass); ?>">
    <label for="<?php echo esc($htcSubtitleId); ?>" class="form-label fw-semibold">Color del subtítulo</label>
    <div class="d-flex align-items-center gap-2">
        <input type="color"
               id="<?php echo esc($htcSubtitleId); ?>_picker"
               class="form-control form-control-color"
               value="<?php echo esc($htcPickerSubtitle); ?>"
               title="Elegir color del subtítulo"
               data-htc-sync="<?php echo esc($htcSubtitleId); ?>">
        <input type="text"
               id="<?php echo esc($htcSubtitleId); ?>"
               name="<?php echo esc($htcSubtitleName); ?>"
               class="form-control form-control-premium"
               value="<?php echo esc($htcSubtitleValue); ?>"
               placeholder="#FFFFFF"
               pattern="^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$"
               maxlength="7"
               autocomplete="off"
               data-htc-hex>
    </div>
    <div class="form-text">Vacío = diseño original. Independiente del color del título.</div>
</div>
<?php
if (!defined('AM_HERO_TEXT_COLORS_ADMIN_JS')):
    define('AM_HERO_TEXT_COLORS_ADMIN_JS', true);
?>
<script>
(function () {
    function normalizeHex(v) {
        v = (v || '').trim();
        if (!/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(v)) return '';
        if (v.length === 4) {
            v = '#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3];
        }
        return v.toUpperCase();
    }
    document.addEventListener('input', function (e) {
        var t = e.target;
        if (!t) return;
        if (t.matches('input[type="color"][data-htc-sync]')) {
            var hex = document.getElementById(t.getAttribute('data-htc-sync'));
            if (hex) hex.value = (t.value || '').toUpperCase();
            return;
        }
        if (t.matches('input[data-htc-hex]')) {
            var n = normalizeHex(t.value);
            var picker = document.getElementById(t.id + '_picker');
            if (picker && n) picker.value = n;
        }
    });
})();
</script>
<?php endif; ?>
