<?php
/**
 * Modal para crear una unidad de negocio personalizada + orden del acordeón.
 */
require_once __DIR__ . '/business-units-registry.php';
?>
<div class="modal fade" id="buUnitModal" tabindex="-1" aria-labelledby="buUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header text-white py-3" style="background-color: #081026;">
                <h5 class="modal-title fw-bold font-montserrat" id="buUnitModalLabel">
                    <i class="bi bi-building-add me-2"></i>Nueva unidad de negocio
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="buUnitModalLabel" class="form-label">Etiqueta del menú superior</label>
                        <input type="text" id="buUnitModalLabel" class="form-control form-control-premium" placeholder="Ej: FUNDACIÓN" required>
                    </div>
                    <div class="col-md-6">
                        <label for="buUnitModalKey" class="form-label">Clave interna</label>
                        <input type="text" id="buUnitModalKey" class="form-control form-control-premium font-monospace" placeholder="fundacion" pattern="[a-z][a-z0-9_]*">
                        <div class="form-text">Solo minúsculas, números y guión bajo. Se genera automáticamente.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="buUnitModalLogoSubtitle" class="form-label">Sub-título del logo</label>
                        <input type="text" id="buUnitModalLogoSubtitle" class="form-control form-control-premium" placeholder="Ej: Moviendo Vidas">
                    </div>
                    <div class="col-md-6">
                        <label for="buUnitModalColor" class="form-label">Color de tema</label>
                        <input type="color" id="buUnitModalColor" class="form-control form-control-color w-100" value="#1f347f" style="height: 43px;">
                    </div>
                    <div class="col-md-6">
                        <label for="buUnitModalHeroTitle" class="form-label">Título hero</label>
                        <input type="text" id="buUnitModalHeroTitle" class="form-control form-control-premium">
                    </div>
                    <div class="col-md-6">
                        <label for="buUnitModalHeroSubtitle" class="form-label">Subtítulo hero</label>
                        <input type="text" id="buUnitModalHeroSubtitle" class="form-control form-control-premium">
                    </div>
                    <div class="col-12">
                        <label for="buUnitModalSlug" class="form-label">Página principal (URL)</label>
                        <input type="text" id="buUnitModalSlug" class="form-control form-control-premium font-monospace" placeholder="unidad.php?u=fundacion">
                        <div class="form-text">Después podrás agregar enlaces y submenús en la sección de la unidad.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-3 bg-light">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger rounded-pill px-4" id="buUnitModalSaveBtn">
                    <i class="bi bi-check-lg me-1"></i>Agregar unidad
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const accordion = document.getElementById('businessUnitsAccordion');
    const orderInput = document.getElementById('businessUnitsOrderInput');
    let unitModal = null;
    let unitSortable = null;
    const builtinKeys = <?php
        echo json_encode(am_builtin_business_unit_keys(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    ?>;

    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function slugifyKey(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '')
            .replace(/_+/g, '_');
    }

    function getOrder() {
        try {
            const parsed = JSON.parse(orderInput?.value || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];
        }
    }

    function setOrder(keys) {
        if (orderInput) {
            orderInput.value = JSON.stringify(keys);
        }
    }

    function syncOrderFromDom() {
        const keys = [...accordion.querySelectorAll('.bu-unit-item')].map((el) => el.getAttribute('data-unit-key')).filter(Boolean);
        setOrder(keys);
    }

    function existingKeys() {
        return new Set([...accordion.querySelectorAll('.bu-unit-item')].map((el) => el.getAttribute('data-unit-key')).filter(Boolean));
    }

    function uniqueKey(baseKey) {
        const keys = existingKeys();
        let key = baseKey || 'unidad';
        if (!/^[a-z]/.test(key)) {
            key = 'u_' + key;
        }
        let candidate = key;
        let i = 2;
        while (keys.has(candidate) || builtinKeys.includes(candidate)) {
            candidate = key + '_' + i;
            i += 1;
        }
        return candidate;
    }

    function bindUnitPanel(panel) {
        const colorInput = panel.querySelector('.bu-unit-color-input');
        const colorText = panel.querySelector('.bu-unit-color-text');
        const labelInput = panel.querySelector('.bu-unit-label-input');
        const titleLabel = panel.querySelector('.bu-unit-title-label');

        colorInput?.addEventListener('input', function () {
            if (colorText) colorText.value = this.value;
            const dot = panel.querySelector('.accordion-button .badge');
            if (dot) dot.style.backgroundColor = this.value;
        });

        labelInput?.addEventListener('input', function () {
            if (titleLabel) titleLabel.textContent = this.value || titleLabel.textContent;
        });
    }

    function buildUnitPanelHtml(key, unit) {
        const isCustom = true;
        const collapseId = 'collapse-' + key.replace(/[^a-z0-9_-]/gi, '-');
        const color = unit.color || '#1f347f';
        const slug = unit.slug || ('unidad.php?u=' + encodeURIComponent(key));
        const label = unit.label || key.toUpperCase();
        const logoSubtitle = unit.logo_subtitle || label;
        const heroTitle = unit.heroTitle || '';
        const heroSubtitle = unit.heroSubtitle || '';

        return ''
            + '<div class="accordion-item border rounded-3 mb-2 overflow-hidden bu-unit-item" data-unit-key="' + escHtml(key) + '" data-is-custom="1">'
            + '  <h2 class="accordion-header d-flex align-items-stretch">'
            + '    <span class="bu-unit-handle d-flex align-items-center px-3 text-muted bg-white border-end" title="Arrastrar unidad"><i class="bi bi-grip-vertical fs-5"></i></span>'
            + '    <button class="accordion-button fw-bold text-navy-light flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#' + escHtml(collapseId) + '" aria-expanded="true">'
            + '      <span class="badge me-3" style="background-color:' + escHtml(color) + ';width:15px;height:15px;border-radius:50%;padding:0;"></span>'
            + '      <span class="bu-unit-title-label">' + escHtml(label) + '</span>'
            + '      <span class="badge bg-info-subtle text-info border ms-2">Personalizada</span>'
            + '    </button>'
            + '    <button type="button" class="btn btn-sm btn-outline-danger rounded-0 px-3 bu-unit-delete-btn" data-unit="' + escHtml(key) + '" title="Eliminar unidad"><i class="bi bi-trash"></i></button>'
            + '  </h2>'
            + '  <div id="' + escHtml(collapseId) + '" class="accordion-collapse collapse show" data-bs-parent="#businessUnitsAccordion">'
            + '    <div class="accordion-body bg-light-gray p-4"><div class="row g-3">'
            + '      <input type="hidden" name="business_units[' + escHtml(key) + '][is_custom]" value="1">'
            + '      <div class="col-md-4"><label class="form-label">Clave interna</label><input type="text" class="form-control form-control-premium bg-white" value="' + escHtml(key) + '" readonly></div>'
            + '      <div class="col-md-8"><label class="form-label">Página principal (slug / URL)</label><input type="text" name="business_units[' + escHtml(key) + '][slug]" class="form-control form-control-premium bg-white bu-unit-slug-input" value="' + escHtml(slug) + '" required></div>'
            + '      <div class="col-md-4"><label class="form-label">Etiqueta de Menú Superior</label><input type="text" name="business_units[' + escHtml(key) + '][label]" class="form-control form-control-premium bg-white bu-unit-label-input" value="' + escHtml(label) + '" required></div>'
            + '      <div class="col-md-4"><label class="form-label">Sub-título del Logo (Header)</label><input type="text" name="business_units[' + escHtml(key) + '][logo_subtitle]" class="form-control form-control-premium bg-white" value="' + escHtml(logoSubtitle) + '" required></div>'
            + '      <div class="col-md-4"><label class="form-label">Color de Tema</label><div class="d-flex gap-2"><input type="color" name="business_units[' + escHtml(key) + '][color]" class="form-control form-control-color bu-unit-color-input" value="' + escHtml(color) + '" required style="height:43px;width:60px;"><input type="text" class="form-control form-control-premium bg-white flex-grow-1 bu-unit-color-text" value="' + escHtml(color) + '" readonly></div></div>'
            + '      <div class="col-md-6"><label class="form-label">Título Hero Principal</label><input type="text" name="business_units[' + escHtml(key) + '][heroTitle]" class="form-control form-control-premium bg-white" value="' + escHtml(heroTitle) + '"></div>'
            + '      <div class="col-md-6"><label class="form-label">Subtítulo Hero Principal</label><input type="text" name="business_units[' + escHtml(key) + '][heroSubtitle]" class="form-control form-control-premium bg-white" value="' + escHtml(heroSubtitle) + '"></div>'
            + '      <div class="col-12 mt-4"><div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h6 class="fw-bold mb-0 text-navy-light"><i class="bi bi-link-45deg me-1"></i>Enlaces del Menú Secundario</h6><button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 bu-menu-add-btn" data-unit="' + escHtml(key) + '"><i class="bi bi-plus-lg me-1"></i>Agregar enlace</button></div><p class="text-muted small mb-2">Arrastra para cambiar el orden. Usa editar para agregar submenús.</p><div class="bu-menu-sortable list-group mb-2" data-unit="' + escHtml(key) + '"></div><div class="bu-menu-fields" data-unit="' + escHtml(key) + '" aria-hidden="true"></div></div>'
            + '    </div></div></div></div>';
    }

    function initUnitSortable() {
        if (!accordion || typeof Sortable === 'undefined') return;
        if (unitSortable) unitSortable.destroy();
        unitSortable = new Sortable(accordion, {
            handle: '.bu-unit-handle',
            animation: 150,
            draggable: '.bu-unit-item',
            onEnd: syncOrderFromDom,
        });
    }

    function openUnitModal() {
        document.getElementById('buUnitModalLabel').value = '';
        document.getElementById('buUnitModalKey').value = '';
        document.getElementById('buUnitModalLogoSubtitle').value = '';
        document.getElementById('buUnitModalColor').value = '#1f347f';
        document.getElementById('buUnitModalHeroTitle').value = '';
        document.getElementById('buUnitModalHeroSubtitle').value = '';
        document.getElementById('buUnitModalSlug').value = '';
        if (!unitModal) {
            unitModal = new bootstrap.Modal(document.getElementById('buUnitModal'));
        }
        unitModal.show();
    }

    function saveUnitModal() {
        const label = document.getElementById('buUnitModalLabel').value.trim();
        if (!label) {
            alert('La etiqueta del menú es obligatoria.');
            return;
        }
        const rawKey = document.getElementById('buUnitModalKey').value.trim() || slugifyKey(label);
        const key = uniqueKey(slugifyKey(rawKey));
        const color = document.getElementById('buUnitModalColor').value || '#1f347f';
        const logoSubtitle = document.getElementById('buUnitModalLogoSubtitle').value.trim() || label;
        const heroTitle = document.getElementById('buUnitModalHeroTitle').value.trim();
        const heroSubtitle = document.getElementById('buUnitModalHeroSubtitle').value.trim();
        const slug = document.getElementById('buUnitModalSlug').value.trim() || ('unidad.php?u=' + encodeURIComponent(key));

        const unit = {
            label,
            color,
            logo_subtitle: logoSubtitle,
            heroTitle,
            heroSubtitle,
            slug,
        };

        const wrapper = document.createElement('div');
        wrapper.innerHTML = buildUnitPanelHtml(key, unit);
        const panel = wrapper.firstElementChild;
        accordion.appendChild(panel);
        bindUnitPanel(panel);

        if (window.BuMenuManager) {
            window.BuMenuManager.registerUnit(key, []);
        }

        const order = getOrder();
        order.push(key);
        setOrder(order);
        syncOrderFromDom();
        initUnitSortable();
        unitModal?.hide();
    }

    document.getElementById('buUnitAddBtn')?.addEventListener('click', openUnitModal);
    document.getElementById('buUnitModalSaveBtn')?.addEventListener('click', saveUnitModal);

    document.getElementById('buUnitModalLabel')?.addEventListener('input', function () {
        const keyInput = document.getElementById('buUnitModalKey');
        const slugInput = document.getElementById('buUnitModalSlug');
        if (keyInput && !keyInput.dataset.touched) {
            const key = slugifyKey(this.value);
            keyInput.value = key;
            if (slugInput && !slugInput.dataset.touched) {
                slugInput.value = key ? ('unidad.php?u=' + encodeURIComponent(key)) : '';
            }
        }
    });

    ['buUnitModalKey', 'buUnitModalSlug'].forEach(function (id) {
        document.getElementById(id)?.addEventListener('input', function () {
            this.dataset.touched = '1';
        });
    });

    accordion?.addEventListener('click', function (e) {
        const deleteBtn = e.target.closest('.bu-unit-delete-btn');
        if (!deleteBtn) return;
        const key = deleteBtn.getAttribute('data-unit');
        const item = deleteBtn.closest('.bu-unit-item');
        if (!item || item.getAttribute('data-is-custom') !== '1') return;
        if (!confirm('¿Eliminar esta unidad de negocio? Se quitará al guardar los cambios globales.')) return;
        item.remove();
        if (window.BuMenuManager) {
            window.BuMenuManager.removeUnit(key);
        }
        syncOrderFromDom();
    });

    accordion?.querySelectorAll('.bu-unit-item').forEach(bindUnitPanel);

    const globalForm = document.querySelector('#tab-global form');
    globalForm?.addEventListener('submit', syncOrderFromDom);

    document.addEventListener('DOMContentLoaded', function () {
        initUnitSortable();
    });
    if (document.readyState !== 'loading') {
        initUnitSortable();
    }
})();
</script>
