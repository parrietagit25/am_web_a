<?php
/**
 * Modal y JS para gestionar menús de unidades de negocio (global admin).
 */
require_once __DIR__ . '/../services/GenericPageService.php';

$buMenuMasterPages = [];
foreach (GenericPageService::published(isset($siteData) && is_array($siteData) ? $siteData : []) as $buMenuGp) {
    $buMenuMasterPages[] = [
        'title' => (string) ($buMenuGp['title'] ?? ''),
        'slug' => (string) ($buMenuGp['slug'] ?? ''),
    ];
}
unset($buMenuGp);
?>
<div class="modal fade" id="buMenuItemModal" tabindex="-1" aria-labelledby="buMenuItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header text-white py-3" style="background-color: #081026;">
                <h5 class="modal-title fw-bold font-montserrat" id="buMenuItemModalLabel">
                    <i class="bi bi-list-nested me-2"></i><span id="buMenuModalTitle">Enlace del menú</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="buMenuModalUnit" value="">
                <input type="hidden" id="buMenuModalIndex" value="-1">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="buMenuModalLabelInput" class="form-label">Texto del enlace</label>
                        <input type="text" id="buMenuModalLabelInput" class="form-control form-control-premium" placeholder="Ej: ALQUILERES">
                    </div>
                    <div class="col-md-6">
                        <label for="buMenuModalLinkInput" class="form-label">URL o ancla</label>
                        <input type="text" id="buMenuModalLinkInput" class="form-control form-control-premium" placeholder="Ej: musica.php o /ruta-externa">
                    </div>
                    <div class="col-12">
                        <label for="buMenuModalPageSelect" class="form-label">
                            Página del maestro <span class="text-muted fw-normal">(opcional — llena la URL automáticamente)</span>
                        </label>
                        <select id="buMenuModalPageSelect" class="form-select form-control-premium">
                            <option value="">— Elegir página creada en Maestro de Páginas —</option>
                        </select>
                        <div class="form-text">Las páginas funcionales (Nuestra flota, Sucursales, etc.) no aparecen aquí: no se pueden reemplazar por páginas genéricas.</div>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="buMenuModalActive" checked>
                            <label class="form-check-label fw-semibold" for="buMenuModalActive">Enlace activo</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="buMenuModalHasSubmenu">
                            <label class="form-check-label fw-semibold" for="buMenuModalHasSubmenu">
                                Este enlace tiene submenú desplegable
                            </label>
                        </div>
                        <div class="form-text">Si activas submenú, la URL principal puede ser <code>#</code>.</div>
                    </div>
                </div>
                <div id="buMenuSubmenuSection" class="mt-4 d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-navy-light mb-0"><i class="bi bi-diagram-3 me-1"></i>Submenús</h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="buMenuAddSubmenuBtn">
                            <i class="bi bi-plus-lg me-1"></i>Agregar submenú
                        </button>
                    </div>
                    <p class="text-muted small mb-2">Arrastra para ordenar los submenús.</p>
                    <div id="buMenuSubmenuList" class="list-group"></div>
                </div>
            </div>
            <div class="modal-footer border-0 p-3 bg-light">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger rounded-pill px-4" id="buMenuModalSaveBtn">
                    <i class="bi bi-check-lg me-1"></i>Guardar enlace
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const buMenuData = {};
    const MASTER_PAGES = <?php echo json_encode($buMenuMasterPages, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
    let modalInstance = null;
    let modalSubmenuSortable = null;

    function masterPageLink(slug) {
        const unitKey = document.getElementById('buMenuModalUnit')?.value || '';
        return '/p/' + slug + (unitKey && unitKey !== 'rentacar' ? '?unit=' + encodeURIComponent(unitKey) : '');
    }

    function masterPageSlugFromLink(link) {
        const match = String(link || '').match(/^\/p\/([a-z0-9-]+)(?:[/?#]|$)/);
        if (!match) return '';
        return MASTER_PAGES.some((p) => p.slug === match[1]) ? match[1] : '';
    }

    function masterPageOptionsHtml(selectedSlug) {
        return MASTER_PAGES.map((p) =>
            '<option value="' + escHtml(p.slug) + '"' + (p.slug === selectedSlug ? ' selected' : '') + '>'
            + escHtml(p.title) + ' (/p/' + escHtml(p.slug) + ')</option>'
        ).join('');
    }

    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function boolValue(value) {
        return value === true || value === 1 || value === '1' || value === 'true';
    }

    function normalizeItem(item, itemIndex) {
        const submenu = Array.isArray(item?.submenu)
            ? item.submenu
                .filter((s) => s && String(s.label || '').trim() && String(s.link || '').trim())
                .map((s, index) => ({
                    label: String(s.label).trim(),
                    link: String(s.link).trim(),
                    active: !Object.prototype.hasOwnProperty.call(s, 'active') || boolValue(s.active),
                    sort_order: Number.isInteger(Number(s.sort_order)) ? Number(s.sort_order) : index,
                }))
            : [];
        return {
            label: String(item?.label || '').trim(),
            link: String(item?.link || '').trim(),
            active: !Object.prototype.hasOwnProperty.call(item || {}, 'active') || boolValue(item.active),
            sort_order: Number.isInteger(Number(item?.sort_order)) ? Number(item.sort_order) : itemIndex,
            submenu,
        };
    }

    function initData() {
        document.querySelectorAll('.bu-menu-initial').forEach((el) => {
            const unit = el.getAttribute('data-unit');
            if (!unit) return;
            try {
                const parsed = JSON.parse(el.textContent || '[]');
                buMenuData[unit] = Array.isArray(parsed) ? parsed.map(normalizeItem) : [];
            } catch (e) {
                buMenuData[unit] = [];
            }
        });
    }

    function registerUnit(unitKey, items) {
        buMenuData[unitKey] = Array.isArray(items) ? items.map(normalizeItem) : [];
        renderMenuList(unitKey);
    }

    function removeUnit(unitKey) {
        delete buMenuData[unitKey];
        document.querySelector('.bu-menu-fields[data-unit="' + unitKey + '"]')?.remove();
        document.querySelector('.bu-menu-sortable[data-unit="' + unitKey + '"]')?.remove();
    }

    function appendHidden(container, name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        container.appendChild(input);
    }

    function syncHiddenFields(unitKey) {
        const container = document.querySelector('.bu-menu-fields[data-unit="' + unitKey + '"]');
        if (!container) return;
        container.innerHTML = '';
        (buMenuData[unitKey] || []).forEach((item, i) => {
            appendHidden(container, 'menu[' + i + '][label]', item.label);
            appendHidden(container, 'menu[' + i + '][link]', item.link);
            appendHidden(container, 'menu[' + i + '][active]', item.active ? '1' : '0');
            appendHidden(container, 'menu[' + i + '][sort_order]', String(i));
            if (item.submenu && item.submenu.length) {
                item.submenu.forEach((sub, j) => {
                    appendHidden(container, 'menu[' + i + '][submenu][' + j + '][label]', sub.label);
                    appendHidden(container, 'menu[' + i + '][submenu][' + j + '][link]', sub.link);
                    appendHidden(container, 'menu[' + i + '][submenu][' + j + '][active]', sub.active ? '1' : '0');
                    appendHidden(container, 'menu[' + i + '][submenu][' + j + '][sort_order]', String(j));
                });
            }
        });
    }

    function syncAllHiddenFields() {
        Object.keys(buMenuData).forEach(syncHiddenFields);
    }

    function renderMenuList(unitKey) {
        const list = document.querySelector('.bu-menu-sortable[data-unit="' + unitKey + '"]');
        if (!list) return;

        const items = buMenuData[unitKey] || [];
        if (!items.length) {
            list.innerHTML = '<div class="list-group-item text-muted small text-center py-4">Sin enlaces. Haz clic en «Agregar enlace».</div>';
            syncHiddenFields(unitKey);
            return;
        }

        list.innerHTML = items.map((item, index) => {
            const subCount = item.submenu ? item.submenu.length : 0;
            const subBadge = subCount
                ? '<span class="badge bg-secondary-subtle text-secondary border ms-2">' + subCount + ' sub</span>'
                : '';
            const inactiveBadge = item.active
                ? ''
                : '<span class="badge bg-light text-muted border ms-2">Inactivo</span>';
            const linkPreview = item.link
                ? '<span class="text-muted small d-block text-truncate">' + escHtml(item.link) + '</span>'
                : '<span class="text-warning small d-block">Sin URL</span>';

            const subRows = (item.submenu || []).map((sub, subIndex) => {
                const subInactive = sub.active
                    ? ''
                    : '<span class="badge bg-light text-muted border ms-2">Inactivo</span>';
                return ''
                    + '<div class="d-flex align-items-center gap-2 ps-4 py-1 border-top small">'
                    + '  <i class="bi bi-arrow-return-right text-muted"></i>'
                    + '  <span class="fw-semibold text-navy">' + escHtml(sub.label) + '</span>' + subInactive
                    + '  <span class="text-muted text-truncate flex-grow-1">' + escHtml(sub.link) + '</span>'
                    + '  <span class="d-flex gap-1 flex-shrink-0">'
                    + '    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 bu-sub-edit-btn" data-unit="' + escHtml(unitKey) + '" data-index="' + index + '" title="Editar subopciones"><i class="bi bi-pencil"></i></button>'
                    + '    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 bu-sub-delete-btn" data-unit="' + escHtml(unitKey) + '" data-index="' + index + '" data-sub-index="' + subIndex + '" title="Eliminar subopción"><i class="bi bi-trash"></i></button>'
                    + '  </span>'
                    + '</div>';
            }).join('');

            const addSubBtn = ''
                + '<button type="button" class="btn btn-sm btn-link text-danger text-decoration-none ps-4 py-1 bu-sub-add-btn" data-unit="' + escHtml(unitKey) + '" data-index="' + index + '">'
                + '  <i class="bi bi-plus-lg me-1"></i>Agregar subopción'
                + '</button>';

            return ''
                + '<div class="list-group-item bu-menu-item" data-index="' + index + '">'
                + '  <div class="d-flex align-items-center gap-2">'
                + '    <span class="bu-menu-handle text-muted" title="Arrastrar"><i class="bi bi-grip-vertical fs-5"></i></span>'
                + '    <div class="flex-grow-1 min-width-0">'
                + '      <div class="fw-semibold text-navy">' + escHtml(item.label || '(Sin texto)') + subBadge + inactiveBadge + '</div>'
                +        linkPreview
                + '    </div>'
                + '    <div class="d-flex gap-1 flex-shrink-0">'
                + '      <button type="button" class="btn btn-sm btn-outline-secondary bu-menu-edit-btn" data-unit="' + escHtml(unitKey) + '" data-index="' + index + '" title="Editar enlace y submenús"><i class="bi bi-pencil"></i></button>'
                + '      <button type="button" class="btn btn-sm btn-outline-danger bu-menu-delete-btn" data-unit="' + escHtml(unitKey) + '" data-index="' + index + '" title="Eliminar"><i class="bi bi-trash"></i></button>'
                + '    </div>'
                + '  </div>'
                + '<div class="mt-2">' + subRows + addSubBtn + '</div>'
                + '</div>';
        }).join('');

        syncHiddenFields(unitKey);

        if (list._sortable) {
            list._sortable.destroy();
        }
        list._sortable = new Sortable(list, {
            handle: '.bu-menu-handle',
            animation: 150,
            draggable: '.bu-menu-item',
            onEnd: function () {
                const newOrder = [];
                list.querySelectorAll('.bu-menu-item').forEach((row) => {
                    const idx = parseInt(row.getAttribute('data-index'), 10);
                    if (!Number.isNaN(idx) && buMenuData[unitKey][idx]) {
                        newOrder.push(buMenuData[unitKey][idx]);
                    }
                });
                buMenuData[unitKey] = newOrder;
                renderMenuList(unitKey);
            },
        });
    }

    function renderAllLists() {
        Object.keys(buMenuData).forEach(renderMenuList);
    }

    function submenuRowHtml(sub, index) {
        const pageSelectHtml = MASTER_PAGES.length
            ? ''
                + '<div class="col-md-12">'
                + '  <select class="form-select form-select-sm bu-submenu-page-select" title="Página del maestro (llena la URL)">'
                + '    <option value="">— Página del maestro (opcional) —</option>'
                +      masterPageOptionsHtml(masterPageSlugFromLink(sub.link))
                + '  </select>'
                + '</div>'
            : '';
        return ''
            + '<div class="list-group-item bu-submenu-row d-flex align-items-start gap-2" data-sub-index="' + index + '">'
            + '  <span class="bu-submenu-handle text-muted pt-2" title="Arrastrar"><i class="bi bi-grip-vertical"></i></span>'
            + '  <div class="row g-2 flex-grow-1">'
            + '    <div class="col-md-5">'
            + '      <input type="text" class="form-control form-control-sm bu-submenu-label" placeholder="Texto" value="' + escHtml(sub.label) + '">'
            + '    </div>'
            + '    <div class="col-md-6">'
            + '      <input type="text" class="form-control form-control-sm bu-submenu-link" placeholder="URL" value="' + escHtml(sub.link) + '">'
            + '    </div>'
            + '    <div class="col-md-1 d-flex align-items-center justify-content-center">'
            + '      <input type="checkbox" class="form-check-input bu-submenu-active" title="Activo"' + (sub.active ? ' checked' : '') + '>'
            + '    </div>'
            +      pageSelectHtml
            + '    <div class="col-md-12 d-flex justify-content-end">'
            + '      <button type="button" class="btn btn-sm btn-outline-danger bu-submenu-remove-btn" title="Quitar"><i class="bi bi-x-lg"></i></button>'
            + '    </div>'
            + '  </div>'
            + '</div>';
    }

    function readSubmenuFromModal() {
        const rows = document.querySelectorAll('#buMenuSubmenuList .bu-submenu-row');
        const items = [];
        rows.forEach((row) => {
            const label = row.querySelector('.bu-submenu-label')?.value.trim() || '';
            const link = row.querySelector('.bu-submenu-link')?.value.trim() || '';
            if (label && link) {
                items.push({
                    label,
                    link,
                    active: !!row.querySelector('.bu-submenu-active')?.checked,
                    sort_order: items.length,
                });
            }
        });
        return items;
    }

    function renderSubmenuList(submenu) {
        const list = document.getElementById('buMenuSubmenuList');
        if (!list) return;
        const items = Array.isArray(submenu) ? submenu : [];
        if (!items.length) {
            list.innerHTML = '<div class="list-group-item text-muted small text-center py-3">Sin submenús. Agrega uno abajo.</div>';
        } else {
            list.innerHTML = items.map((sub, i) => submenuRowHtml(sub, i)).join('');
        }

        if (modalSubmenuSortable) {
            modalSubmenuSortable.destroy();
            modalSubmenuSortable = null;
        }
        modalSubmenuSortable = new Sortable(list, {
            handle: '.bu-submenu-handle',
            animation: 150,
            draggable: '.bu-submenu-row',
        });
    }

    function toggleSubmenuSection(show) {
        const section = document.getElementById('buMenuSubmenuSection');
        if (!section) return;
        section.classList.toggle('d-none', !show);
    }

    function openModal(unitKey, index) {
        document.getElementById('buMenuModalUnit').value = unitKey;
        document.getElementById('buMenuModalIndex').value = String(index);
        document.getElementById('buMenuModalTitle').textContent = index >= 0 ? 'Editar enlace del menú' : 'Nuevo enlace del menú';

        const item = index >= 0 ? buMenuData[unitKey][index] : { label: '', link: '', submenu: [] };
        document.getElementById('buMenuModalLabelInput').value = item.label || '';
        document.getElementById('buMenuModalLinkInput').value = item.link || '';
        const pageSelect = document.getElementById('buMenuModalPageSelect');
        if (pageSelect) {
            pageSelect.innerHTML = '<option value="">— Elegir página creada en Maestro de Páginas —</option>'
                + masterPageOptionsHtml(masterPageSlugFromLink(item.link));
            pageSelect.closest('.col-12')?.classList.toggle('d-none', !MASTER_PAGES.length);
        }
        document.getElementById('buMenuModalActive').checked = !Object.prototype.hasOwnProperty.call(item, 'active') || !!item.active;
        const hasSub = !!(item.submenu && item.submenu.length);
        document.getElementById('buMenuModalHasSubmenu').checked = hasSub;
        toggleSubmenuSection(hasSub);
        renderSubmenuList(item.submenu || []);

        if (!modalInstance) {
            modalInstance = new bootstrap.Modal(document.getElementById('buMenuItemModal'));
        }
        modalInstance.show();
    }

    function saveModal() {
        const unitKey = document.getElementById('buMenuModalUnit').value;
        const index = parseInt(document.getElementById('buMenuModalIndex').value, 10);
        const label = document.getElementById('buMenuModalLabelInput').value.trim();
        const link = document.getElementById('buMenuModalLinkInput').value.trim();
        const hasSubmenu = document.getElementById('buMenuModalHasSubmenu').checked;

        if (!label) {
            alert('El texto del enlace es obligatorio.');
            return;
        }

        const submenu = hasSubmenu ? readSubmenuFromModal() : [];
        if (hasSubmenu && !submenu.length) {
            alert('Agrega al menos un submenú o desactiva la opción de submenú.');
            return;
        }

        const finalLink = link || (hasSubmenu ? '#' : '');
        if (!finalLink && !hasSubmenu) {
            alert('Indica una URL o ancla para el enlace.');
            return;
        }

        const newItem = {
            label,
            link: finalLink,
            active: document.getElementById('buMenuModalActive').checked,
            sort_order: index >= 0 ? index : (buMenuData[unitKey] || []).length,
            submenu,
        };
        if (!buMenuData[unitKey]) {
            buMenuData[unitKey] = [];
        }
        if (index >= 0) {
            buMenuData[unitKey][index] = newItem;
        } else {
            buMenuData[unitKey].push(newItem);
        }

        renderMenuList(unitKey);
        modalInstance?.hide();
    }

    document.addEventListener('click', function (e) {
        const addBtn = e.target.closest('.bu-menu-add-btn');
        if (addBtn) {
            openModal(addBtn.getAttribute('data-unit'), -1);
            return;
        }
        const editBtn = e.target.closest('.bu-menu-edit-btn');
        if (editBtn) {
            openModal(editBtn.getAttribute('data-unit'), parseInt(editBtn.getAttribute('data-index'), 10));
            return;
        }
        const deleteBtn = e.target.closest('.bu-menu-delete-btn');
        if (deleteBtn) {
            const unitKey = deleteBtn.getAttribute('data-unit');
            const idx = parseInt(deleteBtn.getAttribute('data-index'), 10);
            if (confirm('¿Eliminar este enlace del menú?')) {
                buMenuData[unitKey].splice(idx, 1);
                renderMenuList(unitKey);
            }
            return;
        }
        const subEditBtn = e.target.closest('.bu-sub-edit-btn');
        if (subEditBtn) {
            openModal(subEditBtn.getAttribute('data-unit'), parseInt(subEditBtn.getAttribute('data-index'), 10));
            return;
        }
        const subDeleteBtn = e.target.closest('.bu-sub-delete-btn');
        if (subDeleteBtn) {
            const unitKey = subDeleteBtn.getAttribute('data-unit');
            const idx = parseInt(subDeleteBtn.getAttribute('data-index'), 10);
            const subIdx = parseInt(subDeleteBtn.getAttribute('data-sub-index'), 10);
            const parent = (buMenuData[unitKey] || [])[idx];
            if (!parent || !Array.isArray(parent.submenu)) return;
            if (confirm('¿Eliminar esta subopción del menú?')) {
                parent.submenu.splice(subIdx, 1);
                renderMenuList(unitKey);
            }
            return;
        }
        const subAddBtn = e.target.closest('.bu-sub-add-btn');
        if (subAddBtn) {
            const unitKey = subAddBtn.getAttribute('data-unit');
            const idx = parseInt(subAddBtn.getAttribute('data-index'), 10);
            const parent = (buMenuData[unitKey] || [])[idx];
            if (!parent) return;
            openModal(unitKey, idx);
            document.getElementById('buMenuModalHasSubmenu').checked = true;
            toggleSubmenuSection(true);
            renderSubmenuList((parent.submenu || []).concat([{ label: '', link: '', active: true, sort_order: (parent.submenu || []).length }]));
        }
    });

    document.getElementById('buMenuModalHasSubmenu')?.addEventListener('change', function () {
        toggleSubmenuSection(this.checked);
        if (this.checked) {
            const list = document.getElementById('buMenuSubmenuList');
            if (list && !list.querySelector('.bu-submenu-row')) {
                renderSubmenuList([{ label: '', link: '', active: true, sort_order: 0 }]);
            }
        }
    });

    document.getElementById('buMenuAddSubmenuBtn')?.addEventListener('click', function () {
        const list = document.getElementById('buMenuSubmenuList');
        const current = readSubmenuFromModal();
        current.push({ label: '', link: '', active: true, sort_order: current.length });
        renderSubmenuList(current);
    });

    document.getElementById('buMenuSubmenuList')?.addEventListener('click', function (e) {
        const btn = e.target.closest('.bu-submenu-remove-btn');
        if (!btn) return;
        const row = btn.closest('.bu-submenu-row');
        if (!row) return;
        const current = readSubmenuFromModal();
        const rows = [...document.querySelectorAll('#buMenuSubmenuList .bu-submenu-row')];
        const removeIdx = rows.indexOf(row);
        if (removeIdx >= 0) {
            current.splice(removeIdx, 1);
        }
        renderSubmenuList(current);
    });

    document.getElementById('buMenuModalSaveBtn')?.addEventListener('click', saveModal);

    document.getElementById('buMenuModalPageSelect')?.addEventListener('change', function () {
        if (!this.value) return;
        const linkInput = document.getElementById('buMenuModalLinkInput');
        if (linkInput) {
            linkInput.value = masterPageLink(this.value);
        }
        const labelInput = document.getElementById('buMenuModalLabelInput');
        if (labelInput && !labelInput.value.trim()) {
            const page = MASTER_PAGES.find((p) => p.slug === this.value);
            if (page) labelInput.value = page.title;
        }
    });

    document.getElementById('buMenuSubmenuList')?.addEventListener('change', function (e) {
        const select = e.target.closest('.bu-submenu-page-select');
        if (!select || !select.value) return;
        const row = select.closest('.bu-submenu-row');
        const linkInput = row?.querySelector('.bu-submenu-link');
        if (linkInput) {
            linkInput.value = masterPageLink(select.value);
        }
        const labelInput = row?.querySelector('.bu-submenu-label');
        if (labelInput && !labelInput.value.trim()) {
            const page = MASTER_PAGES.find((p) => p.slug === select.value);
            if (page) labelInput.value = page.title;
        }
    });

    document.querySelectorAll('.bu-menu-form').forEach(function (form) {
        form.addEventListener('submit', function () {
            syncHiddenFields(form.getAttribute('data-unit'));
        });
    });

    initData();
    renderAllLists();

    window.BuMenuManager = {
        registerUnit,
        removeUnit,
        syncAll: syncAllHiddenFields,
    };
})();
</script>
