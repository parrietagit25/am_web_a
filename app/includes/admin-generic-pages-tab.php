<?php
/**
 * Pestaña Generales → Maestro de Páginas.
 * Acordeón: Editor de Páginas (producción /p/) + Experimental (base page builder /px/).
 */
require_once __DIR__ . '/../services/GenericPageService.php';
require_once __DIR__ . '/../services/ExperimentalPageService.php';
require_once __DIR__ . '/../services/ExperimentalPageBuilderService.php';

$gpPages = GenericPageService::all($siteData);
$expPages = ExperimentalPageService::all($siteData);
$gpScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$gpHost = $gpScheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$gpBase = $gpHost . '/p/';
$expBase = $gpHost . '/px/';
$gpSection = strtolower(trim((string) ($_GET['gp_section'] ?? $_POST['gp_section'] ?? 'editor')));
if ($gpSection !== 'experimental') {
    $gpSection = 'editor';
}
$gpEditorOpen = $gpSection === 'editor';
$gpExpOpen = $gpSection === 'experimental';
?>
<div class="tab-pane fade<?php echo ($defaultAdminTab ?? '') === 'generic-pages' ? ' show active' : ''; ?>" id="tab-generic-pages" role="tabpanel" aria-labelledby="tab-generic-pages-nav">
    <div class="admin-card mb-4">
        <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-file-earmark-richtext me-2 text-danger"></i>Maestro de Páginas
        </h5>
        <p class="text-muted small mb-0">
            Gestiona páginas de contenido con cabecera y pie del sitio.
            <strong>Editor de Páginas</strong> es el flujo actual (<code>/p/…</code>).
            <strong>Experimental</strong> es un espacio aparte (<code>/px/…</code>) para el futuro page builder; no copia ni altera las páginas del editor.
        </p>
    </div>

    <div class="accordion" id="maestroPaginasAccordion">
        <!-- ========== EDITOR DE PÁGINAS ========== -->
        <div class="accordion-item border rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="gpEditorHeading">
                <button class="accordion-button fw-bold text-navy<?php echo $gpEditorOpen ? '' : ' collapsed'; ?>" type="button"
                        data-bs-toggle="collapse" data-bs-target="#gpEditorCollapse"
                        aria-expanded="<?php echo $gpEditorOpen ? 'true' : 'false'; ?>" aria-controls="gpEditorCollapse">
                    <i class="bi bi-pencil-square me-2 text-danger"></i>Editor de Páginas
                    <span class="badge bg-secondary ms-2"><?php echo count($gpPages); ?></span>
                </button>
            </h2>
            <div id="gpEditorCollapse" class="accordion-collapse collapse<?php echo $gpEditorOpen ? ' show' : ''; ?>"
                 aria-labelledby="gpEditorHeading" data-bs-parent="#maestroPaginasAccordion">
                <div class="accordion-body bg-light-gray p-4">
                    <p class="text-muted small mb-3">
                        URL pública: <code><?php echo esc($gpBase); ?>mi-pagina</code>.
                        Luego puedes asignarlas al menú de cada unidad desde <strong>Generales</strong>.
                    </p>

                    <div class="admin-card mb-4">
                        <h6 class="fw-bold mb-3 text-navy" id="genericPageFormTitle">
                            <i class="bi bi-plus-circle me-2 text-danger"></i>Crear página
                        </h6>
                        <form id="genericPageForm" method="POST" action="?tab=generic-pages&amp;gp_section=editor">
                            <input type="hidden" name="action" value="save_generic_page">
                            <input type="hidden" name="gp_section" value="editor">
                            <input type="hidden" id="generic_page_id" name="generic_page_id" value="">
                            <?php admin_csrf_field(); ?>
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label">Título de la página *</label>
                                    <input type="text" id="generic_page_title" name="generic_page_title" class="form-control form-control-premium" maxlength="150" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Link (slug)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">/p/</span>
                                        <input type="text" id="generic_page_slug" name="generic_page_slug" class="form-control form-control-premium"
                                               placeholder="se-genera-del-titulo" pattern="[a-z0-9]+(-[a-z0-9]+)*">
                                    </div>
                                    <div class="form-text">Vacío = se genera automáticamente del título. Debe ser único.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Subtítulo</label>
                                    <input type="text" id="generic_page_subtitle" name="generic_page_subtitle" class="form-control form-control-premium" maxlength="250" placeholder="Texto opcional debajo del título">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Contenido</label>
                                    <textarea id="generic_page_content" name="generic_page_content" rows="14"
                                              class="form-control form-control-premium js-admin-html-editor"
                                              data-admin-html-height="400"></textarea>
                                </div>
                                <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="generic_page_active" name="generic_page_active" value="1" checked>
                                        <label class="form-check-label fw-semibold" for="generic_page_active">Página publicada (visible en el sitio)</label>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" id="genericPageCancelBtn" class="btn btn-outline-secondary d-none" onclick="resetGenericPageForm()">Cancelar edición</button>
                                        <button type="submit" class="btn btn-premium" id="genericPageSubmitBtn"><i class="bi bi-save2 me-1"></i><span id="genericPageSubmitText">Crear página</span></button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="admin-card mb-0">
                        <h6 class="fw-bold mb-3 text-navy">
                            <i class="bi bi-list-ul me-2 text-danger"></i>Páginas creadas (<?php echo count($gpPages); ?>)
                        </h6>
                        <?php if ($gpPages === []): ?>
                        <p class="text-muted small mb-0">Todavía no hay páginas creadas.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Link público</th>
                                        <th>Estado</th>
                                        <th>Usada en menús</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gpPages as $gpPage):
                                        $gpSlug = (string) ($gpPage['slug'] ?? '');
                                        $gpActive = !isset($gpPage['active']) || filter_var($gpPage['active'], FILTER_VALIDATE_BOOLEAN);
                                        $gpRefs = GenericPageService::menuReferences($siteData, $gpSlug);
                                    ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo esc((string) ($gpPage['title'] ?? '')); ?></td>
                                        <td>
                                            <a href="<?php echo esc(GenericPageService::publicPath($gpSlug)); ?>" target="_blank" rel="noopener">
                                                /p/<?php echo esc($gpSlug); ?> <i class="bi bi-box-arrow-up-right small"></i>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $gpActive ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $gpActive ? 'Publicada' : 'Borrador'; ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            <?php echo $gpRefs === [] ? '—' : esc(implode(' | ', $gpRefs)); ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick='initEditGenericPage(<?php echo json_encode($gpPage, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP); ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" action="?tab=generic-pages&amp;gp_section=editor" class="d-inline"
                                                  onsubmit="return confirm('¿Eliminar la página «<?php echo esc((string) ($gpPage['title'] ?? '')); ?>»?<?php echo $gpRefs !== [] ? ' Está asignada en menús de unidades.' : ''; ?>');">
                                                <input type="hidden" name="action" value="delete_generic_page">
                                                <input type="hidden" name="gp_section" value="editor">
                                                <input type="hidden" name="generic_page_id" value="<?php echo esc((string) ($gpPage['id'] ?? '')); ?>">
                                                <?php admin_csrf_field(); ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== EXPERIMENTAL ========== -->
        <div class="accordion-item border rounded-3 mb-3 overflow-hidden border-warning">
            <h2 class="accordion-header" id="gpExpHeading">
                <button class="accordion-button fw-bold text-navy<?php echo $gpExpOpen ? '' : ' collapsed'; ?>" type="button"
                        data-bs-toggle="collapse" data-bs-target="#gpExpCollapse"
                        aria-expanded="<?php echo $gpExpOpen ? 'true' : 'false'; ?>" aria-controls="gpExpCollapse">
                    <i class="bi bi-flask me-2 text-warning"></i>Experimental
                    <span class="badge bg-warning text-dark ms-2">Page builder</span>
                    <span class="badge bg-secondary ms-1"><?php echo count($expPages); ?></span>
                </button>
            </h2>
            <div id="gpExpCollapse" class="accordion-collapse collapse<?php echo $gpExpOpen ? ' show' : ''; ?>"
                 aria-labelledby="gpExpHeading" data-bs-parent="#maestroPaginasAccordion">
                <div class="accordion-body bg-light-gray p-4">
                    <div class="alert alert-warning small mb-3">
                        <strong>Page builder (Sprint 1).</strong> Crea secciones con 1–3 columnas y widgets
                        (texto, imagen, botón). Independiente del Editor de Páginas. URL pública: <code>/px/…</code>
                    </div>

                    <div class="admin-card mb-4">
                        <h6 class="fw-bold mb-3 text-navy" id="expPageFormTitle">
                            <i class="bi bi-plus-circle me-2 text-warning"></i>Crear página experimental
                        </h6>
                        <form id="expPageForm" method="POST" action="?tab=generic-pages&amp;gp_section=experimental">
                            <input type="hidden" name="action" value="save_experimental_page">
                            <input type="hidden" name="gp_section" value="experimental">
                            <input type="hidden" id="exp_page_id" name="exp_page_id" value="">
                            <input type="hidden" id="exp_page_blocks_json" name="exp_page_blocks_json" value="[]">
                            <?php admin_csrf_field(); ?>
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label">Título de la página *</label>
                                    <input type="text" id="exp_page_title" name="exp_page_title" class="form-control form-control-premium" maxlength="150" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Link (slug)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">/px/</span>
                                        <input type="text" id="exp_page_slug" name="exp_page_slug" class="form-control form-control-premium"
                                               placeholder="se-genera-del-titulo" pattern="[a-z0-9]+(-[a-z0-9]+)*">
                                    </div>
                                    <div class="form-text">Vacío = se genera del título. Único dentro de Experimental.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Subtítulo</label>
                                    <input type="text" id="exp_page_subtitle" name="exp_page_subtitle" class="form-control form-control-premium" maxlength="250" placeholder="Texto opcional debajo del título">
                                </div>

                                <div class="col-12">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                        <label class="form-label mb-0 fw-semibold text-navy">
                                            <i class="bi bi-layout-three-columns me-1 text-warning"></i>Constructor de bloques
                                        </label>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-warning" onclick="expBuilderAddSection(1)">+ 1 col</button>
                                            <button type="button" class="btn btn-outline-warning" onclick="expBuilderAddSection(2)">+ 2 cols</button>
                                            <button type="button" class="btn btn-outline-warning" onclick="expBuilderAddSection(3)">+ 3 cols</button>
                                        </div>
                                    </div>
                                    <div id="expBuilderSections" class="d-flex flex-column gap-3"></div>
                                    <p id="expBuilderEmpty" class="text-muted small mb-0 mt-2">Sin secciones. Agrega una con los botones de arriba.</p>
                                </div>

                                <div class="col-12">
                                    <details class="border rounded-3 p-3 bg-white">
                                        <summary class="fw-semibold text-navy small" style="cursor:pointer">HTML adicional (opcional, legado)</summary>
                                        <p class="form-text mt-2">Si hay bloques, el HTML se muestra debajo. Útil como respaldo.</p>
                                        <textarea id="exp_page_content" name="exp_page_content" rows="8"
                                                  class="form-control form-control-premium js-admin-html-editor"
                                                  data-admin-html-height="220"></textarea>
                                    </details>
                                </div>

                                <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="exp_page_active" name="exp_page_active" value="1" checked>
                                        <label class="form-check-label fw-semibold" for="exp_page_active">Página publicada (visible en /px/…)</label>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" id="expPageCancelBtn" class="btn btn-outline-secondary d-none" onclick="resetExpPageForm()">Cancelar edición</button>
                                        <button type="submit" class="btn btn-warning text-dark" id="expPageSubmitBtn"><i class="bi bi-save2 me-1"></i><span id="expPageSubmitText">Crear página experimental</span></button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="admin-card mb-0">
                        <h6 class="fw-bold mb-3 text-navy">
                            <i class="bi bi-list-ul me-2 text-warning"></i>Páginas experimentales (<?php echo count($expPages); ?>)
                        </h6>
                        <?php if ($expPages === []): ?>
                        <p class="text-muted small mb-0">Todavía no hay páginas experimentales. Empieza desde cero aquí.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Link público</th>
                                        <th>Estado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expPages as $expPage):
                                        $expSlug = (string) ($expPage['slug'] ?? '');
                                        $expActive = !isset($expPage['active']) || filter_var($expPage['active'], FILTER_VALIDATE_BOOLEAN);
                                    ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo esc((string) ($expPage['title'] ?? '')); ?></td>
                                        <td>
                                            <a href="<?php echo esc(ExperimentalPageService::publicPath($expSlug)); ?>" target="_blank" rel="noopener">
                                                /px/<?php echo esc($expSlug); ?> <i class="bi bi-box-arrow-up-right small"></i>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $expActive ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $expActive ? 'Publicada' : 'Borrador'; ?>
                                            </span>
                                            <?php
                                            $expBlockCount = count(ExperimentalPageBuilderService::blocksFromPage($expPage));
                                            if ($expBlockCount > 0):
                                            ?>
                                            <span class="badge bg-warning text-dark"><?php echo (int) $expBlockCount; ?> sec.</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick='initEditExpPage(<?php echo json_encode($expPage, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP); ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" action="?tab=generic-pages&amp;gp_section=experimental" class="d-inline"
                                                  onsubmit="return confirm('¿Eliminar la página experimental «<?php echo esc((string) ($expPage['title'] ?? '')); ?>»?');">
                                                <input type="hidden" name="action" value="delete_experimental_page">
                                                <input type="hidden" name="gp_section" value="experimental">
                                                <input type="hidden" name="exp_page_id" value="<?php echo esc((string) ($expPage['id'] ?? '')); ?>">
                                                <?php admin_csrf_field(); ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function initEditGenericPage(page) {
    document.getElementById('generic_page_id').value = page.id || '';
    document.getElementById('generic_page_title').value = page.title || '';
    document.getElementById('generic_page_subtitle').value = page.subtitle || '';
    document.getElementById('generic_page_slug').value = page.slug || '';
    document.getElementById('generic_page_active').checked = (page.active === true || page.active === 1 || page.active === '1');
    if (window.adminHtmlEditorSetValue) {
        adminHtmlEditorSetValue('generic_page_content', page.content_html || '');
    } else {
        document.getElementById('generic_page_content').value = page.content_html || '';
    }
    document.getElementById('genericPageFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar página';
    document.getElementById('genericPageSubmitText').innerText = 'Guardar cambios';
    document.getElementById('genericPageCancelBtn').classList.remove('d-none');
    document.getElementById('genericPageForm').scrollIntoView({ behavior: 'smooth' });
}

function resetGenericPageForm() {
    document.getElementById('genericPageForm').reset();
    document.getElementById('generic_page_id').value = '';
    document.getElementById('generic_page_active').checked = true;
    if (window.adminHtmlEditorSetValue) {
        adminHtmlEditorSetValue('generic_page_content', '');
    }
    document.getElementById('genericPageFormTitle').innerHTML = '<i class="bi bi-plus-circle me-2 text-danger"></i>Crear página';
    document.getElementById('genericPageSubmitText').innerText = 'Crear página';
    document.getElementById('genericPageCancelBtn').classList.add('d-none');
}

function initEditExpPage(page) {
    document.getElementById('exp_page_id').value = page.id || '';
    document.getElementById('exp_page_title').value = page.title || '';
    document.getElementById('exp_page_subtitle').value = page.subtitle || '';
    document.getElementById('exp_page_slug').value = page.slug || '';
    document.getElementById('exp_page_active').checked = (page.active === true || page.active === 1 || page.active === '1');
    if (window.adminHtmlEditorSetValue) {
        adminHtmlEditorSetValue('exp_page_content', page.content_html || '');
    } else {
        document.getElementById('exp_page_content').value = page.content_html || '';
    }
    expBuilderLoad(Array.isArray(page.blocks) ? page.blocks : []);
    document.getElementById('expPageFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-warning"></i>Editar página experimental';
    document.getElementById('expPageSubmitText').innerText = 'Guardar cambios';
    document.getElementById('expPageCancelBtn').classList.remove('d-none');
    document.getElementById('expPageForm').scrollIntoView({ behavior: 'smooth' });
}

function resetExpPageForm() {
    document.getElementById('expPageForm').reset();
    document.getElementById('exp_page_id').value = '';
    document.getElementById('exp_page_active').checked = true;
    if (window.adminHtmlEditorSetValue) {
        adminHtmlEditorSetValue('exp_page_content', '');
    }
    expBuilderLoad([]);
    document.getElementById('expPageFormTitle').innerHTML = '<i class="bi bi-plus-circle me-2 text-warning"></i>Crear página experimental';
    document.getElementById('expPageSubmitText').innerText = 'Crear página experimental';
    document.getElementById('expPageCancelBtn').classList.add('d-none');
}

/* ===== Experimental page builder (Sprint 1) ===== */
var expBuilderState = [];

function expUid(prefix) {
    return prefix + '_' + Math.random().toString(16).slice(2, 10);
}

function expEsc(str) {
    return String(str == null ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function expBuilderLoad(blocks) {
    expBuilderState = Array.isArray(blocks) ? JSON.parse(JSON.stringify(blocks)) : [];
    expBuilderRender();
}

function expBuilderSyncHidden() {
    var input = document.getElementById('exp_page_blocks_json');
    if (input) {
        input.value = JSON.stringify(expBuilderState);
    }
}

function expBuilderAddSection(cols) {
    cols = parseInt(cols, 10) || 1;
    if (cols < 1) cols = 1;
    if (cols > 3) cols = 3;
    if (expBuilderState.length >= 20) {
        alert('Máximo 20 secciones.');
        return;
    }
    var colArr = [];
    for (var i = 0; i < cols; i++) {
        colArr.push({ id: expUid('col'), widgets: [] });
    }
    expBuilderState.push({
        id: expUid('sec'),
        type: 'section',
        bg: '#ffffff',
        padding: 'md',
        columns: cols,
        cols: colArr
    });
    expBuilderRender();
}

function expBuilderRemoveSection(idx) {
    if (!confirm('¿Eliminar esta sección?')) return;
    expBuilderState.splice(idx, 1);
    expBuilderRender();
}

function expBuilderMoveSection(idx, dir) {
    var to = idx + dir;
    if (to < 0 || to >= expBuilderState.length) return;
    var tmp = expBuilderState[idx];
    expBuilderState[idx] = expBuilderState[to];
    expBuilderState[to] = tmp;
    expBuilderRender();
}

function expBuilderSetColumns(idx, cols) {
    cols = parseInt(cols, 10) || 1;
    if (cols < 1) cols = 1;
    if (cols > 3) cols = 3;
    var sec = expBuilderState[idx];
    if (!sec) return;
    sec.columns = cols;
    if (!Array.isArray(sec.cols)) sec.cols = [];
    while (sec.cols.length < cols) {
        sec.cols.push({ id: expUid('col'), widgets: [] });
    }
    if (sec.cols.length > cols) {
        sec.cols = sec.cols.slice(0, cols);
    }
    expBuilderRender();
}

function expBuilderAddWidget(secIdx, colIdx, type) {
    var sec = expBuilderState[secIdx];
    if (!sec || !sec.cols[colIdx]) return;
    if (!Array.isArray(sec.cols[colIdx].widgets)) sec.cols[colIdx].widgets = [];
    if (sec.cols[colIdx].widgets.length >= 10) {
        alert('Máximo 10 widgets por columna.');
        return;
    }
    var w = { id: expUid('wgt'), type: type };
    if (type === 'text') {
        w.heading = '';
        w.body_html = '';
    } else if (type === 'image') {
        w.src = '';
        w.alt = '';
    } else {
        w.label = 'Ver más';
        w.url = '/';
        w.style = 'primary';
    }
    sec.cols[colIdx].widgets.push(w);
    expBuilderRender();
}

function expBuilderRemoveWidget(secIdx, colIdx, wIdx) {
    var sec = expBuilderState[secIdx];
    if (!sec || !sec.cols[colIdx]) return;
    sec.cols[colIdx].widgets.splice(wIdx, 1);
    expBuilderRender();
}

function expBuilderCollectFromDom() {
    var root = document.getElementById('expBuilderSections');
    if (!root) return;
    root.querySelectorAll('[data-sec-idx]').forEach(function (secEl) {
        var si = parseInt(secEl.getAttribute('data-sec-idx'), 10);
        if (!expBuilderState[si]) return;
        var bg = secEl.querySelector('[data-field="bg"]');
        var pad = secEl.querySelector('[data-field="padding"]');
        if (bg) expBuilderState[si].bg = bg.value || '#ffffff';
        if (pad) expBuilderState[si].padding = pad.value || 'md';
        secEl.querySelectorAll('[data-col-idx]').forEach(function (colEl) {
            var ci = parseInt(colEl.getAttribute('data-col-idx'), 10);
            if (!expBuilderState[si].cols[ci]) return;
            colEl.querySelectorAll('[data-w-idx]').forEach(function (wEl) {
                var wi = parseInt(wEl.getAttribute('data-w-idx'), 10);
                var w = expBuilderState[si].cols[ci].widgets[wi];
                if (!w) return;
                if (w.type === 'text') {
                    var h = wEl.querySelector('[data-field="heading"]');
                    var b = wEl.querySelector('[data-field="body_html"]');
                    if (h) w.heading = h.value;
                    if (b) w.body_html = b.value;
                } else if (w.type === 'image') {
                    var s = wEl.querySelector('[data-field="src"]');
                    var a = wEl.querySelector('[data-field="alt"]');
                    if (s) w.src = s.value;
                    if (a) w.alt = a.value;
                } else if (w.type === 'button') {
                    var l = wEl.querySelector('[data-field="label"]');
                    var u = wEl.querySelector('[data-field="url"]');
                    var st = wEl.querySelector('[data-field="style"]');
                    if (l) w.label = l.value;
                    if (u) w.url = u.value;
                    if (st) w.style = st.value;
                }
            });
        });
    });
    expBuilderSyncHidden();
}

function expBuilderRender() {
    var root = document.getElementById('expBuilderSections');
    var empty = document.getElementById('expBuilderEmpty');
    if (!root) return;
    root.innerHTML = '';
    if (empty) empty.style.display = expBuilderState.length ? 'none' : 'block';

    expBuilderState.forEach(function (sec, si) {
        var cols = parseInt(sec.columns, 10) || 1;
        var html = '<div class="border rounded-3 bg-white p-3" data-sec-idx="' + si + '">';
        html += '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">';
        html += '<strong class="text-navy">Sección ' + (si + 1) + ' <span class="text-muted fw-normal">(' + cols + ' col.)</span></strong>';
        html += '<div class="d-flex flex-wrap gap-1">';
        html += '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="expBuilderCollectFromDom();expBuilderMoveSection(' + si + ',-1)" title="Subir">↑</button>';
        html += '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="expBuilderCollectFromDom();expBuilderMoveSection(' + si + ',1)" title="Bajar">↓</button>';
        html += '<button type="button" class="btn btn-sm btn-outline-danger" onclick="expBuilderCollectFromDom();expBuilderRemoveSection(' + si + ')">Eliminar</button>';
        html += '</div></div>';
        html += '<div class="row g-2 mb-3">';
        html += '<div class="col-md-3"><label class="form-label small mb-1">Columnas</label>';
        html += '<select class="form-select form-select-sm" onchange="expBuilderCollectFromDom();expBuilderSetColumns(' + si + ', this.value)">';
        [1,2,3].forEach(function (n) {
            html += '<option value="' + n + '"' + (cols === n ? ' selected' : '') + '>' + n + '</option>';
        });
        html += '</select></div>';
        html += '<div class="col-md-3"><label class="form-label small mb-1">Fondo</label>';
        html += '<input type="color" class="form-control form-control-sm form-control-color w-100" data-field="bg" value="' + expEsc(sec.bg || '#ffffff') + '"></div>';
        html += '<div class="col-md-3"><label class="form-label small mb-1">Padding</label>';
        html += '<select class="form-select form-select-sm" data-field="padding">';
        [['sm','Compacto'],['md','Normal'],['lg','Amplio']].forEach(function (p) {
            html += '<option value="' + p[0] + '"' + ((sec.padding || 'md') === p[0] ? ' selected' : '') + '>' + p[1] + '</option>';
        });
        html += '</select></div></div>';

        html += '<div class="row g-2">';
        (sec.cols || []).forEach(function (col, ci) {
            html += '<div class="col-md-' + (cols === 1 ? '12' : (cols === 2 ? '6' : '4')) + '" data-col-idx="' + ci + '">';
            html += '<div class="border rounded-2 p-2 bg-light h-100">';
            html += '<div class="d-flex justify-content-between align-items-center mb-2"><span class="small fw-semibold text-navy">Columna ' + (ci + 1) + '</span>';
            html += '<div class="btn-group btn-group-sm">';
            html += '<button type="button" class="btn btn-outline-primary" onclick="expBuilderCollectFromDom();expBuilderAddWidget(' + si + ',' + ci + ',\'text\')" title="Texto">T</button>';
            html += '<button type="button" class="btn btn-outline-primary" onclick="expBuilderCollectFromDom();expBuilderAddWidget(' + si + ',' + ci + ',\'image\')" title="Imagen">Img</button>';
            html += '<button type="button" class="btn btn-outline-primary" onclick="expBuilderCollectFromDom();expBuilderAddWidget(' + si + ',' + ci + ',\'button\')" title="Botón">Btn</button>';
            html += '</div></div>';
            (col.widgets || []).forEach(function (w, wi) {
                html += '<div class="border rounded bg-white p-2 mb-2" data-w-idx="' + wi + '">';
                html += '<div class="d-flex justify-content-between mb-1"><span class="badge bg-secondary">' + expEsc(w.type) + '</span>';
                html += '<button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="expBuilderCollectFromDom();expBuilderRemoveWidget(' + si + ',' + ci + ',' + wi + ')">Quitar</button></div>';
                if (w.type === 'text') {
                    html += '<input type="text" class="form-control form-control-sm mb-1" data-field="heading" placeholder="Título" value="' + expEsc(w.heading || '') + '">';
                    html += '<textarea class="form-control form-control-sm" rows="3" data-field="body_html" placeholder="Texto / HTML simple">' + expEsc(w.body_html || '') + '</textarea>';
                } else if (w.type === 'image') {
                    html += '<input type="text" class="form-control form-control-sm mb-1" data-field="src" placeholder="URL imagen (/assets/... o https://)" value="' + expEsc(w.src || '') + '">';
                    html += '<input type="text" class="form-control form-control-sm" data-field="alt" placeholder="Texto alternativo" value="' + expEsc(w.alt || '') + '">';
                } else {
                    html += '<input type="text" class="form-control form-control-sm mb-1" data-field="label" placeholder="Texto del botón" value="' + expEsc(w.label || '') + '">';
                    html += '<input type="text" class="form-control form-control-sm mb-1" data-field="url" placeholder="URL /ruta o https://" value="' + expEsc(w.url || '') + '">';
                    html += '<select class="form-select form-select-sm" data-field="style">';
                    html += '<option value="primary"' + ((w.style || 'primary') === 'primary' ? ' selected' : '') + '>Primario</option>';
                    html += '<option value="outline"' + ((w.style || '') === 'outline' ? ' selected' : '') + '>Contorno</option>';
                    html += '</select>';
                }
                html += '</div>';
            });
            html += '</div></div>';
        });
        html += '</div></div>';
        root.insertAdjacentHTML('beforeend', html);
    });
    expBuilderSyncHidden();
}

(function () {
    var form = document.getElementById('expPageForm');
    if (form) {
        form.addEventListener('submit', function () {
            expBuilderCollectFromDom();
        });
    }
    expBuilderLoad([]);
})();
</script>
