<?php
/**
 * Pestaña Generales → Maestro de Páginas.
 * Páginas genéricas publicadas en /p/{slug} con cabecera y footer del sitio.
 */
require_once __DIR__ . '/../services/GenericPageService.php';

$gpPages = GenericPageService::all($siteData);
$gpScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$gpBase = $gpScheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/p/';
?>
<div class="tab-pane fade<?php echo ($defaultAdminTab ?? '') === 'generic-pages' ? ' show active' : ''; ?>" id="tab-generic-pages" role="tabpanel" aria-labelledby="tab-generic-pages-nav">
    <div class="admin-card mb-4">
        <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-file-earmark-richtext me-2 text-danger"></i>Maestro de Páginas
        </h5>
        <p class="text-muted small mb-0">
            Crea páginas de contenido que se muestran con la <strong>cabecera y pie del sitio</strong>, con estilo uniforme.
            URL pública: <code><?php echo esc($gpBase); ?>mi-pagina</code>.
            Luego puedes asignarlas al menú de cada unidad de negocio desde su pestaña <strong>Generales</strong>
            (en el editor de menú, selecciona la página para llenar el link automáticamente).
        </p>
    </div>

    <div class="admin-card mb-4">
        <h6 class="fw-bold mb-3 text-navy" id="genericPageFormTitle">
            <i class="bi bi-plus-circle me-2 text-danger"></i>Crear página
        </h6>
        <form id="genericPageForm" method="POST" action="?tab=generic-pages">
            <input type="hidden" name="action" value="save_generic_page">
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
                    <div class="form-text">Vacío = se genera automáticamente del título. Debe ser único (minúsculas, números y guiones).</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Subtítulo</label>
                    <input type="text" id="generic_page_subtitle" name="generic_page_subtitle" class="form-control form-control-premium" maxlength="250" placeholder="Texto opcional que aparece debajo del título">
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

    <div class="admin-card">
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
                            <form method="POST" action="?tab=generic-pages" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar la página «<?php echo esc((string) ($gpPage['title'] ?? '')); ?>»?<?php echo $gpRefs !== [] ? ' Está asignada en menús de unidades.' : ''; ?>');">
                                <input type="hidden" name="action" value="delete_generic_page">
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
</script>
