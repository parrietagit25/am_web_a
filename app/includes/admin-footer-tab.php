<?php
require_once __DIR__ . '/../services/FooterService.php';
$footerService = new FooterService($contentService);
$footerData = $footerService->getFooter();
$footerGeneral = $footerData['general'];
$footerPages = $footerData['pages'];
$footerAlsoKnow = $footerData['also_know'];
$footerSocial = $footerData['social'];
$footerSucursales = $footerData['sucursales'];
$footerBlogPosts = $footerService->collectAllBlogPosts();
$footerColumns = $footerData['columns'] ?? [];
$footerRecursos = ['id' => 'recursos', 'title' => 'Recursos', 'links' => []];
foreach ($footerColumns as $_col) {
    if (($_col['id'] ?? '') === 'recursos') { $footerRecursos = $_col; break; }
}
unset($_col);
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$publicBase = $scheme . '://' . $host;
?>
<div class="tab-pane fade" id="tab-footer" role="tabpanel" aria-labelledby="tab-footer-nav">
    <div class="admin-card mb-3">
        <h5 class="fw-bold mb-3 font-montserrat text-navy">
            <i class="bi bi-layout-text-window-reverse me-2 text-danger"></i>Pie de página — Grupo Automarket
        </h5>
        <p class="text-muted small mb-0">Contenido global del footer (todas las unidades de negocio). Use las pestañas internas para cada sección.</p>
    </div>

    <ul class="nav nav-pills flex-wrap gap-1 mb-4" id="footerSubNav" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#footer-sub-general" type="button">Generales</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#footer-sub-sobre" type="button">Sobre nosotros</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#footer-sub-terminos" type="button">Términos</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#footer-sub-faq" type="button">FAQ</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#footer-sub-sucursales" type="button">Sucursales</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#footer-sub-subastas" type="button">Subastas</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#footer-sub-privacidad" type="button">Privacidad</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#footer-sub-cookies" type="button">Cookies</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#footer-sub-blog" type="button">Blog</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#footer-sub-social" type="button">Redes sociales</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#footer-sub-also" type="button">Conoce también</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#footer-sub-recursos" type="button">Recursos</button></li>
    </ul>

    <div class="tab-content">
        <!-- GENERALES -->
        <div class="tab-pane fade show active" id="footer-sub-general">
            <div class="admin-card">
                <form method="POST" action="?tab=footer" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_footer_general">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Eslogan / frase (columna izquierda)</label>
                            <textarea name="footer_tagline" class="form-control form-control-premium" rows="2"><?php echo esc($footerGeneral['tagline'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="footer_address" class="form-control form-control-premium" value="<?php echo esc($footerGeneral['address'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="footer_phone" class="form-control form-control-premium" value="<?php echo esc($footerGeneral['phone_display'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Correo</label>
                            <input type="email" name="footer_email" class="form-control form-control-premium" value="<?php echo esc($footerGeneral['email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Logo (archivo)</label>
                            <input type="file" name="footer_logo" class="form-control form-control-premium" accept="image/*">
                            <small class="text-muted d-block mt-1">Recomendado: 300×80 px — PNG con fondo transparente</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Logo (URL)</label>
                            <input type="text" name="footer_logo_url" class="form-control form-control-premium" value="<?php echo esc($footerGeneral['logo_url'] ?? ''); ?>">
                            <small class="text-muted d-block mt-1">Recomendado: 300×80 px — PNG con fondo transparente</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Texto copyright</label>
                            <input type="text" name="footer_copyright" class="form-control form-control-premium" value="<?php echo esc($footerGeneral['copyright'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Enlace pie — Privacidad</label>
                            <input type="text" name="footer_privacy_url" class="form-control form-control-premium" value="<?php echo esc($footerGeneral['privacy_url'] ?? '/pagina-institucional.php?p=privacidad'); ?>">
                            <div class="form-text">Por defecto: página institucional de privacidad.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Enlace pie — Cookies</label>
                            <input type="text" name="footer_cookies_url" class="form-control form-control-premium" value="<?php echo esc($footerGeneral['cookies_url'] ?? '/pagina-institucional.php?p=cookies'); ?>">
                            <div class="form-text">Por defecto: página institucional de cookies.</div>
                        </div>
                        <input type="hidden" name="footer_payment_badges_html" value="">
                        <div class="col-12">
                            <p class="small text-muted mb-0"><i class="bi bi-credit-card me-1"></i> Medios de pago en el pie: imágenes <code>visa.png</code> y <code>mastercard.png</code> en <code>/assets/img/</code> (tamaño icono).</p>
                        </div>
                    </div>
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-premium"><i class="bi bi-save me-1"></i> Guardar generales</button>
                    </div>
                </form>
            </div>
        </div>

        <?php
        $pageForms = [
            'sobre' => ['key' => 'sobre_nosotros', 'target' => 'footer-sub-sobre', 'public' => '/pagina-institucional.php?p=sobre-nosotros'],
            'terminos' => ['key' => 'terminos', 'target' => 'footer-sub-terminos', 'public' => '/pagina-institucional.php?p=terminos'],
            'faq' => ['key' => 'faq', 'target' => 'footer-sub-faq', 'public' => '/pagina-institucional.php?p=faq'],
            'subastas' => ['key' => 'subastas', 'target' => 'footer-sub-subastas', 'public' => '/pagina-institucional.php?p=subastas'],
            'privacidad' => ['key' => 'privacidad', 'target' => 'footer-sub-privacidad', 'public' => '/pagina-institucional.php?p=privacidad'],
            'cookies' => ['key' => 'cookies', 'target' => 'footer-sub-cookies', 'public' => '/pagina-institucional.php?p=cookies'],
        ];
        foreach ($pageForms as $pf):
            $pk = $pf['key'];
            $pg = $footerPages[$pk] ?? [];
        ?>
        <div class="tab-pane fade" id="<?php echo esc($pf['target']); ?>">
            <div class="admin-card">
                <p class="small text-muted">Vista pública: <a href="<?php echo esc($pf['public']); ?>" target="_blank" rel="noopener"><?php echo esc($publicBase . $pf['public']); ?></a></p>
                <form method="POST" action="?tab=footer">
                    <input type="hidden" name="action" value="save_footer_page">
                    <input type="hidden" name="page_key" value="<?php echo esc($pk); ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Título de la página</label>
                        <input type="text" name="page_title" class="form-control form-control-premium" value="<?php echo esc($pg['title'] ?? FooterService::PAGE_LABELS[$pk]); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Contenido (HTML permitido)</label>
                        <textarea name="page_content_html" class="form-control form-control-premium js-summernote-mini" rows="12"><?php echo $pg['content_html'] ?? ''; ?></textarea>
                        <div class="form-text">Se renderiza HTML seguro en el sitio (sin scripts). Puede pegar etiquetas como <code>&lt;section&gt;</code>, <code>&lt;h2&gt;</code>, <code>&lt;ul&gt;</code>.</div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="page_active" value="1" id="page_active_<?php echo esc($pk); ?>" <?php echo !empty($pg['active']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="page_active_<?php echo esc($pk); ?>">Página visible en el sitio</label>
                    </div>
                    <button type="submit" class="btn btn-premium"><i class="bi bi-save me-1"></i> Guardar contenido</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- SUCURSALES -->
        <div class="tab-pane fade" id="footer-sub-sucursales">
            <div class="admin-card mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h6 class="fw-bold mb-0">Sucursales del grupo (todas las unidades)</h6>
                    <form method="POST" action="?tab=footer" class="d-inline" onsubmit="return confirm('¿Importar sucursales desde Rent A Car, Venta de Autos, Leasing, Renting y Taller? No elimina las existentes.');">
                        <input type="hidden" name="action" value="sync_footer_sucursales">
                        <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-cloud-download me-1"></i> Importar desde unidades</button>
                    </form>
                </div>
                <p class="small text-muted mb-2">Página pública: <a href="/sucursales-grupo.php" target="_blank" rel="noopener">/sucursales-grupo.php</a> (no aparece en el pie visual del sitio).</p>
                <?php require __DIR__ . '/admin-legacy-locations-notice.php'; ?>
                <div class="alert alert-light border small py-2 mb-3">
                    Cada unidad también tiene su propio listado en <code>*-sucursales.php</code>. Use el campo <strong>Unidad</strong> para agrupar en la vista consolidada. La sincronización importa desde las unidades sin borrar entradas existentes.
                </div>
                <form method="POST" action="?tab=footer" id="footerSucursalForm">
                    <input type="hidden" name="action" value="save_footer_sucursal">
                    <input type="hidden" name="sucursal_id" id="footer_sucursal_id" value="">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small">Unidad</label>
                            <select name="sucursal_unit" id="footer_sucursal_unit" class="form-select form-control-premium">
                                <?php foreach (FooterService::UNIT_LABELS as $uk => $ul): ?>
                                <option value="<?php echo esc($uk); ?>"><?php echo esc($ul); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small">Nombre *</label>
                            <input type="text" name="sucursal_name" id="footer_sucursal_name" class="form-control form-control-premium" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Orden</label>
                            <input type="number" name="sucursal_sort_order" id="footer_sucursal_order" class="form-control form-control-premium" value="99">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="sucursal_active" value="1" id="footer_sucursal_active" checked>
                                <label class="form-check-label small" for="footer_sucursal_active">Activa</label>
                            </div>
                        </div>
                        <div class="col-md-6"><input type="text" name="sucursal_location" id="footer_sucursal_location" class="form-control form-control-premium" placeholder="Ubicación / zona"></div>
                        <div class="col-md-6"><input type="text" name="sucursal_address" id="footer_sucursal_address" class="form-control form-control-premium" placeholder="Dirección"></div>
                        <div class="col-md-4"><input type="text" name="sucursal_phone" id="footer_sucursal_phone" class="form-control form-control-premium" placeholder="Teléfono"></div>
                        <div class="col-md-4"><input type="text" name="sucursal_schedule" id="footer_sucursal_schedule" class="form-control form-control-premium" placeholder="Horario"></div>
                        <div class="col-md-2"><input type="text" name="sucursal_lat" id="footer_sucursal_lat" class="form-control form-control-premium" placeholder="Lat"></div>
                        <div class="col-md-2"><input type="text" name="sucursal_lng" id="footer_sucursal_lng" class="form-control form-control-premium" placeholder="Lng"></div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-premium btn-sm" id="footerSucursalSubmitBtn"><i class="bi bi-plus-lg"></i> Agregar sucursal</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="footerSucursalCancelBtn" onclick="resetFooterSucursalForm()">Cancelar</button>
                    </div>
                </form>
            </div>
            <div class="admin-card">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light"><tr><th>Unidad</th><th>Nombre</th><th>Teléfono</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                        <?php if (empty($footerSucursales)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Sin sucursales. Agregue manualmente o importe desde las unidades.</td></tr>
                        <?php else: foreach ($footerSucursales as $suc): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark border"><?php echo esc(FooterService::UNIT_LABELS[$suc['unit'] ?? 'grupo'] ?? $suc['unit']); ?></span></td>
                                <td><?php echo esc($suc['name'] ?? ''); ?></td>
                                <td class="small"><?php echo esc($suc['phone'] ?? '—'); ?></td>
                                <td><?php echo ($suc['active'] ?? true) ? '<span class="text-success">Activa</span>' : '<span class="text-muted">Inactiva</span>'; ?></td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='editFooterSucursal(<?php echo json_encode($suc, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                    <form method="POST" action="?tab=footer" class="d-inline" onsubmit="return confirm('¿Eliminar?');">
                                        <input type="hidden" name="action" value="delete_footer_sucursal">
                                        <input type="hidden" name="sucursal_id" value="<?php echo esc($suc['id'] ?? ''); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash3-fill"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- BLOG (solo lectura + enlaces admin) -->
        <div class="tab-pane fade" id="footer-sub-blog">
            <div class="admin-card">
                <p class="text-muted small">Publicaciones agregadas desde cada unidad de negocio. Edítelas en su sección correspondiente del menú lateral.</p>
                <p class="small">Vista pública: <a href="/blog-grupo.php" target="_blank" rel="noopener">/blog-grupo.php</a></p>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th>Unidad</th><th>Título</th><th>Fecha</th><th>Enlace público</th></tr></thead>
                        <tbody>
                        <?php if (empty($footerBlogPosts)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No hay publicaciones en ninguna unidad.</td></tr>
                        <?php else: foreach ($footerBlogPosts as $post): ?>
                            <tr>
                                <td><span class="badge bg-danger-subtle text-danger"><?php echo esc($post['unit_label']); ?></span></td>
                                <td><?php echo esc($post['title']); ?></td>
                                <td class="small text-muted"><?php echo esc($post['date']); ?></td>
                                <td><a href="<?php echo esc($post['url']); ?>" target="_blank" rel="noopener" class="small">Ver</a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- REDES SOCIALES -->
        <div class="tab-pane fade" id="footer-sub-social">
            <div class="admin-card">
                <form method="POST" action="?tab=footer">
                    <input type="hidden" name="action" value="save_footer_social">
                    <p class="small text-muted mb-3">Iconos Bootstrap Icons (ej: <code>bi-facebook</code>, <code>bi-instagram</code>, <code>bi-tiktok</code>).</p>
                    <div id="footerSocialRows">
                        <?php foreach ($footerSocial as $i => $sn): ?>
                        <div class="row g-2 align-items-end mb-2 footer-social-row">
                            <input type="hidden" name="social_id[]" value="<?php echo esc($sn['id'] ?? ''); ?>">
                            <div class="col-md-3"><input type="text" name="social_label[]" class="form-control form-control-premium" placeholder="Nombre" value="<?php echo esc($sn['label'] ?? ''); ?>"></div>
                            <div class="col-md-3"><input type="text" name="social_icon[]" class="form-control form-control-premium" placeholder="bi-facebook" value="<?php echo esc($sn['icon'] ?? 'bi-link-45deg'); ?>"></div>
                            <div class="col-md-4"><input type="url" name="social_url[]" class="form-control form-control-premium" placeholder="https://..." value="<?php echo esc($sn['url'] ?? ''); ?>"></div>
                            <div class="col-md-1"><input type="number" name="social_order[]" class="form-control form-control-premium" value="<?php echo esc((string)($sn['sort_order'] ?? 99)); ?>"></div>
                            <div class="col-md-1"><div class="form-check"><input class="form-check-input" type="checkbox" name="social_active[<?php echo $i; ?>]" <?php echo !empty($sn['active']) ? 'checked' : ''; ?>><label class="form-check-label small">On</label></div></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addFooterSocialRow()"><i class="bi bi-plus"></i> Agregar red</button>
                    <div class="text-end"><button type="submit" class="btn btn-premium"><i class="bi bi-save"></i> Guardar redes sociales</button></div>
                </form>
            </div>
        </div>

        <!-- CONOCE TAMBIÉN -->
        <div class="tab-pane fade" id="footer-sub-also">
            <div class="admin-card">
                <form method="POST" action="?tab=footer">
                    <input type="hidden" name="action" value="save_footer_also_know">
                    <div id="footerAlsoRows">
                        <?php foreach ($footerAlsoKnow as $i => $link): ?>
                        <div class="row g-2 align-items-end mb-2 footer-also-row">
                            <input type="hidden" name="also_id[]" value="<?php echo esc($link['id'] ?? ''); ?>">
                            <div class="col-md-4"><input type="text" name="also_label[]" class="form-control form-control-premium" placeholder="Etiqueta" value="<?php echo esc($link['label'] ?? ''); ?>"></div>
                            <div class="col-md-5"><input type="text" name="also_url[]" class="form-control form-control-premium" placeholder="/ruta o https://..." value="<?php echo esc($link['url'] ?? ''); ?>"></div>
                            <div class="col-md-2"><input type="number" name="also_order[]" class="form-control form-control-premium" value="<?php echo esc((string)($link['sort_order'] ?? 99)); ?>"></div>
                            <div class="col-md-1"><div class="form-check"><input class="form-check-input" type="checkbox" name="also_active[<?php echo $i; ?>]" <?php echo !empty($link['active']) ? 'checked' : ''; ?>><label class="form-check-label small">On</label></div></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addFooterAlsoRow()"><i class="bi bi-plus"></i> Agregar enlace</button>
                    <div class="text-end"><button type="submit" class="btn btn-premium"><i class="bi bi-save"></i> Guardar enlaces</button></div>
                </form>
            </div>
        </div>

        <!-- RECURSOS (columna del footer) -->
        <div class="tab-pane fade" id="footer-sub-recursos">
            <div class="admin-card">
                <form method="POST" action="?tab=footer">
                    <input type="hidden" name="action" value="save_footer_columns">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Título de la columna</label>
                        <input type="text" name="col_title" class="form-control form-control-premium" value="<?php echo esc($footerRecursos['title'] ?? 'Recursos'); ?>">
                    </div>
                    <div id="footerResRows">
                        <?php foreach ($footerRecursos['links'] as $i => $link): ?>
                        <div class="row g-2 align-items-end mb-2 footer-res-row">
                            <input type="hidden" name="res_id[]" value="<?php echo esc($link['id'] ?? ''); ?>">
                            <div class="col-md-4"><input type="text" name="res_label[]" class="form-control form-control-premium" placeholder="Etiqueta" value="<?php echo esc($link['label'] ?? ''); ?>"></div>
                            <div class="col-md-5"><input type="text" name="res_url[]" class="form-control form-control-premium" placeholder="/ruta o https://..." value="<?php echo esc($link['url'] ?? ''); ?>"></div>
                            <div class="col-md-2"><input type="number" name="res_order[]" class="form-control form-control-premium" value="<?php echo esc((string)($link['sort_order'] ?? 99)); ?>"></div>
                            <div class="col-md-1"><div class="form-check"><input class="form-check-input" type="checkbox" name="res_active[<?php echo $i; ?>]" <?php echo !empty($link['active']) ? 'checked' : ''; ?>><label class="form-check-label small">On</label></div></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addFooterResRow()"><i class="bi bi-plus"></i> Agregar enlace</button>
                    <div class="text-end"><button type="submit" class="btn btn-premium"><i class="bi bi-save"></i> Guardar Recursos</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function addFooterSocialRow() {
    const wrap = document.getElementById('footerSocialRows');
    const idx = wrap.querySelectorAll('.footer-social-row').length;
    const div = document.createElement('div');
    div.className = 'row g-2 align-items-end mb-2 footer-social-row';
    div.innerHTML = '<input type="hidden" name="social_id[]" value="">' +
        '<div class="col-md-3"><input type="text" name="social_label[]" class="form-control form-control-premium" placeholder="Nombre"></div>' +
        '<div class="col-md-3"><input type="text" name="social_icon[]" class="form-control form-control-premium" placeholder="bi-instagram" value="bi-link-45deg"></div>' +
        '<div class="col-md-4"><input type="url" name="social_url[]" class="form-control form-control-premium" placeholder="https://..."></div>' +
        '<div class="col-md-1"><input type="number" name="social_order[]" class="form-control form-control-premium" value="99"></div>' +
        '<div class="col-md-1"><div class="form-check"><input class="form-check-input" type="checkbox" name="social_active[' + idx + ']"><label class="form-check-label small">On</label></div></div>';
    wrap.appendChild(div);
}
function addFooterAlsoRow() {
    const wrap = document.getElementById('footerAlsoRows');
    const idx = wrap.querySelectorAll('.footer-also-row').length;
    const div = document.createElement('div');
    div.className = 'row g-2 align-items-end mb-2 footer-also-row';
    div.innerHTML = '<input type="hidden" name="also_id[]" value="">' +
        '<div class="col-md-4"><input type="text" name="also_label[]" class="form-control form-control-premium" placeholder="Etiqueta"></div>' +
        '<div class="col-md-5"><input type="text" name="also_url[]" class="form-control form-control-premium" placeholder="/ruta"></div>' +
        '<div class="col-md-2"><input type="number" name="also_order[]" class="form-control form-control-premium" value="99"></div>' +
        '<div class="col-md-1"><div class="form-check"><input class="form-check-input" type="checkbox" name="also_active[' + idx + ']"><label class="form-check-label small">On</label></div></div>';
    wrap.appendChild(div);
}
function addFooterResRow() {
    const wrap = document.getElementById('footerResRows');
    const idx = wrap.querySelectorAll('.footer-res-row').length;
    const div = document.createElement('div');
    div.className = 'row g-2 align-items-end mb-2 footer-res-row';
    div.innerHTML = '<input type="hidden" name="res_id[]" value="">' +
        '<div class="col-md-4"><input type="text" name="res_label[]" class="form-control form-control-premium" placeholder="Etiqueta"></div>' +
        '<div class="col-md-5"><input type="text" name="res_url[]" class="form-control form-control-premium" placeholder="/ruta"></div>' +
        '<div class="col-md-2"><input type="number" name="res_order[]" class="form-control form-control-premium" value="99"></div>' +
        '<div class="col-md-1"><div class="form-check"><input class="form-check-input" type="checkbox" name="res_active[' + idx + ']"><label class="form-check-label small">On</label></div></div>';
    wrap.appendChild(div);
}
function editFooterSucursal(s) {
    document.getElementById('footer_sucursal_id').value = s.id || '';
    document.getElementById('footer_sucursal_unit').value = s.unit || 'grupo';
    document.getElementById('footer_sucursal_name').value = s.name || '';
    document.getElementById('footer_sucursal_location').value = s.location || '';
    document.getElementById('footer_sucursal_address').value = s.address || '';
    document.getElementById('footer_sucursal_phone').value = s.phone || '';
    document.getElementById('footer_sucursal_schedule').value = s.schedule || '';
    document.getElementById('footer_sucursal_lat').value = s.lat || '';
    document.getElementById('footer_sucursal_lng').value = s.lng || '';
    document.getElementById('footer_sucursal_order').value = s.sort_order ?? 99;
    document.getElementById('footer_sucursal_active').checked = s.active !== false;
    document.getElementById('footerSucursalSubmitBtn').innerHTML = '<i class="bi bi-save"></i> Guardar cambios';
    document.getElementById('footerSucursalCancelBtn').classList.remove('d-none');
}
function resetFooterSucursalForm() {
    document.getElementById('footerSucursalForm').reset();
    document.getElementById('footer_sucursal_id').value = '';
    document.getElementById('footer_sucursal_active').checked = true;
    document.getElementById('footerSucursalSubmitBtn').innerHTML = '<i class="bi bi-plus-lg"></i> Agregar sucursal';
    document.getElementById('footerSucursalCancelBtn').classList.add('d-none');
}
</script>
