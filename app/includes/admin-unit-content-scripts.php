<script>
function toggleUnitContentHomeMode(unitDom) {
    const mode = document.getElementById('uc-' + unitDom + '-home_display_mode');
    const singleWrap = document.getElementById('uc-' + unitDom + '-single-wrap');
    const rotationWrap = document.getElementById('uc-' + unitDom + '-rotation-wrap');
    if (!mode || !singleWrap || !rotationWrap) return;
    const isSingle = mode.value === 'single';
    singleWrap.style.display = isSingle ? '' : 'none';
    rotationWrap.style.display = isSingle ? 'none' : '';
}

function addUnitContentRotationRow(unitDom) {
    const tpl = document.getElementById('uc-rotation-row-template-' + unitDom);
    const container = document.getElementById('uc-' + unitDom + '-rotation-rows');
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
    document.querySelectorAll('.uc-home-display-mode').forEach(function (mode) {
        const unitDom = mode.getAttribute('data-uc-unit') || '';
        if (!unitDom) return;
        mode.addEventListener('change', function () { toggleUnitContentHomeMode(unitDom); });
        toggleUnitContentHomeMode(unitDom);
    });
    initUnitContentEditors();
});
</script>
