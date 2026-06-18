<?php
/**
 * Modal y JS para gestionar menús de unidades de negocio (global admin).
 */
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
                        <input type="text" id="buMenuModalLabelInput" class="form-control form-control-premium" placeholder="Ej: ALQUILERES" required>
                    </div>
                    <div class="col-md-6">
                        <label for="buMenuModalLinkInput" class="form-label">URL o ancla</label>
                        <input type="text" id="buMenuModalLinkInput" class="form-control form-control-premium" placeholder="Ej: /flota.php o #">
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
    let modalInstance = null;
    let modalSubmenuSortable = null;

    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function normalizeItem(item) {
        const submenu = Array.isArray(item?.submenu)
            ? item.submenu
                .filter((s) => s && String(s.label || '').trim() && String(s.link || '').trim())
                .map((s) => ({ label: String(s.label).trim(), link: String(s.link).trim() }))
            : [];
        return {
            label: String(item?.label || '').trim(),
            link: String(item?.link || '').trim(),
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
            appendHidden(container, 'business_units[' + unitKey + '][menu][' + i + '][label]', item.label);
            appendHidden(container, 'business_units[' + unitKey + '][menu][' + i + '][link]', item.link);
            if (item.submenu && item.submenu.length) {
                item.submenu.forEach((sub, j) => {
                    appendHidden(container, 'business_units[' + unitKey + '][menu][' + i + '][submenu][' + j + '][label]', sub.label);
                    appendHidden(container, 'business_units[' + unitKey + '][menu][' + i + '][submenu][' + j + '][link]', sub.link);
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
            const linkPreview = item.link
                ? '<span class="text-muted small d-block text-truncate">' + escHtml(item.link) + '</span>'
                : '<span class="text-warning small d-block">Sin URL</span>';

            return ''
                + '<div class="list-group-item bu-menu-item d-flex align-items-center gap-2" data-index="' + index + '">'
                + '  <span class="bu-menu-handle text-muted" title="Arrastrar"><i class="bi bi-grip-vertical fs-5"></i></span>'
                + '  <div class="flex-grow-1 min-width-0">'
                + '    <div class="fw-semibold text-navy">' + escHtml(item.label || '(Sin texto)') + subBadge + '</div>'
                +      linkPreview
                + '  </div>'
                + '  <div class="d-flex gap-1 flex-shrink-0">'
                + '    <button type="button" class="btn btn-sm btn-outline-secondary bu-menu-edit-btn" data-unit="' + escHtml(unitKey) + '" data-index="' + index + '" title="Editar"><i class="bi bi-pencil"></i></button>'
                + '    <button type="button" class="btn btn-sm btn-outline-danger bu-menu-delete-btn" data-unit="' + escHtml(unitKey) + '" data-index="' + index + '" title="Eliminar"><i class="bi bi-trash"></i></button>'
                + '  </div>'
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
            + '    <div class="col-md-1 d-flex align-items-center">'
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
                items.push({ label, link });
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
        if (!finalLink) {
            alert('Indica una URL o ancla para el enlace.');
            return;
        }

        const newItem = { label, link: finalLink, submenu };
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
        }
    });

    document.getElementById('buMenuModalHasSubmenu')?.addEventListener('change', function () {
        toggleSubmenuSection(this.checked);
        if (this.checked) {
            const list = document.getElementById('buMenuSubmenuList');
            if (list && !list.querySelector('.bu-submenu-row')) {
                renderSubmenuList([{ label: '', link: '' }]);
            }
        }
    });

    document.getElementById('buMenuAddSubmenuBtn')?.addEventListener('click', function () {
        const list = document.getElementById('buMenuSubmenuList');
        const current = readSubmenuFromModal();
        current.push({ label: '', link: '' });
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

    const globalForm = document.querySelector('#tab-global form');
    if (globalForm) {
        globalForm.addEventListener('submit', function () {
            syncAllHiddenFields();
        });
    }

    initData();
    renderAllLists();
})();
</script>
